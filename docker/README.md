# Docker 部署（生产级）

一键以生产架构启动整套系统：**Nginx + PHP-FPM + MariaDB + Cron**。

## 架构

```
                    ┌─────────────────────────────────────────┐
 :8080 ──► web(nginx) ─┤ /            h5/dist  H5 用户端          │
                    │  /manage      admin/dist 管理后台         │
                    │  /uploads     上传目录（共享卷，只读）        │
                    │  /api /admin  FastCGI → app:9000         │
                    └─────────────────────────────────────────┘
                                    │
                    ┌───────────────▼──────────────┐
                    │ app (php-fpm 8.2 + crond)     │
                    │  · api/ 后端源码               │
                    │  · 首启自动校验/导入数据库       │
                    │  · 每分钟 cron.php 定时任务     │
                    └───────────────┬──────────────┘
                                    │
                    ┌───────────────▼──────────────┐
                    │ db (mariadb:10.11)            │
                    │  · 首次自动导入 sql/*.sql       │
                    │  · 数据落卷 db_data            │
                    └──────────────────────────────┘
```

- `uploads` 卷在 app（读写）与 web（只读）间共享，nginx 直接静态直出图片
- app 健康检查 = PHP + DB 连通；web 依赖 app 健康，db 健康后 app 才启动
- `JWT_SECRET` / `ADMIN_PASSWORD` 等全部经环境变量注入，无硬编码密钥

## 快速开始

```bash
# 1. 准备环境变量
cp docker/env.docker.example .env.docker
vim .env.docker   # 至少修改 DB_ROOT_PASSWORD / DB_PASSWORD / JWT_SECRET

# 2. 构建并启动（首次约 3-5 分钟）
docker compose --env-file .env.docker -f docker/docker-compose.yml up -d --build
# 或：npm run docker:up

# 3. 查看
docker compose -f docker/docker-compose.yml ps
docker compose -f docker/docker-compose.yml logs -f app
```

| 地址 | 说明 |
|---|---|
| `http://<host>:8080/` | H5 用户端 |
| `http://<host>:8080/manage/` | 管理后台（账号见下方） |
| `http://<host>:8080/api/home` | API 示例 |

默认管理员：`admin / admin123456`（若设置了 `ADMIN_PASSWORD` 则为该值）。**首次登录后请立即修改密码。**

## 环境变量

| 变量 | 必填 | 说明 |
|---|---|---|
| `DB_ROOT_PASSWORD` | ✅ | MariaDB root 密码 |
| `DB_PASSWORD` | ✅ | 应用账号密码（用户名 `DB_USER`，默认 `nongxian`） |
| `JWT_SECRET` | ✅ | JWT 签名密钥，`openssl rand -hex 32` 生成 |
| `ADMIN_PASSWORD` |  | 首次启动自动重置 admin 密码，留空保持默认 |
| `APP_DEBUG` |  | 调试模式，生产保持 `0` |
| `WEB_PORT` |  | 对外端口，默认 `8080` |

## 常用运维

```bash
# 停止 / 删除（保留数据卷）
npm run docker:down

# 重建 app 镜像（代码更新后）
docker compose -f docker/docker-compose.yml build app web
docker compose -f docker/docker-compose.yml up -d

# 进入容器排查
docker compose -f docker/docker-compose.yml exec app sh
docker compose -f docker/docker-compose.yml exec db mariadb -u root -p nongxian_mall

# 手动跑一次定时任务
docker compose -f docker/docker-compose.yml exec app php /var/www/html/api/cron.php
```

## 数据持久化

| 卷 | 内容 |
|---|---|
| `db_data` | 全部数据库数据 |
| `uploads` | 用户/管理员上传的图片（app 可写、web 只读） |

备份：

```bash
docker compose -f docker/docker-compose.yml exec db \
  sh -c 'mariadb-dump -u root -p"$MARIADB_ROOT_PASSWORD" nongxian_mall' > backup.sql
```

## 与 Nginx 裸机部署的差异

- 无需手改 `api/config/database.php`，连接信息全走 `DB_*` 环境变量
- 生产镜像已移除 `api/config/debug.lock`（关闭调试输出）
- 定时任务内置于 app 容器（无需宿主机 crontab）
- 数据库自动初始化（首次空库时导入 `sql/nongxian_mall.sql`）
