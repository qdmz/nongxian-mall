-- =====================================================================
-- 农产品在线商城系统 - 数据库结构
-- 项目：贵州亿田农业 · 田冲红色美丽乡村强村富民工坊
-- 版本：1.0
-- 编码：utf8mb4（支持 emoji）
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- 数据库创建
-- ---------------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `nongxian_mall` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `nongxian_mall`;

-- =====================================================================
-- 一、管理员与权限
-- =====================================================================

-- 管理员角色
DROP TABLE IF EXISTS `admin_roles`;
CREATE TABLE `admin_roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL COMMENT '角色名称',
  `slug` VARCHAR(50) NOT NULL COMMENT '角色标识',
  `permissions` TEXT COMMENT '权限JSON',
  `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '状态 1启用 0禁用',
  `remark` VARCHAR(255) DEFAULT NULL COMMENT '备注',
  `created_at` INT NOT NULL DEFAULT 0 COMMENT '创建时间戳',
  `updated_at` INT NOT NULL DEFAULT 0 COMMENT '更新时间戳',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB COMMENT='管理员角色';

-- 管理员用户
DROP TABLE IF EXISTS `admin_users`;
CREATE TABLE `admin_users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL COMMENT '登录名',
  `password` VARCHAR(255) NOT NULL COMMENT '密码hash',
  `real_name` VARCHAR(50) DEFAULT NULL COMMENT '真实姓名',
  `avatar` VARCHAR(255) DEFAULT NULL COMMENT '头像',
  `phone` VARCHAR(20) DEFAULT NULL COMMENT '手机号',
  `email` VARCHAR(100) DEFAULT NULL COMMENT '邮箱',
  `role_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '角色ID',
  `last_login_at` INT NOT NULL DEFAULT 0 COMMENT '最后登录时间',
  `last_login_ip` VARCHAR(45) DEFAULT NULL COMMENT '最后登录IP',
  `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '状态',
  `created_at` INT NOT NULL DEFAULT 0,
  `updated_at` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  KEY `idx_role` (`role_id`)
) ENGINE=InnoDB COMMENT='管理员用户';

-- 管理员操作日志
DROP TABLE IF EXISTS `admin_logs`;
CREATE TABLE `admin_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` INT UNSIGNED NOT NULL COMMENT '管理员ID',
  `module` VARCHAR(50) NOT NULL COMMENT '模块',
  `action` VARCHAR(50) NOT NULL COMMENT '操作',
  `description` VARCHAR(500) DEFAULT NULL COMMENT '描述',
  `ip` VARCHAR(45) DEFAULT NULL COMMENT 'IP',
  `data` TEXT COMMENT '请求数据快照',
  `created_at` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_admin` (`admin_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB COMMENT='管理员操作日志';

-- =====================================================================
-- 二、用户体系
-- =====================================================================

-- 用户表
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `phone` VARCHAR(20) DEFAULT NULL COMMENT '手机号',
  `email` VARCHAR(100) DEFAULT NULL COMMENT '邮箱',
  `username` VARCHAR(50) DEFAULT NULL COMMENT '用户名',
  `password` VARCHAR(255) DEFAULT NULL COMMENT '密码hash',
  `nickname` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '昵称',
  `avatar` VARCHAR(255) DEFAULT NULL COMMENT '头像',
  `real_name` VARCHAR(50) DEFAULT NULL COMMENT '真实姓名',
  `gender` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '性别 0未知 1男 2女',
  `birthday` DATE DEFAULT NULL COMMENT '生日',
  `wallet_balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '钱包余额',
  `frozen_balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '冻结金额',
  `total_recharge` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '累计充值',
  `points` INT NOT NULL DEFAULT 0 COMMENT '积分',
  `level` TINYINT NOT NULL DEFAULT 1 COMMENT '等级',
  `referral_code` VARCHAR(20) DEFAULT NULL COMMENT '推荐码',
  `referred_by` INT UNSIGNED DEFAULT NULL COMMENT '推荐人ID',
  `email_verified` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '邮箱已验证',
  `phone_verified` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '手机已验证',
  `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '状态 1正常 0禁用',
  `last_login_at` INT NOT NULL DEFAULT 0,
  `created_at` INT NOT NULL DEFAULT 0,
  `updated_at` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_phone` (`phone`),
  UNIQUE KEY `uk_email` (`email`),
  UNIQUE KEY `uk_referral_code` (`referral_code`),
  KEY `idx_referred_by` (`referred_by`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB COMMENT='用户表';

-- 用户收货地址
DROP TABLE IF EXISTS `user_addresses`;
CREATE TABLE `user_addresses` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `consignee` VARCHAR(50) NOT NULL COMMENT '收货人',
  `phone` VARCHAR(20) NOT NULL COMMENT '电话',
  `province` VARCHAR(50) NOT NULL COMMENT '省',
  `city` VARCHAR(50) NOT NULL COMMENT '市',
  `district` VARCHAR(50) NOT NULL COMMENT '区/县',
  `detail` VARCHAR(255) NOT NULL COMMENT '详细地址',
  `is_default` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '默认地址',
  `label` VARCHAR(20) DEFAULT NULL COMMENT '标签：家/公司/学校',
  `created_at` INT NOT NULL DEFAULT 0,
  `updated_at` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB COMMENT='用户收货地址';

-- 钱包流水
DROP TABLE IF EXISTS `wallet_transactions`;
CREATE TABLE `wallet_transactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `type` VARCHAR(20) NOT NULL COMMENT '类型 recharge充值 consume消费 refund退款 reward奖励 adjust调整',
  `amount` DECIMAL(12,2) NOT NULL COMMENT '金额（正收入负支出）',
  `balance_before` DECIMAL(12,2) NOT NULL,
  `balance_after` DECIMAL(12,2) NOT NULL,
  `related_type` VARCHAR(20) DEFAULT NULL COMMENT '关联类型 order/payment/referral',
  `related_id` VARCHAR(50) DEFAULT NULL COMMENT '关联ID',
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB COMMENT='钱包流水';

-- 充值订单
DROP TABLE IF EXISTS `recharge_orders`;
CREATE TABLE `recharge_orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `recharge_no` VARCHAR(32) NOT NULL COMMENT '充值单号',
  `user_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL COMMENT '充值金额',
  `give_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '赠送金额',
  `pay_method` VARCHAR(20) DEFAULT NULL COMMENT '支付方式',
  `pay_no` VARCHAR(64) DEFAULT NULL COMMENT '第三方支付单号',
  `status` TINYINT NOT NULL DEFAULT 0 COMMENT '0待支付 1已支付 2已取消',
  `paid_at` INT NOT NULL DEFAULT 0,
  `created_at` INT NOT NULL DEFAULT 0,
  `updated_at` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_recharge_no` (`recharge_no`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB COMMENT='充值订单';

-- =====================================================================
-- 三、商品体系
-- =====================================================================

-- 商品分类
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '父分类ID，0为顶级',
  `name` VARCHAR(50) NOT NULL COMMENT '分类名',
  `icon` VARCHAR(255) DEFAULT NULL COMMENT '图标',
  `image` VARCHAR(255) DEFAULT NULL COMMENT '图片',
  `sort` INT NOT NULL DEFAULT 0 COMMENT '排序（越大越靠前）',
  `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '状态',
  `is_red` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '红色助农专区',
  `created_at` INT NOT NULL DEFAULT 0,
  `updated_at` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB COMMENT='商品分类';

-- 商品表
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(120) NOT NULL COMMENT '商品名',
  `subtitle` VARCHAR(200) DEFAULT NULL COMMENT '副标题',
  `description` TEXT COMMENT '富文本详情',
  `cover_image` VARCHAR(255) DEFAULT NULL COMMENT '封面图',
  `images` TEXT COMMENT '轮播图JSON',
  `unit` VARCHAR(20) NOT NULL DEFAULT '件' COMMENT '单位：斤/盒/箱/件',
  `price` DECIMAL(12,2) NOT NULL COMMENT '售价',
  `original_price` DECIMAL(12,2) DEFAULT NULL COMMENT '原价',
  `cost_price` DECIMAL(12,2) DEFAULT NULL COMMENT '成本价',
  `stock` INT NOT NULL DEFAULT 0 COMMENT '库存',
  `stock_warn` INT NOT NULL DEFAULT 10 COMMENT '库存预警值',
  `sales` INT NOT NULL DEFAULT 0 COMMENT '销量（累计）',
  `virtual_sales` INT NOT NULL DEFAULT 0 COMMENT '虚拟销量',
  `view_count` INT NOT NULL DEFAULT 0 COMMENT '浏览量',
  `status` TINYINT NOT NULL DEFAULT 0 COMMENT '0下架 1上架 2草稿',
  `sort` INT NOT NULL DEFAULT 0 COMMENT '排序',
  `is_hot` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '热销',
  `is_new` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '新品',
  `is_recommend` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '党员推荐',
  `is_red` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '红色助农专区',
  `is_group_buy` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '支持拼团',
  `origin` VARCHAR(100) DEFAULT NULL COMMENT '产地',
  `farmer` VARCHAR(100) DEFAULT NULL COMMENT '农户/合作社名',
  `created_at` INT NOT NULL DEFAULT 0,
  `updated_at` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category_id`),
  KEY `idx_status_sort` (`status`, `sort`),
  KEY `idx_hot` (`is_hot`),
  KEY `idx_recommend` (`is_recommend`),
  KEY `idx_red` (`is_red`),
  KEY `idx_sales` (`sales`)
) ENGINE=InnoDB COMMENT='商品表';

-- 商品规格 SKU
DROP TABLE IF EXISTS `product_skus`;
CREATE TABLE `product_skus` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `specs` VARCHAR(255) NOT NULL COMMENT '规格组合JSON 如 {"规格":"5斤装"}',
  `price` DECIMAL(12,2) NOT NULL,
  `stock` INT NOT NULL DEFAULT 0,
  `sales` INT NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` INT NOT NULL DEFAULT 0,
  `updated_at` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_product` (`product_id`)
) ENGINE=InnoDB COMMENT='商品SKU';

-- 购物车
DROP TABLE IF EXISTS `carts`;
CREATE TABLE `carts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `sku_id` INT UNSIGNED DEFAULT NULL COMMENT 'SKU ID，无规格商品为NULL',
  `quantity` INT NOT NULL DEFAULT 1,
  `selected` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否勾选',
  `created_at` INT NOT NULL DEFAULT 0,
  `updated_at` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  UNIQUE KEY `uk_user_product_sku` (`user_id`, `product_id`, `sku_id`)
) ENGINE=InnoDB COMMENT='购物车';

-- 轮播图/广告位
DROP TABLE IF EXISTS `banners`;
CREATE TABLE `banners` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(100) DEFAULT NULL,
  `image` VARCHAR(255) NOT NULL,
  `link_type` TINYINT NOT NULL DEFAULT 0 COMMENT '0无跳转 1商品 2分类 3外部链接 4拼团',
  `link_value` VARCHAR(255) DEFAULT NULL,
  `position` VARCHAR(20) NOT NULL DEFAULT 'home' COMMENT '位置 home首页 category分类页',
  `sort` INT NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `start_time` INT NOT NULL DEFAULT 0,
  `end_time` INT NOT NULL DEFAULT 0,
  `created_at` INT NOT NULL DEFAULT 0,
  `updated_at` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_position_status` (`position`, `status`)
) ENGINE=InnoDB COMMENT='轮播图';

-- =====================================================================
-- 四、订单体系
-- =====================================================================

-- 订单表
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_no` VARCHAR(32) NOT NULL COMMENT '订单号',
  `user_id` INT UNSIGNED NOT NULL,
  `type` TINYINT NOT NULL DEFAULT 1 COMMENT '1普通订单 2拼团订单',
  `status` TINYINT NOT NULL DEFAULT 0 COMMENT '0待支付 1已支付/待发货 2已发货 3已收货/完成 4已取消 5已关闭 6退款中 7已退款',
  `total_amount` DECIMAL(12,2) NOT NULL COMMENT '商品总额',
  `pay_amount` DECIMAL(12,2) NOT NULL COMMENT '实付金额',
  `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '优惠金额',
  `use_balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '钱包抵扣',
  `product_count` INT NOT NULL DEFAULT 0 COMMENT '商品总件数',
  `consignee` VARCHAR(50) DEFAULT NULL COMMENT '收货人快照',
  `phone` VARCHAR(20) DEFAULT NULL,
  `address` VARCHAR(500) DEFAULT NULL COMMENT '完整地址快照',
  `remark` VARCHAR(255) DEFAULT NULL COMMENT '买家备注',
  `pay_method` VARCHAR(20) DEFAULT NULL COMMENT 'alipay/wechat/balance',
  `pay_no` VARCHAR(64) DEFAULT NULL COMMENT '第三方支付流水号',
  `paid_at` INT NOT NULL DEFAULT 0,
  `delivered_at` INT NOT NULL DEFAULT 0,
  `completed_at` INT NOT NULL DEFAULT 0,
  `cancelled_at` INT NOT NULL DEFAULT 0,
  `cancel_reason` VARCHAR(255) DEFAULT NULL,
  `referral_rewarded` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '推荐奖励已发放',
  `created_at` INT NOT NULL DEFAULT 0,
  `updated_at` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_no` (`order_no`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB COMMENT='订单表';

-- 订单商品明细
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `sku_id` INT UNSIGNED DEFAULT NULL,
  `name` VARCHAR(120) NOT NULL COMMENT '商品名快照',
  `image` VARCHAR(255) DEFAULT NULL,
  `specs` VARCHAR(255) DEFAULT NULL COMMENT '规格快照',
  `price` DECIMAL(12,2) NOT NULL COMMENT '成交单价',
  `quantity` INT NOT NULL DEFAULT 1,
  `subtotal` DECIMAL(12,2) NOT NULL,
  `created_at` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_product` (`product_id`)
) ENGINE=InnoDB COMMENT='订单商品';

-- 订单状态日志
DROP TABLE IF EXISTS `order_logs`;
CREATE TABLE `order_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `status` TINYINT NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `operator_type` TINYINT NOT NULL DEFAULT 0 COMMENT '0系统 1用户 2管理员',
  `operator_id` INT UNSIGNED DEFAULT NULL,
  `created_at` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`)
) ENGINE=InnoDB COMMENT='订单日志';

-- 退款记录
DROP TABLE IF EXISTS `refunds`;
CREATE TABLE `refunds` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `refund_no` VARCHAR(32) NOT NULL,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `order_no` VARCHAR(32) NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `reason` VARCHAR(255) DEFAULT NULL,
  `status` TINYINT NOT NULL DEFAULT 0 COMMENT '0申请中 1同意 2拒绝 3已退款',
  `reject_reason` VARCHAR(255) DEFAULT NULL,
  `handled_by` INT UNSIGNED DEFAULT NULL COMMENT '处理管理员',
  `created_at` INT NOT NULL DEFAULT 0,
  `updated_at` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_refund_no` (`refund_no`),
  KEY `idx_order` (`order_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB COMMENT='退款记录';

-- =====================================================================
-- 五、拼团体系
-- =====================================================================

-- 拼团活动（按商品维度配置）
DROP TABLE IF EXISTS `group_buy_activities`;
CREATE TABLE `group_buy_activities` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(120) DEFAULT NULL,
  `group_price` DECIMAL(12,2) NOT NULL COMMENT '拼团价',
  `required_count` INT NOT NULL DEFAULT 2 COMMENT '成团人数',
  `max_count` INT NOT NULL DEFAULT 0 COMMENT '限购数量，0不限',
  `valid_hours` INT NOT NULL DEFAULT 24 COMMENT '成团时限（小时）',
  `stock` INT NOT NULL DEFAULT 0 COMMENT '拼团库存',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '0停用 1启用',
  `start_time` INT NOT NULL DEFAULT 0,
  `end_time` INT NOT NULL DEFAULT 0,
  `created_at` INT NOT NULL DEFAULT 0,
  `updated_at` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB COMMENT='拼团活动';

-- 拼团单（一次开团）
DROP TABLE IF EXISTS `group_buys`;
CREATE TABLE `group_buys` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `group_no` VARCHAR(32) NOT NULL COMMENT '团号',
  `activity_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `leader_user_id` INT UNSIGNED NOT NULL COMMENT '团长',
  `required_count` INT NOT NULL DEFAULT 2,
  `joined_count` INT NOT NULL DEFAULT 1 COMMENT '已加入人数',
  `group_price` DECIMAL(12,2) NOT NULL,
  `status` TINYINT NOT NULL DEFAULT 0 COMMENT '0拼团中 1已成团 2拼团失败 3已取消',
  `expire_at` INT NOT NULL DEFAULT 0 COMMENT '过期时间',
  `success_at` INT NOT NULL DEFAULT 0 COMMENT '成团时间',
  `created_at` INT NOT NULL DEFAULT 0,
  `updated_at` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_group_no` (`group_no`),
  KEY `idx_activity` (`activity_id`),
  KEY `idx_leader` (`leader_user_id`),
  KEY `idx_status_expire` (`status`, `expire_at`)
) ENGINE=InnoDB COMMENT='拼团单';

-- 拼团成员
DROP TABLE IF EXISTS `group_buy_members`;
CREATE TABLE `group_buy_members` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `group_buy_id` BIGINT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `order_id` BIGINT NOT NULL DEFAULT 0,
  `order_no` VARCHAR(32) DEFAULT NULL,
  `is_leader` TINYINT(1) NOT NULL DEFAULT 0,
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '1已加入 0已退出',
  `avatar` VARCHAR(255) DEFAULT NULL COMMENT '用户头像快照',
  `nickname` VARCHAR(50) DEFAULT NULL,
  `joined_at` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_group_buy` (`group_buy_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB COMMENT='拼团成员';

-- =====================================================================
-- 六、支付体系（易支付）
-- =====================================================================

-- 支付记录
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_no` VARCHAR(40) NOT NULL COMMENT '支付流水号',
  `biz_type` VARCHAR(20) NOT NULL DEFAULT 'order' COMMENT 'order商品订单 recharge充值',
  `biz_no` VARCHAR(32) NOT NULL COMMENT '业务单号（订单号/充值号）',
  `user_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `method` VARCHAR(20) NOT NULL COMMENT 'alipay/wechat/qqpay',
  `channel` VARCHAR(20) NOT NULL DEFAULT 'epay' COMMENT '渠道 epay易支付',
  `status` TINYINT NOT NULL DEFAULT 0 COMMENT '0待支付 1成功 2失败',
  `trade_no` VARCHAR(64) DEFAULT NULL COMMENT '第三方交易号',
  `callback_data` TEXT COMMENT '回调原始数据',
  `paid_at` INT NOT NULL DEFAULT 0,
  `created_at` INT NOT NULL DEFAULT 0,
  `updated_at` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_payment_no` (`payment_no`),
  KEY `idx_biz` (`biz_type`, `biz_no`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB COMMENT='支付记录';

-- =====================================================================
-- 七、配送体系
-- =====================================================================

-- 配送单
DROP TABLE IF EXISTS `deliveries`;
CREATE TABLE `deliveries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `delivery_no` VARCHAR(40) NOT NULL COMMENT '配送单号',
  `order_id` BIGINT UNSIGNED NOT NULL,
  `order_no` VARCHAR(32) NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `company` VARCHAR(50) DEFAULT NULL COMMENT '快递公司',
  `tracking_no` VARCHAR(64) DEFAULT NULL COMMENT '快递单号',
  `courier_name` VARCHAR(50) DEFAULT NULL COMMENT '配送员姓名（自配送）',
  `courier_phone` VARCHAR(20) DEFAULT NULL COMMENT '配送员电话',
  `status` TINYINT NOT NULL DEFAULT 0 COMMENT '0待配送 1配送中 2已送达 3已签收',
  `sender_name` VARCHAR(50) DEFAULT NULL,
  `sender_phone` VARCHAR(20) DEFAULT NULL,
  `sender_address` VARCHAR(255) DEFAULT NULL,
  `receiver_name` VARCHAR(50) DEFAULT NULL,
  `receiver_phone` VARCHAR(20) DEFAULT NULL,
  `receiver_address` VARCHAR(500) DEFAULT NULL,
  `remark` VARCHAR(255) DEFAULT NULL,
  `delivered_at` INT NOT NULL DEFAULT 0,
  `received_at` INT NOT NULL DEFAULT 0,
  `created_at` INT NOT NULL DEFAULT 0,
  `updated_at` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_delivery_no` (`delivery_no`),
  KEY `idx_order` (`order_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB COMMENT='配送单';

-- 配送轨迹
DROP TABLE IF EXISTS `delivery_tracks`;
CREATE TABLE `delivery_tracks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `delivery_id` BIGINT UNSIGNED NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `location` VARCHAR(100) DEFAULT NULL,
  `operator` VARCHAR(50) DEFAULT NULL,
  `created_at` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_delivery` (`delivery_id`)
) ENGINE=InnoDB COMMENT='配送轨迹';

-- 配送范围/运费模板
DROP TABLE IF EXISTS `delivery_templates`;
CREATE TABLE `delivery_templates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `type` TINYINT NOT NULL DEFAULT 1 COMMENT '1全国包邮 2按区域计费 3固定运费',
  `first_weight_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '首件运费',
  `continue_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '续件运费',
  `free_threshold` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '满额包邮，0不包邮',
  `areas` TEXT COMMENT '区域规则JSON',
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` INT NOT NULL DEFAULT 0,
  `updated_at` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB COMMENT='运费模板';

-- =====================================================================
-- 八、推荐分享体系
-- =====================================================================

-- 推荐记录（用户维度）
DROP TABLE IF EXISTS `referrals`;
CREATE TABLE `referrals` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL COMMENT '分享者',
  `product_id` INT UNSIGNED DEFAULT NULL COMMENT '分享的商品，NULL为通用推广码',
  `code` VARCHAR(20) NOT NULL COMMENT '分享码',
  `click_count` INT NOT NULL DEFAULT 0 COMMENT '点击数',
  `order_count` INT NOT NULL DEFAULT 0 COMMENT '带来的订单数',
  `earnings` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '累计收益',
  `created_at` INT NOT NULL DEFAULT 0,
  `updated_at` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB COMMENT='推荐分享码';

-- 推荐奖励
DROP TABLE IF EXISTS `referral_rewards`;
CREATE TABLE `referral_rewards` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `referral_id` INT UNSIGNED NOT NULL,
  `from_user_id` INT UNSIGNED NOT NULL COMMENT '下单人',
  `to_user_id` INT UNSIGNED NOT NULL COMMENT '受益人（分享者）',
  `order_id` BIGINT UNSIGNED NOT NULL,
  `order_no` VARCHAR(32) NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL COMMENT '奖励金额',
  `rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT '奖励比例%',
  `status` TINYINT NOT NULL DEFAULT 0 COMMENT '0待发放 1已发放 2已取消',
  `created_at` INT NOT NULL DEFAULT 0,
  `updated_at` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_to_user` (`to_user_id`),
  KEY `idx_order` (`order_id`)
) ENGINE=InnoDB COMMENT='推荐奖励';

-- =====================================================================
-- 九、通知体系（短信/邮件）
-- =====================================================================

-- 短信发送记录
DROP TABLE IF EXISTS `sms_logs`;
CREATE TABLE `sms_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `phone` VARCHAR(20) NOT NULL,
  `template` VARCHAR(50) DEFAULT NULL COMMENT '模板标识',
  `content` VARCHAR(500) NOT NULL COMMENT '内容',
  `scene` VARCHAR(30) DEFAULT NULL COMMENT '场景：register/login/verify/notify',
  `code` VARCHAR(10) DEFAULT NULL COMMENT '验证码',
  `provider` VARCHAR(30) DEFAULT NULL COMMENT '服务商 aliyun/tencent',
  `status` TINYINT NOT NULL DEFAULT 0 COMMENT '0待发送 1成功 2失败',
  `error` VARCHAR(500) DEFAULT NULL,
  `response` TEXT,
  `created_at` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_phone` (`phone`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB COMMENT='短信记录';

-- 邮件发送记录
DROP TABLE IF EXISTS `email_logs`;
CREATE TABLE `email_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(100) NOT NULL,
  `subject` VARCHAR(200) NOT NULL,
  `content` TEXT,
  `scene` VARCHAR(30) DEFAULT NULL,
  `status` TINYINT NOT NULL DEFAULT 0 COMMENT '0待发送 1成功 2失败',
  `error` VARCHAR(500) DEFAULT NULL,
  `created_at` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB COMMENT='邮件记录';

-- 验证码
DROP TABLE IF EXISTS `verify_codes`;
CREATE TABLE `verify_codes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `target` VARCHAR(100) NOT NULL COMMENT '手机号或邮箱',
  `type` VARCHAR(10) NOT NULL COMMENT 'sms/email',
  `scene` VARCHAR(30) NOT NULL COMMENT '场景',
  `code` VARCHAR(10) NOT NULL,
  `used` TINYINT(1) NOT NULL DEFAULT 0,
  `expire_at` INT NOT NULL DEFAULT 0,
  `ip` VARCHAR(45) DEFAULT NULL,
  `created_at` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_target` (`target`, `scene`),
  KEY `idx_expire` (`expire_at`)
) ENGINE=InnoDB COMMENT='验证码';

-- 站内消息
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL COMMENT '接收用户，0为广播',
  `title` VARCHAR(100) NOT NULL,
  `content` VARCHAR(1000) DEFAULT NULL,
  `type` VARCHAR(20) NOT NULL DEFAULT 'system' COMMENT 'system/order/promotion',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_user_read` (`user_id`, `is_read`)
) ENGINE=InnoDB COMMENT='站内消息';

-- =====================================================================
-- 十、系统配置与统计
-- =====================================================================

-- 系统配置（支付/SMTP/短信等凭据在后台配置）
DROP TABLE IF EXISTS `system_configs`;
CREATE TABLE `system_configs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `group` VARCHAR(30) NOT NULL COMMENT '分组 pay/smtp/sms/app/share',
  `key` VARCHAR(100) NOT NULL COMMENT '配置键',
  `value` TEXT COMMENT '配置值',
  `type` VARCHAR(20) NOT NULL DEFAULT 'text' COMMENT 'text/password/select/textarea',
  `name` VARCHAR(100) NOT NULL COMMENT '配置名称',
  `description` VARCHAR(255) DEFAULT NULL,
  `sort` INT NOT NULL DEFAULT 0,
  `created_at` INT NOT NULL DEFAULT 0,
  `updated_at` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_group_key` (`group`, `key`)
) ENGINE=InnoDB COMMENT='系统配置';

-- 每日统计
DROP TABLE IF EXISTS `statistics_daily`;
CREATE TABLE `statistics_daily` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `date` DATE NOT NULL,
  `new_users` INT NOT NULL DEFAULT 0,
  `total_users` INT NOT NULL DEFAULT 0,
  `order_count` INT NOT NULL DEFAULT 0 COMMENT '订单数',
  `order_amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '订单金额',
  `pay_count` INT NOT NULL DEFAULT 0 COMMENT '支付订单数',
  `pay_amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '支付金额',
  `recharge_amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '充值金额',
  `group_buy_count` INT NOT NULL DEFAULT 0 COMMENT '成团数',
  `created_at` INT NOT NULL DEFAULT 0,
  `updated_at` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_date` (`date`)
) ENGINE=InnoDB COMMENT='每日统计';

-- 商品销量统计
DROP TABLE IF EXISTS `statistics_products`;
CREATE TABLE `statistics_products` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `date` DATE NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `view_count` INT NOT NULL DEFAULT 0,
  `order_count` INT NOT NULL DEFAULT 0,
  `sales_count` INT NOT NULL DEFAULT 0 COMMENT '售出件数',
  `sales_amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `created_at` INT NOT NULL DEFAULT 0,
  `updated_at` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_date_product` (`date`, `product_id`),
  KEY `idx_product` (`product_id`)
) ENGINE=InnoDB COMMENT='商品销量统计';

-- =====================================================================
-- 初始数据
-- =====================================================================

-- 默认管理员（账号 admin / 密码 admin123456，登录后请立即修改）
INSERT INTO `admin_roles` (`id`, `name`, `slug`, `permissions`, `status`, `remark`, `created_at`, `updated_at`) VALUES
(1, '超级管理员', 'super', '*', 1, '拥有全部权限', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(2, '运营', 'operator', '["dashboard","products","orders","group_buy","statistics"]', 1, '日常运营', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 密码为 admin123456 的 password_hash
INSERT INTO `admin_users` (`id`, `username`, `password`, `real_name`, `role_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$WBPmZQJHBqjXKOHbCazS4O7jLhMR/ZLPSkxV6I9yFtTZoIvTBFz.S', '系统管理员', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 商品分类初始数据（农产品分类）
INSERT INTO `categories` (`id`, `parent_id`, `name`, `sort`, `status`, `is_red`, `created_at`, `updated_at`) VALUES
(1, 0, '新鲜蔬菜', 100, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(2, 0, '时令水果', 90, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(3, 0, '粮油米面', 80, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(4, 0, '禽蛋肉品', 70, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(5, 0, '山珍干货', 60, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(6, 0, '特色特产', 50, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(7, 0, '红色助农专区', 200, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 系统配置初始数据（部署后在后台填入真实凭据）
INSERT INTO `system_configs` (`group`, `key`, `value`, `type`, `name`, `description`, `sort`, `created_at`, `updated_at`) VALUES
-- 易支付
('pay', 'epay_api_url', 'https://pay.example.com', 'text', '易支付网关地址', '易支付接口地址，如 https://pay.xxx.com', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('pay', 'epay_pid', '', 'text', '易支付商户ID', '商户PID', 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('pay', 'epay_key', '', 'password', '易支付商户密钥', '商户KEY', 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('pay', 'epay_pay_type', 'wxpay', 'select', '默认支付方式', '可选 alipay/wxpay/qqpay', 4, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('pay', 'pay_enabled', '0', 'select', '启用在线支付', '部署并配置好商户信息后开启', 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
-- SMTP 邮件
('smtp', 'smtp_host', '', 'text', 'SMTP服务器', '如 smtp.qq.com', 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('smtp', 'smtp_port', '465', 'text', 'SMTP端口', '一般 25/465/587', 11, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('smtp', 'smtp_ssl', '1', 'select', '启用SSL', '465端口用SSL', 12, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('smtp', 'smtp_username', '', 'text', '发件邮箱账号', '发件邮箱地址', 13, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('smtp', 'smtp_password', '', 'password', '发件邮箱密码/授权码', 'QQ/网易邮箱需使用授权码', 14, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('smtp', 'smtp_from_name', '田冲助农商城', 'text', '发件人名称', '邮件中显示的发件人', 15, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('smtp', 'smtp_enabled', '0', 'select', '启用邮件发送', '配置好后开启', 16, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
-- 短信
('sms', 'sms_provider', 'aliyun', 'select', '短信服务商', 'aliyun/tencent', 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('sms', 'sms_access_key', '', 'text', 'AccessKey ID', '阿里云/腾讯云 AccessKey', 21, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('sms', 'sms_secret_key', '', 'password', 'AccessKey Secret', '', 22, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('sms', 'sms_sign', '', 'text', '短信签名', '需在服务商处报备', 23, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('sms', 'sms_template_code', '', 'text', '模板CODE', '验证码模板', 24, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('sms', 'sms_enabled', '0', 'select', '启用短信发送', '配置好后开启', 25, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
-- 应用基础
('app', 'app_name', '田冲助农商城', 'text', '应用名称', '站点名称', 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('app', 'app_logo', '', 'text', '站点LOGO', '', 31, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('app', 'app_description', '贵州亿田农业·田冲红色美丽乡村强村富民工坊助农电商平台', 'textarea', '站点描述', '', 32, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('app', 'app_kefu_phone', '', 'text', '客服电话', '', 33, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('app', 'app_kefu_wechat', '', 'text', '客服微信', '', 34, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('app', 'order_auto_cancel_minutes', '30', 'text', '未支付自动取消（分钟）', '', 35, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('app', 'order_auto_confirm_days', '7', 'text', '发货后自动确认收货（天）', '', 36, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
-- 分销推荐
('share', 'share_reward_enabled', '1', 'select', '启用推荐奖励', '推荐下单返奖励到钱包', 40, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('share', 'share_reward_rate', '5', 'text', '推荐奖励比例（%）', '按订单实付金额计算', 41, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('share', 'share_reward_max', '100', 'text', '单笔奖励上限（元）', '0不限', 42, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 默认运费模板（全国包邮）
INSERT INTO `delivery_templates` (`id`, `name`, `type`, `first_weight_fee`, `continue_fee`, `free_threshold`, `status`, `created_at`, `updated_at`) VALUES
(1, '全国包邮', 1, 0.00, 0.00, 0.00, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

SET FOREIGN_KEY_CHECKS = 1;
