<?php
declare(strict_types=1);
namespace Ecommerce\Database;

use PDO;
use RuntimeException;

final class Connection
{
    public static function open(string $root): PDO
    {
        $configPath = dirname($root) . '/ecommerce-db.php';
        if (!is_file($configPath)) {
            throw new RuntimeException('Database configuration missing');
        }
        $config = require $configPath;
        if (!is_array($config)) {
            throw new RuntimeException('Invalid database configuration');
        }
        foreach (['host', 'database', 'username', 'password'] as $key) {
            if (!isset($config[$key]) || !is_string($config[$key])) {
                throw new RuntimeException('Invalid database configuration');
            }
        }
        $host = $config['host'];
        $database = $config['database'];
        $port = $config['port'] ?? 3306;
        if (!is_int($port) || $port < 1 || $port > 65535 || !preg_match('/^[a-zA-Z0-9_.-]+$/', $host) || !preg_match('/^[a-zA-Z0-9_]+$/', $database)) {
            throw new RuntimeException('Invalid database host, port or name');
        }
        if (!extension_loaded('pdo_mysql')) {
            throw new RuntimeException('pdo_mysql extension required');
        }
        return new PDO(
            "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
            $config['username'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
}
