#!/usr/bin/env bash
set -euo pipefail
source "$(dirname "$0")/wp-local-guard.sh"

LANDING_HTML="$(mktemp)"
THANK_YOU_HTML="$(mktemp)"
trap 'rm -f "$LANDING_HTML" "$THANK_YOU_HTML"' EXIT

LANDING_STATUS="$(curl --silent --output "$LANDING_HTML" --write-out '%{http_code}' "$WP_LOCAL_URL/")"
THANK_YOU_STATUS="$(curl --silent --output "$THANK_YOU_HTML" --write-out '%{http_code}' "$WP_LOCAL_URL/thank-you/")"
[[ "$LANDING_STATUS" == "200" ]] || { echo "Landing page returned $LANDING_STATUS" >&2; exit 1; }
[[ "$THANK_YOU_STATUS" == "200" ]] || { echo "Thank You page returned $THANK_YOU_STATUS" >&2; exit 1; }

assert_present() { grep -Eiq "$1" "$2" || { echo "Missing: $3" >&2; exit 1; }; }
assert_absent() { ! grep -Eiq "$1" "$2" || { echo "Unexpected: $3" >&2; exit 1; }; }

assert_present '<main id="top">' "$LANDING_HTML" 'landing page main content'
assert_present 'id="services"' "$LANDING_HTML" 'services section'
assert_present 'id="pricing"' "$LANDING_HTML" 'pricing section'
assert_present 'api\.leadconnectorhq\.com/widget/form/PV33s1v3pTF8y2bzSIIs' "$LANDING_HTML" 'GHL form iframe'
assert_present 'link\.msgsndr\.com/js/form_embed\.js' "$LANDING_HTML" 'GHL embed script'
assert_present 'You.re booked in' "$THANK_YOU_HTML" 'Thank You page content'
assert_absent 'apex_lp_submit_lead|admin-ajax\.php' "$LANDING_HTML" 'obsolete WordPress lead handler'
assert_absent '[—–]' "$LANDING_HTML" 'long dash in landing-page output'

"$WP_ENV_BIN" run cli wp plugin is-active apex-landing-page
[[ -n "$("$WP_ENV_BIN" run cli wp plugin list --status=active --field=name | grep '^wordpress-seo' | head -1)" ]] \
  || { echo "Yoast SEO is not active." >&2; exit 1; }

echo "Smoke tests passed: routes, plugin activation, GHL embed, required sections, and obsolete-handler guard."
