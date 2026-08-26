<?php

namespace App\Models;

use Core\Model;

class Payment extends Model
{
    protected static string $table = 'payments';

    public static function findByPaymentNo(string $paymentNo): ?array
    {
        return (new static())->where(['payment_no' => $paymentNo]);
    }

    public static function findByBiz(string $bizType, string $bizNo): ?array
    {
        return (new static())->where(['biz_type' => $bizType, 'biz_no' => $bizNo]);
    }
}
