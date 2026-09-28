# 更新日志 / CHANGELOG

本文件汇总各版本的主要变更。发布说明（Release Notes）可从对应 git tag 自动生成。

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
