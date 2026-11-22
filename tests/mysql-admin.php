<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';
if (getenv('ECOMMERCE_TEST_MYSQL') !== '1') {
    echo "MySQL admin test skipped\n"; exit(0);
}
$host = getenv('MYSQL_HOST') ?: '127.0.0.1';
$name = getenv('MYSQL_DATABASE') ?: '';
$user = getenv('MYSQL_USER') ?: '';
$pass = getenv('MYSQL_PASSWORD') ?: '';
if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) throw new RuntimeException('Dedicated test database required');
$db = new PDO("mysql:host={$host};dbname={$name};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
(new Ecommerce\Database\Installer($db,dirname(__DIR__)))->install();
$auth = new Ecommerce\Admin\Auth($db);
if (!$auth->createFirst('admin@example.com','a-very-long-password')) throw new RuntimeException('Admin creation failed');
if ($auth->createFirst('other@example.com','a-very-long-password')) throw new RuntimeException('Duplicate first admin accepted');
session_start();
if ($auth->login('admin@example.com','wrong')) throw new RuntimeException('Bad password accepted');
if (!$auth->login('admin@example.com','a-very-long-password')) throw new RuntimeException('Login failed');
$settings = new Ecommerce\Admin\Settings($db);
$settings->save(['site_language'=>'tr_TR','site_title'=>'Test']);
if ($settings->all()['site_language'] !== 'tr_TR') throw new RuntimeException('Settings update failed');
$auth->logout();
if ($auth->authenticated()) throw new RuntimeException('Logout failed');
echo "MySQL admin tests passed\n";
