<?php
declare(strict_types=1);
require __DIR__ . '/src/bootstrap.php';
use Ecommerce\Admin\Auth;
use Ecommerce\Database\Connection;
use Ecommerce\Database\Installer;

$root = __DIR__;
$configPath = dirname($root) . '/ecommerce-db.php';
$https = ($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off';
if (!$https) { http_response_code(403); exit('HTTPS is required.'); }
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
ini_set('session.use_strict_mode','1');
ini_set('session.cookie_httponly','1');
ini_set('session.cookie_samesite','Lax');
ini_set('session.cookie_secure','1');
session_start();
$csrf = $_SESSION['install_csrf'] ??= bin2hex(random_bytes(32));
$escape = static fn(string $v): string => htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$error = '';
$step = is_file($configPath) ? 'admin' : 'database';

try {
    if ($step === 'admin') {
        $db = Connection::open($root);
        (new Installer($db, $root))->install();
        if ((new Auth($db))->hasAdministrator()) {
            header('Location: /admin/login', true, 303); exit;
        }
    }
} catch (Throwable $e) {
    error_log('Installer initialization: ' . $e->getMessage());
    $error = 'Database connection or initialization failed. Check the server error log.';
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(403); exit('Invalid request token');
    }
    if ($step === 'database' && !$error) {
        $host = trim((string) ($_POST['host'] ?? ''));
        $database = trim((string) ($_POST['database'] ?? ''));
        $username = (string) ($_POST['username'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $port = filter_var($_POST['port'] ?? '', FILTER_VALIDATE_INT);
        if (!preg_match('/^[a-zA-Z0-9_.-]+$/D', $host) ||
            !preg_match('/^[a-zA-Z0-9_]+$/D', $database) ||
            $username === '' || strlen($username) > 191 || $port === false || $port < 1 || $port > 65535) {
            $error = 'Enter valid MySQL connection details.';
        } elseif (!extension_loaded('pdo_mysql')) {
            $error = 'MySQL PDO extension is not available.';
        } else {
            try {
                $db = new PDO("mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4", $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                (new Installer($db, $root))->install();
                $config = ['host'=>$host,'port'=>$port,'database'=>$database,'username'=>$username,'password'=>$password];
                $payload = "<?php\nreturn " . var_export($config, true) . ";\n";
                $handle = @fopen($configPath, 'x');
                if ($handle === false) {
                    throw new RuntimeException('Cannot create private configuration. Check hosting directory permissions.');
                }
                try {
                    chmod($configPath, 0600);
                    if (fwrite($handle, $payload) !== strlen($payload)) {
                        throw new RuntimeException('Could not write private configuration.');
                    }
                    fflush($handle);
                } catch (Throwable $e) {
                    fclose($handle);
                    @unlink($configPath);
                    throw $e;
                }
                fclose($handle);
                unset($password, $payload, $config);
                header('Location: /install.php', true, 303); exit;
            } catch (Throwable $e) {
                error_log('Installer database setup: ' . $e->getMessage());
                $error = 'Database setup failed. Check credentials, database permissions and server logs.';
            }
        }
    } elseif ($step === 'admin' && !$error) {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        try {
            if ((new Auth($db))->createFirst($email, $password)) {
                unset($_SESSION['install_csrf']);
                header('Location: /admin/login', true, 303); exit;
            }
            $error = 'Enter a valid email and a password of at least 12 characters.';
        } catch (Throwable $e) {
            error_log('Installer admin creation: ' . $e->getMessage());
            $error = 'Could not create the administrator account.';
        }
    }
}
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Install Ecommerce</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head>
<body><main class="container py-5" style="max-width:620px"><h1>Install Ecommerce</h1>
<p><?= $step === 'database' ? 'Step 1: MySQL connection' : 'Step 2: Administrator account' ?></p>
<?php if ($error !== ''): ?><div class="alert alert-danger"><?= $escape($error) ?></div><?php endif; ?>
<form method="post" autocomplete="off"><input type="hidden" name="csrf" value="<?= $escape($csrf) ?>">
<?php if ($step === 'database'): ?>
<label class="form-label d-block">MySQL host<input class="form-control" name="host" value="localhost" required></label>
<label class="form-label d-block">Port<input class="form-control" name="port" type="number" value="3306" min="1" max="65535" required></label>
<label class="form-label d-block">Database name<input class="form-control" name="database" required></label>
<label class="form-label d-block">Database user<input class="form-control" name="username" required></label>
<label class="form-label d-block">Database password<input class="form-control" name="password" type="password" autocomplete="new-password"></label>
<button class="btn btn-primary">Connect and initialize database</button>
<?php else: ?>
<label class="form-label d-block">Administrator email<input class="form-control" name="email" type="email" required></label>
<label class="form-label d-block">Administrator password<input class="form-control" name="password" type="password" minlength="12" autocomplete="new-password" required></label>
<button class="btn btn-primary">Install and open login</button>
<?php endif; ?></form></main></body></html>
