#!/usr/bin/env bash
#
# Build a throwaway WordPress install for testing the Apex plugin.
#
# Why not wp-env: wp-env needs Docker images, and this repo is often worked on
# from sandboxes where the container registry is unreachable. This builds the
# same thing from sources that are reachable over plain git and HTTPS:
#
#   - WordPress core from the official GitHub mirror (wordpress.org is often blocked)
#   - SQLite instead of MySQL, via WordPress's own sqlite-database-integration,
#     so no database server is needed at all
#   - PHP's built-in web server
#
# What this canNOT test: Elementor, Elementor Pro, or the live site's kit. Those
# are licensed or private and have to come from a real site export. See
# docs/local-testing.md.
#
# Usage:
#   ./scripts/setup-wp-sandbox.sh            # build, install, seed, serve
#   WP_SANDBOX_DIR=/path ./scripts/...       # override location
#
set -euo pipefail

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SANDBOX="${WP_SANDBOX_DIR:-/tmp/apex-wp-sandbox}"
# Matches the Apex staging site. Override with WP_VERSION when testing an upgrade.
WP_VERSION="${WP_VERSION:-7.0.4}"
PORT="${WP_SANDBOX_PORT:-8080}"
WP_DIR="$SANDBOX/wp"
WP_CLI="$SANDBOX/wp-cli.phar"

say() { printf '\n\033[1m==> %s\033[0m\n' "$1"; }

mkdir -p "$SANDBOX"

say "wp-cli"
if [ ! -f "$WP_CLI" ]; then
	curl -sSL -o "$WP_CLI" https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
fi
wp() { php "$WP_CLI" --allow-root --path="$WP_DIR" "$@"; }

say "WordPress core $WP_VERSION"
if [ ! -d "$WP_DIR" ]; then
	git clone --depth 1 --branch "$WP_VERSION" https://github.com/WordPress/WordPress.git "$WP_DIR"
fi

say "SQLite driver"
SQLITE_SRC="$SANDBOX/sqlite-database-integration"
if [ ! -d "$SQLITE_SRC" ]; then
	git clone --depth 1 https://github.com/WordPress/sqlite-database-integration.git "$SQLITE_SRC"
fi
PLUG="$WP_DIR/wp-content/plugins/sqlite-database-integration"
mkdir -p "$WP_DIR/wp-content/plugins" "$WP_DIR/wp-content/database"
rm -rf "$PLUG"
cp -R "$SQLITE_SRC/packages/plugin-sqlite-database-integration" "$PLUG"
# The repo ships the driver as a symlink; the release build replaces it with a copy.
rm -rf "$PLUG/wp-includes/database"
cp -R "$SQLITE_SRC/packages/mysql-on-sqlite/src" "$PLUG/wp-includes/database"
rm -f "$PLUG/composer.json"
sed -e "s#{SQLITE_IMPLEMENTATION_FOLDER_PATH}#$PLUG#" \
    -e "s#{SQLITE_PLUGIN}#sqlite-database-integration/load.php#" \
    "$SQLITE_SRC/packages/plugin-sqlite-database-integration/db.copy" > "$WP_DIR/wp-content/db.php"

say "wp-config.php"
cat > "$WP_DIR/wp-config.php" <<PHP
<?php
define( 'DB_NAME', 'wordpress' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );
define( 'AUTH_KEY', 'sandbox' ); define( 'SECURE_AUTH_KEY', 'sandbox' );
define( 'LOGGED_IN_KEY', 'sandbox' ); define( 'NONCE_KEY', 'sandbox' );
define( 'AUTH_SALT', 'sandbox' ); define( 'SECURE_AUTH_SALT', 'sandbox' );
define( 'LOGGED_IN_SALT', 'sandbox' ); define( 'NONCE_SALT', 'sandbox' );
\$table_prefix = 'wp_';
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'WP_ENVIRONMENT_TYPE', 'local' );
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ . '/' ); }
require_once ABSPATH . 'wp-settings.php';
PHP

say "Install"
if ! wp core is-installed 2>/dev/null; then
	wp core install \
		--url="http://localhost:$PORT" --title="Apex Sandbox" \
		--admin_user=admin --admin_password=admin \
		--admin_email=dev@example.com --skip-email
fi
wp option update home "http://localhost:$PORT" >/dev/null
wp option update siteurl "http://localhost:$PORT" >/dev/null

say "Apex plugin"
ln -sfn "$REPO_DIR/wordpress/apex-landing-page" "$WP_DIR/wp-content/plugins/apex-landing-page"
wp plugin activate sqlite-database-integration >/dev/null 2>&1 || true
wp plugin activate apex-landing-page
wp rewrite structure '/%postname%/' --hard >/dev/null 2>&1 || true
wp rewrite flush --hard >/dev/null 2>&1 || true

say "Seed the Austin location"
wp eval-file "$REPO_DIR/scripts/seed-location-austin.php"

say "Done"
cat <<EOF

  Sandbox:  $SANDBOX
  Serve:    php -S 127.0.0.1:$PORT -t $WP_DIR $WP_DIR/index.php
  Admin:    http://localhost:$PORT/wp-admin  (admin / admin)
  Location: http://localhost:$PORT/locations/austin-tx/
  Test:     php $WP_CLI --allow-root --path=$WP_DIR eval-file $REPO_DIR/scripts/test-location-integration.php

EOF
