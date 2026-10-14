"""What this service can convert, and into what.

One table, three readers: the API serves it so the site's own pages can be
built from it, the validators refuse anything not in it, and the converters
read the flags out of it. A format the browser offers but the server does not
know is a conversion that fails after the upload, which is the worst place to
find out.
"""

from __future__ import annotations

from dataclasses import dataclass

# ── Documents ────────────────────────────────────────────────────────────
#
# Everything LibreOffice opens, which is the point of using it: the browser
# could only ever read .docx and .xlsx, and only their text.

DOCUMENT_INPUTS: dict[str, str] = {
    "doc": "application/msword",
    "docx": "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
    "odt": "application/vnd.oasis.opendocument.text",
    "rtf": "application/rtf",
    "txt": "text/plain",
    "md": "text/markdown",
    "html": "text/html",
    "htm": "text/html",
    "xls": "application/vnd.ms-excel",
    "xlsx": "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    "ods": "application/vnd.oasis.opendocument.spreadsheet",
    "csv": "text/csv",
    "ppt": "application/vnd.ms-powerpoint",
    "pptx": "application/vnd.openxmlformats-officedocument.presentationml.presentation",
    "odp": "application/vnd.oasis.opendocument.presentation",
}

# What a document may be turned into. PDF is the one everybody asks for; the
# others earn their place because they are the ones people are sent and cannot
# open.
DOCUMENT_OUTPUTS: dict[str, str] = {
    "pdf": "application/pdf",
    "docx": "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
    "odt": "application/vnd.oasis.opendocument.text",
    "xlsx": "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    "csv": "text/csv",
    "html": "text/html",
    "txt": "text/plain",
}

# A spreadsheet does not become a .docx, and a document does not become a
# .csv. Offering conversions that cannot work is how a converter earns the
# reputation of being unreliable.
DOCUMENT_FAMILY: dict[str, str] = {
    "doc": "text", "docx": "text", "odt": "text", "rtf": "text",
    "txt": "text", "md": "text", "html": "text", "htm": "text",
    "xls": "sheet", "xlsx": "sheet", "ods": "sheet", "csv": "sheet",
    "ppt": "slides", "pptx": "slides", "odp": "slides",
}

FAMILY_OUTPUTS: dict[str, list[str]] = {
    "text": ["pdf", "docx", "odt", "html", "txt"],
    "sheet": ["pdf", "xlsx", "csv", "html"],
    "slides": ["pdf"],
}

# ── Audio ────────────────────────────────────────────────────────────────

AUDIO_INPUTS: dict[str, str] = {
    "mp3": "audio/mpeg",
    "wav": "audio/wav",
    "ogg": "audio/ogg",
    "oga": "audio/ogg",
    "opus": "audio/opus",
    "m4a": "audio/mp4",
    "aac": "audio/aac",
    "flac": "audio/flac",
    "wma": "audio/x-ms-wma",
    "amr": "audio/amr",
    "webm": "audio/webm",
    "mp4": "video/mp4",
    "mkv": "video/x-matroska",
    "mov": "video/quicktime",
    "avi": "video/x-msvideo",
}


@dataclass(frozen=True)
class AudioTarget:
    """How ffmpeg is asked for one output format."""

    mime: str
    codec: str
    # Lossless formats ignore a bitrate, and passing one makes ffmpeg complain
    # rather than obey.
    takes_bitrate: bool = True
    extra: tuple[str, ...] = ()


AUDIO_OUTPUTS: dict[str, AudioTarget] = {
    "mp3": AudioTarget("audio/mpeg", "libmp3lame"),
    "m4a": AudioTarget("audio/mp4", "aac"),
    "ogg": AudioTarget("audio/ogg", "libvorbis"),
    "opus": AudioTarget("audio/opus", "libopus"),
    "wav": AudioTarget("audio/wav", "pcm_s16le", takes_bitrate=False),
    "flac": AudioTarget("audio/flac", "flac", takes_bitrate=False),
}

AUDIO_BITRATES: tuple[int, ...] = (64, 96, 128, 160, 192, 256, 320)


def document_targets(extension: str) -> list[str]:
    """What this document may become, by what kind of document it is."""
    family = DOCUMENT_FAMILY.get(extension.lower())

    return FAMILY_OUTPUTS.get(family, []) if family else []


def catalogue() -> dict:
    """The whole table, as the site reads it to build its own pages."""
    return {
        "documents": {
            "inputs": [
                {
                    "extension": extension,
                    "mime": mime,
                    "family": DOCUMENT_FAMILY.get(extension, "text"),
                    "targets": document_targets(extension),
                }
                for extension, mime in sorted(DOCUMENT_INPUTS.items())
            ],
            "outputs": [
                {"extension": extension, "mime": mime}
                for extension, mime in sorted(DOCUMENT_OUTPUTS.items())
            ],
        },
        "audio": {
            "inputs": [
                {"extension": extension, "mime": mime}
                for extension, mime in sorted(AUDIO_INPUTS.items())
            ],
            "outputs": [
                {
                    "extension": extension,
                    "mime": target.mime,
                    "takes_bitrate": target.takes_bitrate,
                }
                for extension, target in sorted(AUDIO_OUTPUTS.items())
            ],
            "bitrates": list(AUDIO_BITRATES),
        },
    }
