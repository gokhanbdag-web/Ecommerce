<?php
declare(strict_types=1);
namespace Ecommerce\Admin;
use PDO;
use InvalidArgumentException;
final class Settings
{
    private const FIELDS = ['site_title','site_description','site_email','site_timezone','site_language'];
    public function __construct(private PDO $db) {}
    public function all(): array
    {
        return $this->db->query('SELECT name,value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    public function save(array $values): void
    {
        $statement = $this->db->prepare('INSERT INTO settings(name,value) VALUES (?,?) ON CONFLICT(name) DO UPDATE SET value=excluded.value');
        foreach (self::FIELDS as $key) {
            if (!array_key_exists($key,$values)) continue;
            $value = trim((string) $values[$key]);
            if ($key === 'site_language' && !in_array($value,['en_US','tr_TR'],true)) throw new InvalidArgumentException('Invalid language');
            if ($key === 'site_email' && $value !== '' && !filter_var($value,FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Invalid email');
            if ($key === 'site_timezone' && !in_array($value,timezone_identifiers_list(),true)) throw new InvalidArgumentException('Invalid timezone');
            if (strlen($value) > 500) throw new InvalidArgumentException('Setting too long');
            $statement->execute([$key,$value]);
        }
    }
}
