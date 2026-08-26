<?php
/**
 * 应用配置
 */
return [
    'name' => '田冲助农商城',
    'jwt_secret' => getenv('JWT_SECRET') ?: 'nongxian-mall-jwt-secret-2026-change-in-production',
    'debug' => false,
    'version' => '1.0.0',
    // 上传文件大小限制（字节）
    'upload_max_size' => 10 * 1024 * 1024,
    // 允许的图片扩展名
    'upload_image_ext' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
    // 图片访问基础URL（部署后改为正式域名）
    'upload_url_prefix' => '/uploads',
];
