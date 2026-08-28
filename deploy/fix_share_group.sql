UPDATE system_configs SET `group` = 'app' WHERE `group` = 'share' AND `key` IN ('share_reward_enabled', 'share_reward_rate', 'share_reward_max');
SELECT `group`, `key`, `value`, `type`, `sort` FROM system_configs WHERE `key` LIKE '%share%' OR `key` LIKE '%reward%' ORDER BY `group`, `sort`;
