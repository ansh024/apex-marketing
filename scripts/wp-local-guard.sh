#!/usr/bin/env bash
set -euo pipefail

WP_LOCAL_URL="${WP_LOCAL_URL:-http://localhost:8892}"
case "$WP_LOCAL_URL" in
  http://127.0.0.1:8892|http://localhost:8892) ;;
  *)
    echo "Refusing to run: WP_LOCAL_URL must be the isolated local wp-env URL, not '$WP_LOCAL_URL'." >&2
    exit 64
    ;;
esac
export WP_LOCAL_URL

WP_ENV_BIN="${WP_ENV_BIN:-./node_modules/.bin/wp-env}"
if [[ ! -x "$WP_ENV_BIN" ]]; then
  echo "Official @wordpress/env is not installed. Run 'npm ci' first." >&2
  exit 69
fi
export WP_ENV_BIN
