<?php
declare(strict_types=1);
namespace Ecommerce;

use PDO;
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

        $database = $this->root . '/storage/site.sqlite';
        if (!is_file($database)) {
            http_response_code(503);
            echo 'Site is not installed yet.';
            return;
        }

        try {
            $db = new PDO('sqlite:' . $database, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
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
