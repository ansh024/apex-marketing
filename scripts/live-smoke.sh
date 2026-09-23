#!/usr/bin/env bash
# Read-only checks against the deployed site.
#
# The local wp-env cannot reproduce the real Elementor header and footer -
# those live in the production database - so a stub stands in locally. That
# stub does not generate the per-template stylesheets the real chrome depends
# on (local-<id>-frontend-*), which is how a CSS allowlist shipped that
# stripped them and left the header unstyled. These checks run against the
# real thing and would have caught it.
#
#   usage: bash scripts/live-smoke.sh [base-url]
set -uo pipefail

BASE="${1:-https://apex-marketing.ai}"
PAGES=(
  "/"
  "/plastic-surgeon-industry/"
  "/case-studies/"
  "/gc-events-case-study/"
)

fail=0
note() { printf '   %-42s %s\n' "$1" "$2"; }

# What version is actually serving? LiteSpeed combines the assets, so the
# ?ver= carrying APEX_LP_VERSION never reaches the page; the build marker is
# written at stamp time and served statically.
# Two plugin folders exist: apex-marketing (the deploy target) and
# apex-landing-page (installed by a manual zip upload). Exactly one should be
# active. Report whichever is serving a build marker.
DEPLOYED=""
for folder in apex-marketing apex-landing-page; do
  v="$(curl -s --max-time 20 "$BASE/wp-content/plugins/$folder/assets/build.txt" | tr -d '\r\n')"
  if [[ "$v" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo "build marker in $folder: $v"
    DEPLOYED="$v"
  fi
done
if [[ "$DEPLOYED" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
  echo "deployed build: $DEPLOYED"
else
  echo "deployed build: unknown (no build.txt - predates the marker)"
fi
echo

for path in "${PAGES[@]}"; do
  url="$BASE$path"
  body="$(mktemp)"
  code="$(curl -s -o "$body" -w '%{http_code}' --max-time 30 "$url")"
  echo "== $path  [$code]"
  if [[ "$code" != "200" ]]; then
    note "status" "FAIL - expected 200"; fail=1; rm -f "$body"; continue
  fi

  # PHP notices/fatals leaking into the page
  if grep -qE 'Fatal error|Parse error|There has been a critical error' "$body"; then
    note "php errors" "FAIL"; fail=1
  else
    note "php errors" "none"
  fi

  # Our own stylesheet must be present, and scoped to a body class or every
  # rule in it is inert.
  if grep -qE 'apex-(marketing|landing-page)/assets/css/[a-z-]+\.css' "$body"; then
    note "plugin stylesheet" "present"
  else
    note "plugin stylesheet" "FAIL - missing"; fail=1
  fi
  if grep -qE 'class="[^"]*apex-(home|industry|cases|case-detail|landing)-?[a-z]*' "$body"; then
    note "css scope class" "present"
  else
    note "css scope class" "FAIL - scoped CSS would not apply"; fail=1
  fi

  # Chrome: either Elementor's, or the template's own fallback - never neither,
  # and whichever renders must have styling to go with it.
  if grep -q 'elementor-location-header' "$body"; then
    note "header" "elementor"
    # Derive the header/footer template IDs from the markup
    # (class="elementor-918 ... elementor-location-header") and require that
    # template's OWN stylesheet. A generic "some elementor css is present"
    # check passes even when the chrome is stripped bare, which is exactly the
    # bug this script exists to catch.
    ids="$(grep -oE 'elementor-[0-9]+ [^"]*elementor-location-(header|footer)' "$body" \
           | grep -oE 'elementor-[0-9]+' | grep -oE '[0-9]+' | sort -u)"
    if [[ -z "$ids" ]]; then
      note "chrome template ids" "FAIL - could not determine"; fail=1
    fi
    for id in $ids; do
      # elementor-post-<id> alone is not enough: the per-breakpoint
      # local-<id>-frontend-* files carry the actual layout and typography.
      if grep -qE "local-$id-frontend" "$body"; then
        note "chrome css for #$id" "present"
      else
        note "chrome css for #$id" "FAIL - local-$id-frontend-* missing, renders unstyled"; fail=1
      fi
    done
    # The theme and Elementor base stylesheets the chrome is built on.
    for handle in hello-elementor base-desktop; do
      if grep -qE "id=['\"]$handle-css" "$body"; then
        note "$handle" "present"
      else
        note "$handle" "FAIL - missing"; fail=1
      fi
    done
  elif grep -qE '<header class="nav"|<nav class="cs-nav"|<nav class="nav"' "$body"; then
    note "header" "template fallback"
  else
    note "header" "FAIL - no header at all"; fail=1
  fi

  if grep -qE 'elementor-location-footer|<footer class="foot' "$body"; then
    note "footer" "present"
  else
    note "footer" "FAIL - no footer"; fail=1
  fi

  rm -f "$body"
done

echo
if (( fail )); then echo "LIVE SMOKE FAILED"; exit 1; fi
echo "Live smoke passed."
