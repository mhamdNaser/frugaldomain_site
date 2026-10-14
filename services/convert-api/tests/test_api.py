"""What the service does before, and instead of, running a converter.

LibreOffice and ffmpeg are not on the machine these tests run on, and that is
deliberate: everything checked here is the part that decides whether a file
ever reaches them. The refusals are the interesting half - a converter that
accepts a file it cannot convert wastes the upload and the person's time, and
one that accepts a request without a key is a free conversion farm.

The conversions themselves are stubbed where a success path has to be walked;
what a real LibreOffice produces is not something a test can assert anyway.
"""

from __future__ import annotations

import importlib
import os
import sys
from pathlib import Path

import pytest
from fastapi.testclient import TestClient

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

KEY = "test-key-9f2c"


@pytest.fixture()
def app_module(monkeypatch):
    """A freshly imported app, since the settings are read at import time."""
    monkeypatch.setenv("CONVERT_API_KEY", KEY)
    monkeypatch.setenv("CONVERT_MAX_DOCUMENT_MB", "1")
    monkeypatch.setenv("CONVERT_MAX_AUDIO_MB", "1")

    for name in ("app.settings", "app.formats", "app.runner", "app.main"):
        sys.modules.pop(name, None)

    return importlib.import_module("app.main")


@pytest.fixture()
def client(app_module):
    return TestClient(app_module.app)


def post_document(client, *, name="report.docx", target="pdf", body=b"x" * 64, key=KEY):
    headers = {"X-Convert-Key": key} if key is not None else {}

    return client.post(
        "/convert/document",
        files={"file": (name, body, "application/octet-stream")},
        data={"target": target},
        headers=headers,
    )


# ── what it says about itself ────────────────────────────────────────────


def test_index_names_its_endpoints(client):
    body = client.get("/").json()

    assert "/convert/document" in " ".join(body["endpoints"])
    assert "/convert/audio" in " ".join(body["endpoints"])


def test_health_reports_a_missing_tool_rather_than_claiming_ready(client):
    response = client.get("/health")
    body = response.json()

    # Neither tool is installed here, so the service must say it is not ready.
    assert body["ready"] is False
    assert response.status_code == 503
    assert set(body["tools"]) == {"libreoffice", "ffmpeg"}
    assert body["configured"] is True


def test_formats_lists_what_each_document_can_become(client):
    body = client.get("/formats").json()
    inputs = {entry["extension"]: entry for entry in body["documents"]["inputs"]}

    # A spreadsheet does not become a text document, and a deck becomes a pdf
    # and nothing else.
    assert "pdf" in inputs["docx"]["targets"]
    assert "docx" not in inputs["xlsx"]["targets"]
    assert inputs["xlsx"]["targets"] == ["pdf", "xlsx", "csv", "html"]
    assert inputs["pptx"]["targets"] == ["pdf"]

    audio = {entry["extension"]: entry for entry in body["audio"]["outputs"]}
    assert audio["wav"]["takes_bitrate"] is False
    assert audio["mp3"]["takes_bitrate"] is True
    assert 320 in body["audio"]["bitrates"]


# ── who may call it ──────────────────────────────────────────────────────


def test_a_request_with_no_key_is_refused(client):
    assert post_document(client, key=None).status_code == 401


def test_a_request_with_the_wrong_key_is_refused(client):
    assert post_document(client, key="not-the-key").status_code == 401


def test_a_service_without_its_secret_refuses_everything(monkeypatch):
    """An unset key means stop, not "let everyone in"."""
    monkeypatch.setenv("CONVERT_API_KEY", "")

    for name in ("app.settings", "app.formats", "app.runner", "app.main"):
        sys.modules.pop(name, None)

    module = importlib.import_module("app.main")
    client = TestClient(module.app)

    response = client.post(
        "/convert/document",
        files={"file": ("a.docx", b"x", "application/octet-stream")},
        data={"target": "pdf"},
        headers={"X-Convert-Key": "anything"},
    )

    assert response.status_code == 503
    assert client.get("/health").json()["ready"] is False


def test_the_open_endpoints_stay_open(client):
    assert client.get("/formats").status_code == 200
    assert client.get("/").status_code == 200


# ── what it refuses before converting ────────────────────────────────────


def test_an_unreadable_extension_is_refused(client):
    response = post_document(client, name="archive.zip")

    assert response.status_code == 400
    assert ".zip" in response.json()["detail"]


def test_a_conversion_that_makes_no_sense_is_refused(client):
    response = post_document(client, name="sheet.xlsx", target="docx")

    assert response.status_code == 400
    # It says what the file *can* become rather than only what it cannot.
    assert "pdf" in response.json()["detail"]


def test_an_empty_file_is_refused(client):
    assert post_document(client, body=b"").status_code == 400


def test_a_file_over_the_limit_is_refused(client):
    response = post_document(client, body=b"x" * (2 * 1024 * 1024))

    assert response.status_code == 413
    assert "1 MB" in response.json()["detail"]


def test_an_unknown_audio_target_is_refused(client):
    response = client.post(
        "/convert/audio",
        files={"file": ("voice.m4a", b"x" * 64, "application/octet-stream")},
        data={"target": "mp2"},
        headers={"X-Convert-Key": KEY},
    )

    assert response.status_code == 400


def test_a_bitrate_nobody_offers_is_refused(client):
    response = client.post(
        "/convert/audio",
        files={"file": ("voice.m4a", b"x" * 64, "application/octet-stream")},
        data={"target": "mp3", "bitrate": "7"},
        headers={"X-Convert-Key": KEY},
    )

    assert response.status_code == 400


# ── the success path, with the converter stubbed ─────────────────────────


def test_a_converted_document_comes_back_named_after_the_original(client, app_module, tmp_path):
    produced = tmp_path / "out.pdf"
    produced.write_bytes(b"%PDF-1.4 stub")

    from app.runner import Converted

    async def fake(data, source, target):
        assert source == "docx" and target == "pdf"
        return Converted(path=produced, directory=tmp_path)

    app_module.to_document = fake

    response = post_document(client, name="  my report (final).docx  ")

    assert response.status_code == 200
    assert response.content == b"%PDF-1.4 stub"
    # Starlette percent-encodes the name per RFC 5987, which is what lets a
    # non-latin filename survive the header at all.
    from urllib.parse import unquote

    assert "my report (final).pdf" in unquote(response.headers["content-disposition"])


def test_a_converter_failure_is_reported_as_the_reason_not_a_crash(client, app_module):
    async def fake(data, source, target):
        raise app_module.ConversionError("It may be corrupt, or protected by a password.")

    app_module.to_document = fake

    response = post_document(client)

    assert response.status_code == 422
    assert "password" in response.json()["detail"]


def test_a_conversion_that_hangs_is_reported_as_a_timeout(client, app_module):
    async def fake(data, source, target):
        raise app_module.ConversionError("It took too long and was stopped.", status=504)

    app_module.to_document = fake

    assert post_document(client).status_code == 504


# ── names ────────────────────────────────────────────────────────────────


@pytest.mark.parametrize(
    "given,expected",
    [
        ("report.docx", "report.pdf"),
        ("../../etc/passwd", "passwd.pdf"),
        ("C:\\Users\\me\\تقرير.docx", "pdf"),  # non-latin stems fall back
        ("", None),
        ("a" * 200 + ".docx", None),
    ],
)
def test_the_download_name_carries_no_path(app_module, given, expected):
    name = app_module.safe_name(given, "pdf")

    assert "/" not in name and "\\" not in name
    assert name.endswith(".pdf")
    assert len(name) <= 90

    if expected and expected != "pdf":
        assert name == expected
