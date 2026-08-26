# 农产品在线商城系统 - 田冲助农商城

> 贵州亿田农业发展有限公司 · 田冲红色美丽乡村强村富民工坊
> 党建引领 · 产业赋能 · 乡村振兴 · 助农电商

一套完整的农产品在线商城系统，支持在线订购、订单管理、钱包充值、发起拼团、推荐分享、配送管理、销量统计，对接易支付/SMTP邮件/短信接口。

## 技术栈

| 层 | 技术 |
|---|---|
| 后端 | PHP 8.0+（轻量自研 MVC，无需 Composer） |
| 数据库 | MySQL 8.0 |
| 管理后台 | Vue3 + Element Plus + Vite + ECharts |
| H5 用户端 | Vue3 + Vant + Vite |
| Web 服务器 | Nginx + PHP-FPM（生产）/ PHP 内置服务器（本地） |

## 目录结构

```
nongxian-mall/
├── api/                    # PHP 后端
│   ├── config/             # 配置文件（数据库、应用、调试锁）
│   ├── core/               # 核心框架（Router/Database/Model/Auth/...）
│   ├── controllers/
│   │   ├── admin/          # 管理后台 API 控制器
│   │   └── api/            # 用户端 API 控制器
│   ├── models/             # 数据模型
│   ├── services/           # 业务服务（PayService/SmsService/EmailService/OrderService）
│   ├── middleware/         # 中间件（UserAuth/AdminAuth/AdminLog）
│   ├── routes/             # 路由定义
│   ├── public/             # Web 根目录（Nginx root）
│   ├── index.php           # 入口
│   ├── cron.php            # 定时任务入口
│   └── router.php          # 本地开发路由器
├── admin/                  # 管理后台前端（Vue3）
├── h5/                     # H5 用户端前端（Vue3）
├── sql/                    # 数据库 SQL
│   └── nongxian_mall.sql
├── nginx/                  # Nginx 配置示例
├── docs/                   # 文档
│   └── API.md              # API 接口规范
└── README.md
```

## 功能模块

### 用户端（H5）
- 注册登录（手机验证码/邮箱验证码/手机密码三种方式）
- 商品浏览（分类/搜索/排序/筛选）
- 商品详情（多规格SKU选择、助农信息展示）
- 购物车（勾选/数量调整/失效提醒）
- 下单支付（易支付：支付宝/微信/QQ；余额抵扣）
- 订单管理（查看/取消/确认收货/申请退款）
- 钱包充值（易支付在线充值）
- 拼团（开团/参团/邀请好友/进度跟踪）
- 推荐分享（推广码/链接/团队/奖励明细）
- 站内消息、地址管理、绑定手机邮箱

### 管理后台
- 仪表盘（今日/昨日/本月/累计统计 + 销售趋势图 + 分类占比 + 最新订单 + 库存预警）
- 用户管理（搜索/详情/启禁用/调整余额/发站内信）
- 商品分类（树形/增删改/红色助农标记）
- 商品管理（增删改/上下架/快速改库存/SKU规格/标签/助农信息/图片上传）
- 订单管理（筛选/发货/取消/完成/汇总统计）
- 退款管理（同意/拒绝）
- 配送管理（配送单/添加轨迹）
- 拼团管理（活动CRUD/拼团单/成员查看）
- 轮播图管理
- 系统配置（支付/SMTP/短信/应用/分销——后台可配，密码字段脱敏）
- 销量统计（商品排行/导出）

### 第三方对接（凭据在后台配置，先做功能后填密钥）
- 易支付：标准易支付协议，MD5 签名，支持支付宝/微信/QQ
- SMTP 邮件：纯 PHP SMTP 实现（支持 SSL），无需 Composer 依赖
- 短信：阿里云短信 / 腾讯云短信（V3 签名），未配置时开发模式验证码进日志

## 快速开始

### 1. 环境要求
- PHP 8.0+（需 pdo_mysql、curl、fileinfo、mbstring 扩展）
- MySQL 8.0+
- Node.js 18+（前端构建）

### 2. 初始化数据库
```bash
mysql -u root -p < sql/nongxian_mall.sql
```
默认创建数据库 `nongxian_mall`，含全部表结构和初始数据（管理员账号、商品分类、系统配置）。

### 3. 配置后端
```bash
# 编辑数据库配置
vi api/config/database.php
# 修改 host / database / username / password
```

### 4. 启动后端（本地开发）
```bash
cd api
php -S localhost:8000 -t public router.php
```
后端 API 运行在 `http://localhost:8000`

### 5. 启动管理后台
```bash
cd admin
npm install
npm run dev
```
访问 `http://localhost:5173`，默认账号 `admin` / `admin123456`（**登录后请立即修改密码**）

### 6. 启动 H5 用户端
```bash
cd h5
npm install
npm run dev
```
访问 `http://localhost:5174`

## 部署到云服务器

### 1. 构建前端
```bash
cd admin && npm run build    # 产出 admin/dist/
cd h5 && npm run build       # 产出 h5/dist/
```

### 2. 上传文件
```
/www/wwwroot/nongxian-mall/
├── api/
├── admin/dist/
├── h5/dist/
└── sql/（导入后可删）
```

### 3. Nginx 配置
参考 `nginx/nginx.conf`，按实际路径和 PHP-FPM 配置修改。

### 4. 配置定时任务（自动取消超时订单、自动确认收货、处理过期拼团、统计汇总）
```bash
crontab -e
# 添加：
* * * * * php /www/wwwroot/nongxian-mall/api/cron.php >> /dev/null 2>&1
```

### 5. 后台配置第三方接口
登录管理后台 → 系统设置：
- **支付配置**：填入易支付网关地址、商户PID、商户密钥，启用
- **邮件配置**：填入SMTP服务器、端口、账号、授权码，启用
- **短信配置**：选择服务商，填入AccessKey/Secret/签名/模板，启用

每个配置页都有测试按钮，可先测试再启用。

### 6. 高并发优化建议
- PHP-FPM 调优：`pm = dynamic`，`pm.max_children = 50`（按服务器内存调整）
- MySQL 调优：`innodb_buffer_pool_size` 设为内存的 60-70%
- 开启 OPcache：`opcache.enable=1`、`opcache.memory_consumption=128`
- 上传目录用 CDN 或对象存储（生产环境）
- 数据库读写分离 / 加 Redis 缓存（高并发场景）
- Nginx 开启 gzip（已配置）

## 预留扩展

- **微信小程序**：API 已预留 `/api/` 接口，`api/config/miniapp.php` 配置小程序凭据，后续用 uni-app 改造 H5 前端即可生成小程序
- **秒杀活动**：商品表已有 `is_hot` 标记，可扩展秒杀活动表
- **优惠券**：订单表已有 `discount_amount` 字段，可扩展优惠券模块

## 安全注意事项

1. **删除 `api/config/debug.lock` 文件**（生产环境关闭调试模式）
2. 修改 JWT secret（`api/config/app.php` 的 `jwt_secret`）
3. 修改默认管理员密码
4. Nginx 配置中已禁止访问 config/core/models 等敏感目录
5. 数据库用户使用最小权限原则
6. 开启 HTTPS（Let's Encrypt 免费证书）

## 许可

本项目为贵州亿田农业发展有限公司定制开发。
