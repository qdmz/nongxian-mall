SELECT `id`, `group`, `key`, LEFT(`value`, 50) as val, `name` FROM system_configs ORDER BY `group`, `key`;
