# Deployment setup

This first stage verifies signed pushes and stages an exact commit. **It does not yet deploy to the live web root.**

Requirements: PHP 8.2+, curl, zip, HTTPS, CLI PHP and cron (or equivalent scheduler). The queue and both scripts should be stored outside the public web root; expose only the webhook receiver.

Server environment:
- ECOMMERCE_WEBHOOK_SECRET: a strong random GitHub webhook secret.
- ECOMMERCE_DEPLOY_QUEUE: absolute path to a writable private queue file.
- ECOMMERCE_GITHUB_TOKEN: optional read-only fine-grained token for private repositories.

GitHub webhook: POST JSON to your HTTPS receiver URL, select only push events, set the same secret. A 202 response means queued, **not deployed**.

Run worker.php through cron as CLI PHP. It downloads the exact commit into a private staging directory, checks archive paths and refuses symlinks. The queue is intentionally not cleared before live activation is configured.

Before enabling live activation: confirm domain/document root, cron, CLI PHP, permissions, persistent files, health check URL and rollback mechanism. Never replace the live directory blindly or delete .env, uploads or database data.
