<?php

namespace App\Services;

/**
 * 运费计算（2026-10-14b 新增，使用已有的 delivery_templates 表）
 *
 * 规则（商品表没有重量字段，模板 type=1 按件计费）：
 *  1. 按收货地址的省份匹配启用中的模板：areas(JSON 数组) 中包含该省（兼容"贵州"/"贵州省"写法）；多个命中取 id 最小的；
 *  2. 没有命中时使用默认模板：启用中且 areas 为空的模板（如"全国包邮"）；都没有则运费为 0；
 *  3. free_threshold > 0 且商品金额 >= free_threshold → 包邮；
 *  4. 否则 运费 = 首件费 first_weight_fee + (总件数 - 1) × 续件费 continue_fee。
 *  拼团订单不收运费（拼团价包邮）。
 */
class FreightService
{
    public static function normalizeProvince(string $p): string
    {
        $p = trim($p);
        return preg_replace('/(省|市|自治区|壮族自治区|回族自治区|维吾尔自治区|特别行政区)$/u', '', $p) ?? $p;
    }

    /** 匹配模板 */
    public static function matchTemplate(string $province): ?array
    {
        $templates = db()->all('SELECT * FROM delivery_templates WHERE status = 1 ORDER BY id ASC');
        $target = self::normalizeProvince($province);
        $default = null;
        foreach ($templates as $tpl) {
            $areas = json_decode((string)($tpl['areas'] ?? ''), true);
            if (!is_array($areas) || !$areas) {
                if ($default === null) $default = $tpl;
                continue;
            }
            foreach ($areas as $a) {
                if ($target !== '' && self::normalizeProvince((string)$a) === $target) {
                    return $tpl;
                }
            }
        }
        return $default;
    }

    /**
     * @param array $address 至少含 province
     * @param float $goodsAmount 商品金额
     * @param int $pieces 总件数
     * @return array{fee: float, template_id: int, template_name: string, free_threshold: float}
     */
    public static function calculate(array $address, float $goodsAmount, int $pieces): array
    {
        $tpl = self::matchTemplate((string)($address['province'] ?? ''));
        if (!$tpl || $pieces <= 0) {
            return ['fee' => 0.0, 'template_id' => 0, 'template_name' => '', 'free_threshold' => 0.0];
        }
        $threshold = (float)$tpl['free_threshold'];
        if ($threshold > 0 && $goodsAmount + 0.0001 >= $threshold) {
            $fee = 0.0;
        } else {
            $fee = (float)$tpl['first_weight_fee'] + max(0, $pieces - 1) * (float)$tpl['continue_fee'];
        }
        return [
            'fee' => round(max(0, $fee), 2),
            'template_id' => (int)$tpl['id'],
            'template_name' => (string)$tpl['name'],
            'free_threshold' => $threshold,
        ];
    }
}
