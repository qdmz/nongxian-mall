<?php
header('Content-Type: application/json');
require __DIR__ . '/../api/vendor/autoload.php';

// Simulate admin token
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer test';

// Run the controller
$controller = new \App\Controllers\Admin\ConfigController();
$controller->index();
