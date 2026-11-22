<?php
declare(strict_types=1);
namespace Ecommerce\Database;

use PDO;
use RuntimeException;

final class Installer
{
    public function __construct(private PDO $db, private string $root) {}

    public function install(): void
    {
        $this->migrate();
        $count = (int) $this->db->query('SELECT COUNT(*) FROM pages')->fetchColumn();
        if ($count !== 0) {
            return;
        }
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('INSERT INTO pages(title,slug,status,content_json) VALUES (?,?,?,?)');
            $stmt->execute(['Home', 'home', 'published', '[{"type":"heading","text":"Welcome"}]']);
            $pageId = (string) $this->db->lastInsertId();
            $settings = $this->db->prepare('INSERT INTO settings(name,value) VALUES (?,?)');
            $check = $this->db->prepare('SELECT COUNT(*) FROM settings WHERE name = ?');
            foreach (['homepage_page_id' => $pageId, 'site_language' => 'en_US'] as $name => $value) {
                $check->execute([$name]);
                if ((int) $check->fetchColumn() === 0) {
                    $settings->execute([$name, $value]);
                }
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function migrate(): void
    {
        $mysql = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql';
        $this->db->exec($mysql
            ? 'CREATE TABLE IF NOT EXISTS schema_migrations (version VARCHAR(191) NOT NULL PRIMARY KEY) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
            : 'CREATE TABLE IF NOT EXISTS schema_migrations (version TEXT PRIMARY KEY)');
        $dir = $mysql ? 'database/mysql' : 'database/migrations';
        $paths = glob($this->root . '/' . $dir . '/*.sql') ?: [];
        sort($paths, SORT_STRING);
        $migrations = $paths ? array_combine(array_map(static fn(string $path): string => basename($path, '.sql'), $paths), array_map(static fn(string $path): string|false => file_get_contents($path), $paths)) : ($mysql ? Schema::mysql() : []);
        foreach ($migrations as $version => $sql) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM schema_migrations WHERE version = ?');
            $stmt->execute([$version]);
            if ((int) $stmt->fetchColumn() > 0) {
                continue;
            }
            if ($sql === false) {
                throw new RuntimeException('Cannot read migration');
            }
            // MySQL DDL implicitly commits: record version only after all statements succeed.
            if (!$mysql) {
                $this->db->beginTransaction();
            }
            try {
                if ($mysql) {
                    foreach (explode(';', $sql) as $statement) {
                        if (trim($statement) !== '') {
                            $this->db->exec($statement);
                        }
                    }
                } else {
                    $this->db->exec($sql);
                }
                $record = $this->db->prepare('INSERT INTO schema_migrations(version) VALUES (?)');
                $record->execute([$version]);
                if (!$mysql) {
                    $this->db->commit();
                }
            } catch (\Throwable $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                throw $e;
            }
        }
    }
}
