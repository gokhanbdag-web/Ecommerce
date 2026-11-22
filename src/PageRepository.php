<?php
declare(strict_types=1);
namespace Ecommerce;

use PDO;

final class PageRepository
{
    public function __construct(private PDO $db) {}

    public function findForPath(string $path): ?array
    {
        if ($path === '') {
            $query = $this->db->prepare(
                "SELECT p.* FROM pages p JOIN settings s ON s.value = CAST(p.id AS TEXT)
                 WHERE s.name = 'homepage_page_id' AND p.status = 'published' LIMIT 1"
            );
            $query->execute();
        } else {
            $query = $this->db->prepare(
                "SELECT * FROM pages WHERE slug = :slug AND status = 'published' LIMIT 1"
            );
            $query->execute(['slug' => $path]);
        }
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result === false ? null : $result;
    }
}
