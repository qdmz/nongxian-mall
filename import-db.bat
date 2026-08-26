@echo off
chcp 65001 >nul
echo ========================================
echo   导入数据库
echo ========================================
echo.
set /p dbuser="请输入 MySQL 用户名 (默认 root): " || set dbuser=root
set /p dbpass="请输入 MySQL 密码: "

echo.
echo 正在导入数据库 nongxian_mall...
mysql -u%dbuser% -p%dbpass% < "%~dp0sql\nongxian_mall.sql"

if %errorlevel% == 0 (
    echo.
    echo [成功] 数据库已导入！
    echo.
    echo 默认管理员账号: admin
    echo 默认管理员密码: admin123456
    echo.
    echo 请编辑 api\config\database.php 确认数据库连接信息。
) else (
    echo.
    echo [失败] 导入失败，请检查 MySQL 是否运行、用户名密码是否正确。
)
echo.
pause
