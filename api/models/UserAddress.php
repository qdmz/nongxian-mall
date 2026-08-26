<?php

namespace App\Models;

use Core\Model;

class UserAddress extends Model
{
    protected static string $table = 'user_addresses';

    /** 设置默认地址 */
    public static function setDefault(int $userId, int $addressId): bool
    {
        db()->query('UPDATE user_addresses SET is_default = 0, updated_at = ? WHERE user_id = ?', [time(), $userId]);
        db()->query('UPDATE user_addresses SET is_default = 1, updated_at = ? WHERE id = ? AND user_id = ?', [time(), $addressId, $userId]);
        return true;
    }
}
