-- =============================================
-- 田冲助农商城 - 测试演示数据 v3 (修正版)
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================
-- 1. 测试用户 (密码: 123456)
-- =============================================
INSERT IGNORE INTO `users` (`id`, `phone`, `email`, `username`, `password`, `nickname`, `avatar`, `real_name`, `gender`, `wallet_balance`, `total_recharge`, `points`, `status`, `created_at`, `updated_at`) VALUES
(1, '13800138000', '<EMAIL>', 'demo', '$2y$10$UxuR7viXr1g2Wrqtwf33zelkf9M75t4i5Gg5liV3DsuTtqdenOGQa', '田冲小哥', NULL, '田冲小哥', 1, 200.00, 500.00, 500, 1, UNIX_TIMESTAMP() - 30*86400, UNIX_TIMESTAMP()),
(2, '13800138001', '<EMAIL>', 'zhang', '$2y$10$UxuR7viXr1g2Wrqtwf33zelkf9M75t4i5Gg5liV3DsuTtqdenOGQa', '小张', NULL, '张三', 1, 50.00, 100.00, 100, 1, UNIX_TIMESTAMP() - 25*86400, UNIX_TIMESTAMP()),
(3, '13800138002', '<EMAIL>', 'li', '$2y$10$UxuR7viXr1g2Wrqtwf33zelkf9M75t4i5Gg5liV3DsuTtqdenOGQa', '小李', NULL, '李四', 2, 100.00, 200.00, 200, 1, UNIX_TIMESTAMP() - 20*86400, UNIX_TIMESTAMP()),
(4, '13800138003', '<EMAIL>', 'wang', '$2y$10$UxuR7viXr1g2Wrqtwf33zelkf9M75t4i5Gg5liV3DsuTtqdenOGQa', '小王', NULL, '王五', 1, 30.00, 50.00, 50, 1, UNIX_TIMESTAMP() - 15*86400, UNIX_TIMESTAMP()),
(5, '13800138004', '<EMAIL>', 'zhao', '$2y$10$UxuR7viXr1g2Wrqtwf33zelkf9M75t4i5Gg5liV3DsuTtqdenOGQa', '小赵', NULL, '赵六', 2, 80.00, 150.00, 150, 1, UNIX_TIMESTAMP() - 10*86400, UNIX_TIMESTAMP()),
(6, '13800138005', '<EMAIL>', 'chen', '$2y$10$UxuR7viXr1g2Wrqtwf33zelkf9M75t4i5Gg5liV3DsuTtqdenOGQa', '小陈', NULL, '陈七', 1, 150.00, 300.00, 300, 1, UNIX_TIMESTAMP() - 5*86400, UNIX_TIMESTAMP());

-- =============================================
-- 2. 收货地址
-- =============================================
INSERT IGNORE INTO `user_addresses` (`id`, `user_id`, `consignee`, `phone`, `province`, `city`, `district`, `detail`, `is_default`, `label`, `created_at`, `updated_at`) VALUES
(1, 1, '田冲小哥', '13800138000', '贵州省', '遵义市', '红花岗区', '田冲村强村富民工坊1号', 1, '家', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(2, 1, '田冲小哥', '13800138000', '贵州省', '贵阳市', '观山湖区', '金融城A3栋2305', 0, '公司', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(3, 2, '张三', '13800138001', '贵州省', '遵义市', '汇川区', '上海路阳光小区5栋302', 1, '家', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(4, 3, '李四', '13800138002', '贵州省', '贵阳市', '云岩区', '大营坡翠屏巷12号', 1, '家', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(5, 4, '王五', '13800138003', '贵州省', '安顺市', '西秀区', '塔山东路东方小区8栋101', 1, '家', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(6, 5, '赵六', '13800138004', '贵州省', '毕节市', '七星关区', '开行路联邦花园6栋502', 1, '家', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(7, 6, '陈七', '13800138005', '贵州省', '都匀市', '广惠路', '剑江中路沙包堡安置房3栋702', 1, '家', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- =============================================
-- 3. 订单数据
-- =============================================
INSERT IGNORE INTO `orders` (`id`, `order_no`, `user_id`, `type`, `status`, `total_amount`, `pay_amount`, `discount_amount`, `use_balance`, `product_count`, `consignee`, `phone`, `address`, `remark`, `created_at`, `paid_at`, `delivered_at`, `completed_at`, `updated_at`) VALUES
(1, 'NX2026080100001', 1, 1, 4, 115.60, 105.60, 10.00, 0.00, 2, '田冲小哥', '13800138000', '贵州省遵义市红花岗区田冲村强村富民工坊1号', '尽快发货', UNIX_TIMESTAMP() - 20*86400, UNIX_TIMESTAMP() - 20*86400 + 300, UNIX_TIMESTAMP() - 18*86400, UNIX_TIMESTAMP() - 15*86400, UNIX_TIMESTAMP()),
(2, 'NX2026080200002', 2, 1, 4, 55.80, 55.80, 0.00, 0.00, 2, '张三', '13800138001', '贵州省遵义市汇川区上海路阳光小区5栋302', '', UNIX_TIMESTAMP() - 18*86400, UNIX_TIMESTAMP() - 18*86400 + 600, UNIX_TIMESTAMP() - 16*86400, UNIX_TIMESTAMP() - 13*86400, UNIX_TIMESTAMP()),
(3, 'NX2026080500003', 3, 1, 4, 39.80, 39.80, 0.00, 0.00, 2, '李四', '13800138002', '贵州省贵阳市云岩区大营坡翠屏巷12号', '', UNIX_TIMESTAMP() - 14*86400, UNIX_TIMESTAMP() - 14*86400 + 180, UNIX_TIMESTAMP() - 12*86400, UNIX_TIMESTAMP() - 10*86400, UNIX_TIMESTAMP()),
(4, 'NX2026081000004', 1, 1, 4, 88.70, 78.70, 10.00, 0.00, 3, '田冲小哥', '13800138000', '贵州省遵义市红花岗区田冲村强村富民工坊1号', '', UNIX_TIMESTAMP() - 10*86400, UNIX_TIMESTAMP() - 10*86400 + 240, UNIX_TIMESTAMP() - 8*86400, UNIX_TIMESTAMP() - 6*86400, UNIX_TIMESTAMP()),
(5, 'NX2026081200005', 4, 1, 3, 29.90, 29.90, 0.00, 0.00, 1, '王五', '13800138003', '贵州省安顺市西秀区塔山东路东方小区8栋101', '', UNIX_TIMESTAMP() - 8*86400, UNIX_TIMESTAMP() - 8*86400 + 360, UNIX_TIMESTAMP() - 6*86400, NULL, UNIX_TIMESTAMP()),
(6, 'NX2026081500006', 5, 1, 2, 118.00, 118.00, 0.00, 0.00, 2, '赵六', '13800138004', '贵州省毕节市七星关区开行路联邦花园6栋502', '送人，请包装好一点', UNIX_TIMESTAMP() - 5*86400, UNIX_TIMESTAMP() - 5*86400 + 480, NULL, NULL, UNIX_TIMESTAMP()),
(7, 'NX2026081800007', 6, 1, 1, 12.80, 12.80, 0.00, 0.00, 1, '陈七', '13800138005', '贵州省都匀市广惠路剑江中路沙包堡安置房3栋702', '', UNIX_TIMESTAMP() - 2*86400, NULL, NULL, NULL, UNIX_TIMESTAMP()),
(8, 'NX2026082000008', 1, 2, 4, 79.90, 79.90, 0.00, 0.00, 3, '田冲小哥', '13800138000', '贵州省遵义市红花岗区田冲村强村富民工坊1号', '拼团成功', UNIX_TIMESTAMP() - 3*86400, UNIX_TIMESTAMP() - 3*86400 + 120, UNIX_TIMESTAMP() - 1*86400, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9, 'NX2026082200009', 2, 1, 4, 45.80, 45.80, 0.00, 0.00, 2, '张三', '13800138001', '贵州省遵义市汇川区上海路阳光小区5栋302', '', UNIX_TIMESTAMP() - 1*86400, UNIX_TIMESTAMP() - 1*86400 + 180, UNIX_TIMESTAMP() + 86400, NULL, UNIX_TIMESTAMP()),
(10, 'NX2026082500010', 3, 1, 0, 156.00, 146.00, 10.00, 0.00, 3, '李四', '13800138002', '贵州省贵阳市云岩区大营坡翠屏巷12号', '', UNIX_TIMESTAMP(), NULL, NULL, NULL, UNIX_TIMESTAMP());

-- =============================================
-- 4. 订单商品
-- =============================================
INSERT IGNORE INTO `order_items` (`id`, `order_id`, `product_id`, `sku_id`, `name`, `image`, `specs`, `price`, `quantity`, `subtotal`, `created_at`) VALUES
(1, 1, 1, 2, '遵义朝天椒', '/uploads/products/chili.jpg', '1斤装', 29.90, 1, 29.90, UNIX_TIMESTAMP() - 20*86400),
(2, 1, 9, 5, '湄潭翠芽', '/uploads/products/tea.jpg', '100g试用装', 88.00, 1, 88.00, UNIX_TIMESTAMP() - 20*86400),
(3, 2, 2, NULL, '安顺山药', '/uploads/products/yam.jpg', '5斤', 15.80, 2, 31.60, UNIX_TIMESTAMP() - 18*86400),
(4, 2, 13, NULL, '绿壳蛋', '/uploads/products/greenegg.jpg', '30枚装', 3.50, 7, 24.50, UNIX_TIMESTAMP() - 18*86400),
(5, 3, 11, NULL, '威宁洋芋', '/uploads/products/potato.jpg', '5斤', 6.80, 3, 20.40, UNIX_TIMESTAMP() - 14*86400),
(6, 3, 10, NULL, '从江香猪', '/uploads/products/xiangzhu.jpg', '半斤', 68.00, 1, 68.00, UNIX_TIMESTAMP() - 14*86400),
(7, 4, 3, NULL, '贵阳折耳根', '/uploads/products/zheergen.jpg', '2斤', 9.90, 2, 19.80, UNIX_TIMESTAMP() - 10*86400),
(8, 4, 4, NULL, '毕节大蒜', '/uploads/products/garlic.jpg', '3斤', 12.80, 1, 12.80, UNIX_TIMESTAMP() - 10*86400),
(9, 4, 18, NULL, '野生黑木耳', '/uploads/products/woodear.jpg', '两', 35.00, 1, 35.00, UNIX_TIMESTAMP() - 10*86400),
(10, 5, 1, 2, '遵义朝天椒', '/uploads/products/chili.jpg', '1斤装', 29.90, 1, 29.90, UNIX_TIMESTAMP() - 8*86400),
(11, 6, 21, 8, '贵州茅台镇酱香酒', '/uploads/products/maotai.jpg', '单瓶装', 299.00, 1, 299.00, UNIX_TIMESTAMP() - 5*86400),
(12, 7, 22, 11, '老干妈辣椒酱', '/uploads/products/laoganma.jpg', '单瓶装', 12.80, 1, 12.80, UNIX_TIMESTAMP() - 2*86400),
(13, 8, 1, 3, '遵义朝天椒', '/uploads/products/chili.jpg', '3斤家庭装', 79.90, 1, 79.90, UNIX_TIMESTAMP() - 3*86400),
(14, 9, 5, NULL, '镇宁樱桃', '/uploads/products/cherry.jpg', '2斤', 58.00, 1, 58.00, UNIX_TIMESTAMP() - 1*86400),
(15, 9, 20, NULL, '贵州香菇', '/uploads/products/mushroom.jpg', '两', 28.00, 1, 28.00, UNIX_TIMESTAMP() - 1*86400),
(16, 10, 21, 8, '贵州茅台镇酱香酒', '/uploads/products/maotai.jpg', '单瓶装', 299.00, 1, 299.00, UNIX_TIMESTAMP()),
(17, 10, 23, NULL, '刺梨干', '/uploads/products/ciligan.jpg', '袋', 25.80, 2, 51.60, UNIX_TIMESTAMP());

-- =============================================
-- 5. 拼团活动
-- =============================================
INSERT IGNORE INTO `group_buy_activities` (`id`, `product_id`, `title`, `group_price`, `required_count`, `max_count`, `valid_hours`, `stock`, `status`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES
(1, 1, '遵义朝天椒3斤家庭装 3人团', 69.90, 3, 0, 48, 200, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + 30*86400, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(2, 9, '湄潭翠芽100g 2人团', 79.90, 2, 0, 48, 100, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + 30*86400, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(3, 22, '老干妈辣椒酱 2人团', 9.90, 2, 0, 48, 500, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + 30*86400, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(4, 5, '镇宁樱桃 5人团', 49.90, 5, 0, 24, 150, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + 30*86400, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- =============================================
-- 6. 购物车数据
-- =============================================
INSERT IGNORE INTO `carts` (`id`, `user_id`, `product_id`, `sku_id`, `quantity`, `selected`, `created_at`, `updated_at`) VALUES
(1, 1, 21, 8, 1, 1, UNIX_TIMESTAMP() - 86400, UNIX_TIMESTAMP()),
(2, 1, 9, 5, 2, 1, UNIX_TIMESTAMP() - 86400, UNIX_TIMESTAMP()),
(3, 1, 1, 2, 1, 1, UNIX_TIMESTAMP() - 86400, UNIX_TIMESTAMP()),
(4, 2, 5, NULL, 2, 1, UNIX_TIMESTAMP() - 86400, UNIX_TIMESTAMP()),
(5, 2, 18, NULL, 1, 0, UNIX_TIMESTAMP() - 86400, UNIX_TIMESTAMP()),
(6, 3, 10, NULL, 1, 1, UNIX_TIMESTAMP() - 86400, UNIX_TIMESTAMP()),
(7, 3, 22, 12, 1, 1, UNIX_TIMESTAMP() - 86400, UNIX_TIMESTAMP());

-- =============================================
-- 7. 站内消息
-- =============================================
INSERT IGNORE INTO `notifications` (`id`, `user_id`, `title`, `content`, `type`, `is_read`, `created_at`) VALUES
(1, 1, '欢迎来到田冲助农商城！', '亲爱的用户，欢迎加入田冲助农商城！在这里您可以购买到正宗的贵州特色农产品，每一份消费都是对乡村振兴的支持。', 'system', 0, UNIX_TIMESTAMP() - 30*86400),
(2, 1, '您有新的订单已发货', '订单NX2026081500006已发货，请注意查收。', 'order', 0, UNIX_TIMESTAMP() - 4*86400),
(3, 1, '拼团成功提醒', '您参加的遵义朝天椒拼团已成功，商家将尽快发货。', 'order', 1, UNIX_TIMESTAMP() - 2*86400),
(4, 2, '欢迎来到田冲助农商城！', '亲爱的用户，欢迎加入田冲助农商城！', 'system', 0, UNIX_TIMESTAMP() - 25*86400),
(5, 3, '欢迎来到田冲助农商城！', '亲爱的用户，欢迎加入田冲助农商城！', 'system', 0, UNIX_TIMESTAMP() - 20*86400),
(6, 4, '欢迎来到田冲助农商城！', '亲爱的用户，欢迎加入田冲助农商城！', 'system', 0, UNIX_TIMESTAMP() - 15*86400),
(7, 5, '欢迎来到田冲助农商城！', '亲爱的用户，欢迎加入田冲助农商城！', 'system', 0, UNIX_TIMESTAMP() - 10*86400),
(8, 6, '欢迎来到田冲助农商城！', '亲爱的用户，欢迎加入田冲助农商城！', 'system', 0, UNIX_TIMESTAMP() - 5*86400);

-- =============================================
-- 8. 推广分享
-- =============================================
INSERT IGNORE INTO `referrals` (`id`, `user_id`, `product_id`, `code`, `click_count`, `order_count`, `earnings`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'DEMO2026', 156, 12, 25.80, UNIX_TIMESTAMP() - 30*86400, UNIX_TIMESTAMP()),
(2, 1, 9, 'DEMO2026', 89, 6, 35.60, UNIX_TIMESTAMP() - 30*86400, UNIX_TIMESTAMP()),
(3, 2, 5, 'ZHANG2026', 67, 4, 12.40, UNIX_TIMESTAMP() - 25*86400, UNIX_TIMESTAMP()),
(4, 3, 22, 'LI2026', 45, 3, 8.50, UNIX_TIMESTAMP() - 20*86400, UNIX_TIMESTAMP()),
(5, 4, 1, 'WANG2026', 23, 2, 5.80, UNIX_TIMESTAMP() - 15*86400, UNIX_TIMESTAMP());

-- =============================================
-- 9. 每日统计 (修正字段名)
-- =============================================
INSERT IGNORE INTO `statistics_daily` (`date`, `order_count`, `order_amount`, `new_users`, `pay_count`, `pay_amount`, `created_at`) VALUES
(DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 30 DAY), '%Y%m%d'), 5, 356.80, 6, 4, 298.00, UNIX_TIMESTAMP() - 30*86400),
(DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 25 DAY), '%Y%m%d'), 3, 189.50, 1, 3, 189.50, UNIX_TIMESTAMP() - 25*86400),
(DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 20 DAY), '%Y%m%d'), 4, 245.60, 1, 3, 198.00, UNIX_TIMESTAMP() - 20*86400),
(DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 15 DAY), '%Y%m%d'), 6, 456.70, 1, 5, 389.00, UNIX_TIMESTAMP() - 15*86400),
(DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 10 DAY), '%Y%m%d'), 8, 567.80, 1, 7, 498.00, UNIX_TIMESTAMP() - 10*86400),
(DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 7 DAY), '%Y%m%d'), 7, 432.50, 0, 6, 398.00, UNIX_TIMESTAMP() - 7*86400),
(DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 DAY), '%Y%m%d'), 4, 298.60, 1, 4, 298.60, UNIX_TIMESTAMP() - 5*86400),
(DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 3 DAY), '%Y%m%d'), 6, 389.90, 0, 5, 356.00, UNIX_TIMESTAMP() - 3*86400),
(DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), '%Y%m%d'), 5, 345.80, 0, 4, 298.00, UNIX_TIMESTAMP() - 1*86400),
(DATE_FORMAT(CURDATE(), '%Y%m%d'), 3, 156.80, 0, 2, 128.00, UNIX_TIMESTAMP());

-- =============================================
-- 10. 商品统计 (修正字段名)
-- =============================================
INSERT IGNORE INTO `statistics_products` (`product_id`, `date`, `view_count`, `sales_count`, `sales_amount`, `created_at`) VALUES
(1, DATE_FORMAT(CURDATE(), '%Y%m%d'), 1280, 256, 7654.40, UNIX_TIMESTAMP()),
(2, DATE_FORMAT(CURDATE(), '%Y%m%d'), 960, 189, 2986.20, UNIX_TIMESTAMP()),
(3, DATE_FORMAT(CURDATE(), '%Y%m%d'), 1560, 320, 3168.00, UNIX_TIMESTAMP()),
(5, DATE_FORMAT(CURDATE(), '%Y%m%d'), 2340, 89, 5162.00, UNIX_TIMESTAMP()),
(9, DATE_FORMAT(CURDATE(), '%Y%m%d'), 1450, 156, 13704.00, UNIX_TIMESTAMP()),
(10, DATE_FORMAT(CURDATE(), '%Y%m%d'), 760, 89, 6052.00, UNIX_TIMESTAMP()),
(11, DATE_FORMAT(CURDATE(), '%Y%m%d'), 2100, 432, 2937.60, UNIX_TIMESTAMP()),
(13, DATE_FORMAT(CURDATE(), '%Y%m%d'), 2890, 567, 1984.50, UNIX_TIMESTAMP()),
(18, DATE_FORMAT(CURDATE(), '%Y%m%d'), 1230, 245, 8575.00, UNIX_TIMESTAMP()),
(21, DATE_FORMAT(CURDATE(), '%Y%m%d'), 1560, 178, 53222.00, UNIX_TIMESTAMP()),
(22, DATE_FORMAT(CURDATE(), '%Y%m%d'), 4560, 876, 11212.80, UNIX_TIMESTAMP()),
(23, DATE_FORMAT(CURDATE(), '%Y%m%d'), 1120, 234, 6037.20, UNIX_TIMESTAMP());

-- =============================================
-- 11. 配送模板
-- =============================================
INSERT IGNORE INTO `delivery_templates` (`id`, `name`, `type`, `first_weight_fee`, `continue_fee`, `free_threshold`, `areas`, `status`, `created_at`, `updated_at`) VALUES
(1, '全国包邮', 1, 8.00, 3.00, 99.00, '["全国"]', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(2, '贵州本省', 1, 6.00, 2.00, 59.00, '["贵州省"]', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(3, '江浙沪', 1, 10.00, 4.00, 129.00, '["江苏省","浙江省","上海市"]', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(4, '偏远地区', 1, 15.00, 8.00, 199.00, '["新疆维吾尔自治区","西藏自治区","青海省","内蒙古自治区"]', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- =============================================
-- 12. 管理员日志 (修正字段名)
-- =============================================
INSERT IGNORE INTO `admin_logs` (`id`, `admin_id`, `module`, `action`, `description`, `ip`, `created_at`) VALUES
(1, 1, '系统', '登录', '系统登录', '192.168.1.100', UNIX_TIMESTAMP() - 86400),
(2, 1, '商品', '上架', '商品ID: 1,2,3,4,5', '192.168.1.100', UNIX_TIMESTAMP() - 86400),
(3, 1, '轮播图', '设置', '首页轮播图', '192.168.1.100', UNIX_TIMESTAMP() - 86400),
(4, 2, '订单', '发货', '订单NX2026081500006', '192.168.1.101', UNIX_TIMESTAMP() - 4*86400),
(5, 2, '拼团', '创建', '创建拼团活动', '192.168.1.101', UNIX_TIMESTAMP() - 3*86400),
(6, 3, '订单', '处理', '退款申请处理', '192.168.1.102', UNIX_TIMESTAMP() - 2*86400);

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- 数据导入完成
-- =============================================
