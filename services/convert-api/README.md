---
title: Fruga Conversion Service
emoji: 📄
colorFrom: blue
colorTo: indigo
sdk: gradio
sdk_version: 6.31.0
python_version: '3.12'
app_file: app.py
pinned: false
short_description: Documents and audio converted with LibreOffice and ffmpeg
---

# Fruga conversion service

Two jobs the site cannot do reliably in a browser, done here by the tools that
do them properly.

**Documents.** LibreOffice reads the supported office and text formats and
preserves document layout, tables, images and Arabic text when producing PDFs.

**Audio.** Files are sent to ffmpeg on the conversion service instead of
downloading and running ffmpeg in WebAssembly in each visitor's browser.

Everything else the site converts stays in the browser, where it should: images
never leave the visitor's machine.

Files are held only while they are being converted. People put identity
documents and recordings through a converter, and the safest place for them
afterwards is nowhere — the working directory is removed as the response is
sent, including when the conversion fails.

## Endpoints

| | |
|---|---|
| `GET /health` | whether this Space really has LibreOffice and ffmpeg |
| `GET /info` | service information |
| `GET /formats` | everything it converts, for the site to build its pages from |
| `POST /convert/document` | `file`, `target` (pdf, docx, odt, xlsx, csv, html, txt) |
| `POST /convert/audio` | `file`, `target` (mp3, m4a, ogg, opus, aac, wav, flac), `bitrate` |

The two conversion endpoints need the header `X-Convert-Key`. The site's own
backend holds the key and proxies for the browser, so the key is never in a
page and the service's address is never in one either.

```bash
curl -X POST https://<space>.hf.space/convert/document \
  -H "X-Convert-Key: $CONVERT_API_KEY" \
  -F "file=@report.docx" -F "target=pdf" -o report.pdf
```

## Settings

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

## A note on starting

The free Space may sleep when nobody is converting. The first request after a
quiet spell can wait up to 90 seconds for it to wake; the site shows a waiting
message and retries the health check before forwarding the file.

The deployed Space uses Hugging Face's free ZeroGPU tier. Its Gradio status
check is the only GPU-decorated function, as required by ZeroGPU; actual
document and audio conversions use LibreOffice and ffmpeg on CPU. The Space may
sleep while idle; Laravel waits for it to wake before forwarding a conversion.
