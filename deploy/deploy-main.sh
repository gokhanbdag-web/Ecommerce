#!/usr/bin/env bash
set -Eeuo pipefail
umask 027

# Server-specific paths must be set outside GitHub.
: "${ECOMMERCE_REPO_DIR:?Set staging repository path}"
: "${ECOMMERCE_LIVE_DIR:?Set live document root}"
: "${ECOMMERCE_DEPLOY_LOG:?Set private log path}"
: "${ECOMMERCE_HEALTH_URL:?Set health check URL}"
sha="${1:-}"
[[ "$sha" =~ ^[a-f0-9]{40}$ ]] || exit 2
exec 9>"${ECOMMERCE_DEPLOY_LOG}.lock"
flock -n 9 || exit 0
exec >>"${ECOMMERCE_DEPLOY_LOG}" 2>&1
echo "Starting deployment: $sha"
cd "$ECOMMERCE_REPO_DIR"
git fetch --quiet origin main
git merge-base --is-ancestor "$sha" origin/main || { echo "Commit not in main"; exit 1; }
# Only deploy latest main, never regress when delayed webhooks arrive.
current="$(git rev-parse origin/main)"
[[ "$sha" == "$current" ]] || { echo "Superseded by $current"; exit 0; }
git checkout --force --detach "$sha"
find . -type f -name '*.php' -not -path './.git/*' -print0 |
  while IFS= read -r -d '' file; do php -l "$file" >/dev/null; done
# Activation must be explicitly configured after live paths and persistence are verified.
if [[ "${ECOMMERCE_ENABLE_LIVE:-0}" != "1" ]]; then
  echo "Validation succeeded; live activation disabled"
  exit 0
fi
# Never sync deployment utilities or repository metadata into the web root.
rsync -a --delete \
  --exclude='.git/' --exclude='.github/' --exclude='deploy/' \
  --exclude='.env' --exclude='config/local.php' --exclude='uploads/' \
  --exclude='storage/' --exclude='DEPLOYMENT.md' \
  "$ECOMMERCE_REPO_DIR/" "$ECOMMERCE_LIVE_DIR/"
curl --fail --silent --show-error --max-time 20 "$ECOMMERCE_HEALTH_URL" >/dev/null
printf '%s\n' "$sha" > "${ECOMMERCE_DEPLOY_LOG}.commit"
echo "Health check passed; deployed $sha"
