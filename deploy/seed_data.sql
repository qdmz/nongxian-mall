-- =============================================
-- 田冲助农商城 - 测试演示数据
-- 贵州亿田农业 · 田冲红色美丽乡村强村富民工坊
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================
-- 1. 管理员角色
-- =============================================
INSERT INTO `admin_roles` (`id`, `name`, `slug`, `permissions`, `status`, `remark`) VALUES
(1, '超级管理员', 'super', '*', 1, '拥有全部权限'),
(2, '运营经理', 'operator', 'dashboard,products,categories,orders,users,group_buy,banners,statistics', 1, '负责日常运营管理'),
(3, '客服专员', 'support', 'orders,users,notifications', 1, '负责订单和用户咨询');

-- =============================================
-- 2. 管理员账号 (密码: admin123 / operator123 / support123)
-- =============================================
INSERT INTO `admin_users` (`id`, `username`, `password`, `real_name`, `phone`, `email`, `role_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$UxuR7viXr1g2Wrqtwf33zelkf9M75t4i5Gg5liV3DsuTtqdenOGQa', '系统管理员', '13800138000', 'admin@nongxian.com', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(2, 'operator', '$2y$10$UxuR7viXr1g2Wrqtwf33zelkf9M75t4i5Gg5liV3DsuTtqdenOGQa', '张运营', '13800138001', 'operator@nongxian.com', 2, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(3, 'support', '$2y$10$UxuR7viXr1g2Wrqtwf33zelkf9M75t4i5Gg5liV3DsuTtqdenOGQa', '李客服', '13800138002', 'support@nongxian.com', 3, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- =============================================
-- 3. 商品分类 (保留原有，增加图标和排序)
-- =============================================
UPDATE `categories` SET `sort` = 100, `icon` = '🥬' WHERE `id` = 1;
UPDATE `categories` SET `sort` = 90, `icon` = '🍎' WHERE `id` = 2;
UPDATE `categories` SET `sort` = 80, `icon` = '🌾' WHERE `id` = 3;
UPDATE `categories` SET `sort` = 70, `icon` = '🥚' WHERE `id` = 4;
UPDATE `categories` SET `sort` = 60, `icon` = '🍄' WHERE `id` = 5;
UPDATE `categories` SET `sort` = 50, `icon' = '🎁' WHERE `id` = 6;
UPDATE `categories` SET `sort` = 200, `icon` = '🚩', `is_red` = 1 WHERE `id` = 7;

-- =============================================
-- 4. 商品数据 - 贵州特色农产品
-- =============================================

-- 分类1: 新鲜蔬菜
INSERT INTO `products` (`category_id`, `name`, `subtitle`, `description`, `cover_image`, `images`, `unit`, `price`, `original_price`, `cost_price`, `stock`, `stock_warn`, `sales`, `virtual_sales`, `view_count`, `status`, `is_hot`, `is_new`, `is_recommend`, `created_at`, `updated_at`) VALUES
(1, '遵义朝天椒', '贵州特产 香辣可口 地理标志产品', '遵义朝天椒是贵州特产，椒果细长，皮薄肉厚，香辣可口，富含维生素C和辣椒素。适合炒菜、做调料、腌制泡菜等。', '/uploads/products/chili.jpg', '["/uploads/products/chili1.jpg","/uploads/products/chili2.jpg"]', '斤', 29.90, 39.90, 18.00, 500, 20, 256, 300, 1280, 1, 1, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(1, '安顺山药', '粉糯香甜 营养丰富 新鲜现挖', '安顺山药产于安顺市，土壤肥沃，气候温和，山药块茎粗大，肉质粉糯，口感香甜，是老少皆宜的滋补佳品。', '/uploads/products/yam.jpg', '["/uploads/products/yam1.jpg","/uploads/products/yam2.jpg"]', '斤', 15.80, 22.00, 9.50, 800, 30, 189, 200, 960, 1, 1, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(1, '贵阳折耳根', '清热解毒 凉拌佳品 鲜嫩多汁', '折耳根又名鱼腥草，是贵州人最爱的野菜之一，清热解毒，消肿排脓，凉拌折耳根是贵州经典凉菜。', '/uploads/products/zheergen.jpg', '["/uploads/products/zheergen1.jpg"]', '斤', 9.90, 15.00, 5.00, 600, 25, 320, 350, 1560, 1, 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(1, '毕节大蒜', '蒜香浓郁 瓣大饱满 绿色无污染', '毕节大蒜种植于高海拔山区，日照充足，昼夜温差大，大蒜素含量高达0.3%，蒜香浓郁，瓣大饱满。', '/uploads/products/garlic.jpg', '["/uploads/products/garlic1.jpg"]', '斤', 12.80, 18.00, 7.00, 1000, 40, 145, 180, 720, 1, 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 分类2: 时令水果
INSERT INTO `products` (`category_id`, `name`, `subtitle`, `description`, `cover_image`, `images`, `unit`, `price`, `original_price`, `cost_price`, `stock`, `stock_warn`, `sales`, `virtual_sales`, `view_count`, `status`, `is_hot`, `is_new`, `is_recommend`, `created_at`, `updated_at`) VALUES
(2, '镇宁樱桃', '红宝石 酸甜多汁 现摘现发', '镇宁樱桃是贵州著名水果，果实晶莹剔透，红如宝石，果肉细腻，酸甜多汁，每年4-5月上市，供不应求。', '/uploads/products/cherry.jpg', '["/uploads/products/cherry1.jpg","/uploads/products/cherry2.jpg"]', '斤', 58.00, 78.00, 35.00, 300, 15, 89, 100, 2340, 1, 1, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(2, '修文猕猴桃', '维C之王 软糯香甜 有机种植', '修文猕猴桃生长于贵州高原，海拔800-1300米，昼夜温差大，猕猴桃糖分积累充足，软糯香甜，维生素C含量高达100mg/100g。', '/uploads/products/kiwi.jpg', '["/uploads/products/kiwi1.jpg","/uploads/products/kiwi2.jpg"]', '斤', 25.80, 35.00, 15.00, 600, 25, 234, 280, 1120, 1, 1, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(2, '从江香柚', '清甜爽口 香气扑鼻 百年老树', '从江香柚产于黔东南州，种植历史超过200年，果肉晶莹剔透，清甜爽口，带有独特香气，是柚中珍品。', '/uploads/products/pomelo.jpg', '["/uploads/products/pomelo1.jpg"]', '个', 38.00, 50.00, 22.00, 400, 20, 112, 150, 890, 1, 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(2, '罗甸火龙果', '红心蜜甜 个大饱满 热带水果', '罗甸火龙果生长在贵州南部低热河谷，日照充足，火龙果个大饱满，最大可达1.5kg，果肉鲜甜多汁，富含花青素。', '/uploads/products/dragonfruit.jpg', '["/uploads/products/dragonfruit1.jpg"]', '斤', 19.90, 29.90, 12.00, 450, 20, 178, 200, 980, 1, 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 分类3: 粮油米面
INSERT INTO `products` (`category_id`, `name`, `subtitle`, `description`, `cover_image`, `images`, `unit`, `price`, `original_price`, `cost_price`, `stock`, `stock_warn`, `sales`, `virtual_sales`, `view_count`, `status`, `is_hot`, `is_new`, `is_recommend`, `created_at`, `updated_at`) VALUES
(3, '湄潭翠芽', '贵州十大名茶 清香回甘 手工采摘', '湄潭翠芽产于遵义市湄潭县，是贵州十大名茶之一。外形扁平光滑，色泽翠绿，香气清高持久，滋味鲜醇回甘。', '/uploads/products/tea.jpg', '["/uploads/products/tea1.jpg","/uploads/products/tea2.jpg"]', '两', 88.00, 128.00, 50.00, 200, 10, 156, 200, 1450, 1, 1, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(3, '从江香猪', '中华地理标志 肉质鲜美 营养丰富', '从江香猪是贵州特产，体型矮小，肉质鲜嫩，肌间脂肪含量高，富含不饱和脂肪酸，是猪肉中的极品。', '/uploads/products/xiangzhu.jpg', '["/uploads/products/xiangzhu1.jpg"]', '斤', 68.00, 88.00, 42.00, 300, 15, 89, 120, 760, 1, 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(3, '威宁洋芋', '粉糯香甜 高山种植 绿色无污染', '威宁洋芋产于毕节市威宁县，海拔2000米以上，日照充足，昼夜温差大，洋芋粉糯香甜，淀粉含量高达22%。', '/uploads/products/potato.jpg', '["/uploads/products/potato1.jpg"]', '斤', 6.80, 9.90, 3.50, 2000, 100, 432, 500, 2100, 1, 1, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(3, '贵州红米', '营养丰富 软糯可口 高山梯田', '贵州红米种植于黔东南梯田，采用传统耕作方式，不使用农药化肥，红米富含铁、锌等矿物质，口感软糯。', '/uploads/products/redrice.jpg', '["/uploads/products/redrice1.jpg"]', '斤', 18.80, 26.00, 11.00, 800, 35, 198, 250, 1340, 1, 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 分类4: 禽蛋肉品
INSERT INTO `products` (`category_id`, `name`, `subtitle`, `description`, `cover_image`, `images`, `unit`, `price`, `original_price`, `cost_price`, `stock`, `stock_warn`, `sales`, `virtual_sales`, `view_count`, `status`, `is_hot`, `is_new`, `is_recommend`, `created_at`, `updated_at`) VALUES
(4, '绿壳蛋', '营养蛋黄 蛋黄饱满 农家散养', '绿壳蛋由贵州本地土鸡产出，蛋壳呈浅绿色，蛋黄比例高达35%，富含卵磷脂和多种氨基酸，是鸡蛋中的珍品。', '/uploads/products/greenegg.jpg', '["/uploads/products/greenegg1.jpg"]', '枚', 3.50, 5.00, 1.80, 500, 25, 567, 600, 2890, 1, 1, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(4, '贵州黄牛', '草饲放养 肉质紧实 鲜香可口', '贵州黄牛采用传统放养方式，食天然牧草，饮山泉水，肉质紧实，脂肪分布均匀，口感鲜香，是牛肉中的上品。', '/uploads/products/cattle.jpg', '["/uploads/products/cattle1.jpg"]', '斤', 58.00, 75.00, 35.00, 250, 12, 98, 130, 870, 1, 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(4, '黔东南土鸡', '散养土鸡 肉质鲜嫩 营养丰富', '黔东南土鸡散养于苗岭山林，自由觅食昆虫和野草，生长周期长达180天，肉质鲜嫩，汤汁醇厚，营养丰富。', '/uploads/products/chicken.jpg', '["/uploads/products/chicken1.jpg"]', '只', 128.00, 168.00, 80.00, 150, 8, 67, 80, 560, 1, 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(4, '习酒鸡蛋', '贵州特产 口感醇厚 传统工艺', '习酒鸡蛋产自贵州习水县，采用传统工艺制作，鸡蛋经过酒醅腌制，口感醇厚，酒香浓郁，是贵州特色美食。', '/uploads/products/xijiuegg.jpg', '["/uploads/products/xijiuegg1.jpg"]', '盒', 45.00, 60.00, 28.00, 300, 15, 134, 160, 920, 1, 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 分类5: 山珍干货
INSERT INTO `products` (`category_id`, `name`, `subtitle`, `description`, `cover_image`, `images`, `unit`, `price`, `original_price`, `cost_price`, `stock`, `stock_warn`, `sales`, `virtual_sales`, `view_count`, `status`, `is_hot`, `is_new`, `is_recommend`, `created_at`, `updated_at`) VALUES
(5, '织金竹荪', '菌中皇后 营养丰富 贵州特产', '织金竹荪产于毕节市织金县，被誉为"菌中皇后"，富含多种氨基酸和微量元素，口感脆嫩，煲汤佳品。', '/uploads/products/zhusun.jpg', '["/uploads/products/zhusun1.jpg"]', '两', 128.00, 188.00, 80.00, 150, 8, 78, 100, 650, 1, 0, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(5, '野生黑木耳', '肉质厚实 营养丰富 森林采摘', '贵州野生黑木耳生长于茂密森林，自然生长，肉质厚实，富含铁、钙和多种维生素，是天然保健食品。', '/uploads/products/woodear.jpg', '["/uploads/products/woodear1.jpg"]', '两', 35.00, 50.00, 20.00, 400, 20, 245, 300, 1230, 1, 1, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(5, '大方天麻', '名贵中药材 天麻素含量高 野生采集', '大方天麻产于毕节市大方县，是贵州名贵中药材，天麻素含量高达0.4%，是治疗头痛、眩晕的良药。', '/uploads/products/tianma.jpg', '["/uploads/products/tianma1.jpg"]', '两', 168.00, 228.00, 100.00, 100, 5, 45, 60, 380, 1, 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(5, '贵州香菇', '菇香浓郁 肉质鲜嫩 椴木栽培', '贵州香菇采用椴木栽培，自然生长，菇香浓郁，肉质鲜嫩，富含香菇多糖，具有增强免疫力的功效。', '/uploads/products/mushroom.jpg', '["/uploads/products/mushroom1.jpg"]', '两', 28.00, 38.00, 15.00, 600, 25, 189, 220, 980, 1, 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 分类6: 特色特产
INSERT INTO `products` (`category_id`, `name`, `subtitle`, `description`, `cover_image`, `images`, `unit`, `price`, `original_price`, `cost_price`, `stock`, `stock_warn`, `sales`, `virtual_sales`, `view_count`, `status`, `is_hot`, `is_new`, `is_recommend`, `created_at`, `updated_at`) VALUES
(6, '贵州茅台镇酱香酒', '53度坤沙工艺 纯粮酿造 回味悠长', '产自贵州茅台镇7.5平方公里核心产区，采用传统坤沙工艺，端午踩曲，重阳下沙，九次蒸煮，八次发酵，七次取酒。', '/uploads/products/maotai.jpg', '["/uploads/products/maotai1.jpg","/uploads/products/maotai2.jpg"]', '瓶', 299.00, 399.00, 180.00, 300, 15, 178, 200, 1560, 1, 1, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(6, '老干妈辣椒酱', '贵州风味 香辣可口 国民辣酱', '老干妈辣椒酱是贵州风味经典代表，采用贵州特有辣椒品种，经传统工艺炒制而成，香辣可口，回味悠长。', '/uploads/products/laoganma.jpg', '["/uploads/products/laoganma1.jpg"]', '瓶', 12.80, 18.00, 7.00, 1500, 80, 876, 1000, 4560, 1, 1, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(6, '刺梨干', '维C之王 酸甜可口 天然营养', '刺梨是贵州特色水果，维生素C含量高达2500mg/100g，被称为"维C之王"，刺梨干酸甜可口，营养丰富。', '/uploads/products/ciligan.jpg', '["/uploads/products/ciligan1.jpg"]', '袋', 25.80, 35.00, 14.00, 500, 25, 234, 280, 1120, 1, 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(6, '贵州蜡染', '非遗手工 民族风情 独特工艺', '贵州蜡染是国家级非物质文化遗产，采用传统手工蜡绘工艺，图案精美，色彩绚丽，是贵州民族文化的瑰宝。', '/uploads/products/lalan.jpg', '["/uploads/products/lalan1.jpg","/uploads/products/lalan2.jpg"]', '件', 168.00, 228.00, 90.00, 100, 5, 56, 80, 560, 1, 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 分类7: 红色助农专区 (is_red=1)
INSERT INTO `products` (`category_id`, `name`, `subtitle`, `description`, `cover_image`, `images`, `unit`, `price`, `original_price`, `cost_price`, `stock`, `stock_warn`, `sales`, `virtual_sales`, `view_count`, `status`, `is_hot`, `is_new`, `is_recommend`, `is_red`, `created_at`, `updated_at`) VALUES
(7, '红色助农·爱心礼盒A', '党建引领 乡村振兴 消费帮扶', '由田冲红色美丽乡村强村富民工坊出品，精选贵州特色农产品组合，每购买一份即为乡村振兴贡献一份力量。', '/uploads/products/redbox1.jpg', '["/uploads/products/redbox1_1.jpg"]', '盒', 128.00, 168.00, 80.00, 300, 15, 234, 280, 1450, 1, 1, 0, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(7, '红色助农·爱心礼盒B', '强村富民 消费助农 爱心传递', '精选贵州5款特色农产品，党员推荐，品质保障。每售出一份，将有10%捐赠给当地困难农户。', '/uploads/products/redbox2.jpg', '["/uploads/products/redbox2_1.jpg"]', '盒', 198.00, 268.00, 120.00, 200, 10, 156, 200, 980, 1, 1, 1, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(7, '党员推荐·有机大米', '绿色种植 无农药无化肥 党员示范田', '由田冲村党员示范田种植，全程绿色管控，不使用农药化肥，大米颗粒饱满，米香浓郁。', '/uploads/products/redrice_party.jpg', '["/uploads/products/redrice1.jpg"]', '袋', 45.00, 58.00, 25.00, 500, 25, 312, 380, 1890, 1, 0, 0, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(7, '助农山茶油', '物理压榨 原生态 红色工坊出品', '精选贵州高山茶籽，采用传统物理压榨工艺，茶油色泽金黄，香味浓郁，营养丰富，是健康首选食用油。', '/uploads/products/teaoil.jpg', '["/uploads/products/teaoil1.jpg"]', '瓶', 88.00, 118.00, 50.00, 400, 20, 198, 250, 1230, 1, 1, 0, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- =============================================
-- 5. 商品SKU
-- =============================================
INSERT INTO `product_skus` (`product_id`, `specs`, `price`, `stock`, `sales`, `status`, `created_at`, `updated_at`) VALUES
-- 遵义朝天椒 SKU
(1, '半斤装', 15.80, 250, 128, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(1, '1斤装', 29.90, 500, 256, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(1, '3斤家庭装', 79.90, 200, 89, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(1, '5斤批发装', 128.00, 150, 45, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
-- 湄潭翠芽 SKU
(9, '100g试用装', 88.00, 100, 78, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9, '250g礼盒装', 198.00, 80, 56, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9, '500g收藏装', 368.00, 50, 23, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
-- 贵州茅台镇酱香酒 SKU
(25, '单瓶装', 299.00, 100, 78, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(25, '2瓶装礼盒', 568.00, 50, 45, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(25, '6瓶装整箱', 1688.00, 30, 12, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
-- 老干妈 SKU
(26, '单瓶装', 12.80, 800, 438, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(26, '3瓶装', 35.80, 400, 234, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(26, '6瓶装整箱', 68.00, 200, 123, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- =============================================
-- 6. 首页轮播图
-- =============================================
INSERT INTO `banners` (`title`, `image`, `link_type`, `link_value`, `position`, `sort`, `status`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES
('党建引领乡村振兴 消费助农在路上', '/uploads/banners/banner1.jpg', 0, NULL, 'home', 100, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + 365*86400, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('田冲助农商城正式上线 特色农产品买起来', '/uploads/banners/banner2.jpg', 0, NULL, 'home', 90, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + 365*86400, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('红色助农专区 党员推荐品质保障', '/uploads/banners/banner3.jpg', 0, NULL, 'home', 80, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + 365*86400, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('拼团专区上线 邀好友一起享优惠', '/uploads/banners/banner4.jpg', 0, NULL, 'home', 70, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + 365*86400, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('新用户专享 首单立减10元', '/uploads/banners/banner5.jpg', 0, NULL, 'home', 60, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + 365*86400, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- =============================================
-- 7. 测试用户 (密码: 123456)
-- =============================================
INSERT INTO `users` (`phone`, `email`, `username`, `password`, `nickname`, `avatar`, `real_name`, `gender`, `wallet_balance`, `total_recharge`, `points`, `level`, `status`, `invite_code`, `created_at`, `updated_at`) VALUES
('13800138000', '<EMAIL>', 'demo', '$2y$10$UxuR7viXr1g2Wrqtwf33zelkf9M75t4i5Gg5liV3DsuTtqdenOGQa', '田冲小哥', NULL, '田冲小哥', 1, 200.00, 500.00, 500, 1, 1, 'DEMO2026', UNIX_TIMESTAMP() - 30*86400, UNIX_TIMESTAMP()),
('13800138001', '<EMAIL>', 'zhang', '$2y$10$UxuR7viXr1g2Wrqtwf33zelkf9M75t4i5Gg5liV3DsuTtqdenOGQa', '小张', NULL, '张三', 1, 50.00, 100.00, 100, 1, 1, 'ZHANG2026', UNIX_TIMESTAMP() - 25*86400, UNIX_TIMESTAMP()),
('13800138002', '<EMAIL>', 'li', '$2y$10$UxuR7viXr1g2Wrqtwf33zelkf9M75t4i5Gg5liV3DsuTtqdenOGQa', '小李', NULL, '李四', 2, 100.00, 200.00, 200, 1, 1, 'LI2026', UNIX_TIMESTAMP() - 20*86400, UNIX_TIMESTAMP()),
('13800138003', '<EMAIL>', 'wang', '$2y$10$UxuR7viXr1g2Wrqtwf33zelkf9M75t4i5Gg5liV3DsuTtqdenOGQa', '小王', NULL, '王五', 1, 30.00, 50.00, 50, 1, 1, 'WANG2026', UNIX_TIMESTAMP() - 15*86400, UNIX_TIMESTAMP()),
('13800138004', '<EMAIL>', 'zhao', '$2y$10$UxuR7viXr1g2Wrqtwf33zelkf9M75t4i5Gg5liV3DsuTtqdenOGQa', '小赵', NULL, '赵六', 2, 80.00, 150.00, 150, 1, 1, 'ZHAO2026', UNIX_TIMESTAMP() - 10*86400, UNIX_TIMESTAMP()),
('13800138005', '<EMAIL>', 'chen', '$2y$10$UxuR7viXr1g2Wrqtwf33zelkf9M75t4i5Gg5liV3DsuTtqdenOGQa', '小陈', NULL, '陈七', 1, 150.00, 300.00, 300, 2, 1, 'CHEN2026', UNIX_TIMESTAMP() - 5*86400, UNIX_TIMESTAMP());

-- =============================================
-- 8. 收货地址
-- =============================================
INSERT INTO `user_addresses` (`user_id`, `consignee`, `phone`, `province`, `city`, `district`, `detail`, `is_default`, `label`, `created_at`, `updated_at`) VALUES
(1, '田冲小哥', '13800138000', '贵州省', '遵义市', '红花岗区', '田冲村强村富民工坊1号', 1, '家', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(1, '田冲小哥', '13800138000', '贵州省', '贵阳市', '观山湖区', '金融城A3栋2305', 0, '公司', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(2, '张三', '13800138001', '贵州省', '遵义市', '汇川区', '上海路阳光小区5栋302', 1, '家', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(3, '李四', '13800138002', '贵州省', '贵阳市', '云岩区', '大营坡翠屏巷12号', 1, '家', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(4, '王五', '13800138003', '贵州省', '安顺市', '西秀区', '塔山东路东方小区8栋101', 1, '家', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(5, '赵六', '13800138004', '贵州省', '毕节市', '七星关区', '开行路联邦花园6栋502', 1, '家', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(6, '陈七', '13800138005', '贵州省', '都匀市', '广惠路', '剑江中路沙包堡安置房3栋702', 1, '家', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- =============================================
-- 9. 订单数据
-- =============================================
INSERT INTO `orders` (`order_no`, `user_id`, `type`, `status`, `total_amount`, `pay_amount`, `discount_amount`, `use_balance`, `product_count`, `consignee`, `phone`, `province`, `city`, `district`, `address`, `remark`, `created_at`, `paid_at`, `shipped_at`, `completed_at`, `updated_at`) VALUES
('NX2026080100001', 1, 1, 4, 115.60, 105.60, 10.00, 0.00, 2, '田冲小哥', '13800138000', '贵州省', '遵义市', '红花岗区', '田冲村强村富民工坊1号', '尽快发货', UNIX_TIMESTAMP() - 20*86400, UNIX_TIMESTAMP() - 20*86400 + 300, UNIX_TIMESTAMP() - 18*86400, UNIX_TIMESTAMP() - 15*86400, UNIX_TIMESTAMP()),
('NX2026080200002', 2, 1, 4, 55.80, 55.80, 0.00, 0.00, 2, '张三', '13800138001', '贵州省', '遵义市', '汇川区', '上海路阳光小区5栋302', '', UNIX_TIMESTAMP() - 18*86400, UNIX_TIMESTAMP() - 18*86400 + 600, UNIX_TIMESTAMP() - 16*86400, UNIX_TIMESTAMP() - 13*86400, UNIX_TIMESTAMP()),
('NX2026080500003', 3, 1, 4, 39.80, 39.80, 0.00, 0.00, 2, '李四', '13800138002', '贵州省', '贵阳市', '云岩区', '大营坡翠屏巷12号', '', UNIX_TIMESTAMP() - 14*86400, UNIX_TIMESTAMP() - 14*86400 + 180, UNIX_TIMESTAMP() - 12*86400, UNIX_TIMESTAMP() - 10*86400, UNIX_TIMESTAMP()),
('NX2026081000004', 1, 1, 4, 88.70, 78.70, 10.00, 0.00, 3, '田冲小哥', '13800138000', '贵州省', '遵义市', '红花岗区', '田冲村强村富民工坊1号', '', UNIX_TIMESTAMP() - 10*86400, UNIX_TIMESTAMP() - 10*86400 + 240, UNIX_TIMESTAMP() - 8*86400, UNIX_TIMESTAMP() - 6*86400, UNIX_TIMESTAMP()),
('NX2026081200005', 4, 1, 3, 29.90, 29.90, 0.00, 0.00, 1, '王五', '13800138003', '贵州省', '安顺市', '西秀区', '塔山东路东方小区8栋101', '', UNIX_TIMESTAMP() - 8*86400, UNIX_TIMESTAMP() - 8*86400 + 360, UNIX_TIMESTAMP() - 6*86400, NULL, UNIX_TIMESTAMP()),
('NX2026081500006', 5, 1, 2, 118.00, 118.00, 0.00, 0.00, 2, '赵六', '13800138004', '贵州省', '毕节市', '七星关区', '开行路联邦花园6栋502', '送人，请包装好一点', UNIX_TIMESTAMP() - 5*86400, UNIX_TIMESTAMP() - 5*86400 + 480, NULL, NULL, UNIX_TIMESTAMP()),
('NX2026081800007', 6, 1, 1, 12.80, 12.80, 0.00, 0.00, 1, '陈七', '13800138005', '贵州省', '都匀市', '广惠路', '剑江中路沙包堡安置房3栋702', '', UNIX_TIMESTAMP() - 2*86400, NULL, NULL, NULL, UNIX_TIMESTAMP()),
('NX2026082000008', 1, 2, 4, 79.90, 79.90, 0.00, 0.00, 3, '田冲小哥', '13800138000', '贵州省', '遵义市', '红花岗区', '田冲村强村富民工坊1号', '拼团成功', UNIX_TIMESTAMP() - 3*86400, UNIX_TIMESTAMP() - 3*86400 + 120, UNIX_TIMESTAMP() - 1*86400, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('NX2026082200009', 2, 1, 4, 45.80, 45.80, 0.00, 0.00, 2, '张三', '13800138001', '贵州省', '遵义市', '汇川区', '上海路阳光小区5栋302', '', UNIX_TIMESTAMP() - 1*86400, UNIX_TIMESTAMP() - 1*86400 + 180, UNIX_TIMESTAMP() + 86400, NULL, UNIX_TIMESTAMP()),
('NX2026082500010', 3, 1, 0, 156.00, 146.00, 10.00, 0.00, 3, '李四', '13800138002', '贵州省', '贵阳市', '云岩区', '大营坡翠屏巷12号', '', UNIX_TIMESTAMP(), NULL, NULL, NULL, UNIX_TIMESTAMP());

-- =============================================
-- 10. 订单商品
-- =============================================
INSERT INTO `order_items` (`order_id`, `product_id`, `sku_id`, `name`, `image`, `specs`, `price`, `quantity`, `subtotal`, `created_at`) VALUES
-- 订单1
(1, 1, 2, '遵义朝天椒', '/uploads/products/chili.jpg', '1斤装', 29.90, 1, 29.90, UNIX_TIMESTAMP() - 20*86400),
(1, 9, 5, '湄潭翠芽', '/uploads/products/tea.jpg', '100g试用装', 88.00, 1, 88.00, UNIX_TIMESTAMP() - 20*86400),
-- 订单2
(2, 2, NULL, '安顺山药', '/uploads/products/yam.jpg', '5斤', 15.80, 2, 31.60, UNIX_TIMESTAMP() - 18*86400),
(2, 17, NULL, '绿壳蛋', '/uploads/products/greenegg.jpg', '30枚装', 3.50, 7, 24.50, UNIX_TIMESTAMP() - 18*86400),
-- 订单3
(3, 11, NULL, '威宁洋芋', '/uploads/products/potato.jpg', '5斤', 6.80, 3, 20.40, UNIX_TIMESTAMP() - 14*86400),
(3, 10, NULL, '从江香猪', '/uploads/products/xiangzhu.jpg', '半斤', 68.00, 1, 68.00, UNIX_TIMESTAMP() - 14*86400),
-- 订单4
(4, 3, NULL, '贵阳折耳根', '/uploads/products/zheergen.jpg', '2斤', 9.90, 2, 19.80, UNIX_TIMESTAMP() - 10*86400),
(4, 4, NULL, '毕节大蒜', '/uploads/products/garlic.jpg', '3斤', 12.80, 1, 12.80, UNIX_TIMESTAMP() - 10*86400),
(4, 19, NULL, '野生黑木耳', '/uploads/products/woodear.jpg', '两', 35.00, 1, 35.00, UNIX_TIMESTAMP() - 10*86400),
-- 订单5
(5, 1, 2, '遵义朝天椒', '/uploads/products/chili.jpg', '1斤装', 29.90, 1, 29.90, UNIX_TIMESTAMP() - 8*86400),
-- 订单6
(6, 25, 27, '贵州茅台镇酱香酒', '/uploads/products/maotai.jpg', '单瓶装', 299.00, 1, 299.00, UNIX_TIMESTAMP() - 5*86400),
-- 订单7
(7, 26, 29, '老干妈辣椒酱', '/uploads/products/laoganma.jpg', '单瓶装', 12.80, 1, 12.80, UNIX_TIMESTAMP() - 2*86400),
-- 订单8 (拼团)
(8, 1, 3, '遵义朝天椒', '/uploads/products/chili.jpg', '3斤家庭装', 79.90, 1, 79.90, UNIX_TIMESTAMP() - 3*86400),
-- 订单9
(9, 5, NULL, '镇宁樱桃', '/uploads/products/cherry.jpg', '2斤', 58.00, 1, 58.00, UNIX_TIMESTAMP() - 1*86400),
(9, 20, NULL, '贵州香菇', '/uploads/products/mushroom.jpg', '两', 28.00, 1, 28.00, UNIX_TIMESTAMP() - 1*86400),
-- 订单10
(10, 25, 27, '贵州茅台镇酱香酒', '/uploads/products/maotai.jpg', '单瓶装', 299.00, 1, 299.00, UNIX_TIMESTAMP()),
(10, 27, NULL, '刺梨干', '/uploads/products/ciligan.jpg', '袋', 25.80, 2, 51.60, UNIX_TIMESTAMP());

-- =============================================
-- 11. 拼团活动
-- =============================================
INSERT INTO `group_buy_activities` (`product_id`, `title`, `group_price`, `required_count`, `max_count`, `valid_hours`, `stock`, `status`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES
(1, '遵义朝天椒3斤家庭装 3人团', 69.90, 3, 0, 48, 200, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + 30*86400, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9, '湄潭翠芽100g 2人团', 79.90, 2, 0, 48, 100, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + 30*86400, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(26, '老干妈辣椒酱 2人团', 9.90, 2, 0, 48, 500, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + 30*86400, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(5, '镇宁樱桃 5人团', 49.90, 5, 0, 24, 150, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + 30*86400, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- =============================================
-- 12. 购物车数据
-- =============================================
INSERT INTO `carts` (`user_id`, `product_id`, `sku_id`, `quantity`, `selected`, `created_at`, `updated_at`) VALUES
(1, 25, 27, 1, 1, UNIX_TIMESTAMP() - 86400, UNIX_TIMESTAMP()),
(1, 9, 5, 2, 1, UNIX_TIMESTAMP() - 86400, UNIX_TIMESTAMP()),
(1, 1, 2, 1, 1, UNIX_TIMESTAMP() - 86400, UNIX_TIMESTAMP()),
(2, 5, NULL, 2, 1, UNIX_TIMESTAMP() - 86400, UNIX_TIMESTAMP()),
(2, 19, NULL, 1, 0, UNIX_TIMESTAMP() - 86400, UNIX_TIMESTAMP()),
(3, 10, NULL, 1, 1, UNIX_TIMESTAMP() - 86400, UNIX_TIMESTAMP()),
(3, 26, 30, 1, 1, UNIX_TIMESTAMP() - 86400, UNIX_TIMESTAMP());

-- =============================================
-- 13. 站内消息
-- =============================================
INSERT INTO `notifications` (`user_id`, `title`, `content`, `type`, `is_read`, `created_at`) VALUES
(1, '欢迎来到田冲助农商城！', '亲爱的用户，欢迎加入田冲助农商城！在这里您可以购买到正宗的贵州特色农产品，每一份消费都是对乡村振兴的支持。', 'system', 0, UNIX_TIMESTAMP() - 30*86400),
(1, '您有新的订单已发货', '订单NX2026081500006已发货，请注意查收。', 'order', 0, UNIX_TIMESTAMP() - 4*86400),
(1, '拼团成功提醒', '您参加的遵义朝天椒拼团已成功，商家将尽快发货。', 'order', 1, UNIX_TIMESTAMP() - 2*86400),
(2, '欢迎来到田冲助农商城！', '亲爱的用户，欢迎加入田冲助农商城！', 'system', 0, UNIX_TIMESTAMP() - 25*86400),
(3, '欢迎来到田冲助农商城！', '亲爱的用户，欢迎加入田冲助农商城！', 'system', 0, UNIX_TIMESTAMP() - 20*86400),
(4, '欢迎来到田冲助农商城！', '亲爱的用户，欢迎加入田冲助农商城！', 'system', 0, UNIX_TIMESTAMP() - 15*86400),
(5, '欢迎来到田冲助农商城！', '亲爱的用户，欢迎加入田冲助农商城！', 'system', 0, UNIX_TIMESTAMP() - 10*86400),
(6, '欢迎来到田冲助农商城！', '亲爱的用户，欢迎加入田冲助农商城！', 'system', 0, UNIX_TIMESTAMP() - 5*86400);

-- =============================================
-- 14. 推广分享
-- =============================================
INSERT INTO `referrals` (`user_id`, `product_id`, `code`, `click_count`, `order_count`, `earnings`, `created_at`, `updated_at`) VALUES
(1, 1, 'DEMO2026', 156, 12, 25.80, UNIX_TIMESTAMP() - 30*86400, UNIX_TIMESTAMP()),
(1, 9, 'DEMO2026', 89, 6, 35.60, UNIX_TIMESTAMP() - 30*86400, UNIX_TIMESTAMP()),
(2, 5, 'ZHANG2026', 67, 4, 12.40, UNIX_TIMESTAMP() - 25*86400, UNIX_TIMESTAMP()),
(3, 26, 'LI2026', 45, 3, 8.50, UNIX_TIMESTAMP() - 20*86400, UNIX_TIMESTAMP()),
(4, 1, 'WANG2026', 23, 2, 5.80, UNIX_TIMESTAMP() - 15*86400, UNIX_TIMESTAMP());

-- =============================================
-- 15. 系统配置
-- =============================================
INSERT INTO `system_configs` (`key`, `value`, `remark`) VALUES
('site_name', '田冲助农商城', '站点名称'),
('site_slogan', '党建引领·强村富民·乡村振兴', '站点标语'),
('site_logo', '/assets/logo.png', '站点Logo'),
('site_icon', '/favicon.ico', '站点图标'),
('contact_phone', '400-888-8888', '客服电话'),
('contact_email', '<EMAIL>', '客服邮箱'),
('contact_address', '贵州省遵义市红花岗区田冲村强村富民工坊', '联系地址'),
('banner_ratio', '16:5', '轮播图比例'),
('order_auto_cancel', '30', '订单自动取消时间(分钟)'),
('order_auto_confirm', '7', '订单自动确认收货(天)'),
('group_buy_commission', '5', '拼团佣金比例(%)'),
('referral_commission', '10', '推广佣金比例(%)'),
('min_withdraw', '10', '最低提现金额'),
('recharge_gift', '5', '充值赠送比例(%)'),
('free_shipping', '99', '免邮门槛'),
('maintenance', '0', '维护模式');

-- =============================================
-- 16. 每日统计
-- =============================================
INSERT INTO `statistics_daily` (`date`, `order_count`, `order_amount`, `new_users`, `new_orders`, `paid_orders`, `paid_amount`, `refund_amount`, `created_at`) VALUES
(DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 30 DAY), '%Y%m%d'), 5, 356.80, 6, 5, 4, 298.00, 0.00, UNIX_TIMESTAMP() - 30*86400),
(DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 25 DAY), '%Y%m%d'), 3, 189.50, 1, 3, 3, 189.50, 0.00, UNIX_TIMESTAMP() - 25*86400),
(DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 20 DAY), '%Y%m%d'), 4, 245.60, 1, 4, 3, 198.00, 0.00, UNIX_TIMESTAMP() - 20*86400),
(DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 15 DAY), '%Y%m%d'), 6, 456.70, 1, 6, 5, 389.00, 0.00, UNIX_TIMESTAMP() - 15*86400),
(DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 10 DAY), '%Y%m%d'), 8, 567.80, 1, 8, 7, 498.00, 0.00, UNIX_TIMESTAMP() - 10*86400),
(DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 7 DAY), '%Y%m%d'), 7, 432.50, 0, 7, 6, 398.00, 0.00, UNIX_TIMESTAMP() - 7*86400),
(DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 DAY), '%Y%m%d'), 4, 298.60, 1, 4, 4, 298.60, 0.00, UNIX_TIMESTAMP() - 5*86400),
(DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 3 DAY), '%Y%m%d'), 6, 389.90, 0, 6, 5, 356.00, 0.00, UNIX_TIMESTAMP() - 3*86400),
(DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), '%Y%m%d'), 5, 345.80, 0, 5, 4, 298.00, 0.00, UNIX_TIMESTAMP() - 1*86400),
(DATE_FORMAT(CURDATE(), '%Y%m%d'), 3, 156.80, 0, 3, 2, 128.00, 0.00, UNIX_TIMESTAMP());

-- =============================================
-- 17. 商品统计
-- =============================================
INSERT INTO `statistics_products` (`product_id`, `date`, `view_count`, `sales_count`, `sales_amount`, `created_at`) VALUES
(1, DATE_FORMAT(CURDATE(), '%Y%m%d'), 1280, 256, 7654.40, UNIX_TIMESTAMP()),
(2, DATE_FORMAT(CURDATE(), '%Y%m%d'), 960, 189, 2986.20, UNIX_TIMESTAMP()),
(3, DATE_FORMAT(CURDATE(), '%Y%m%d'), 1560, 320, 3168.00, UNIX_TIMESTAMP()),
(5, DATE_FORMAT(CURDATE(), '%Y%m%d'), 2340, 89, 5162.00, UNIX_TIMESTAMP()),
(9, DATE_FORMAT(CURDATE(), '%Y%m%d'), 1450, 156, 13704.00, UNIX_TIMESTAMP()),
(10, DATE_FORMAT(CURDATE(), '%Y%m%d'), 760, 89, 6052.00, UNIX_TIMESTAMP()),
(11, DATE_FORMAT(CURDATE(), '%Y%m%d'), 2100, 432, 2937.60, UNIX_TIMESTAMP()),
(17, DATE_FORMAT(CURDATE(), '%Y%m%d'), 2890, 567, 1984.50, UNIX_TIMESTAMP()),
(19, DATE_FORMAT(CURDATE(), '%Y%m%d'), 1230, 245, 8575.00, UNIX_TIMESTAMP()),
(25, DATE_FORMAT(CURDATE(), '%Y%m%d'), 1560, 178, 53222.00, UNIX_TIMESTAMP()),
(26, DATE_FORMAT(CURDATE(), '%Y%m%d'), 4560, 876, 11212.80, UNIX_TIMESTAMP()),
(27, DATE_FORMAT(CURDATE(), '%Y%m%d'), 1120, 234, 6037.20, UNIX_TIMESTAMP());

-- =============================================
-- 18. 配送模板
-- =============================================
INSERT INTO `delivery_templates` (`name`, `type`, `first_weight_fee`, `continue_fee`, `free_threshold`, `areas`, `status`, `created_at`, `updated_at`) VALUES
('全国包邮', 1, 8.00, 3.00, 99.00, '["全国"]', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('贵州本省', 1, 6.00, 2.00, 59.00, '["贵州省"]', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('江浙沪', 1, 10.00, 4.00, 129.00, '["江苏省","浙江省","上海市"]', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('偏远地区', 1, 15.00, 8.00, 199.00, '["新疆维吾尔自治区","西藏自治区","青海省","内蒙古自治区"]', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- =============================================
-- 19. 管理员日志
-- =============================================
INSERT INTO `admin_logs` (`admin_id`, `admin_name`, `action`, `target`, `ip`, `created_at`) VALUES
(1, '系统管理员', '登录', '系统登录', '192.168.1.100', UNIX_TIMESTAMP() - 86400),
(1, '系统管理员', '商品上架', '商品ID: 1,2,3,4,5', '192.168.1.100', UNIX_TIMESTAMP() - 86400),
(1, '系统管理员', '轮播图设置', '首页轮播图', '192.168.1.100', UNIX_TIMESTAMP() - 86400),
(2, '张运营', '订单发货', '订单NX2026081500006', '192.168.1.101', UNIX_TIMESTAMP() - 4*86400),
(2, '张运营', '拼团活动', '创建拼团活动', '192.168.1.101', UNIX_TIMESTAMP() - 3*86400),
(3, '李客服', '订单处理', '退款申请处理', '192.168.1.102', UNIX_TIMESTAMP() - 2*86400);

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- 数据导入完成
-- =============================================
