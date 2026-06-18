<?php

declare(strict_types=1);

namespace app\models;

use flight\database\PdoWrapper;

class AdminModel
{
    private PdoWrapper $db;

    public function __construct(PdoWrapper $db)
    {
        $this->db = $db;
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->runQuery(
            'SELECT id, username, full_name, password_hash FROM admin_users WHERE username = ? LIMIT 1',
            [$username]
        );

        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function verifyCredentials(string $username, string $password): ?array
    {
        $admin = $this->findByUsername($username);
        if ($admin === null) {
            return null;
        }

        if (!password_verify($password, (string) $admin['password_hash'])) {
            return null;
        }

        return [
            'id' => (int) $admin['id'],
            'username' => (string) $admin['username'],
            'full_name' => (string) $admin['full_name'],
        ];
    }
}
