---
title: Fruga Conversion Service
emoji: 📄
colorFrom: blue
colorTo: indigo
sdk: docker
app_port: 7860
pinned: false
short_description: Documents and audio converted with LibreOffice and ffmpeg
---

# Fruga conversion service

Two jobs the site cannot do well in a browser, done here by the tools that do
them properly.

**Documents.** The browser converter re-typesets a `.docx` with jsPDF: the text
survives, nothing else does — no images, no table borders, no styles, and only
`.docx` and `.xlsx` are read at all. LibreOffice opens fifteen formats and lays
them out the way the program that wrote them did.

**Audio.** The browser runs ffmpeg compiled to WebAssembly: a thirty megabyte
download before anything starts, single threaded, and out of memory on a long
recording or an older phone. Here it is ffmpeg itself.

Everything else the site converts stays in the browser, where it should: images
never leave the visitor's machine.

Files are held only while they are being converted. People put identity
documents and recordings through a converter, and the safest place for them
afterwards is nowhere — the working directory is removed as the response is
sent, including when the conversion fails.

## Endpoints

| | |
|---|---|
| `GET /health` | whether this container really has LibreOffice and ffmpeg |
| `GET /formats` | everything it converts, for the site to build its pages from |
| `POST /convert/document` | `file`, `target` (pdf, docx, odt, xlsx, csv, html, txt) |
| `POST /convert/audio` | `file`, `target` (mp3, m4a, ogg, opus, wav, flac), `bitrate` |

The two conversion endpoints need the header `X-Convert-Key`. The site's own
backend holds the key and proxies for the browser, so the key is never in a
page and the Space's address is never in one either.

```bash
curl -X POST https://<user>-<space>.hf.space/convert/document \
  -H "X-Convert-Key: $CONVERT_API_KEY" \
  -F "file=@report.docx" -F "target=pdf" -o report.pdf
```

## Settings

Set these as **Secrets** on the Space (Settings → Variables and secrets).
`CONVERT_API_KEY` is the only one that is required — with it unset the service
answers `503` to every conversion, because a service deployed without its
secret is misconfigured, and the safe reading of a misconfiguration is to stop.

| name | | default |
|---|---|---|
| `CONVERT_API_KEY` | **required.** Shared with the site's backend, nowhere else | — |
| `CONVERT_MAX_DOCUMENT_MB` | | 30 |
| `CONVERT_MAX_AUDIO_MB` | | 80 |
| `CONVERT_DOCUMENT_TIMEOUT` | seconds; LibreOffice hangs on some malformed files | 90 |
| `CONVERT_AUDIO_TIMEOUT` | seconds | 180 |
| `CONVERT_MAX_CONCURRENT` | two cores on a free Space | 2 |
| `CONVERT_ALLOWED_ORIGINS` | only if a browser ever calls this directly | empty |

## Running it locally

```bash
pip install -r requirements.txt
CONVERT_API_KEY=dev-key python -m app.main
```

Without LibreOffice and ffmpeg on the machine, `/health` reports which one is
missing and conversions fail with a clear message rather than a stack trace.

## A note on sleeping

A free Space stops after a period with no traffic and takes thirty to sixty
seconds to come back. The first conversion after a quiet night is therefore
slow, and the site says so rather than appearing to hang.
