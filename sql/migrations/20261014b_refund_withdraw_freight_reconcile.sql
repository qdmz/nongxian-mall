-- 田冲助农商城 第二轮迁移（2026-10-14b）：只新增，不删除任何表/列/数据
SET NAMES utf8mb4;

-- 1) 订单：运费、拼团意向（付款后才入团）
ALTER TABLE orders
  ADD COLUMN freight_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '运费（服务端按运费模板计算）' AFTER discount_amount,
  ADD COLUMN group_activity_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '拼团订单：活动ID（付款后入团）' AFTER type,
  ADD COLUMN group_target_id BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '拼团订单：意向参加的团ID，0=开新团' AFTER group_activity_id;

-- 2) 推荐返佣：退款扣回记录
ALTER TABLE referral_rewards
  ADD COLUMN clawback_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '退款扣回金额' AFTER status,
  ADD COLUMN clawback_at INT NOT NULL DEFAULT 0 COMMENT '扣回时间' AFTER clawback_amount;

-- 3) 退款打款记录（原路退回易支付 / 退回钱包）
CREATE TABLE IF NOT EXISTS refund_records (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  record_no VARCHAR(40) NOT NULL COMMENT '本地退款流水号',
  order_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  order_no VARCHAR(32) NOT NULL DEFAULT '',
  user_id INT UNSIGNED NOT NULL,
  payment_no VARCHAR(40) NULL COMMENT '对应支付单（易支付 out_trade_no）',
  trade_no VARCHAR(64) NULL COMMENT '易支付平台订单号',
  channel VARCHAR(20) NOT NULL COMMENT 'epay=原路退回 / wallet=退回钱包',
  amount DECIMAL(12,2) NOT NULL,
  status TINYINT NOT NULL DEFAULT 0 COMMENT '0=待处理 1=成功 2=原路失败已转钱包 3=失败待人工',
  attempts INT NOT NULL DEFAULT 0,
  reason VARCHAR(255) NULL,
  last_error VARCHAR(500) NULL,
  created_at INT NOT NULL DEFAULT 0,
  updated_at INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uk_record_no (record_no),
  UNIQUE KEY uk_payment (payment_no),
  KEY idx_order (order_id),
  KEY idx_status (status, updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='退款打款记录';

-- 4) 钱包提现
CREATE TABLE IF NOT EXISTS withdrawals (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  withdraw_no VARCHAR(40) NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  account_type VARCHAR(20) NOT NULL COMMENT 'alipay/wechat/bank',
  account_name VARCHAR(50) NOT NULL,
  account_no VARCHAR(64) NOT NULL,
  bank_name VARCHAR(100) NULL,
  request_id VARCHAR(64) NULL COMMENT '客户端幂等键',
  status TINYINT NOT NULL DEFAULT 0 COMMENT '0=待审核 1=已通过(已打款) 2=已驳回(已退回余额) 3=用户撤销',
  remark VARCHAR(255) NULL COMMENT '用户备注',
  reject_reason VARCHAR(255) NULL,
  transfer_no VARCHAR(100) NULL COMMENT '线下打款流水号',
  handled_by INT UNSIGNED NULL,
  handled_at INT NOT NULL DEFAULT 0,
  created_at INT NOT NULL DEFAULT 0,
  updated_at INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uk_withdraw_no (withdraw_no),
  UNIQUE KEY uk_user_request (user_id, request_id),
  KEY idx_user (user_id, created_at),
  KEY idx_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='钱包提现申请';

-- 5) 易支付对账差异（只记录，不改账）
CREATE TABLE IF NOT EXISTS pay_reconcile_diffs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  bill_date DATE NOT NULL,
  diff_type VARCHAR(40) NOT NULL COMMENT 'remote_paid_local_unpaid / local_paid_remote_missing / amount_mismatch / status_mismatch / remote_unknown',
  payment_no VARCHAR(40) NULL,
  trade_no VARCHAR(64) NULL,
  local_amount DECIMAL(12,2) NULL,
  remote_amount DECIMAL(12,2) NULL,
  local_status TINYINT NULL,
  remote_status VARCHAR(10) NULL,
  detail VARCHAR(500) NULL,
  created_at INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uk_day_type_pay (bill_date, diff_type, payment_no),
  KEY idx_date (bill_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='易支付对账差异';

-- 6) 新配置项（已存在则跳过）
INSERT INTO system_configs (`group`,`key`,`value`,`type`,`name`,`description`,`sort`,`created_at`,`updated_at`)
SELECT * FROM (
  SELECT 'pay' g,'epay_refund_enabled' k,'1' v,'switch' t,'易支付原路退款' n,'开启后退款优先调用易支付退款接口，失败自动退回钱包' d,90 s,UNIX_TIMESTAMP() c,UNIX_TIMESTAMP() u
  UNION ALL SELECT 'app','withdraw_enabled','1','switch','钱包提现','是否允许用户申请提现',80,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
  UNION ALL SELECT 'app','withdraw_min','10','number','单笔最低提现(元)','',81,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
  UNION ALL SELECT 'app','withdraw_max','5000','number','单笔最高提现(元)','',82,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
  UNION ALL SELECT 'app','withdraw_daily_count','3','number','每日最多提现次数','',83,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
) x
WHERE NOT EXISTS (SELECT 1 FROM system_configs sc WHERE sc.`group` = x.g AND sc.`key` = x.k);
