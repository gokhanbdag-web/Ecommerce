<?php
declare(strict_types=1);
namespace Ecommerce;

use Ecommerce\Database\Connection;
use Throwable;

final class Application
{
    public function __construct(private string $root) {}

    public function run(): void
    {
        if (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) === '/health') {
            header('Content-Type: text/plain; charset=utf-8');
            echo 'ok';
            return;
        }

        if (!is_file(dirname($this->root) . '/ecommerce-db.php')) {
            http_response_code(503);
            echo 'Site is not installed yet.';
            return;
        }

        try {
            $db = Connection::open($this->root);
            $path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
            if ($path === 'admin' || str_starts_with($path, 'admin/')) {
                (new \Ecommerce\Admin\Controller($db, $this->root))->run($path === 'admin' ? 'settings' : substr($path, 6));
                return;
            }
            $page = (new PageRepository($db))->findForPath($path);
            if ($page === null) {
                http_response_code(404);
                echo 'Page not found.';
                return;
            }
            $menus = (new MenuRepository($db))->itemsForLocation('primary');
            require $this->root . '/page.php';
        } catch (Throwable $error) {
            error_log($error->getMessage());
            http_response_code(503);
            echo 'Site is temporarily unavailable.';
        }
    }
}
