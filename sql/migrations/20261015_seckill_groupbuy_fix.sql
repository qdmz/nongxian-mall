-- 田冲助农商城 v0.09 迁移（2026-10-15）：新增秒杀功能 + 修正拼团演示数据
-- 只新增表/列与演示数据，不删除任何表/列/数据。可重复执行（演示数据按标题去重）。
SET NAMES utf8mb4;

-- 1) 秒杀活动表
CREATE TABLE IF NOT EXISTS seckill_activities (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id INT UNSIGNED NOT NULL,
  sku_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '指定规格，0=按商品默认价/规格',
  title VARCHAR(120) NULL,
  seckill_price DECIMAL(12,2) NOT NULL COMMENT '秒杀价',
  stock INT NOT NULL DEFAULT 0 COMMENT '秒杀剩余库存（独立于商品库存，下单时两者同时扣减）',
  sold INT NOT NULL DEFAULT 0 COMMENT '已售（含待支付，取消/退款回补）',
  limit_per_user INT NOT NULL DEFAULT 1 COMMENT '每人限购件数，0=不限',
  start_time INT NOT NULL DEFAULT 0,
  end_time INT NOT NULL DEFAULT 0,
  status TINYINT NOT NULL DEFAULT 1 COMMENT '0=停用 1=启用',
  sort INT NOT NULL DEFAULT 0,
  created_at INT NOT NULL DEFAULT 0,
  updated_at INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_product (product_id),
  KEY idx_status_time (status, start_time, end_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='秒杀活动';

-- 2) 订单：秒杀活动 ID（type=3 秒杀订单）
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'seckill_activity_id');
SET @sql := IF(@col = 0,
  'ALTER TABLE orders ADD COLUMN seckill_activity_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT ''秒杀订单：秒杀活动ID'' AFTER group_target_id',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
ALTER TABLE orders MODIFY COLUMN `type` TINYINT NOT NULL DEFAULT 1 COMMENT '1普通 2拼团 3秒杀';

-- 3) 演示秒杀活动：从执行时刻开始，到 2026-11-14 23:59:59（北京时间，unix 1794671999）
INSERT INTO seckill_activities (product_id, sku_id, title, seckill_price, stock, sold, limit_per_user, start_time, end_time, status, sort, created_at, updated_at)
SELECT * FROM (
  SELECT 6  AS product_id, 0 AS sku_id, '修文猕猴桃 限时秒杀' AS title, 16.80 AS seckill_price, 50 AS stock, 0 AS sold, 3 AS limit_per_user, UNIX_TIMESTAMP() AS start_time, 1794671999 AS end_time, 1 AS status, 40 AS sort, UNIX_TIMESTAMP() AS created_at, UNIX_TIMESTAMP() AS updated_at
  UNION ALL SELECT 18, 0, '野生黑木耳 限时秒杀', 23.90, 40, 0, 2, UNIX_TIMESTAMP(), 1794671999, 1, 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
  UNION ALL SELECT 11, 0, '威宁洋芋 限时秒杀', 3.99, 100, 0, 5, UNIX_TIMESTAMP(), 1794671999, 1, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
  UNION ALL SELECT 25, 0, '红色助农·爱心礼盒A 限时秒杀', 89.00, 20, 0, 1, UNIX_TIMESTAMP(), 1794671999, 1, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
) d
WHERE NOT EXISTS (SELECT 1 FROM seckill_activities s WHERE s.title = d.title);

-- 4) 拼团演示数据修正：活动 1 原为「遵义朝天椒3斤家庭装 3人团」拼团价 69.90。
--    拼团下单不带规格，按商品默认规格（1斤，单买价 29.90）发货，69.90 高于单买价且与“3斤”不符。
--    修正为 1 斤装、拼团价 24.90（与其它拼团活动约 8.5 折的力度一致）。
UPDATE group_buy_activities
   SET title = '遵义朝天椒1斤 3人团', group_price = 24.90, updated_at = UNIX_TIMESTAMP()
 WHERE id = 1 AND product_id = 1 AND group_price = 69.90;
