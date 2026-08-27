<?php
$hash = '$2y$10$WBPmZQJHBqjXKOHbCazS4O7jLhMR/ZLPSkxV6I9yFtTZoIvTBFz.S';
echo 'admin123 verify: ' . (password_verify('admin123', $hash) ? 'true' : 'false') . PHP_EOL;
echo 'admin verify: ' . (password_verify('admin', $hash) ? 'true' : 'false') . PHP_EOL;
echo 'Generated hash for admin123: ' . password_hash('admin123', PASSWORD_BCRYPT) . PHP_EOL;
