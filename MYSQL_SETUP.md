# CyberPanel MySQL configuration

The application uses a pre-existing MySQL/MariaDB database. Do not commit credentials.

1. Create `/home/kodcommerce.kodvadisi.com.tr/ecommerce-db.php` outside `public_html` using `config/ecommerce-db.example.php` as a template. Set the database name, user and password from CyberPanel. Keep permissions at 0600 owned by the hosting account.
2. Confirm the CLI and LSAPI PHP runtimes have `pdo_mysql` installed.
3. Deploy the application files including `database/mysql/` and `bin/install.php` to the live application root. Keep the configuration file outside the document root.
4. Back up any existing database before running migrations. Run `php bin/install.php` from the deployed application root as the hosting user; it creates application tables in the existing database, not a new database.
5. Confirm `/health` returns 200 and `/` renders the seeded homepage.

MySQL migration files are separate from legacy SQLite migrations. MySQL DDL is not transactional: a failed migration may leave tables behind. Inspect and reconcile before retrying. The initial installer only creates CMS tables and a homepage; administrator creation is not implemented on main yet. Do not enable automatic live deployment until deployment safety, protected storage, and rollback are addressed.

For CI, run `ECOMMERCE_TEST_MYSQL=1 MYSQL_HOST=127.0.0.1 MYSQL_DATABASE=ecommerce_ci MYSQL_USER=ecommerce MYSQL_PASSWORD=... php tests/mysql.php` against a dedicated disposable database.
