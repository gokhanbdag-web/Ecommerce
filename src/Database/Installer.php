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
            $this->db->exec("INSERT INTO pages(title,slug,status,content_json) VALUES ('Home','home','published','[{\"type\":\"heading\",\"text\":\"Welcome\"}]')");
            $this->db->exec("INSERT OR IGNORE INTO settings(name,value) VALUES ('homepage_page_id','1')");
            $this->db->exec("INSERT OR IGNORE INTO settings(name,value) VALUES ('site_language','en_US')");
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function migrate(): void
    {
        $this->db->exec('CREATE TABLE IF NOT EXISTS schema_migrations (version TEXT PRIMARY KEY)');
        $paths = glob($this->root . '/database/migrations/*.sql') ?: [];
        sort($paths, SORT_STRING);
        foreach ($paths as $path) {
            $version = basename($path, '.sql');
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM schema_migrations WHERE version = ?');
            $stmt->execute([$version]);
            if ((int) $stmt->fetchColumn() > 0) {
                continue;
            }
            $sql = file_get_contents($path);
            if ($sql === false) {
                throw new RuntimeException('Cannot read migration');
            }
            $this->db->beginTransaction();
            try {
                $this->db->exec($sql);
                $record = $this->db->prepare('INSERT INTO schema_migrations(version) VALUES (?)');
                $record->execute([$version]);
                $this->db->commit();
            } catch (\Throwable $e) {
                $this->db->rollBack();
                throw $e;
            }
        }
    }
}
