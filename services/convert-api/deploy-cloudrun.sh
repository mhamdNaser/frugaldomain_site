#!/usr/bin/env bash
#
# Put the conversion service on Google Cloud Run.
#
# Meant to be pasted into Cloud Shell (the terminal button at the top of
# console.cloud.google.com). Cloud Shell already has gcloud, docker and git,
# it is free, and it means no cloud credential has to be handed to anybody.
#
# It is written to be run twice: everything it creates it checks for first, so
# a second run deploys a new revision and changes nothing else.
#
#   bash deploy-cloudrun.sh
#
set -euo pipefail

# ── what this needs to know ──────────────────────────────────────────────

PROJECT="${PROJECT:-$(gcloud config get-value project 2>/dev/null)}"
REGION="${REGION:-europe-west1}"
SERVICE="${SERVICE:-fruga-convert}"
REPO="${REPO:-fruga}"

# The secret the site and this service share. Passed in rather than written
# here, so it never sits in a file in a repository.
CONVERT_API_KEY="${CONVERT_API_KEY:-}"

if [ -z "$PROJECT" ]; then
  echo "No project selected. Run:  gcloud config set project YOUR_PROJECT_ID" >&2
  exit 1
fi

if [ -z "$CONVERT_API_KEY" ]; then
  echo "Set the shared key first, for example:" >&2
  echo "  export CONVERT_API_KEY='…the key…'" >&2
  exit 1
fi

echo "project : $PROJECT"
echo "region  : $REGION"
echo "service : $SERVICE"
echo

# ── the apis this uses ───────────────────────────────────────────────────

echo "→ enabling the services this needs (no-op if already on)"
gcloud services enable \
  run.googleapis.com \
  cloudbuild.googleapis.com \
  artifactregistry.googleapis.com \
  secretmanager.googleapis.com \
  --project "$PROJECT" --quiet

# ── somewhere to keep the built image ────────────────────────────────────

if ! gcloud artifacts repositories describe "$REPO" \
      --location "$REGION" --project "$PROJECT" >/dev/null 2>&1; then
  echo "→ creating the image repository"
  gcloud artifacts repositories create "$REPO" \
    --repository-format=docker \
    --location "$REGION" \
    --description "Fruga services" \
    --project "$PROJECT" --quiet
else
  echo "→ image repository already there"
fi

# ── the shared key, kept in Secret Manager rather than in the service ────
#
# A key passed with --set-env-vars is visible to anyone who can read the
# service's description. In Secret Manager it is readable only by what is
# granted it, and rotating it is one command.

if ! gcloud secrets describe convert-api-key --project "$PROJECT" >/dev/null 2>&1; then
  echo "→ storing the shared key"
  printf '%s' "$CONVERT_API_KEY" | gcloud secrets create convert-api-key \
    --data-file=- --replication-policy=automatic --project "$PROJECT" --quiet
else
  echo "→ updating the shared key"
  printf '%s' "$CONVERT_API_KEY" | gcloud secrets versions add convert-api-key \
    --data-file=- --project "$PROJECT" --quiet
fi

# The service's own identity needs to be allowed to read it.
PROJECT_NUMBER=$(gcloud projects describe "$PROJECT" --format='value(projectNumber)')
gcloud secrets add-iam-policy-binding convert-api-key \
  --member="serviceAccount:${PROJECT_NUMBER}-compute@developer.gserviceaccount.com" \
  --role="roles/secretmanager.secretAccessor" \
  --project "$PROJECT" --quiet >/dev/null

# ── build and deploy ─────────────────────────────────────────────────────

IMAGE="${REGION}-docker.pkg.dev/${PROJECT}/${REPO}/${SERVICE}"

echo "→ building the image (five to eight minutes the first time: it is"
echo "  installing LibreOffice, ffmpeg and the Arabic fonts)"
gcloud builds submit --tag "$IMAGE" --project "$PROJECT" --quiet .

echo "→ deploying"
#
#   --memory 1Gi      LibreOffice needs about a gigabyte while it works; the
#                     512Mi default kills it half way through a conversion.
#   --cpu 1           one core per instance, which is what one conversion uses.
#   --concurrency 4   a conversion is not a web request: it occupies the cpu
#                     for seconds. Sending five to one instance makes all five
#                     slow, so Cloud Run is told to start another instead.
#   --max-instances 3 the ceiling on what a bad day can cost. Three instances
#                     is far more than this site needs and still cannot run
#                     away.
#   --timeout 300s    longer than the service's own 90s and 180s limits, so a
#                     conversion is stopped by the service with a readable
#                     message rather than cut off by the platform.
#   --allow-unauthenticated
#                     the service does its own checking with the shared key.
#                     Google's own IAM auth would mean the site had to carry a
#                     Google credential as well, for no more safety.
gcloud run deploy "$SERVICE" \
  --image "$IMAGE" \
  --region "$REGION" \
  --platform managed \
  --allow-unauthenticated \
  --memory 1Gi \
  --cpu 1 \
  --concurrency 4 \
  --max-instances 3 \
  --min-instances 0 \
  --timeout 300s \
  --set-secrets "CONVERT_API_KEY=convert-api-key:latest" \
  --project "$PROJECT" --quiet

URL=$(gcloud run services describe "$SERVICE" --region "$REGION" \
      --project "$PROJECT" --format='value(status.url)')

echo
echo "─────────────────────────────────────────────"
echo "the service is at: $URL"
echo
echo "checking it has the tools it needs:"
curl -s --max-time 60 "$URL/health" || echo "(no answer yet; it may still be starting)"
echo
echo
echo "Send that url back and the site gets wired to it."
