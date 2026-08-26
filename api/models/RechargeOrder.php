<?php

namespace App\Models;

use Core\Model;

class RechargeOrder extends Model
{
    protected static string $table = 'recharge_orders';

    public static function findByNo(string $no): ?array
    {
        return (new static())->where(['recharge_no' => $no]);
    }
}
