SELECT `group`, `key`, `value`, `type`, `name`, `sort` FROM system_configs WHERE `group` IN ('pay', 'smtp', 'sms', 'app') ORDER BY `group`, `sort`, `id`;
