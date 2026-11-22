# Ecommerce webhook deployment (no cron)

Flow: GitHub main push -> signed HTTPS PHP webhook -> direct background shell execution -> fetch exact commit -> PHP lint -> live sync -> HTTP health check.

## Requirements
- PHP with exec enabled; bash, git, flock, rsync, curl and a PHP CLI binary
- Web server permission to launch a private executable script
- Outbound access to GitHub, HTTPS endpoint and read access to repository (deploy key for private repository)
- Verified document root and a persistent staging Git checkout

## Setup
1. Place webhook.php at a publicly reachable HTTPS path; keep deploy-main.sh outside the web root.
2. Set ECOMMERCE_WEBHOOK_SECRET and ECOMMERCE_DEPLOY_SCRIPT in the web server PHP process environment. Script path must be absolute.
3. Configure ECOMMERCE_REPO_DIR, ECOMMERCE_LIVE_DIR, ECOMMERCE_DEPLOY_LOG, ECOMMERCE_HEALTH_URL for the shell process.
4. In GitHub Settings > Webhooks, add the endpoint URL, choose application/json, only push events, and enter the same secret.
5. Verify signature rejection, ignored branches, commit fetch, PHP lint, target paths, and deploy log before setting ECOMMERCE_ENABLE_LIVE=1.

The HTTP 202 response means **triggered**, not deployed. Read the private deploy log and verify the deployed commit and application pages.

## Important
The initial script has no atomic release swap or automatic rollback. Do not enable live activation on a production store before these safeguards are added and tested. It preserves .env, config/local.php, uploads and storage; add any other persistent paths before enabling. Database schema migrations must be separately reviewed. No cron is used.

## Confirmed target
- Domain: https://kodcommerce.kodvadisi.com.tr
- Live document root: `/home/kodvadisi/domains/kodcommerce.kodvadisi.com.tr/public_html`
- Suggested private repo checkout: `/home/kodvadisi/ecommerce-repo` (must be created/verified)
- Suggested private log: `/home/kodvadisi/ecommerce-deploy.log` (must be writable)
- Health URL: `https://kodcommerce.kodvadisi.com.tr/` (must return HTTP 2xx after installation)

Do not enable live sync until PHP shell execution and permissions are tested. Configure these as server environment values, not repository secrets.
