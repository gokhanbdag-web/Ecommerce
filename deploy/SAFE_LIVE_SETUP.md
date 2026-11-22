# CyberPanel live MySQL and deployment runbook

This runbook does **not** run on the server automatically. Complete each checkpoint before enabling live deployment.

## 1. Prerequisites and safety

- Verify the site vhost routes clean URLs to index.php; /health must return 200.
- Confirm `pdo_mysql` is loaded for both the hosting account CLI and LiteSpeed PHP.
- The CyberPanel MySQL database already exists. Never create, drop, truncate or overwrite it from this runbook.
- Back up the existing database using CyberPanel before applying any migrations. Check existing table names for conflicts with pages, settings, menus, menu_items and schema_migrations.
- Keep `/home/kodcommerce.kodvadisi.com.tr/ecommerce-db.php` outside the web root. Owner `kodco8881`, mode `0600`; populate from `config/ecommerce-db.example.php` using the existing CyberPanel database credentials. Never paste credentials into chat, GitHub, or deployment logs.
- `deploy-main.sh` must be installed at `/home/kodcommerce.kodvadisi.com.tr/deploy-main.sh` and owned by `kodco8881`. Installing it is a **separate** manual server step; merging GitHub changes does not update that installed copy.

## 2. Database migration (manual)

After backing up MySQL and checking for conflicting tables, execute from the repository as the hosting account:

```bash
cd /home/kodcommerce.kodvadisi.com.tr/ecommerce-repo
php bin/install.php
```

The installer uses the existing database and records versioned migrations. It does not create an administrator yet. MySQL DDL may implicitly commit; if a migration fails, inspect the partially applied schema before retrying.

## 3. First public runtime sync

The runtime allowlist is `index.php`, `page.php`, `src/` and `languages/`. Keep `bin/`, `database/`, `deploy/`, `config/`, `tests/`, `.git/`, `.github/` and private configuration **outside** public sync. Do not use `rsync --delete`.

Perform initial deployment with a dry run, then manually apply only after inspecting output. Verify `/health` returns 200 and `/` returns 200 before enabling webhook live activation.

## 4. Activate webhook only after validation

The webhook uses a server-installed script and its process environment. Exporting `ECOMMERCE_ENABLE_LIVE=1` in an interactive SSH session does **not** enable it for the LiteSpeed webhook process. Configure the flag through a reviewed, server-side activation mechanism with restricted permissions. Until then, leave live activation disabled.

The updated script validates the latest main SHA, lints PHP, backs up the managed runtime subset, deploys only allowlisted files, and checks both /health and /. If a command fails after backup, it attempts to restore the previous managed files. This is **file rollback only**; it does not roll back MySQL migrations, and does not remove newly introduced files. Retain and manage backups outside the web root.

## 5. Verification

Check `/health` (200), `/` (200 after installation), the webhook GET response (405), and the private deployment log. Keep PR #4 unmerged until its own tests and approval. Do not enable automatic schema migrations on webhook pushes.
