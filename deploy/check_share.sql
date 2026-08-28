SELECT `group`, `key`, `value`, `type`, `sort` FROM system_configs WHERE `key` LIKE '%share%' OR `key` LIKE '%reward%' OR `key` LIKE '%recommend%' ORDER BY `group`, `sort`;
