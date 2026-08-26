# API 接口规范（前后端对接文档）

## 通用约定

- **Base URL**：本地开发 `http://localhost:8000`（PHP 内置服务器）；生产环境同域名
- **认证**：`Authorization: Bearer <token>`（登录接口返回）
- **响应格式**：
```json
{ "code": 0, "msg": "success", "data": {...} }
```
- code=0 成功；401 未登录；其他为业务错误（msg 为中文提示）
- **分页格式**（data 内）：
```json
{ "list": [...], "total": 100, "page": 1, "page_size": 15 }
```
- **时间**：均为 Unix 时间戳（秒），前端自行格式化

## 用户端 API（H5 前端调用）

### 认证
| 方法 | 路径 | 说明 |
|------|------|------|
| POST | /api/auth/register | 注册 body: {register_type: phone_code/email_code/password, phone, email, code, password, nickname, invite_code} |
| POST | /api/auth/login | 登录 body: {login_type: password/sms, account, password, phone, code} |
| POST | /api/auth/send-code | 发验证码 body: {type: sms/email, target, scene: register/login/verify} |
| GET | /api/auth/check-token | 校验token |

### 首页/商品
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/home | 首页聚合：banners, categories, hot, recommend, new, red, group_buy |
| GET | /api/products?category_id=&keyword=&is_red=&is_hot=&is_recommend=&is_new=&order_by=&page=&page_size= | 商品列表 order_by: sales/price_asc/price_desc/newest |
| GET | /api/products/{id} | 商品详情（含skus, group_buy_activity, related） |
| GET | /api/categories | 分类树 |
| GET | /api/search/hot-keywords | 热搜词 |

### 购物车
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/cart | 购物车列表 |
| POST | /api/cart | 加入购物车 body: {product_id, sku_id, quantity} |
| PUT | /api/cart | 更新 body: {id, quantity, selected} |
| DELETE | /api/cart/{id} | 删除 |
| POST | /api/cart/clear-invalid | 清失效 |

### 订单
| 方法 | 路径 | 说明 |
|------|------|------|
| POST | /api/orders | 创建订单 body: {address_id, items: [{product_id, sku_id, quantity}], remark, use_balance, from_cart} |
| GET | /api/orders?status=&page= | 订单列表 status: 0待支付 1待发货 2已发货 3已完成 4已取消 6退款中 7已退款 |
| GET | /api/orders/{id} | 订单详情（含items, logs, delivery） |
| POST | /api/orders/{id}/cancel | 取消 |
| POST | /api/orders/{id}/confirm | 确认收货 |
| POST | /api/orders/{id}/apply-refund | 申请退款 body: {reason} |
| POST | /api/orders/{id}/pay | 发起支付 body: {pay_type: alipay/wxpay/qqpay} 返回 pay_url |

### 拼团
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/group-buy/activities | 拼团活动列表 |
| GET | /api/group-buy/activities/{id} | 活动详情（含进行中的groups） |
| POST | /api/group-buy/orders | 拼团下单 body: {activity_id, group_buy_id(参团时传), quantity, address_id, remark} |
| GET | /api/group-buy/groups/{id} | 拼团详情（分享页） |
| GET | /api/group-buy/my-groups | 我的拼团 |

### 用户中心
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/user/profile | 个人信息 |
| PUT | /api/user/profile | 更新 body: {nickname, avatar, gender, real_name, birthday} |
| POST | /api/user/change-password | 改密码 |
| GET | /api/user/addresses | 地址列表 |
| POST | /api/user/addresses | 新增地址 body: {consignee, phone, province, city, district, detail, is_default} |
| PUT | /api/user/addresses/{id} | 修改地址 |
| DELETE | /api/user/addresses/{id} | 删除地址 |
| GET | /api/user/wallet | 钱包+流水 |
| POST | /api/user/recharge | 充值 body: {amount, pay_type} 返回 pay_url |
| GET | /api/user/recharge-orders | 充值记录 |
| GET | /api/user/notifications | 站内消息 |
| POST | /api/user/notifications/read | 全部已读 |
| POST | /api/user/bind-phone | 绑定手机 {phone, code} |
| POST | /api/user/bind-email | 绑定邮箱 {email, code} |
| POST | /api/upload/image | 上传图片 form-data: file |

### 推荐分享
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/share/code | 我的推广码/链接/统计 |
| GET | /api/share/product/{id} | 商品分享卡片 |
| GET | /api/share/rewards | 奖励明细 |
| GET | /api/share/my-team | 我推荐的用户 |
| POST | /api/share/track | 点击追踪 {code} |

## 管理后台 API（Admin 前端调用）

### 认证
| 方法 | 路径 | 说明 |
|------|------|------|
| POST | /admin/auth/login | 登录 {username, password} |
| GET | /admin/auth/profile | 当前管理员信息 |
| PUT | /admin/auth/profile | 更新个人信息 |
| POST | /admin/auth/change-password | 改密码 |

### 仪表盘
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /admin/dashboard | 概览（today/yesterday/month/total） |
| GET | /admin/dashboard/sales-trend?days=30 | 销售趋势 |
| GET | /admin/dashboard/product-rank?days=30&limit=20 | 商品排行 |
| GET | /admin/dashboard/category-sales?days=30 | 分类销售占比 |
| GET | /admin/dashboard/latest-orders | 最新订单 |
| GET | /admin/dashboard/low-stock | 库存预警 |

### 用户管理
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /admin/users?keyword=&status=&page= | 用户列表 |
| GET | /admin/users/{id} | 用户详情（含近订单/流水） |
| PUT | /admin/users/{id} | 编辑 {nickname, real_name, status, phone, email} |
| POST | /admin/users/{id}/adjust-balance | 调余额 {amount, remark} |
| POST | /admin/users/send-notification | 发站内信 {user_id, title, content} |

### 分类管理
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /admin/categories | 列表（含product_count） |
| POST | /admin/categories | 新增 {name, parent_id, icon, image, sort, status, is_red} |
| PUT | /admin/categories/{id} | 修改 |
| DELETE | /admin/categories/{id} | 删除 |

### 商品管理
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /admin/products?keyword=&category_id=&status=&page= | 列表 |
| GET | /admin/products/{id} | 详情（含skus） |
| POST | /admin/products | 新增（含skus数组） |
| PUT | /admin/products/{id} | 修改（传skus则全量替换） |
| POST | /admin/products/{id}/toggle-status | 上下架切换 |
| POST | /admin/products/{id}/update-stock | 改库存 {stock} |
| DELETE | /admin/products/{id} | 删除 |

### 订单管理
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /admin/orders?status=&order_no=&user_id=&type=&start_date=&end_date=&page= | 列表（含summary汇总） |
| GET | /admin/orders/{id} | 详情 |
| POST | /admin/orders/{id}/deliver | 发货 {company, tracking_no} 或 {courier_name, courier_phone} |
| POST | /admin/orders/{id}/complete | 完成订单 |
| POST | /admin/orders/{id}/cancel | 取消 {reason} |
| GET | /admin/refunds?status=&page= | 退款列表 |
| POST | /admin/refunds/{id}/handle | 处理退款 {action: approve/reject, reject_reason} |
| GET | /admin/deliveries?status=&page= | 配送列表 |
| POST | /admin/deliveries/{id}/track | 加轨迹 {description, location, status} |

### 拼团管理
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /admin/group-buy/activities?status=&page= | 活动列表 |
| POST | /admin/group-buy/activities | 创建活动 {product_id, group_price, required_count, valid_hours, max_count, stock, start_time, end_time} |
| PUT | /admin/group-buy/activities/{id} | 修改 |
| DELETE | /admin/group-buy/activities/{id} | 停用 |
| GET | /admin/group-buy/groups?status=&page= | 拼团单列表 |
| GET | /admin/group-buy/groups/{id} | 拼团详情（含members） |

### 轮播图
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /admin/banners | 列表 |
| POST | /admin/banners | 新增 {image, title, link_type, link_value, position, sort, status} |
| PUT | /admin/banners/{id} | 修改 |
| DELETE | /admin/banners/{id} | 删除 |

### 系统配置
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /admin/config?group=pay/smtp/sms/app/share | 读配置（password类返回is_set） |
| POST | /admin/config | 保存 {group, configs: {key: value}} |
| POST | /admin/config/test-smtp | 测试邮件 {to} |
| POST | /admin/config/test-sms | 测试短信 {phone} |
| POST | /admin/config/test-pay | 测试支付连通 |
| GET | /admin/config/payment-records?status=&page= | 支付记录 |
| GET | /admin/config/logs?type=sms/email | 发送日志 |

### 上传
| 方法 | 路径 | 说明 |
|------|------|------|
| POST | /admin/upload/image | form-data: file，返回 {url} |

## 关键业务状态

### 订单状态 status
- 0 待支付（可取消、可支付）
- 1 已支付待发货（管理员可发货、可取消退款）
- 2 已发货（用户可确认收货）
- 3 已完成
- 4 已取消
- 5 已关闭
- 6 退款中
- 7 已退款

### 拼团状态
- 0 拼团中
- 1 已成团
- 2 拼团失败（自动退款）
- 3 已取消

### 支付方式 pay_type
- alipay 支付宝
- wxpay 微信
- qqpay QQ钱包

### 商品 flags
- is_hot 热销 / is_new 新品 / is_recommend 党员推荐 / is_red 红色助农专区 / is_group_buy 支持拼团
