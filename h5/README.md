# 田冲助农商城 H5 移动端前端

贵州亿田农业 · 田冲红色美丽乡村强村富民工坊 —— 党建引领乡村振兴助农电商平台的用户端 H5 前端。

## 技术栈

- Vue 3（Composition API，`<script setup>`）
- Vite 5
- Vant 4（unplugin-vue-components + @vant/auto-import-resolver 自动按需引入）
- Vue Router 4（**hash 模式**，兼容后端支付回调 `#/` 跳转）
- Pinia（token / 用户信息 / 购物车数量）
- Axios（统一请求封装：Bearer token、统一错误提示、401 跳登录）
- dayjs（时间戳格式化）、@vant/area-data（省市区数据）
- postcss-px-to-viewport（设计稿 375px，自动转 vw）

## 启动方式

```bash
cd h5
npm install
npm run dev
# 访问 http://localhost:5174
```

> 后端 API 默认代理到 `http://localhost:8000`（`/api`、`/uploads` 前缀），请先启动后端服务（PHP 内置服务器）。

## 构建部署

```bash
npm run build   # 产物输出到 dist/
npm run preview # 本地预览构建产物
```

生产环境需将 `/api`、`/uploads` 反向代理到后端（参考 nginx 配置）。

## 目录结构

```
h5/
├── index.html
├── vite.config.js          # 端口 5174 + 后端代理
├── postcss.config.js       # px -> vw（375 设计稿）
└── src/
    ├── main.js
    ├── App.vue             # 根组件 + 底部 Tabbar（购物车角标）
    ├── router/index.js     # hash 路由 + 登录守卫 + 邀请码追踪
    ├── store/user.js       # Pinia 用户状态
    ├── api/                # 请求封装与各业务模块
    │   ├── request.js      # axios 实例（token/错误/401）
    │   ├── auth.js / product.js / cart.js / order.js
    │   ├── user.js / groupBuy.js / share.js
    ├── utils/index.js      # 金额/时间/倒计时/剪贴板/状态映射
    ├── styles/index.css    # 主题（党建红 #E63946 + 田园绿 #2A9D5C）
    ├── components/
    │   ├── ProductCard.vue # 商品卡片（🚩助农标识）
    │   └── CountDown.vue   # 秒级倒计时组件
    └── views/              # 全部页面（见下方路由清单）
```

## 页面路由（hash 模式）

| 路径 | 说明 |
|------|------|
| `/` | 首页：搜索框 + 轮播图 + 金刚区 + 党员推荐横幅 + 拼团/热销/新品/红色助农专区 |
| `/category` | 分类页：左侧分类 + 右侧商品网格 |
| `/search` | 搜索页：历史 + 热词 + 结果列表（支持排序） |
| `/product/:id` | 商品详情：轮播 + SKU 弹层 + 助农档案 + 加购/立即购买/发起拼团 |
| `/cart` | 购物车：勾选 + 步进器 + 滑动删除 + 失效置灰 + 结算栏 |
| `/confirm-order` | 确认订单：地址 + 清单 + 余额抵扣 + 备注 |
| `/order/list?status=` | 订单列表：状态 tab + 待支付倒计时 |
| `/order/detail/:id` | 订单详情：步骤条 + 明细 + 配送跟踪 + 取消/支付/收货/退款 |
| `/pay/:orderId` | 收银台：微信/支付宝/QQ → 跳转 pay_url |
| `/group-buy` | 拼团专区 |
| `/group-buy/:id` | 拼团活动详情：进行中的团 + 开团/参团 |
| `/group-buy/group/:id` | 拼团详情（分享页）：成员进度 + 倒计时 + 邀请好友 |
| `/login` `/register` | 登录（密码/验证码）、注册（手机码/邮箱码/手机密码，支持 ?invite=） |
| `/profile` | 我的：余额入口 + 订单四宫格（角标） + 功能列表 |
| `/address` `/address/edit/:id?` | 地址列表 / 编辑（van-area 省市区） |
| `/wallet` `/recharge` | 钱包（流水） / 充值 |
| `/notifications` | 站内消息（全部已读） |
| `/share` `/share/product/:id` | 推广中心（码/链接/统计/团队/奖励） / 商品分享海报 |
| `/my-groups` | 我的拼团 |
| `/bind` | 绑定手机/邮箱（60s 倒计时验证码） |
| `/about` | 关于我们（工坊介绍） |

## 说明

- 需要登录的页面（购物车、订单、钱包、推广等）未登录会跳转 `/login?redirect=xxx`
- 支付：调 `POST /api/orders/{id}/pay` 拿 `pay_url` 后 `window.location.href` 跳转，回调后回到订单详情
- 拼团：开团/参团统一走 `POST /api/group-buy/orders`，参团传 `group_buy_id`
- 推广：`App` 路由守卫检测 `?invite=` 参数自动上报点击追踪
