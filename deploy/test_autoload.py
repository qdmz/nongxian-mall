#!/usr/bin/env python3
"""测试PHP autoload"""
import sys
sys.path.insert(0, 'C:/Users/admin/WorkBuddy/2026-08-25-16-30-21/nongxian-mall/deploy')
from ssh_tool import connect

TEST_SCRIPT = """
<?php
require '/var/www/nongxian-mall/api/core/Helper.php';
require '/var/www/nongxian-mall/api/core/Config.php';
require '/var/www/nongxian-mall/api/core/Database.php';
require '/var/www/nongxian-mall/api/core/Router.php';
require '/var/www/nongxian-mall/api/core/Request.php';
require '/var/www/nongxian-mall/api/core/Response.php';
require '/var/www/nongxian-mall/api/core/Auth.php';
require '/var/www/nongxian-mall/api/core/Controller.php';
require '/var/www/nongxian-mall/api/core/Model.php';

// Test autoload
$class = 'App\\\\Controllers\\\\Api\\\\ProductController';
echo "Testing: $class\\n";

// Simulate autoload logic
$baseDir = '/var/www/nongxian-mall/api/';
$relative = str_replace('App\\\\', '', $class);
$file = $baseDir . str_replace('\\\\', '/', $relative) . '.php';
echo "Expected file: $file\\n";
echo "File exists: " . (file_exists($file) ? 'YES' : 'NO') . "\\n";

$fileLower = strtolower($file);
echo "Lower file: $fileLower\\n";
echo "Lower exists: " . (file_exists($fileLower) ? 'YES' : 'NO') . "\\n";

// Try to load
if (file_exists($fileLower)) {
    require $fileLower;
    echo "Class loaded from lower case path!\\n";
    echo "Class exists: " . (class_exists($class) ? 'YES' : 'NO') . "\\n";
}
"""

c = connect()
channel = c.get_transport().open_session()
channel.exec_command(f'php -r "{TEST_SCRIPT}"')
output = channel.recv(4096).decode()
print(output)
channel.close()
c.close()
