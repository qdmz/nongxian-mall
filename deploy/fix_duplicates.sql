-- 修复重复配置项

-- 1. 删除 pay.epay_pay_type 的重复行（保留id=4）
DELETE FROM system_configs WHERE `group`='pay' AND `key`='epay_pay_type' AND id != 4;

-- 2. 删除 smtp.smtp_from_name 的重复行（保留id=11）
DELETE FROM system_configs WHERE `group`='smtp' AND `key`='smtp_from_name' AND id != 11;

-- 3. 删除 sms.sms_template_code 的重复行（保留id=17）
DELETE FROM system_configs WHERE `group`='sms' AND `key`='sms_template_code' AND id != 17;

-- 4. 删除 app.order_auto_cancel_minutes 的重复行（保留id=24）
DELETE FROM system_configs WHERE `group`='app' AND `key`='order_auto_cancel_minutes' AND id != 24;

-- 5. 确保 pay.pay_enabled 存在
INSERT IGNORE INTO system_configs (`group`, `key`, `value`, `type`, `name`, `description`, `sort`)
VALUES ('pay', 'pay_enabled', '1', 'select', '启用在线支付', '是否启用在线支付功能', 0);

-- 6. 确保 smtp.smtp_enabled 存在
INSERT IGNORE INTO system_configs (`group`, `key`, `value`, `type`, `name`, `description`, `sort`)
VALUES ('smtp', 'smtp_enabled', '1', 'select', '启用邮件服务', '是否启用邮件发送功能', 9);

-- 7. 确保 sms.sms_enabled 存在
INSERT IGNORE INTO system_configs (`group`, `key`, `value`, `type`, `name`, `description`, `sort`)
VALUES ('sms', 'sms_enabled', '1', 'select', '启用短信服务', '是否启用短信发送功能', 19);

-- 8. 确保 share.share_reward_enabled 存在
INSERT IGNORE INTO system_configs (`group`, `key`, `value`, `type`, `name`, `description`, `sort`)
VALUES ('share', 'share_reward_enabled', '1', 'select', '启用推荐奖励', '是否开启推荐奖励功能', 36);

-- 9. 确保 app.order_auto_confirm_days 存在
INSERT IGNORE INTO system_configs (`group`, `key`, `value`, `type`, `name`, `description`, `sort`)
VALUES ('app', 'order_auto_confirm_days', '7', 'text', '自动确认收货（天）', '发货后几天自动确认收货', 36);
