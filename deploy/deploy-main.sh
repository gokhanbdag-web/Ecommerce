#!/usr/bin/env bash
set -Eeuo pipefail
umask 077

if [[ "$(id -u)" != "5004" || "$(id -un)" != "kodco8881" ]]; then
  echo "Refusing deployment: expected hosting user kodco8881 (UID 5004)" >&2
  exit 1
fi

SITE_ROOT="/home/kodcommerce.kodvadisi.com.tr"
ECOMMERCE_REPO_DIR="$SITE_ROOT/ecommerce-repo"
ECOMMERCE_LIVE_DIR="$SITE_ROOT/public_html"
ECOMMERCE_DEPLOY_LOG="$SITE_ROOT/ecommerce-deploy.log"
ECOMMERCE_HEALTH_URL="https://kodcommerce.kodvadisi.com.tr/health"
ECOMMERCE_HOME_URL="https://kodcommerce.kodvadisi.com.tr/"
sha="${1:-}"
[[ "$sha" =~ ^[a-f0-9]{40}$ ]] || exit 2
exec 9>"${ECOMMERCE_DEPLOY_LOG}.lock"
flock -n 9 || exit 0
exec >>"$ECOMMERCE_DEPLOY_LOG" 2>&1
echo "Starting deployment: $sha"
cd "$ECOMMERCE_REPO_DIR"
git fetch --quiet origin main
git merge-base --is-ancestor "$sha" origin/main || { echo "Commit not in main"; exit 1; }
current="$(git rev-parse origin/main)"
[[ "$sha" == "$current" ]] || { echo "Superseded by $current"; exit 0; }
git checkout --force --detach "$sha"
find . -type f -name '*.php' -not -path './.git/*' -print0 |
  while IFS= read -r -d '' file; do php -l "$file" >/dev/null; done

if [[ "${ECOMMERCE_ENABLE_LIVE:-0}" != "1" ]]; then
  echo "Validation succeeded; live activation disabled"
  exit 0
fi

[[ -f "$ECOMMERCE_REPO_DIR/index.php" && -f "$ECOMMERCE_REPO_DIR/page.php" ]] ||
  { echo "Refusing deployment: application entrypoints missing"; exit 1; }
[[ -d "$ECOMMERCE_LIVE_DIR" && ! -L "$ECOMMERCE_LIVE_DIR" ]] ||
  { echo "Refusing deployment: live directory missing or symlink"; exit 1; }
[[ -f "$SITE_ROOT/ecommerce-db.php" ]] ||
  { echo "Refusing deployment: private MySQL config missing"; exit 1; }

# Only public runtime files are deployed. No --delete: existing user files remain.
# Database migrations must be applied manually, with a DB backup, before activation.
includes=(
  --include='/index.php' --include='/page.php'
  --include='/src/***' --include='/languages/***'
  --exclude='*'
)
backup_dir="$SITE_ROOT/ecommerce-deploy-backups/$sha"
mkdir -p "$backup_dir"
# Snapshot the current managed runtime files for recovery.
rsync -rlt --prune-empty-dirs "${includes[@]}" \
  "$ECOMMERCE_LIVE_DIR/" "$backup_dir/"
echo "Runtime backup created: $backup_dir"

restore_on_failure() {
  echo "Deployment failed; restoring previous runtime files"
  rsync -rlt --prune-empty-dirs "${includes[@]}" \
    "$backup_dir/" "$ECOMMERCE_LIVE_DIR/" || true
}
trap restore_on_failure ERR

rsync -rlt --prune-empty-dirs "${includes[@]}" \
  "$ECOMMERCE_REPO_DIR/" "$ECOMMERCE_LIVE_DIR/"

curl --fail --silent --show-error --max-time 20 "$ECOMMERCE_HEALTH_URL" >/dev/null
curl --fail --silent --show-error --max-time 20 "$ECOMMERCE_HOME_URL" >/dev/null
trap - ERR
printf '%s\n' "$sha" > "${ECOMMERCE_DEPLOY_LOG}.commit"
echo "Health checks passed; deployed $sha"
