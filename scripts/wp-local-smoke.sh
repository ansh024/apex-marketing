#!/usr/bin/env bash
set -euo pipefail
source "$(dirname "$0")/wp-local-guard.sh"

LANDING_HTML="$(mktemp)"
THANK_YOU_HTML="$(mktemp)"
CASES_HTML="$(mktemp)"
CASE_HTML="$(mktemp)"
trap 'rm -f "$LANDING_HTML" "$THANK_YOU_HTML" "$CASES_HTML" "$CASE_HTML"' EXIT

LANDING_STATUS="$(curl --silent --output "$LANDING_HTML" --write-out '%{http_code}' "$WP_LOCAL_URL/")"
THANK_YOU_STATUS="$(curl --silent --output "$THANK_YOU_HTML" --write-out '%{http_code}' "$WP_LOCAL_URL/thank-you/")"
CASES_STATUS="$(curl --silent --output "$CASES_HTML" --write-out '%{http_code}' "$WP_LOCAL_URL/case-studies/")"
CASE_STATUS="$(curl --silent --output "$CASE_HTML" --write-out '%{http_code}' "$WP_LOCAL_URL/case-studies/gc-events-studio/")"
[[ "$LANDING_STATUS" == "200" ]] || { echo "Landing page returned $LANDING_STATUS" >&2; exit 1; }
[[ "$THANK_YOU_STATUS" == "200" ]] || { echo "Thank You page returned $THANK_YOU_STATUS" >&2; exit 1; }
[[ "$CASES_STATUS" == "200" ]] || { echo "Case Studies returned $CASES_STATUS" >&2; exit 1; }
[[ "$CASE_STATUS" == "200" ]] || { echo "GC Events case study returned $CASE_STATUS" >&2; exit 1; }

assert_present() { grep -Eiq "$1" "$2" || { echo "Missing: $3" >&2; exit 1; }; }
assert_absent() { ! grep -Eiq "$1" "$2" || { echo "Unexpected: $3" >&2; exit 1; }; }
# Multibyte literals only. A bracket expression like [—–] is matched byte-wise
# under a C locale, so it also fires on any other U+2xxx character (the 0xE2
# lead byte) - e.g. the arrows in the landing markup. -F compares bytes exactly.
assert_absent_literal() { ! grep -Fq -e "$1" -e "$2" "$3" || { echo "Unexpected: $4" >&2; exit 1; }; }

assert_present '<main id="top">' "$LANDING_HTML" 'landing page main content'
assert_present 'id="services"' "$LANDING_HTML" 'services section'
assert_present 'id="pricing"' "$LANDING_HTML" 'pricing section'
assert_present 'api\.leadconnectorhq\.com/widget/form/PV33s1v3pTF8y2bzSIIs' "$LANDING_HTML" 'GHL form iframe'
assert_present 'data-src="https://api\.leadconnectorhq\.com/widget/form/PV33s1v3pTF8y2bzSIIs"' "$LANDING_HTML" 'lazy GHL form source'
assert_absent '<script[^>]+src=[^>]*link\.msgsndr\.com/js/form_embed\.js' "$LANDING_HTML" 'eager GHL embed script'
assert_present 'You.re booked in' "$THANK_YOU_HTML" 'Thank You page content'
assert_absent 'apex_lp_submit_lead|admin-ajax\.php' "$LANDING_HTML" 'obsolete WordPress lead handler'
assert_absent_literal '—' '–' "$LANDING_HTML" 'long dash in landing-page output'
assert_present 'Proof, not' "$CASES_HTML" 'case studies hero'
assert_present 'GC Events Studio' "$CASES_HTML" 'featured case study'
assert_present 'cs-hero-gradient' "$CASES_HTML" 'hero motion gradient mount'
assert_present 'neat-1\.0\.2\.umd\.js' "$CASES_HTML" 'self-hosted gradient library'
assert_present 'A clearer view' "$CASE_HTML" 'GC Events detail hero'
assert_present 'arthur-testimonial\.mp4' "$CASE_HTML" 'Arthur testimonial video'
assert_present '<track[^>]+kind="captions"' "$CASE_HTML" 'testimonial caption track'
assert_present 'Read the transcript' "$CASE_HTML" 'accessible transcript'
assert_present 'cs-quote__chart' "$CASE_HTML" 'cost-per-lead graph on the detail page'
assert_absent 'cs-bars' "$CASES_HTML" 'chart left behind on the collection page'
# Chrome is Elementor's where a theme-builder location matches, otherwise the
# bundled fallback. Assert one or the other is present, never neither.
assert_chrome() {
  grep -Eq 'elementor-location-header|<nav class="cs-nav"' "$1" || { echo "Missing: header on $2" >&2; exit 1; }
  grep -Eq 'elementor-location-footer|<footer class="foot"' "$1" || { echo "Missing: footer on $2" >&2; exit 1; }
}
assert_chrome "$CASES_HTML" 'collection'
assert_chrome "$CASE_HTML" 'detail'
assert_absent 'cs-kicker' "$CASES_HTML" 'eyebrow label on collection'
assert_absent 'cs-kicker' "$CASE_HTML" 'eyebrow label on detail'
# APEX has no naming rights for these accounts; only GC Events is cleared.
assert_absent 'Palm Valley|Click-N-Save|Nation.s Low Cost|TX Artificial Turf' "$CASES_HTML" 'unlicensed client name'

"$WP_ENV_BIN" run cli wp plugin is-active apex-landing-page
ACF_SLUG="$("$WP_ENV_BIN" run cli wp plugin list --field=name | grep '^advanced-custom-fields' | head -1 | tr -d '\r')"
[[ -n "$ACF_SLUG" ]] || { echo "Advanced Custom Fields is missing." >&2; exit 1; }
"$WP_ENV_BIN" run cli wp plugin is-active "$ACF_SLUG"
[[ -n "$("$WP_ENV_BIN" run cli wp plugin list --status=active --field=name | grep '^wordpress-seo' | head -1)" ]] \
  || { echo "Yoast SEO is not active." >&2; exit 1; }

echo "Smoke tests passed: routes, plugin activation, GHL embed, required sections, and obsolete-handler guard."
