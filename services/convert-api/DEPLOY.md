# Putting this on Hugging Face, and wiring the site to it

Two sides. The Space runs the converters; the site holds the key and does the
asking. Neither half is useful without the other, so both are below.

---

## 1. What I need from you

Four things. Nothing else.

| | what | where it comes from |
|---|---|---|
| 1 | **Your Hugging Face username** (or the organisation's) | top right of huggingface.co once you are signed in |
| 2 | **A name for the Space** | your choice. `fruga-convert` keeps it obvious |
| 3 | **An access token with write permission** | huggingface.co → your avatar → Settings → **Access Tokens** → *Create new token* → type **Write** → copy the `hf_…` string |
| 4 | **Say whether I generate the shared key** | if yes I make one and hand it to you to paste in; if you would rather choose it, send it instead |

The token lets me push this folder to the Space. It is the only credential I
need, and it should be deleted on that page once the Space is up — pushing
again later is a matter of making another one.

**Do not** put the token or the shared key in a message you would not want
stored. Send them the way you sent the hosting password.

---

## 2. What I do with them

- Create the Space (SDK **Docker**, visibility **Public**) or push to the one
  you made.
- Push this folder. Hugging Face reads the `Dockerfile` and builds it, which
  takes about five to eight minutes the first time — it is installing
  LibreOffice, ffmpeg and the Arabic fonts.
- Check `https://<username>-<space>.hf.space/health` and show you it answering
  `"ready": true` with both tools found.
- Put the two settings into the hosting's `.env` over ssh, clear the config
  cache, and convert one real file end to end.

---

## 3. What you set on the Space

One secret, and it is the one that matters.

**Space → Settings → Variables and secrets → New secret**

| name | value |
|---|---|
| `CONVERT_API_KEY` | the shared key (a long random string) |

With it unset the service answers `503` to every conversion. That is
deliberate: a converter deployed without its secret is misconfigured, and a
misconfigured service that works is a service anyone who finds the address can
use for free.

Optional, only if the defaults do not suit:

| name | default | |
|---|---|---|
| `CONVERT_MAX_DOCUMENT_MB` | 30 | |
| `CONVERT_MAX_AUDIO_MB` | 80 | |
| `CONVERT_DOCUMENT_TIMEOUT` | 90 | seconds |
| `CONVERT_AUDIO_TIMEOUT` | 180 | seconds |
| `CONVERT_MAX_CONCURRENT` | 2 | the free Space has two cores |

**Hardware**: *CPU basic* — the free one — is enough. LibreOffice needs about a
gigabyte while it works and the Space has far more. There is no model here and
nothing a GPU would help with.

---

## 4. What goes in the hosting's `.env`

```ini
CONVERT_SERVICE_URL=https://<username>-<space>.hf.space
CONVERT_SERVICE_KEY=<the same key as the Space secret>

# Optional; these are the site's own limits, checked before anything is
# uploaded to the Space.
CONVERT_MAX_DOCUMENT_MB=30
CONVERT_MAX_AUDIO_MB=80
```

Then, because this host caches its configuration:

```bash
cd ~/domains/frugaldomain.site/public_html/api && php artisan config:clear
```

With these unset the site's conversion endpoints answer `503` and say the
feature is not set up — they do not fail in a way that looks like a bug.

---

## 5. How the two halves talk

```
browser  ──►  frugaldomain.site/api/convert/document  ──►  <space>.hf.space/convert/document
              (the site adds X-Convert-Key here)
```

The browser never sees the key and never learns the Space's address. That is
the whole reason for the middle step:

- a key in a page is a public key, and this one buys cpu time;
- an address in a page is an address strangers will use;
- and our own limits — how often, how large — belong on our side, where we can
  change them without redeploying the Space.

The uploaded file is streamed through. It is not written to the site's disk,
and the Space deletes its working directory as it sends the result back,
including when the conversion fails. People convert identity documents through
converters, and the safest place for one afterwards is nowhere.

---

## 6. Things worth knowing before they surprise you

**A free Space sleeps when nobody uses it.** The first conversion after a quiet
night takes thirty to sixty seconds while the container wakes. The site treats
that as a wait rather than an error and says "the converter is waking up".
If you would rather it stayed warm, a cron hitting `/health` every twenty
minutes does it:

```
*/20 * * * * curl -s -o /dev/null https://<username>-<space>.hf.space/health
```

**The build is slow the first time, fast afterwards.** Hugging Face caches the
image layers, so a change to the Python code rebuilds in under a minute; a
change to the `Dockerfile` rebuilds everything.

**What is not converted here.** Images stay in the browser, where they always
were — there is no reason to upload somebody's photograph to convert it, and
the browser does that job well.

**What I could not test from here.** This machine has no LibreOffice, no
ffmpeg and no Docker, so the conversions themselves have not been run. What has
been tested is everything around them: 21 checks on the service's own
behaviour, and 18 on the chain from the site through to it, including a file
too large, a conversion that makes no sense, a request with no key, and a
converter with no tools behind it. The first real conversion happens on the
Space, and I will do it with you watching the output.
