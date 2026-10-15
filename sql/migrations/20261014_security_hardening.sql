-- 田冲助农商城 2026-10-14 迁移：只新增列/表/索引，不删改数据
ALTER TABLE verify_codes ADD COLUMN fail_count TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER used;
ALTER TABLE verify_codes ADD KEY idx_ip_created (ip, created_at);
CREATE TABLE IF NOT EXISTS rate_limits (
  `k` CHAR(64) NOT NULL PRIMARY KEY,
  hits INT UNSIGNED NOT NULL DEFAULT 0,
  reset_at INT NOT NULL DEFAULT 0,
  KEY idx_reset (reset_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='接口限流计数';
ALTER TABLE deliveries ADD KEY idx_order_no (order_no);
ALTER TABLE group_buy_members ADD KEY idx_order_no (order_no);
ALTER TABLE orders ADD KEY idx_status_created (status, created_at);
ALTER TABLE orders ADD KEY idx_status_delivered (status, delivered_at);
ALTER TABLE referral_rewards ADD UNIQUE KEY uk_order (order_id);
