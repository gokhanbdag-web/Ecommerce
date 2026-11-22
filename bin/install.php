<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';
$root = dirname(__DIR__);
if (!extension_loaded('pdo_sqlite')) {
    fwrite(STDERR, "pdo_sqlite is required\n");
    exit(1);
}
$databasePath = getenv('ECOMMERCE_DB_PATH') ?: dirname($root) . '/ecommerce-storage/site.sqlite';
$dir = dirname($databasePath);
if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
    fwrite(STDERR, "Unable to create storage directory\n");
    exit(1);
}
$db = new PDO('sqlite:' . $databasePath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
(new Ecommerce\Database\Installer($db, $root))->install();
echo "Database initialized. Administrator setup is not implemented yet.\n";
