# Putting this on Google Cloud Run, and wiring the site to it

Two sides. Cloud Run runs the converters; the site holds the key and does the
asking. Both are below.

> **Why not Hugging Face.** It was the plan, and it no longer works: Hugging
> Face now requires a PRO subscription to host a Docker or Gradio Space, even
> on the free `cpu-basic` hardware. Only Static Spaces are free, and a static
> Space cannot run LibreOffice. Their own API says so in as many words — both
> when creating a Docker Space and when asking an existing Space to move to
> `cpu-basic`. Nothing in this folder changed because of the move: it is the
> same `Dockerfile`, and it still runs on a Space unchanged if that ever
> becomes free again.

---

## 1. Your side: five minutes in Cloud Shell

Cloud Shell is the terminal button at the top right of
[console.cloud.google.com](https://console.cloud.google.com). It is free, it
already has `gcloud`, `docker` and `git`, and using it means no cloud
credential has to be handed to anybody.

**Before the first run** you need a project with billing enabled. The free
allowance is permanent, not a trial, but Google will not run anything without a
card on file. [Create a project →](https://console.cloud.google.com/projectcreate)

Then, in Cloud Shell:

```bash
# 1. the project this belongs to
gcloud config set project YOUR_PROJECT_ID

# 2. this folder
git clone https://github.com/mhamdNaser/frugaldomain_site.git
cd frugaldomain_site/services/convert-api

# 3. the shared key (the one sent to you separately)
export CONVERT_API_KEY='…'

# 4. go
bash deploy-cloudrun.sh
```

It prints the service's address at the end and checks that LibreOffice and
ffmpeg are really inside the container. Send that address back and the site
gets wired to it.

Running it again later deploys a new revision and changes nothing else.

---

## 2. What it costs

Nothing, for this site's traffic.

Cloud Run's permanent free allowance is **180,000 vCPU-seconds** and **360,000
GiB-seconds of memory** a month, with **2 million requests**. A document
conversion takes a few seconds of one core. Even at eight seconds each that is
something like twenty thousand conversions a month inside the free allowance —
far more than this site will do.

The script sets `--max-instances 3`, so there is a ceiling on what a strange
day can cost even if something goes wrong.

The service scales to zero when nobody is converting, which is why it is free
and also why the first conversion after a quiet spell takes a few seconds
longer while a container starts. The site treats that as a wait rather than an
error.

---

## 3. What the script sets, and why

| | |
|---|---|
| `--memory 1Gi` | LibreOffice needs about a gigabyte while it works. The 512Mi default kills it half way through a conversion, which looks like a corrupt file rather than a memory limit |
| `--cpu 1` | one core per instance, which is what one conversion uses |
| `--concurrency 4` | a conversion is not a web request: it holds the cpu for seconds. Sending five to one instance makes all five slow, so Cloud Run starts another instead |
| `--max-instances 3` | the ceiling on a bad day |
| `--timeout 300s` | longer than the service's own 90s and 180s limits, so a stuck conversion is stopped by the service with a readable message rather than cut off by the platform |
| `--allow-unauthenticated` | the service checks the shared key itself. Google's own IAM auth would mean the site had to carry a Google credential too, for no more safety |
| `--set-secrets` | the key lives in Secret Manager, not in the service's environment where anyone who can read the service can read it |

---

## 4. The site's side

Over ssh, in `api/.env`:

```ini
CONVERT_SERVICE_URL=https://fruga-convert-xxxxx-ew.a.run.app
CONVERT_SERVICE_KEY=…the same key…

# Optional; the site's own limits, checked before anything is uploaded.
CONVERT_MAX_DOCUMENT_MB=30
CONVERT_MAX_AUDIO_MB=80
```

Then, because this host caches its configuration:

```bash
cd ~/domains/frugaldomain.site/public_html/api && php artisan config:clear
```

With these unset the conversion endpoints answer `503` and say the feature is
not set up — they do not fail in a way that looks like a bug. That is what they
are doing right now.

---

## 5. How the two halves talk

```
browser  ──►  frugaldomain.site/api/convert/document  ──►  <service>.run.app/convert/document
              (the site adds X-Convert-Key here)
```

The browser never sees the key and never learns the service's address:

- a key in a page is a public key, and this one buys cpu time;
- an address in a page is an address strangers will use;
- and our own limits — how often, how large — belong on our side, where they
  can change without redeploying anything.

The uploaded file is streamed through. It is not written to the site's disk,
and the service removes its working directory as it sends the result back,
including when the conversion fails. People convert identity documents through
converters, and the safest place for one afterwards is nowhere.

---

## 6. What was tested, and what was not

This machine has no LibreOffice, no ffmpeg and no Docker, so the conversions
themselves have not been run here. What has been tested is everything that
decides whether a file ever reaches them:

- **21 checks** on the service — a request with no key, with the wrong key, a
  service with no key configured at all, a file over the limit, an empty file,
  a conversion that makes no sense, a converter with no tools behind it, and
  the name a converted file comes back under.
- **18 checks** on the chain from the site through to the service, with a real
  one running: the key added on the server, the site's limits applied before
  anything is forwarded, the service's own refusals arriving as readable
  sentences, and a sleeping service treated as a wait.

Two real faults were found that way and fixed: a filename arriving with a
trailing space had its extension read as `docx ` and was refused, and a missing
tool produced a 500 with a stack trace instead of a sentence saying the
converter is unavailable.

The first real conversion happens on Cloud Run, and it is the first thing to do
once the address comes back.
