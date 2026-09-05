#!/usr/bin/env bash
set -euo pipefail
root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
command -v php >/dev/null || { echo 'PHP CLI is required.' >&2; exit 127; }
find "$root_dir/orderbridge-for-woocommerce" "$root_dir/mock-erp" "$root_dir/tests" -name '*.php' -print0 | xargs -0 -n1 php -l
if rg -n "(sk_live_|sk-proj-|BEGIN (RSA |EC )?PRIVATE KEY|password\s*=\s*['\"][^'\"]+)" "$root_dir" --glob '!tests/static-check.sh'; then
  echo 'Potential secret detected.' >&2
  exit 1
fi
php "$root_dir/tests/run.php"
echo 'Static and unit checks passed.'
