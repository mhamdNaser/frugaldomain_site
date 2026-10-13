#!/bin/sh
#
# Copy a finished frontend build into the repository root, which is also the
# web root.
#
# The reason this is a script rather than a one-liner is the asset directory.
# Every build fingerprints its chunks, so a deploy that simply replaced
# `assets/` removed every file the previous build referenced - and a browser
# holding the previous html (an open tab, a page restored from history) then
# asked for a chunk that no longer existed. React answered with "Failed to
# fetch dynamically imported module" and the person was stuck on an error
# page.
#
# So one generation is kept. Each deploy writes the list of files it shipped;
# the next one keeps its own files plus that list, and removes anything older.
# A tab open across a single deploy keeps working, and the app reloads itself
# if it ever does meet a missing chunk (see src/lazyPage.js).
#
# Usage, from the repository root:   sh scripts/deploy-build.sh
#
set -eu

ROOT=$(cd "$(dirname "$0")/.." && pwd)
DIST="$ROOT/sydev-front/dist"
MANIFEST="$ROOT/assets/_shipped.txt"

if [ ! -d "$DIST" ]; then
  echo "No build at $DIST - run 'npm run build' in sydev-front first." >&2
  exit 1
fi

if [ ! -f "$DIST/index.html" ]; then
  echo "$DIST has no index.html; refusing to deploy a partial build." >&2
  exit 1
fi

cd "$ROOT"

# The generation before this one, which is what any tab opened since the last
# deploy is still referencing.
PREVIOUS=$(mktemp)
if [ -f "$MANIFEST" ]; then
  cat "$MANIFEST" > "$PREVIOUS"
else
  # First run under this script: keep whatever is there now.
  ls assets 2>/dev/null > "$PREVIOUS" || true
fi

# The build being shipped.
CURRENT=$(mktemp)
ls "$DIST/assets" > "$CURRENT"

cp -r "$DIST/." .

# Anything in neither list is two generations old: nothing still points at it.
KEEP=$(mktemp)
sort -u "$PREVIOUS" "$CURRENT" > "$KEEP"

removed=0
for file in assets/*; do
  name=$(basename "$file")
  [ "$name" = "_shipped.txt" ] && continue

  if ! grep -qxF "$name" "$KEEP"; then
    rm -f "$file"
    removed=$((removed + 1))
  fi
done

cp "$CURRENT" "$MANIFEST"
rm -f "$PREVIOUS" "$CURRENT" "$KEEP"

echo "deployed $(wc -l < "$MANIFEST" | tr -d ' ') assets; removed $removed from two generations back"
echo "assets now holds $(ls assets | wc -l | tr -d ' ') files"
