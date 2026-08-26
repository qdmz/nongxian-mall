<?php
/**
 * 用户端 API 路由
 * 前缀：/api
 */

/** @var Core\Router $router */

// ---------- 无需登录 ----------
// 认证
$router->post('/api/auth/register', [\App\Controllers\Api\AuthController::class, 'register']);
$router->post('/api/auth/login', [\App\Controllers\Api\AuthController::class, 'login']);
$router->post('/api/auth/send-code', [\App\Controllers\Api\AuthController::class, 'sendCode']);
$router->get('/api/auth/check-token', [\App\Controllers\Api\AuthController::class, 'checkToken']);

// 首页/商品/分类（公开）
$router->get('/api/home', [\App\Controllers\Api\ProductController::class, 'home']);
$router->get('/api/products', [\App\Controllers\Api\ProductController::class, 'index']);
$router->get('/api/products/{id}', [\App\Controllers\Api\ProductController::class, 'show']);
$router->get('/api/categories', [\App\Controllers\Api\ProductController::class, 'categories']);
$router->get('/api/search/hot-keywords', [\App\Controllers\Api\ProductController::class, 'hotKeywords']);

// 拼团活动（公开浏览）
$router->get('/api/group-buy/activities', [\App\Controllers\Api\GroupBuyController::class, 'activities']);
$router->get('/api/group-buy/activities/{id}', [\App\Controllers\Api\GroupBuyController::class, 'activityDetail']);
$router->get('/api/group-buy/groups/{id}', [\App\Controllers\Api\GroupBuyController::class, 'groupDetail']);

// 支付回调（易支付异步通知，不能有登录校验）
$router->get('/api/pay/notify', [\App\Controllers\Api\OrderController::class, 'payNotify']);
$router->post('/api/pay/notify', [\App\Controllers\Api\OrderController::class, 'payNotify']);

// 分享点击追踪（公开）
$router->post('/api/share/track', [\App\Controllers\Api\ShareController::class, 'track']);

// ---------- 需要登录 ----------
$auth = ['UserAuth'];

// 用户中心
$router->get('/api/user/profile', [\App\Controllers\Api\UserController::class, 'profile'], $auth);
$router->put('/api/user/profile', [\App\Controllers\Api\UserController::class, 'updateProfile'], $auth);
$router->post('/api/user/change-password', [\App\Controllers\Api\UserController::class, 'changePassword'], $auth);
$router->get('/api/user/bind-info', [\App\Controllers\Api\UserController::class, 'bindInfo'], $auth);
$router->post('/api/user/bind-phone', [\App\Controllers\Api\UserController::class, 'bindPhone'], $auth);
$router->post('/api/user/bind-email', [\App\Controllers\Api\UserController::class, 'bindEmail'], $auth);

// 收货地址
$router->get('/api/user/addresses', [\App\Controllers\Api\UserController::class, 'addresses'], $auth);
$router->post('/api/user/addresses', [\App\Controllers\Api\UserController::class, 'storeAddress'], $auth);
$router->put('/api/user/addresses/{id}', [\App\Controllers\Api\UserController::class, 'updateAddress'], $auth);
$router->delete('/api/user/addresses/{id}', [\App\Controllers\Api\UserController::class, 'destroyAddress'], $auth);

// 钱包/充值
$router->get('/api/user/wallet', [\App\Controllers\Api\UserController::class, 'wallet'], $auth);
$router->post('/api/user/recharge', [\App\Controllers\Api\UserController::class, 'recharge'], $auth);
$router->get('/api/user/recharge-orders', [\App\Controllers\Api\UserController::class, 'rechargeOrders'], $auth);

// 站内消息
$router->get('/api/user/notifications', [\App\Controllers\Api\UserController::class, 'notifications'], $auth);
$router->post('/api/user/notifications/read', [\App\Controllers\Api\UserController::class, 'readNotifications'], $auth);

// 购物车
$router->get('/api/cart', [\App\Controllers\Api\CartController::class, 'index'], $auth);
$router->post('/api/cart', [\App\Controllers\Api\CartController::class, 'store'], $auth);
$router->put('/api/cart', [\App\Controllers\Api\CartController::class, 'update'], $auth);
$router->post('/api/cart/clear-invalid', [\App\Controllers\Api\CartController::class, 'clearInvalid'], $auth);
$router->delete('/api/cart/{id}', [\App\Controllers\Api\CartController::class, 'destroy'], $auth);

// 订单
$router->post('/api/orders', [\App\Controllers\Api\OrderController::class, 'store'], $auth);
$router->get('/api/orders', [\App\Controllers\Api\OrderController::class, 'index'], $auth);
$router->get('/api/orders/{id}', [\App\Controllers\Api\OrderController::class, 'show'], $auth);
$router->post('/api/orders/{id}/cancel', [\App\Controllers\Api\OrderController::class, 'cancel'], $auth);
$router->post('/api/orders/{id}/confirm', [\App\Controllers\Api\OrderController::class, 'confirm'], $auth);
$router->post('/api/orders/{id}/apply-refund', [\App\Controllers\Api\OrderController::class, 'applyRefund'], $auth);
$router->post('/api/orders/{id}/pay', [\App\Controllers\Api\OrderController::class, 'pay'], $auth);

// 拼团下单
$router->post('/api/group-buy/orders', [\App\Controllers\Api\GroupBuyController::class, 'createOrder'], $auth);
$router->get('/api/group-buy/my-groups', [\App\Controllers\Api\GroupBuyController::class, 'myGroups'], $auth);

// 推荐分享
$router->get('/api/share/code', [\App\Controllers\Api\ShareController::class, 'code'], $auth);
$router->get('/api/share/product/{id}', [\App\Controllers\Api\ShareController::class, 'productShare'], $auth);
$router->get('/api/share/rewards', [\App\Controllers\Api\ShareController::class, 'rewards'], $auth);
$router->get('/api/share/my-team', [\App\Controllers\Api\ShareController::class, 'myTeam'], $auth);

// 上传
$router->post('/api/upload/image', [\App\Controllers\Api\UploadController::class, 'image'], $auth);
