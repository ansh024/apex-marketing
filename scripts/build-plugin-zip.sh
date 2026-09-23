#!/usr/bin/env bash
# Build the distributable plugin zip.
#
# The repo working tree carries dev-only files (local Elementor fixtures,
# scratch output, editor junk). Those must never reach the live site, so this
# builds from a clean copy with an explicit exclude list rather than zipping
# the directory in place.
set -euo pipefail
cd "$(dirname "$0")/.."

SRC="wordpress/apex-landing-page"
VERSION="$(grep -m1 "define( 'APEX_LP_VERSION'" "$SRC/apex-landing-page.php" | sed "s/.*'\([0-9.]*\)'.*/\1/")"
OUT="dist"
STAGE="$(mktemp -d)"
trap 'rm -rf "$STAGE"' EXIT

mkdir -p "$OUT"
rsync -a --quiet \
  --exclude '.*' \
  --exclude '*.map' \
  --exclude '_proto/' \
  --exclude '*.mp4' \
  --exclude 'node_modules/' \
  --exclude '__MACOSX/' \
  "$SRC/" "$STAGE/apex-landing-page/"

# Nothing dev-only should survive the copy.
if find "$STAGE" -name '.*' -not -name '.' -not -name '..' | grep -q .; then
  echo "Refusing to build: dotfiles present in the staged plugin." >&2
  find "$STAGE" -name '.*' -not -name '.' -not -name '..' >&2
  exit 1
fi

printf '%s\n' "$VERSION" > "$STAGE/apex-landing-page/assets/build.txt"

ZIP="$OUT/apex-landing-page-$VERSION.zip"
rm -f "$ZIP"
( cd "$STAGE" && zip -qr "$OLDPWD/$ZIP" apex-landing-page )

echo "Built $ZIP (video excluded - upload it to the Media Library and set the case study's Testimonial video field)"
unzip -l "$ZIP" | tail -1
