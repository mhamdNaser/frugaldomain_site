"""The conversion service.

The site converts files in the browser, which is the right default: nothing
leaves the visitor's machine. But two jobs cannot be done there well.

A document becomes a PDF by being re-typeset with jsPDF, so it comes out
readable and nothing like the original - no images, no table borders, no
styles, and only .docx and .xlsx at all. A real conversion needs a layout
engine, and the site's host has no way to run one.

Audio is converted with ffmpeg compiled to WebAssembly: a thirty megabyte
download before anything starts, single threaded, and out of memory on a long
recording or an older phone.

So those two are done here instead, by the tools that do them properly, and
nothing else moves off the browser. Files are held only while they are being
converted: people put identity documents and recordings through a converter,
and the safest place for them afterwards is nowhere.
"""

from __future__ import annotations

import logging
import os

from fastapi import Depends, FastAPI, File, Form, Header, HTTPException, UploadFile
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import FileResponse, JSONResponse

from .formats import (
    AUDIO_BITRATES,
    AUDIO_INPUTS,
    AUDIO_OUTPUTS,
    DOCUMENT_INPUTS,
    catalogue,
    document_targets,
)
from .runner import (
    ConversionError,
    extension_of,
    safe_name,
    to_audio,
    to_document,
    tool_versions,
)
from .settings import settings

logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(name)s: %(message)s")
log = logging.getLogger("convert")

VERSION = "1.0.0"

app = FastAPI(
    title="Fruga conversion service",
    version=VERSION,
    description="Documents and audio, converted by the tools that do it properly.",
    docs_url=None,
    redoc_url=None,
)

# The browser never calls this service: the site's own backend does, which is
# what keeps the key out of the page. The list is normally empty.
if settings.allowed_origins:
    app.add_middleware(
        CORSMiddleware,
        allow_origins=settings.allowed_origins,
        allow_methods=["POST", "GET"],
        allow_headers=["X-Convert-Key", "Content-Type"],
        max_age=600,
    )


async def authorise(x_convert_key: str = Header(default="")) -> None:
    """Only the site's backend may convert anything.

    A Space is reachable by anyone who learns its address, and a conversion
    costs real cpu time on a machine with two cores. An empty key in the
    environment refuses everything rather than allowing everything: a service
    that was deployed without its secret is misconfigured, and the safe
    reading of a misconfiguration is to stop.
    """
    if not settings.api_key:
        log.error("CONVERT_API_KEY is not set; refusing every request")
        raise HTTPException(status_code=503, detail="This service is not configured yet.")

    # Compared in constant time: a key checked with == leaks its length and
    # its prefix to anyone willing to measure.
    import hmac

    if not hmac.compare_digest(x_convert_key or "", settings.api_key):
        raise HTTPException(status_code=401, detail="Not allowed.")


async def read_upload(file: UploadFile, limit: int) -> bytes:
    """Read an upload, refusing one that is too large as it arrives.

    Read in chunks and counted rather than read whole and measured, so a file
    far over the limit never occupies the memory it would have taken.
    """
    chunks: list[bytes] = []
    total = 0

    while True:
        chunk = await file.read(1024 * 1024)
        if not chunk:
            break

        total += len(chunk)
        if total > limit:
            raise HTTPException(
                status_code=413,
                detail=f"This file is larger than the {limit // (1024 * 1024)} MB this converter accepts.",
            )

        chunks.append(chunk)

    if total == 0:
        raise HTTPException(status_code=400, detail="The file is empty.")

    return b"".join(chunks)


@app.get("/")
async def index() -> JSONResponse:
    """What this is, for anyone who opens the address in a browser."""
    return JSONResponse(
        {
            "service": "Fruga conversion service",
            "version": VERSION,
            "what": "Documents and audio, converted with LibreOffice and ffmpeg.",
            "endpoints": ["GET /health", "GET /formats", "POST /convert/document", "POST /convert/audio"],
            "note": "Conversions need a key; the site's backend holds it.",
        }
    )


@app.get("/health")
async def health() -> JSONResponse:
    """Whether this container actually has the tools it needs.

    The commonest way for this service to be broken is to be running somewhere
    one of them was never installed, and that otherwise only shows up when
    somebody uploads a file.
    """
    tools = tool_versions()
    ready = all(tools.values()) and bool(settings.api_key)

    return JSONResponse(
        status_code=200 if ready else 503,
        content={
            "ready": ready,
            "version": VERSION,
            "tools": tools,
            "configured": bool(settings.api_key),
            "limits": {
                "document_mb": settings.max_document_mb,
                "audio_mb": settings.max_audio_mb,
                "concurrent": settings.max_concurrent,
            },
        },
    )


@app.get("/formats")
async def formats() -> JSONResponse:
    """Everything this service converts, for the site to build its pages from."""
    return JSONResponse(catalogue())


@app.post("/convert/document", dependencies=[Depends(authorise)])
async def convert_document(
    file: UploadFile = File(...),
    target: str = Form("pdf"),
):
    """A document, in the format it was asked for."""
    source = extension_of(file.filename)
    target = (target or "pdf").lower().strip()

    if source not in DOCUMENT_INPUTS:
        raise HTTPException(
            status_code=400,
            detail=f"This converter does not read .{source or '?'} files.",
        )

    allowed = document_targets(source)

    if target not in allowed:
        # A spreadsheet does not become a .docx. Saying so plainly is kinder
        # than attempting it and returning something unusable.
        raise HTTPException(
            status_code=400,
            detail=f"A .{source} file can be converted to: {', '.join(allowed)}.",
        )

    data = await read_upload(file, settings.max_document_bytes)

    try:
        result = await to_document(data, source, target)
    except ConversionError as error:
        log.info("document conversion refused: %s", error.detail or error.message)
        raise HTTPException(status_code=error.status, detail=error.message) from error

    return FileResponse(
        path=result.path,
        filename=safe_name(file.filename, target),
        media_type="application/octet-stream",
        background=_cleanup(result),
    )


@app.post("/convert/audio", dependencies=[Depends(authorise)])
async def convert_audio(
    file: UploadFile = File(...),
    target: str = Form("mp3"),
    bitrate: int | None = Form(None),
):
    """A recording, in the format it was asked for."""
    source = extension_of(file.filename)
    target = (target or "mp3").lower().strip()

    if source not in AUDIO_INPUTS:
        raise HTTPException(
            status_code=400,
            detail=f"This converter does not read .{source or '?'} files.",
        )

    if target not in AUDIO_OUTPUTS:
        raise HTTPException(
            status_code=400,
            detail=f"This converter produces: {', '.join(sorted(AUDIO_OUTPUTS))}.",
        )

    if bitrate is not None and bitrate not in AUDIO_BITRATES:
        raise HTTPException(
            status_code=400,
            detail=f"Choose one of these bitrates: {', '.join(str(rate) for rate in AUDIO_BITRATES)}.",
        )

    data = await read_upload(file, settings.max_audio_bytes)

    try:
        result = await to_audio(data, source, target, bitrate)
    except ConversionError as error:
        log.info("audio conversion refused: %s", error.detail or error.message)
        raise HTTPException(status_code=error.status, detail=error.message) from error

    return FileResponse(
        path=result.path,
        filename=safe_name(file.filename, target),
        media_type=AUDIO_OUTPUTS[target].mime,
        background=_cleanup(result),
    )


def _cleanup(result):
    """Delete the working directory once the response has been sent.

    A background task rather than a `finally`: the file still has to be read
    off disk while it is being streamed to the caller.
    """
    from starlette.background import BackgroundTask

    return BackgroundTask(result.cleanup)


if __name__ == "__main__":  # pragma: no cover
    import uvicorn

    # 7860 is the port a Hugging Face Space exposes.
    uvicorn.run(
        "app.main:app",
        host="0.0.0.0",
        port=int(os.environ.get("PORT", "7860")),
        workers=1,
    )
