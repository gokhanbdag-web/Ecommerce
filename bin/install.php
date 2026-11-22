<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';
$root = dirname(__DIR__);
try {
    $db = Ecommerce\Database\Connection::open($root);
    (new Ecommerce\Database\Installer($db, $root))->install();
    echo "MySQL database initialized. Administrator setup is not implemented yet.\n";
} catch (Throwable $error) {
    fwrite(STDERR, "Installation failed: " . $error->getMessage() . "\n");
    exit(1);
}
