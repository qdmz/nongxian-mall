SELECT id, `group`, `key`, `value`, sort FROM system_configs WHERE `group` = 'pay' ORDER BY id;
SELECT '--- all duplicates ---' AS info;
SELECT `key`, COUNT(*) as cnt FROM system_configs WHERE `group` IN ('pay','smtp','sms','app') GROUP BY `group`,`key` HAVING cnt > 1;
