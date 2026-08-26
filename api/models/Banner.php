<?php

namespace App\Models;

use Core\Model;

class Banner extends Model
{
    protected static string $table = 'banners';

    public static function listByPosition(string $position = 'home'): array
    {
        $now = time();
        $sql = 'SELECT * FROM banners WHERE position = ? AND status = 1 
                AND (start_time = 0 OR start_time <= ?) 
                AND (end_time = 0 OR end_time >= ?) 
                ORDER BY sort DESC, id DESC';
        return db()->all($sql, [$position, $now, $now]);
    }
}
