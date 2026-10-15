# 更新日志 / CHANGELOG

本文件汇总各版本的主要变更。发布说明（Release Notes）可从对应 git tag 自动生成。

---

## v0.08（2026-10-15）—— 安全加固 + 原路退款 / 钱包提现 / 运费模板 / 易支付对账

本版本同步线上 `qiniu.ypvps.com` 于 2026-10-14 完成的两轮修复（后端以线上 `/var/www/nongxian-mall/api` 为准；线上前端只有编译产物，已把同等改动移植回 `admin/`、`h5/` 源码）。升级步骤见 `docs/UPGRADE-v0.08.md`。

### 第一轮：安全加固与并发一致性
- **JWT**：线上不再使用仓库内的公开默认密钥（旧默认值可伪造管理员 token）。生产环境必须通过 `JWT_SECRET` 环境变量设置随机密钥，更换后所有旧 token 失效。
- **入口收敛**：Nginx 改为 `/api/`、`/admin/` 全部交给 `api/index.php`（固定 `SCRIPT_FILENAME`），`cron.php`、`config/`、`core/`、`controllers/` 等无法再从 Web 直接执行或下载（`nginx/nongxian-mall.conf`）。
- **关闭调试**：生产移除 `api/config/debug.lock`；异常统一写 `error_log`（`[nongxian-mall]` 前缀），对外只返回“服务器内部错误”。
- **定时任务**：新增 cron（每分钟，flock 防重入），超时未付订单自动取消、自动确认收货、过期拼团退款、每日统计；附 logrotate。
- **支付与回调**：余额支付改为事务 + `SELECT … FOR UPDATE` + 原子状态流转，杜绝并发重复扣款；回调签名用 `hash_equals`、密钥为空拒绝、校验商户号与金额、充值/支付单原子入账；订单超时取消后的迟到/重复支付自动退款。
- **原子操作**：取消订单、退款、确认收货改为条件更新，防止重复归还库存/余额、重复退款、重复发放推荐奖励。
- **后台权限**：`admin_roles.permissions` 真正生效（按模块拦截 users/products/orders/group_buy/banners/config/statistics/finance）；后台操作日志中间件挂到所有登录路由并记录 JSON 请求体。
- **防暴力与限流**：验证码错误次数上限、登录/注册/后台登录限流（新表 `rate_limits`，`core/RateLimit.php`）；客户端 IP 只信任 `REMOTE_ADDR`。
- **Bug 修复**：推荐返佣读取 `app.*` 配置（兼容 `share.*`）；`Referral::ensureCode` 的 `IS NULL` 条件；拼团详情 SQL 缺 JOIN 导致 500；禁止重复参团、取消订单释放名额；拒绝退款时按是否已发货恢复状态；下单校验 SKU 状态及数量/种类上限；用户资料与地址字段校验；后台订单列表 status=0 汇总。
- **数据库**：补充索引（deliveries/group_buy_members.order_no、orders 状态时间、verify_codes ip 时间）、`referral_rewards` 按订单唯一 —— `sql/migrations/20261014_security_hardening.sql`。

### 第二轮：资金闭环与运营功能
- **推荐返佣**：确认收货时发放，基数为商品金额（不含运费），防自荐；退款时扣回（不足扣到 0，差额记 `clawback_amount`）。
- **原路退款**（`services/RefundService.php`、表 `refund_records`）：易支付支付的部分优先调用易支付退款接口原路退回（钱包支付部分退回钱包）；原路明确失败时转入钱包，结果不确定由 cron 查单重试，仍不确定转人工；开关 `pay.epay_refund_enabled`；迟到支付同样原路退回。
- **钱包提现**（`services/WithdrawService.php`、`controllers/api/WithdrawController.php`、表 `withdrawals`）：
  - 用户接口：`GET /api/user/withdraw/config`、`POST /api/user/withdraw`、`GET /api/user/withdrawals`、`POST /api/user/withdrawals/{id}/cancel`；
  - 申请即冻结，通过扣冻结、驳回/撤销退回；行锁 + 条件更新 + `request_id` 幂等；每人同时 1 笔待审；默认单笔 10–5000 元、每日 3 次（`app.withdraw_*` 配置）；
  - 后台「财务管理」：提现审核 / 退款打款记录 / 易支付对账差异（`/admin/finance/*`，权限 `finance`）。“通过”表示已线下打款，暂未接自动打款。
- **拼团**：付款成功后才建团/占名额；团满或活动结束时按拼团价开新团；失败先原子标记再退款；成员退款释放名额；未成团不可发货；拼团订单包邮。
- **运费模板**（`services/FreightService.php`）：按省份匹配 `delivery_templates`，首件 + 续件，满额包邮；新增 `orders.freight_amount`；运费预估接口 `POST /api/orders/freight`。
- **易支付每日对账**：`api/scripts/reconcile_epay.php`（仅限 CLI），每天 03:15 对账前一日，差异写入 `pay_reconcile_diffs` 与 `/var/log/nongxian-mall/reconcile.log`，只记录不改账，只核对本站支付单。
- **顺带修复**：`order_no()` 偶发带小数点；充值/支付完成后的跳转路由。
- **数据库**：`sql/migrations/20261014b_refund_withdraw_freight_reconcile.sql`（只新增列/表/配置项，不删改数据）。

### 前端（源码）
- `admin/`：登录资料返回权限数组，侧边栏按角色权限隐藏菜单；新增「财务管理」子菜单与路由（`views/finance/FinanceFrame.vue` 嵌入 `public/finance.html`，构建后为 `/manage/finance.html`）；每次进入后台刷新权限。
- `h5/`：钱包页新增「提现」入口（`public/withdraw.html`，构建后为 `/withdraw.html`）；确认订单页显示运费（包邮显示“包邮”），并计入预计实付与余额抵扣。

### 运维与部署文件
- `nginx/nongxian-mall.conf`、`nginx/nongxian-security-headers.conf`：线上加固版站点配置示例（安全响应头、HSTS、index.html 不缓存、隐藏文件 404、uploads 禁止脚本类后缀、上传 12M）。
- `deploy/ops/cron.d-nongxian-mall`：`/etc/cron.d` 定时任务（每分钟 `cron.php` + 每天 03:15 对账）；`deploy/ops/logrotate-nongxian-mall`。
- `docker/app/crontab`：增加 03:15 对账任务。
- `deploy/*.py`：去掉历史遗留的硬编码服务器密码默认值，一律从 `DEPLOY_PWD` 环境变量读取。

### 已知遗留
- 后台订单详情未显示运费；提现需线下打款；易支付退款接口尚未在真实付款订单上验证。
- `sql/nongxian_mall.sql` 全量建库脚本尚未合入新表/新列，新装环境请在导入后依次执行 `sql/migrations/` 下的迁移。

---

## v0.07（2026-09-28）—— 前端响应式与自适应优化

### 背景
线上 `qiniu.ypvps.com` 在桌面浏览器下显示异常：H5 用户端底部 tabbar 图标/文字被放大到铺满整屏；管理后台在窄屏被固定侧边栏挤占。根因是 H5 使用 `postcss-px-to-viewport`（viewportUnit: rem）且根字号被写死为 `37.5px`，而设计稿 375px 对应的正确基准应为 `3.75px`，导致所有 rem 单位被放大 10 倍。

### H5 用户端（`h5/`）
- `h5/src/styles/index.css`：根字号改为 `1vw` 并在 `≥375px` 锁定为 `3.75px`；删除原"手机壳"式居中窄条，改为真正的**桌面卡片式宽屏自适应**。
  - `<768px`：维持全屏手机布局。
  - `≥768px`：内容壳居中、最大 1200px；商品网格 `.grid2` → **4 列**，拼团 → 6 列，金刚区 → 10 列；轮播图加高、卡片加阴影。
  - `≥1100px`：内容壳最大 1280px；商品 → **5 列**，轮播更高。
- `h5/postcss.config.js`：`selectorBlackList` 增加 `html`，避免根字号被 postcss 二次转换。
- `h5/src/App.vue`：清理失效的 `onMount` 引用与未生效的 `app-shell-wide` 包裹逻辑。
- `h5/index.html`：补充基础 `<style>` 保证 html/body/#app 高度与边距。

### 管理后台（`admin/`）
- `admin/src/layout/AdminLayout.vue`：侧边栏响应式——`<768px` 变为**抽屉 + 遮罩**（汉堡按钮唤出、点菜单自动收起）；`768–992px` 自动折叠为 64px 图标栏；`≥992px` 正常 220px；监听 `resize` 自动切换。
- `admin/src/styles/index.css`：全局卡片式增强——`.el-card` 圆角 10px + 轻阴影；`.stat-card` 悬浮上浮动效；`<768px` 时筛选表单换行铺满、图表降高、弹窗近全宽、分页居中、表格字号收敛。

### 部署工具
- 新增 `deploy/deploy_frontend.py`：统一、参数化、复用既有 `deploy/ssh_tool.py` 的部署脚本（打包 dist → 上传 → 备份 → 解压 → 修正属主）。**所有凭据从环境变量/`.env` 注入，无硬编码密钥**。
- 新增 `deploy/.env.example`：部署配置模板（`.env` 已被 `.gitignore` 忽略）。
- 根 `.gitignore` 增加 `*.tar.gz`，避免把部署临时包提交进仓库。

### 后台登录说明
- 本地/文档默认后台账号：`admin / admin123456`（见 `README.md`）。
- 线上生产环境的 admin 密码已单独设置（非仓库默认值）；若遗忘，可用 `deploy/reset_admin.sql` 重置为仓库内置的默认哈希。
- ⚠️ 安全提醒：无论本地还是线上，登录后台后请尽快在「个人中心 → 修改密码」改掉默认/弱密码；不要把真实服务器密码写进 `.env` 之外的任何仓库文件。

### 相关 commit
- `fc9bcea` fix(h5)：修复移动端根字号换算、桌面端居中自适应
- `0402c5f` fix(h5)：PC 端改为卡片式宽屏自适应，商品多列网格铺满
- `1a0da57` feat(admin)：后台侧边栏响应式抽屉化 + 卡片式全局自适应样式

---

## v0.01 – v0.06
早期迭代版本，详见各历史 tag 与提交记录（`git log`）。
