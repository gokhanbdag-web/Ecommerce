<?php
declare(strict_types=1);
namespace Ecommerce;

use PDO;

final class MenuRepository
{
    public function __construct(private PDO $db) {}

    public function itemsForLocation(string $location): array
    {
        $query = $this->db->prepare(
            "SELECT mi.label, mi.url, p.slug AS page_slug, mi.parent_id
             FROM menu_items mi
             JOIN menus m ON m.id = mi.menu_id
             LEFT JOIN pages p ON p.id = mi.page_id AND p.status = 'published'
             WHERE m.location = :location
               AND (mi.page_id IS NULL OR p.id IS NOT NULL)
             ORDER BY mi.position, mi.id"
        );
        $query->execute(['location' => $location]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}
