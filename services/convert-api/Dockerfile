# The conversion service, with the two tools it exists to run.
#
# A Hugging Face Space built from a Dockerfile is the only kind that can
# install system packages, which is the whole reason this service exists: the
# site's own host has no LibreOffice and no ffmpeg, and no way to add them.
#
# The image is deliberately a plain Debian one rather than a LibreOffice base
# image, so what goes in is visible here and nothing arrives by surprise.

FROM python:3.11-slim-bookworm

ENV PYTHONUNBUFFERED=1 \
    PYTHONDONTWRITEBYTECODE=1 \
    PIP_NO_CACHE_DIR=1 \
    DEBIAN_FRONTEND=noninteractive \
    # LibreOffice writes a profile on first run and refuses to start with no
    # home directory it can write to.
    HOME=/tmp/home

# `--no-install-recommends` keeps this from pulling in a desktop. The three
# LibreOffice packages are the writer, calc and impress filters; the fonts are
# what make an Arabic document come out as Arabic rather than as boxes, which
# is the failure this service is replacing.
RUN apt-get update && apt-get install -y --no-install-recommends \
        libreoffice-writer \
        libreoffice-calc \
        libreoffice-impress \
        libreoffice-java-common \
        default-jre-headless \
        ffmpeg \
        fonts-dejavu-core \
        fonts-liberation2 \
        fonts-noto-core \
        fonts-noto-ui-core \
        fonts-kacst \
        fonts-hosny-amiri \
        fontconfig \
    && fc-cache -f \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY app ./app

# A Space runs the container as uid 1000, not as root, so everything it writes
# to has to belong to that user.
RUN mkdir -p /tmp/home && chmod 777 /tmp/home

# Warm the LibreOffice profile at build time. The first conversion otherwise
# pays for it - twenty seconds or more - and that first conversion is somebody
# waiting on the site.
RUN soffice --headless --norestore --nolockcheck --nodefault \
        -env:UserInstallation=file:///tmp/home/.libreoffice-warmup \
        --convert-to pdf --outdir /tmp/warmup /etc/hostname \
    || echo "warmup did not produce a file; the profile is still created"

# Cloud Run tells the container which port to listen on and expects it to be
# obeyed; 7860 is what a Hugging Face Space expects. Honouring $PORT with that
# as the fallback runs unchanged on either.
ENV PORT=7860
EXPOSE 7860

# One worker. Each conversion is a separate process of its own doing the actual
# work, and the service's own limit decides how many run at once - a second
# uvicorn worker would only double the memory for no more throughput.
#
# Shell form, so $PORT is read at start rather than baked in at build.
CMD exec uvicorn app.main:app --host 0.0.0.0 --port ${PORT:-7860} --workers 1
