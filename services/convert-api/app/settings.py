"""Everything this service reads from its environment.

Kept in one place so the deployment checklist and the code cannot drift: every
name here is a variable that has to exist on the Space, and nothing else is
read from the environment anywhere.
"""

from __future__ import annotations

import os
from dataclasses import dataclass, field


def _int(name: str, default: int) -> int:
    raw = os.environ.get(name, "").strip()
    try:
        return int(raw) if raw else default
    except ValueError:
        return default


def _list(name: str, default: list[str]) -> list[str]:
    raw = os.environ.get(name, "").strip()
    if not raw:
        return default
    return [item.strip() for item in raw.split(",") if item.strip()]


@dataclass(frozen=True)
class Settings:
    """Configuration, with defaults that are safe rather than permissive."""

    # The shared secret the caller must present. A Space is reachable by
    # anyone who knows its url, so without this the service is a free
    # conversion farm for whoever finds it. Empty means "refuse everything",
    # not "allow everything" - a missing secret is a misconfiguration, and the
    # safe reading of a misconfiguration is to stop.
    api_key: str = field(default_factory=lambda: os.environ.get("CONVERT_API_KEY", "").strip())

    # Browsers never call this service directly; the site's own backend does.
    # The list exists for a browser-side test page and stays empty otherwise.
    allowed_origins: list[str] = field(default_factory=lambda: _list("CONVERT_ALLOWED_ORIGINS", []))

    # Megabytes. A document that large is a scanned book, and an audio file
    # that large is an album; both are better refused than queued for minutes.
    max_document_mb: int = field(default_factory=lambda: _int("CONVERT_MAX_DOCUMENT_MB", 30))
    max_audio_mb: int = field(default_factory=lambda: _int("CONVERT_MAX_AUDIO_MB", 80))

    # Seconds. LibreOffice occasionally hangs on a malformed file rather than
    # failing, so a conversion that has not finished by now is killed.
    document_timeout: int = field(default_factory=lambda: _int("CONVERT_DOCUMENT_TIMEOUT", 90))
    audio_timeout: int = field(default_factory=lambda: _int("CONVERT_AUDIO_TIMEOUT", 180))

    # How many conversions may run at once. Each LibreOffice process is a few
    # hundred megabytes; the free Space has two cores, and letting ten start
    # together makes all ten slow instead of making one fast.
    max_concurrent: int = field(default_factory=lambda: _int("CONVERT_MAX_CONCURRENT", 2))

    soffice_bin: str = field(default_factory=lambda: os.environ.get("SOFFICE_BIN", "soffice"))
    ffmpeg_bin: str = field(default_factory=lambda: os.environ.get("FFMPEG_BIN", "ffmpeg"))

    @property
    def max_document_bytes(self) -> int:
        return self.max_document_mb * 1024 * 1024

    @property
    def max_audio_bytes(self) -> int:
        return self.max_audio_mb * 1024 * 1024


settings = Settings()
