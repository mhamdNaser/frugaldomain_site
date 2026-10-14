"""Running the two converters, and cleaning up after them.

Both are command line tools, both are given a file and asked for another one,
and both occasionally hang rather than fail. The shape is the same, so the
awkward parts - temp directories that always get removed, a timeout that
actually kills the process, output that is found rather than assumed - live
here once.
"""

from __future__ import annotations

import asyncio
import logging
import shutil
import tempfile
import uuid
from dataclasses import dataclass
from pathlib import Path

from .formats import AUDIO_OUTPUTS, AudioTarget
from .settings import settings

log = logging.getLogger("convert")


class ConversionError(RuntimeError):
    """A conversion that failed for a reason worth telling the caller."""

    def __init__(self, message: str, *, status: int = 422, detail: str = ""):
        super().__init__(message)
        self.message = message
        self.status = status
        self.detail = detail


@dataclass
class Converted:
    path: Path
    directory: Path

    def cleanup(self) -> None:
        """Remove the file and the directory it lived in.

        People convert identity documents and recordings through this. Nothing
        is kept once it has been sent, and the directory goes with it so a
        failed conversion leaves no half-written file behind either.
        """
        shutil.rmtree(self.directory, ignore_errors=True)


# Two cores on the free Space. Letting ten conversions start together makes
# all ten slow instead of making one fast, and LibreOffice is measured in
# hundreds of megabytes per process.
_slots = asyncio.Semaphore(settings.max_concurrent)


async def _run(command: list[str], *, timeout: int, what: str) -> None:
    """Run a command, and make sure it is gone afterwards either way."""
    log.info("running %s: %s", what, " ".join(command[:3]))

    try:
        process = await asyncio.create_subprocess_exec(
            *command,
            stdout=asyncio.subprocess.PIPE,
            stderr=asyncio.subprocess.PIPE,
        )
    except FileNotFoundError as error:
        # The tool is not in this container at all. That is a deployment
        # fault, not the caller's file, and saying so plainly is what makes it
        # findable - the alternative is a stack trace in a log nobody reads
        # and a 500 the caller cannot act on. /health reports the same thing
        # before anybody uploads anything.
        log.error("%s is not installed (%s)", command[0], error)
        raise ConversionError(
            "This converter is not available right now.",
            status=503,
            detail=f"{command[0]} is not installed in this container",
        ) from error

    try:
        _, stderr = await asyncio.wait_for(process.communicate(), timeout=timeout)
    except asyncio.TimeoutError:
        # kill(), not terminate(): the reason for the timeout is usually that
        # the process has stopped responding to anything polite.
        process.kill()
        await process.wait()
        raise ConversionError(
            f"The {what} took longer than {timeout} seconds and was stopped.",
            status=504,
        ) from None

    if process.returncode != 0:
        message = (stderr or b"").decode("utf-8", "replace").strip()
        log.warning("%s failed (%s): %s", what, process.returncode, message[-600:])
        raise ConversionError(
            f"The file could not be converted. It may be corrupt, or protected by a password.",
            detail=message[-600:],
        )


def _workspace() -> Path:
    directory = Path(tempfile.mkdtemp(prefix="convert-"))
    directory.chmod(0o700)

    return directory


async def to_document(data: bytes, source_extension: str, target: str) -> Converted:
    """Hand a document to LibreOffice and take back what it produces."""
    directory = _workspace()

    try:
        source = directory / f"input.{source_extension}"
        source.write_bytes(data)

        output_dir = directory / "out"
        output_dir.mkdir()

        # Its own profile per conversion. Two LibreOffice processes sharing a
        # profile directory fight over the same lock file, and the second one
        # exits reporting success while producing nothing - which is far worse
        # than failing, because the caller gets an empty file.
        profile = directory / "profile"

        command = [
            settings.soffice_bin,
            "--headless",
            "--norestore",
            "--nolockcheck",
            "--nodefault",
            "--nofirststartwizard",
            f"-env:UserInstallation=file://{profile.as_posix()}",
            "--convert-to",
            target,
            "--outdir",
            str(output_dir),
            str(source),
        ]

        async with _slots:
            await _run(command, timeout=settings.document_timeout, what="document conversion")

        produced = sorted(output_dir.glob(f"*.{target}"))

        if not produced or produced[0].stat().st_size == 0:
            # LibreOffice reports success for a file it could not read, so the
            # output is checked rather than assumed.
            raise ConversionError(
                "The file was read but produced nothing. It may be empty, or in a format this file does not really use."
            )

        return Converted(path=produced[0], directory=directory)

    except Exception:
        shutil.rmtree(directory, ignore_errors=True)
        raise


async def to_audio(data: bytes, source_extension: str, target: str, bitrate: int | None) -> Converted:
    """Hand a recording to ffmpeg and take back what it produces."""
    spec: AudioTarget | None = AUDIO_OUTPUTS.get(target)

    if spec is None:
        raise ConversionError(f"This service does not produce .{target} files.", status=400)

    directory = _workspace()

    try:
        source = directory / f"input.{source_extension}"
        source.write_bytes(data)
        output = directory / f"output.{target}"

        command = [
            settings.ffmpeg_bin,
            "-hide_banner",
            "-loglevel", "error",
            "-nostdin",
            "-y",
            "-i", str(source),
            # Video in, audio out: a .mp4 or a voice note recorded as .webm is
            # one of the commonest things people want the sound out of.
            "-vn",
            "-map_metadata", "0",
            "-c:a", spec.codec,
        ]

        if spec.takes_bitrate and bitrate:
            command += ["-b:a", f"{bitrate}k"]

        command += [*spec.extra, str(output)]

        async with _slots:
            await _run(command, timeout=settings.audio_timeout, what="audio conversion")

        if not output.exists() or output.stat().st_size == 0:
            raise ConversionError("The recording was read but produced no sound.")

        return Converted(path=output, directory=directory)

    except Exception:
        shutil.rmtree(directory, ignore_errors=True)
        raise


def tool_versions() -> dict[str, str | None]:
    """Which of the two tools this container actually has.

    Reported by the health endpoint, because the commonest way for this
    service to be broken is to be running somewhere one of them was never
    installed - and that failure otherwise only shows up on a real upload.
    """
    found: dict[str, str | None] = {}

    for name, binary in (("libreoffice", settings.soffice_bin), ("ffmpeg", settings.ffmpeg_bin)):
        path = shutil.which(binary)
        found[name] = path

    return found


def safe_name(original: str, extension: str) -> str:
    """A download name taken from the uploaded one, with the path taken out."""
    stem = Path(original or "").stem
    stem = "".join(character for character in stem if character.isalnum() or character in " _-()[]").strip()
    stem = stem[:80] or f"converted-{uuid.uuid4().hex[:8]}"

    return f"{stem}.{extension}"


def extension_of(filename: str) -> str:
    """The extension of an uploaded name, however the browser sent it.

    Stripped first: a name copied on Windows often arrives with a trailing
    space, and `Path("report.docx ").suffix` is `.docx ` - which matches no
    format and refuses a file that was perfectly fine.
    """
    return Path((filename or "").strip()).suffix.strip().lower().lstrip(".")
