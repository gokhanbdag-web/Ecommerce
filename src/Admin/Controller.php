<?php
declare(strict_types=1);
namespace Ecommerce\Admin;
use PDO;
use Ecommerce\I18n\Translator;
final class Controller
{
    public function __construct(private PDO $db, private string $root) {}
    public function run(string $path): void
    {
        ini_set('session.use_strict_mode','1');
        ini_set('session.cookie_httponly','1');
        ini_set('session.cookie_samesite','Lax');
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ini_set('session.cookie_secure','1');
        session_start();
        $auth = new Auth($this->db);
        $settings = new Settings($this->db);
        $values = $settings->all();
        $t = new Translator($this->root,$values['site_language'] ?? 'en_US');
        $translate = static fn(string $s): string => $t->translate($s);
        $escape = static fn(string $s): string => htmlspecialchars($s,ENT_QUOTES | ENT_SUBSTITUTE,'UTF-8');
        $csrf = $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
        $error = '';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method === 'POST') {
            if (!hash_equals($csrf,(string) ($_POST['csrf'] ?? ''))) {
                http_response_code(403); exit('Invalid request token');
            }
            if ($path === 'logout' && $auth->authenticated()) {
                $auth->logout(); header('Location: /admin/login',true,303); return;
            }
            if ($path === 'login') {
                if ($auth->login((string) ($_POST['email'] ?? ''),(string) ($_POST['password'] ?? ''))) {
                    header('Location: /admin/settings',true,303); return;
                }
                $error = 'Invalid credentials';
            } elseif ($path === 'settings' && $auth->authenticated()) {
                try {
                    $settings->save($_POST);
                    header('Location: /admin/settings',true,303); return;
                } catch (\InvalidArgumentException $e) { $error = $e->getMessage(); }
            }
        }
        if (!$auth->authenticated() && $path !== 'login') {
            header('Location: /admin/login',true,303); return;
        }
        if ($auth->authenticated() && $path === 'login') {
            header('Location: /admin/settings',true,303); return;
        }
        if (!in_array($path,['login','settings'],true)) { http_response_code(404); return; }
        require $this->root . '/templates/admin.php';
    }
}
