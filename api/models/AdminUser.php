<?php

namespace App\Models;

use Core\Model;

class AdminUser extends Model
{
    protected static string $table = 'admin_users';

    public static function findByUsername(string $username): ?array
    {
        return (new static())->where(['username' => $username]);
    }

    public static function safe(array $admin): array
    {
        unset($admin['password']);
        return $admin;
    }
}
