<?php
declare(strict_types=1);
require __DIR__ . '/src/bootstrap.php';
use Ecommerce\Database\Installer;
use Ecommerce\Admin\Auth;
$root = __DIR__;
if (!extension_loaded('pdo_sqlite')) { http_response_code(503); exit('Database extension unavailable'); }
$databasePath = getenv('ECOMMERCE_DB_PATH') ?: dirname($root) . '/ecommerce-storage/site.sqlite';
$dir = dirname($databasePath);
if (!is_dir($dir) && !mkdir($dir,0700,true) && !is_dir($dir)) { http_response_code(503); exit('Storage unavailable'); }
$db = new PDO('sqlite:' . $databasePath,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
(new Installer($db,$root))->install();
$auth = new Auth($db);
if ($auth->hasAdministrator()) { http_response_code(403); exit('Installation already completed'); }
ini_set('session.use_strict_mode','1');
ini_set('session.cookie_httponly','1');
ini_set('session.cookie_samesite','Lax');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ini_set('session.cookie_secure','1');
session_start();
$csrf = $_SESSION['install_csrf'] ??= bin2hex(random_bytes(32));
$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!hash_equals($csrf,(string) ($_POST['csrf'] ?? ''))) { http_response_code(403); exit('Invalid request token'); }
    if ($auth->createFirst((string) ($_POST['email'] ?? ''),(string) ($_POST['password'] ?? ''))) {
        header('Location: /admin/login',true,303); exit;
    }
    $error = 'Use a valid email and password of at least 12 characters.';
}
$escape = static fn(string $v): string => htmlspecialchars($v,ENT_QUOTES | ENT_SUBSTITUTE,'UTF-8');
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Install Ecommerce</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head>
<body><main class="container py-5" style="max-width:600px"><h1>Install Ecommerce</h1>
<?php if ($error): ?><p class="alert alert-danger"><?= $escape($error) ?></p><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= $escape($csrf) ?>">
<label class="form-label d-block">Administrator Email<input class="form-control" name="email" type="email" required></label>
<label class="form-label d-block">Administrator Password<input class="form-control" name="password" type="password" minlength="12" required></label>
<button class="btn btn-primary">Complete Installation</button></form></main></body></html>
