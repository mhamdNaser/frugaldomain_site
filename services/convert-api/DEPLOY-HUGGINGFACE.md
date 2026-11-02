# Hugging Face Spaces deployment (free ZeroGPU)

The conversion service runs as a Gradio 6 Space with FastAPI endpoints served
alongside the Gradio interface. This keeps the HTTP API available to Laravel
on the account's free ZeroGPU hardware; it does not use a Docker Space. The
conversion endpoints run LibreOffice and ffmpeg on CPU. A small Gradio
readiness check is GPU-decorated to satisfy ZeroGPU's startup requirement.

The Space sleeps when idle on free hardware. Laravel requests `/health` until
the Space is ready, then forwards the conversion. The browser remains on a
waiting screen while that request is in progress.

## 1. Create the Space

Use the existing public **Gradio** Space `92naser92/fileConvert`, configured
with **ZeroGPU**. Do not switch it to CPU Basic or paid hardware; Hugging Face
currently requires a PRO subscription to create or switch this account to CPU
Basic. The Space must be public so Hostinger can call its API; conversion
requests themselves require the shared secret described below.

The account in the project configuration already has another Space at
`92naser92-arabic-gestures.hf.space`, but this is a separate conversion Space.
The proposed converter address is
`https://<username>-<space-name>.hf.space`.

## 2. Upload the service files

Authenticate to Hugging Face locally with the Hugging Face CLI or Git Credential
Manager. Use a write token only on your own computer; never put it in this
repository or send it in chat.

From the project root in PowerShell, clone the empty Space and copy the service
files into its repository:

```powershell
$spaceDir = Join-Path $env:TEMP "frugaldomain-convert"
git clone https://huggingface.co/spaces/<username>/<space-name> $spaceDir
Copy-Item .\services\convert-api\app.py, .\services\convert-api\README.md, `
  .\services\convert-api\.gitignore, .\services\convert-api\packages.txt, `
  .\services\convert-api\requirements.txt `
  -Destination $spaceDir
Copy-Item .\services\convert-api\app -Destination $spaceDir -Recurse -Force
Set-Location $spaceDir
git add app.py README.md .gitignore packages.txt requirements.txt app
git commit -m "Deploy FrugalDomain conversion service"
git push
```

Wait for the Space build to finish. The build installs LibreOffice, ffmpeg and
Arabic-capable fonts from `packages.txt`. Debian calls the Amiri font package
`fonts-hosny-amiri`; there is no Debian `fonts-amiri` package. Font installation
is followed by `fc-cache -f` in the Space entrypoint.

## 3. Add the shared secret

Generate a strong value locally:

```powershell
python -c "import secrets; print(secrets.token_urlsafe(32))"
```

In the Space's **Settings → Variables and secrets**, add `CONVERT_API_KEY` as a
secret. Do not add it as a public variable or commit it to Git.

The same value must be added to `api/.env` on Hostinger:

```dotenv
CONVERT_SERVICE_URL=https://<username>-<space-name>.hf.space
CONVERT_SERVICE_KEY=<the same secret>
CONVERT_MAX_DOCUMENT_MB=30
CONVERT_MAX_AUDIO_MB=80
```

Then clear Laravel's cached configuration over SSH:

```bash
cd ~/domains/frugaldomain.site/public_html/api
php artisan config:clear
php artisan config:cache
```

The secret stays on the Space and the Laravel server. It is never put into the
React build or sent to a visitor's browser.

## 4. Hostinger limits to check

The conversion endpoints are synchronous: Hostinger holds the request while
the free Space wakes and converts the file. Configure PHP's `max_execution_time`
and `max_input_time` to at least 360 seconds if the hosting plan permits it.
Also ensure `upload_max_filesize` is at least 80M and `post_max_size` is larger
than 80M. If hPanel does not allow these values, large or slow conversions may
be cut off by Hostinger before they finish.

The service's own limits are 30 MB for documents, 80 MB for audio and 90
seconds to wake the Space. LibreOffice and ffmpeg each have conversion timeouts.

## 5. Verify the Space and both conversion types

Set a local environment variable with the shared key, then use `curl.exe` from
PowerShell:

```powershell
$env:CONVERT_API_KEY = "<the shared secret>"
$space = "https://<username>-<space-name>.hf.space"

curl.exe -i "$space/health"
curl.exe -fS -X POST "$space/convert/document" `
  -H "X-Convert-Key: $env:CONVERT_API_KEY" `
  -F "file=@.\sample-arabic.docx" -F "target=pdf" -o .\sample-arabic.pdf
curl.exe -fS -X POST "$space/convert/audio" `
  -H "X-Convert-Key: $env:CONVERT_API_KEY" `
  -F "file=@.\sample.wav" -F "target=mp3" -o .\sample.mp3
```

Use a non-sensitive Arabic DOCX containing a mixed-script line such as
`مكاتب gis في كل المحافظات`. Inspect the PDF to verify connected Arabic,
images, tables and layout. The automated tests mock LibreOffice and ffmpeg;
they cannot prove real Arabic rendering. Do not consider that acceptance test
passed until it has been run against the built Space.

## Cost and availability

Hugging Face lists ZeroGPU as free for eligible personal accounts. The Space
uses GPU allocation only when its Gradio readiness check is clicked; normal
file conversions use CPU tools. Free Spaces can sleep while idle, and their
startup time and availability are not guaranteed. Verify that the Space still
uses ZeroGPU and not paid hardware.

The project also retains `deploy-cloudrun.sh` as an alternate deployment path.
That is not needed for this free Hugging Face setup.
