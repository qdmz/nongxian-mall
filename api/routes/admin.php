<?php
/**
 * 管理后台 API 路由
 * 前缀：/admin
 */

/** @var Core\Router $router */

// ---------- 无需登录 ----------
$router->post('/admin/auth/login', [\App\Controllers\Admin\AuthController::class, 'login']);

// ---------- 需要登录 ----------
$auth = ['AdminAuth'];

// 认证相关
$router->get('/admin/auth/profile', [\App\Controllers\Admin\AuthController::class, 'profile'], $auth);
$router->put('/admin/auth/profile', [\App\Controllers\Admin\AuthController::class, 'updateProfile'], $auth);
$router->post('/admin/auth/change-password', [\App\Controllers\Admin\AuthController::class, 'changePassword'], $auth);
$router->post('/admin/auth/logout', [\App\Controllers\Admin\AuthController::class, 'logout'], $auth);

// 仪表盘
$router->get('/admin/dashboard', [\App\Controllers\Admin\DashboardController::class, 'index'], $auth);
$router->get('/admin/dashboard/sales-trend', [\App\Controllers\Admin\DashboardController::class, 'salesTrend'], $auth);
$router->get('/admin/dashboard/product-rank', [\App\Controllers\Admin\DashboardController::class, 'productRank'], $auth);
$router->get('/admin/dashboard/category-sales', [\App\Controllers\Admin\DashboardController::class, 'categorySales'], $auth);
$router->get('/admin/dashboard/latest-orders', [\App\Controllers\Admin\DashboardController::class, 'latestOrders'], $auth);
$router->get('/admin/dashboard/low-stock', [\App\Controllers\Admin\DashboardController::class, 'lowStock'], $auth);

// 用户管理
$router->get('/admin/users', [\App\Controllers\Admin\UserController::class, 'index'], $auth);
$router->get('/admin/users/{id}', [\App\Controllers\Admin\UserController::class, 'show'], $auth);
$router->put('/admin/users/{id}', [\App\Controllers\Admin\UserController::class, 'update'], $auth);
$router->post('/admin/users/{id}/adjust-balance', [\App\Controllers\Admin\UserController::class, 'adjustBalance'], $auth);
$router->get('/admin/users/{id}/orders', [\App\Controllers\Admin\UserController::class, 'orders'], $auth);
$router->post('/admin/users/send-notification', [\App\Controllers\Admin\UserController::class, 'sendNotification'], $auth);

// 商品分类
$router->get('/admin/categories', [\App\Controllers\Admin\CategoryController::class, 'index'], $auth);
$router->post('/admin/categories', [\App\Controllers\Admin\CategoryController::class, 'store'], $auth);
$router->put('/admin/categories/{id}', [\App\Controllers\Admin\CategoryController::class, 'update'], $auth);
$router->delete('/admin/categories/{id}', [\App\Controllers\Admin\CategoryController::class, 'destroy'], $auth);

// 商品管理
$router->get('/admin/products', [\App\Controllers\Admin\ProductController::class, 'index'], $auth);
$router->get('/admin/products/{id}', [\App\Controllers\Admin\ProductController::class, 'show'], $auth);
$router->post('/admin/products', [\App\Controllers\Admin\ProductController::class, 'store'], $auth);
$router->put('/admin/products/{id}', [\App\Controllers\Admin\ProductController::class, 'update'], $auth);
$router->post('/admin/products/{id}/toggle-status', [\App\Controllers\Admin\ProductController::class, 'toggleStatus'], $auth);
$router->post('/admin/products/{id}/update-stock', [\App\Controllers\Admin\ProductController::class, 'updateStock'], $auth);
$router->delete('/admin/products/{id}', [\App\Controllers\Admin\ProductController::class, 'destroy'], $auth);

// 订单管理
$router->get('/admin/orders', [\App\Controllers\Admin\OrderController::class, 'index'], $auth);
$router->get('/admin/orders/{id}', [\App\Controllers\Admin\OrderController::class, 'show'], $auth);
$router->post('/admin/orders/{id}/deliver', [\App\Controllers\Admin\OrderController::class, 'deliver'], $auth);
$router->post('/admin/orders/{id}/complete', [\App\Controllers\Admin\OrderController::class, 'complete'], $auth);
$router->post('/admin/orders/{id}/cancel', [\App\Controllers\Admin\OrderController::class, 'cancel'], $auth);

// 退款
$router->get('/admin/refunds', [\App\Controllers\Admin\OrderController::class, 'refunds'], $auth);
$router->post('/admin/refunds/{id}/handle', [\App\Controllers\Admin\OrderController::class, 'handleRefund'], $auth);

// 配送管理
$router->get('/admin/deliveries', [\App\Controllers\Admin\OrderController::class, 'deliveries'], $auth);
$router->post('/admin/deliveries/{id}/track', [\App\Controllers\Admin\OrderController::class, 'addTrack'], $auth);

// 拼团管理
$router->get('/admin/group-buy/activities', [\App\Controllers\Admin\GroupBuyController::class, 'activities'], $auth);
$router->post('/admin/group-buy/activities', [\App\Controllers\Admin\GroupBuyController::class, 'storeActivity'], $auth);
$router->put('/admin/group-buy/activities/{id}', [\App\Controllers\Admin\GroupBuyController::class, 'updateActivity'], $auth);
$router->delete('/admin/group-buy/activities/{id}', [\App\Controllers\Admin\GroupBuyController::class, 'destroyActivity'], $auth);
$router->get('/admin/group-buy/groups', [\App\Controllers\Admin\GroupBuyController::class, 'groups'], $auth);
$router->get('/admin/group-buy/groups/{id}', [\App\Controllers\Admin\GroupBuyController::class, 'groupDetail'], $auth);

// 轮播图
$router->get('/admin/banners', [\App\Controllers\Admin\BannerController::class, 'index'], $auth);
$router->post('/admin/banners', [\App\Controllers\Admin\BannerController::class, 'store'], $auth);
$router->put('/admin/banners/{id}', [\App\Controllers\Admin\BannerController::class, 'update'], $auth);
$router->delete('/admin/banners/{id}', [\App\Controllers\Admin\BannerController::class, 'destroy'], $auth);

// 系统配置
$router->get('/admin/config', [\App\Controllers\Admin\ConfigController::class, 'index'], $auth);
$router->post('/admin/config', [\App\Controllers\Admin\ConfigController::class, 'save'], $auth);
$router->post('/admin/config/test-smtp', [\App\Controllers\Admin\ConfigController::class, 'testSmtp'], $auth);
$router->post('/admin/config/test-sms', [\App\Controllers\Admin\ConfigController::class, 'testSms'], $auth);
$router->post('/admin/config/test-pay', [\App\Controllers\Admin\ConfigController::class, 'testPay'], $auth);
$router->get('/admin/config/payment-records', [\App\Controllers\Admin\ConfigController::class, 'paymentRecords'], $auth);
$router->get('/admin/config/logs', [\App\Controllers\Admin\ConfigController::class, 'logs'], $auth);

// 上传
$router->post('/admin/upload/image', [\App\Controllers\Admin\UploadController::class, 'image'], $auth);
