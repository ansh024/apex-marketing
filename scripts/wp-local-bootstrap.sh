#!/usr/bin/env bash
set -euo pipefail
source "$(dirname "$0")/wp-local-guard.sh"

if ! "$WP_ENV_BIN" run cli wp core is-installed >/dev/null 2>&1; then
  "$WP_ENV_BIN" run cli wp core install \
    --url="$WP_LOCAL_URL" \
    --title="Apex Marketing Local QA" \
    --admin_user=admin \
    --admin_password=password \
    --admin_email=admin@example.test \
    --skip-email >/dev/null
fi

YOAST_SLUG="$("$WP_ENV_BIN" run cli wp plugin list --field=name | grep '^wordpress-seo' | head -1 | tr -d '\r')"
[[ -n "$YOAST_SLUG" ]] || { echo "Yoast SEO was not mounted by wp-env." >&2; exit 1; }
"$WP_ENV_BIN" run cli wp plugin activate apex-landing-page "$YOAST_SLUG" >/dev/null
"$WP_ENV_BIN" run cli wp option update blogname "Apex Marketing Local QA" >/dev/null
"$WP_ENV_BIN" run cli wp option update permalink_structure '/%postname%/' >/dev/null

ensure_page() {
  local title="$1" slug="$2" template="$3"
  local page_id
  page_id="$("$WP_ENV_BIN" run cli wp post list --post_type=page --name="$slug" --post_status=any --field=ID | tr -d '\r' | grep -E '^[0-9]+$' | head -1 || true)"
  if [[ ! "$page_id" =~ ^[0-9]+$ ]]; then
    page_id="$("$WP_ENV_BIN" run cli wp post create --post_type=page --post_status=publish --post_title="$title" --post_name="$slug" --porcelain | tr -d '\r')"
  else
    "$WP_ENV_BIN" run cli wp post update "$page_id" --post_status=publish >/dev/null
  fi
  "$WP_ENV_BIN" run cli wp post meta update "$page_id" _wp_page_template "$template" >/dev/null
  printf '%s' "$page_id"
}

LANDING_ID="$(ensure_page 'Apex Landing' 'apex-landing' 'templates/template-apex-landing.php')"
ensure_page 'Thank You' 'thank-you' 'templates/template-apex-thank-you.php' >/dev/null
"$WP_ENV_BIN" run cli wp option update show_on_front page >/dev/null
"$WP_ENV_BIN" run cli wp option update page_on_front "$LANDING_ID" >/dev/null
"$WP_ENV_BIN" run cli wp rewrite flush --hard >/dev/null

echo "Local WordPress ready: $WP_LOCAL_URL/"
echo "Admin: $WP_LOCAL_URL/wp-admin/ (admin / password)"
