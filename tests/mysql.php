<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';
$root = dirname(__DIR__);
if (!extension_loaded('pdo_mysql')) {
    echo "MySQL integration skipped: pdo_mysql not installed\n";
    exit(0);
}
if (getenv('ECOMMERCE_TEST_MYSQL') !== '1') {
    echo "MySQL integration skipped: ECOMMERCE_TEST_MYSQL not set\n";
    exit(0);
}
$host = getenv('MYSQL_HOST') ?: '127.0.0.1';
$port = (int) (getenv('MYSQL_PORT') ?: 3306);
$name = getenv('MYSQL_DATABASE') ?: '';
$user = getenv('MYSQL_USER') ?: '';
$pass = getenv('MYSQL_PASSWORD') ?: '';
if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
    throw new RuntimeException('A dedicated test database name is required');
}
$db = new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
$installer = new Ecommerce\Database\Installer($db, $root);
$installer->install();
$installer->install();
if ((int) $db->query('SELECT COUNT(*) FROM pages')->fetchColumn() !== 1) {
    throw new RuntimeException('MySQL homepage seed not idempotent');
}
if ($db->query("SELECT value FROM settings WHERE name='site_language'")->fetchColumn() !== 'en_US') {
    throw new RuntimeException('MySQL language seed failed');
}
if ((new Ecommerce\PageRepository($db))->findForPath('')['slug'] !== 'home') {
    throw new RuntimeException('MySQL homepage lookup failed');
}
echo "MySQL integration tests passed\n";
