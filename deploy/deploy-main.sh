#!/usr/bin/env bash
set -Eeuo pipefail
umask 027
if [[ "$(id -u)" != "5004" || "$(id -un)" != "kodco8881" ]]; then
  echo "Refusing deployment: expected hosting user kodco8881 (UID 5004)" >&2
  exit 1
fi

# Server-specific paths must be set outside GitHub.
ECOMMERCE_REPO_DIR="/home/kodcommerce.kodvadisi.com.tr/ecommerce-repo"
ECOMMERCE_LIVE_DIR="/home/kodcommerce.kodvadisi.com.tr/public_html"
ECOMMERCE_DEPLOY_LOG="/home/kodcommerce.kodvadisi.com.tr/ecommerce-deploy.log"
ECOMMERCE_HEALTH_URL="https://kodcommerce.kodvadisi.com.tr/"
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
if [[ ! -f "$ECOMMERCE_REPO_DIR/index.php" ]]; then
  echo "Live activation refused: repository root index.php is missing"
  exit 1
fi
# Never sync deployment utilities or repository metadata into the web root.
rsync -rltD --delete \
  --exclude='deploy-hook.php' \
  --exclude='.git/' --exclude='.github/' --exclude='deploy/' \
  --exclude='.env' --exclude='config/local.php' --exclude='uploads/' \
  --exclude='storage/' --exclude='DEPLOYMENT.md' \
  "$ECOMMERCE_REPO_DIR/" "$ECOMMERCE_LIVE_DIR/"
curl --fail --silent --show-error --max-time 20 "$ECOMMERCE_HEALTH_URL" >/dev/null
printf '%s\n' "$sha" > "${ECOMMERCE_DEPLOY_LOG}.commit"
echo "Health check passed; deployed $sha"
