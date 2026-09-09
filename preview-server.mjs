/**
 * Freebuff 预览服务器：单端口整合服务
 * - 静态托管 h5/dist（H5 用户端，hash 路由）
 * - /manage 静态托管 admin/dist（管理后台，history 模式，fallback 到 /manage/index.html）
 * - /api、/admin、/uploads 转发到 PHP 后端（php -S 127.0.0.1:8000 router.php）
 *
 * 环境变量：
 *   PORT        监听端口（默认 8080）
 *   PHP_BACKEND PHP 后端地址（默认 http://127.0.0.1:8000）
 *   STATIC_ROOT 静态文件根目录（默认仓库根目录）
 */
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import { spawn } from 'node:child_process';

const PORT = parseInt(process.env.PORT || '8080', 10);
const PHP_BACKEND = process.env.PHP_BACKEND || 'http://127.0.0.1:8000';
const ROOT = process.env.STATIC_ROOT || process.cwd();
const H5_DIST = path.join(ROOT, 'h5', 'dist');
const ADMIN_DIST = path.join(ROOT, 'admin', 'dist');

const MIME = {
  '.html': 'text/html; charset=utf-8',
  '.js': 'text/javascript; charset=utf-8',
  '.mjs': 'text/javascript; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.json': 'application/json; charset=utf-8',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.jpeg': 'image/jpeg',
  '.gif': 'image/gif',
  '.svg': 'image/svg+xml',
  '.webp': 'image/webp',
  '.ico': 'image/x-icon',
  '.woff': 'font/woff',
  '.woff2': 'font/woff2',
  '.ttf': 'font/ttf',
  '.eot': 'application/vnd.ms-fontobject',
  '.map': 'application/json',
};

function send(res, status, body, headers = {}) {
  res.writeHead(status, headers);
  res.end(body);
}

function serveStatic(res, filePath, baseDir) {
  let target = filePath;
  try {
    if (!path.resolve(target).startsWith(path.resolve(baseDir))) {
      return send(res, 403, 'Forbidden');
    }
    const stat = fs.statSync(target);
    if (stat.isDirectory()) target = path.join(target, 'index.html');
    const ext = path.extname(target).toLowerCase();
    const isAsset = target.includes(`${path.sep}assets${path.sep}`);
    const headers = {
      'Content-Type': MIME[ext] || 'application/octet-stream',
      'Access-Control-Allow-Origin': '*',
      // 入口 HTML 不缓存，带 hash 的资源长缓存
      'Cache-Control': isAsset ? 'public, max-age=31536000, immutable' : 'no-cache',
    };
    send(res, 200, fs.readFileSync(target), headers);
  } catch {
    send(res, 404, 'Not Found');
  }
}

function proxyPhp(req, res) {
  const backend = new URL(req.url, PHP_BACKEND);
  const proxyReq = http.request(
    {
      hostname: backend.hostname,
      port: backend.port || 8000,
      path: backend.pathname + backend.search,
      method: req.method,
      headers: { ...req.headers, host: `${backend.hostname}:${backend.port || 8000}` },
    },
    (proxyRes) => {
      res.writeHead(proxyRes.statusCode || 502, proxyRes.headers);
      proxyRes.pipe(res);
    }
  );
  proxyReq.on('error', (err) => {
    send(res, 502, JSON.stringify({ code: 502, msg: 'PHP 后端不可用: ' + err.message, data: null }), {
      'Content-Type': 'application/json; charset=utf-8',
    });
  });
  req.pipe(proxyReq);
}

// ---------- 启动 PHP 后端子进程 ----------
// PHP 监听端口与 PHP_BACKEND 保持一致（默认 127.0.0.1:8000）
const backendUrl = new URL(PHP_BACKEND);
const PHP_HOST = backendUrl.hostname || '127.0.0.1';
const PHP_PORT = parseInt(backendUrl.port || '8000', 10);

const PHP_MAX_RESTARTS = 5;
let phpRestarts = 0;

function startPhp() {
  const child = spawn('php', ['-S', `${PHP_HOST}:${PHP_PORT}`, '-t', 'public', 'router.php'], {
    cwd: path.join(ROOT, 'api'),
    stdio: ['ignore', 'inherit', 'inherit'],
  });
  child.on('exit', (code) => {
    phpRestarts += 1;
    if (phpRestarts > PHP_MAX_RESTARTS) {
      console.error(`[preview] PHP backend exited ${phpRestarts} times (last code=${code}), giving up. Check logs above.`);
      return;
    }
    console.error(`[preview] PHP backend exited (code=${code}), restarting (${phpRestarts}/${PHP_MAX_RESTARTS}) in 2s...`);
    setTimeout(startPhp, 2000);
  });
}
startPhp();

const server = http.createServer((req, res) => {
  let pathname;
  try {
    pathname = decodeURIComponent(new URL(req.url, 'http://localhost').pathname);
  } catch {
    return send(res, 400, 'Bad Request');
  }

  // PHP 后端
  if (pathname === '/api' || pathname.startsWith('/api/') || pathname === '/admin' || pathname.startsWith('/admin/') || pathname.startsWith('/uploads/')) {
    return proxyPhp(req, res);
  }

  // 管理后台（history 模式 fallback）
  if (pathname === '/manage' || pathname.startsWith('/manage/')) {
    const rel = pathname.replace(/^\/manage\/?/, '') || 'index.html';
    const file = path.join(ADMIN_DIST, rel);
    let isDir = false;
    try {
      isDir = rel !== '' && rel !== 'index.html' && fs.statSync(file).isDirectory();
    } catch {
      isDir = false;
    }
    if (rel === '' || rel === 'index.html' || !fs.existsSync(file) || isDir) {
      return serveStatic(res, path.join(ADMIN_DIST, 'index.html'), ADMIN_DIST);
    }
    return serveStatic(res, file, ADMIN_DIST);
  }

  // H5 用户端（hash 路由，全部回退 index.html）
  const h5File = path.join(H5_DIST, pathname === '/' ? 'index.html' : pathname);
  if (fs.existsSync(h5File) && fs.statSync(h5File).isFile()) {
    return serveStatic(res, h5File, H5_DIST);
  }
  return serveStatic(res, path.join(H5_DIST, 'index.html'), H5_DIST);
});

server.listen(PORT, '0.0.0.0', () => {
  console.log(`[preview] 田冲助农商城 preview listening on 0.0.0.0:${PORT}`);
  console.log(`[preview]   H5 用户端:   /`);
  console.log(`[preview]   管理后台:    /manage/  (admin / admin123456)`);
  console.log(`[preview]   PHP API:     /api/*  /admin/*  → ${PHP_BACKEND}`);
});
