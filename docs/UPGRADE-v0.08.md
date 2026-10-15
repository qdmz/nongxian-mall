# 升级到 v0.08（2026-10-15）

适用于从 v0.07 及更早版本升级。变更内容见 `CHANGELOG.md` 的 v0.08 条目。

## 1. 备份
- 代码目录、`mysqldump` 全库、Nginx 站点配置、crontab。

## 2. 数据库迁移（按顺序执行，只新增列/表/索引/配置项，不删改数据）
```bash
mysql -u <用户> -p nongxian_mall < sql/migrations/20261014_security_hardening.sql
mysql -u <用户> -p nongxian_mall < sql/migrations/20261014b_refund_withdraw_freight_reconcile.sql
```
- 第一个脚本中的 `ADD COLUMN` / `ADD KEY` 不是幂等的，重复执行会报“已存在”错误，可忽略对应语句。
- 新装环境：先导入 `sql/nongxian_mall.sql`，再依次执行上面两个迁移（全量脚本暂未合入新表/新列）。

## 3. 配置（不要把真实值提交进仓库）
- `JWT_SECRET`：**必须**在 PHP-FPM 环境（或 `.env` / 容器环境变量）设置为足够长的随机串；仓库 `api/config/app.php` 中的默认值是公开的，不能用于生产。更换后所有已登录用户需要重新登录。
- 数据库：通过 `DB_HOST` / `DB_PORT` / `DB_NAME` / `DB_USER` / `DB_PASS` 环境变量注入；`api/config/database.php` 中的默认值仅供本地开发。
- 生产环境删除 `api/config/debug.lock`（否则异常详情会返回给客户端）。
- 易支付商户 PID / 密钥在后台「系统配置 → 支付配置」里填写（存数据库），不写进代码。
- 新配置项（迁移脚本已插入默认值，可在后台调整）：`pay.epay_refund_enabled`（易支付原路退款，默认开）、`app.withdraw_enabled`、`app.withdraw_min`（10）、`app.withdraw_max`（5000）、`app.withdraw_daily_count`（3）。

## 4. Nginx
参考 `nginx/nongxian-mall.conf` 与 `nginx/nongxian-security-headers.conf`（放到 `/etc/nginx/snippets/`）：
- `/api/`、`/admin/` 全部交给 `api/index.php`（固定 `SCRIPT_FILENAME`），**不要**用 `alias` 映射整个 api 目录；
- 隐藏文件 404；`/uploads/` 禁止脚本、html、svg 类后缀；两个 `index.html` 设为 no-cache；
- 上传上限：`client_max_body_size 12m`，fastcgi `PHP_VALUE upload_max_filesize=10M / post_max_size=12M`。

## 5. 定时任务
模板：`deploy/ops/cron.d-nongxian-mall`（复制到 `/etc/cron.d/nongxian-mall`，按实际路径修改）；日志轮转：`deploy/ops/logrotate-nongxian-mall`（复制到 `/etc/logrotate.d/`），并创建 `/var/log/nongxian-mall/`（属主 www-data）。

| 任务 | 频率 | 命令 | 作用 |
|---|---|---|---|
| 主定时任务 | 每分钟 | `php api/cron.php` | 超时未付订单取消、自动确认收货、过期拼团退款、易支付退款结果查单重试、每日统计 |
| 易支付对账 | 每天 03:15（Asia/Shanghai） | `php api/scripts/reconcile_epay.php [YYYY-MM-DD]` | 对比前一日易支付平台订单与本地支付单，差异写 `pay_reconcile_diffs`，只记录不改账 |

- 两者都以 `www-data` 运行，并用 `flock -n` 防止重入。
- **只限 CLI**：`reconcile_epay.php` 自带 `PHP_SAPI !== 'cli'` 检查；`cron.php` 依靠 Nginx 只放行 `index.php` 来阻止 Web 访问——务必使用第 4 步的 Nginx 配置。
- Docker 部署：`docker/app/crontab` 已包含这两项。

## 6. 前端
```bash
npm run build          # 构建 h5 与 admin
```
- 后台构建产物含 `finance.html`（财务管理，嵌入在「财务管理」菜单中，访问 `/manage/finance.html`）；
- H5 构建产物含 `withdraw.html`（钱包提现页，访问 `/withdraw.html`）。

## 7. 后台角色权限
`admin_roles.permissions` 现已生效：`*` 为全部权限，否则为模块列表（`users`、`products`、`orders`、`group_buy`、`banners`、`config`、`statistics`、`finance`）。「财务管理」需要 `finance` 权限，默认仅超级管理员。

## 回滚
代码回退到 v0.07 并 reload php-fpm；删除 cron 中的对账行。新增的表和列不影响旧代码，可以保留。
