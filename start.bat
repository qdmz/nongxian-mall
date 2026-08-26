@echo off
chcp 65001 >nul
echo ========================================
echo   田冲助农商城 - 本地启动
echo ========================================
echo.

echo [1/3] 启动后端 API (端口 8000)...
start "田冲助农API" cmd /k "cd /d %~dp0api && php -S localhost:8000 -t public router.php"

echo [2/3] 启动管理后台 (端口 5173)...
start "田冲助农Admin" cmd /k "cd /d %~dp0admin && npm run dev"

echo [3/3] 启动 H5 用户端 (端口 5174)...
start "田冲助农H5" cmd /k "cd /d %~dp0h5 && npm run dev"

echo.
echo ========================================
echo   全部已启动！
echo.
echo   管理后台: http://localhost:5173
echo   H5 用户端: http://localhost:5174
echo   API 地址:  http://localhost:8000
echo.
echo   默认管理员: admin / admin123456
echo ========================================
echo.
pause
