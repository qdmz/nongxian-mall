-- 修复系统配置表：删除重复行，确保 *_enabled 字段存在

-- 1. 删除 smtp_from_name 的重复行（保留id=11的第一条）
DELETE FROM system_configs WHERE id > 11 AND `key` = 'smtp_from_name' AND `group` = 'smtp';

-- 2. 删除 epay_pay_type 的重复行（保留id=4的第一条）
DELETE FROM system_configs WHERE id > 4 AND `key` = 'epay_pay_type' AND `group` = 'pay';

-- 3. 删除 sms_template_code 的重复行（保留id=17的第一条）
DELETE FROM system_configs WHERE id > 17 AND `key` = 'sms_template_code' AND `group` = 'sms';

-- 4. 删除 order_auto_cancel_minutes 的重复行（保留id=24的第一条）
DELETE FROM system_configs WHERE id > 24 AND `key` = 'order_auto_cancel_minutes' AND `group` = 'app';

-- 5. 确保 smtp_enabled 存在
INSERT IGNORE INTO system_configs (`group`, `key`, `value`, `type`, `name`, `description`, `sort`)
VALUES ('smtp', 'smtp_enabled', '1', 'select', '启用邮件服务', '是否启用邮件发送功能', 9);

-- 6. 确保 sms_enabled 存在
INSERT IGNORE INTO system_configs (`group`, `key`, `value`, `type`, `name`, `description`, `sort`)
VALUES ('sms', 'sms_enabled', '1', 'select', '启用短信服务', '是否启用短信发送功能', 19);

-- 7. 确保 pay_enabled 存在（如果不存在）
INSERT IGNORE INTO system_configs (`group`, `key`, `value`, `type`, `name`, `description`, `sort`)
VALUES ('pay', 'pay_enabled', '1', 'select', '启用在线支付', '是否启用在线支付功能', 0);

-- 8. 确保 share_reward_enabled 存在（如果不存在）
INSERT IGNORE INTO system_configs (`group`, `key`, `value`, `type`, `name`, `description`, `sort`)
VALUES ('share', 'share_reward_enabled', '1', 'select', '启用推荐奖励', '是否开启推荐奖励功能', 36);

-- 9. 确保 order_auto_confirm_days 存在
INSERT IGNORE INTO system_configs (`group`, `key`, `value`, `type`, `name`, `description`, `sort`)
VALUES ('app', 'order_auto_confirm_days', '7', 'text', '自动确认收货（天）', '发货后几天自动确认收货', 36);
