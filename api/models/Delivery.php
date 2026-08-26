<?php

namespace App\Models;

use Core\Model;

class Delivery extends Model
{
    protected static string $table = 'deliveries';

    public static function findByOrderNo(string $orderNo): ?array
    {
        return (new static())->where(['order_no' => $orderNo]);
    }

    /** 配送单带轨迹 */
    public static function detailWithTracks(int $deliveryId): ?array
    {
        $model = new static();
        $delivery = $model->find($deliveryId);
        if (!$delivery) return null;
        $delivery['tracks'] = db()->all('SELECT * FROM delivery_tracks WHERE delivery_id = ? ORDER BY id DESC', [$deliveryId]);
        return $delivery;
    }
}
