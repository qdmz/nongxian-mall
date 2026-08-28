SELECT `key`, `value` FROM system_configs 
WHERE `key` LIKE '%enabled%' OR `key` LIKE 'smtp%' OR `key` LIKE 'epay%' OR `key` LIKE 'sms%' OR `key` LIKE 'app_%' 
ORDER BY `key`;
