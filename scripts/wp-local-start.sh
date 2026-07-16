#!/usr/bin/env bash
set -euo pipefail
source "$(dirname "$0")/wp-local-guard.sh"
"$WP_ENV_BIN" start
bash "$(dirname "$0")/wp-local-bootstrap.sh"
