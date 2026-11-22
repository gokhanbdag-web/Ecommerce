<?php
declare(strict_types=1);
namespace Ecommerce\Admin;
use PDO;
final class Auth
{
    public function __construct(private PDO $db) {}
    public function hasAdministrator(): bool
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM administrators')->fetchColumn() > 0;
    }
    public function createFirst(string $email, string $password): bool
    {
        if ($this->hasAdministrator() || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) return false;
        $statement = $this->db->prepare('INSERT INTO administrators(email,password_hash) VALUES (?,?)');
        $statement->execute([$email,password_hash($password,PASSWORD_DEFAULT)]);
        return true;
    }
    public function login(string $email, string $password): bool
    {
        $statement = $this->db->prepare('SELECT id,password_hash FROM administrators WHERE email = ? LIMIT 1');
        $statement->execute([$email]);
        $admin = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$admin || !password_verify($password,$admin['password_hash'])) return false;
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['id'];
        return true;
    }
    public function authenticated(): bool { return isset($_SESSION['admin_id']) && is_int($_SESSION['admin_id']); }
    public function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }
}
