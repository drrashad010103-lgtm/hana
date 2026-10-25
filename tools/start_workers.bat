@echo off
REM Start 4 PHP built-in workers for rs_own (guard-enforced + production flags)
set PHP=C:\phplocal\php\php.exe
set APP=D:\RS_Own\app
set LOG=D:\RS_Own\logs
if not exist "%LOG%" mkdir "%LOG%"
for %%p in (8083 8084 8085 8086) do (
  tasklist /FI "WINDOWTITLE eq rsown-worker-%%p" 2>nul | find /I "php.exe" >nul
  if errorlevel 1 start "rsown-worker-%%p" /B "%PHP%" -d expose_php=0 -d display_errors=0 -d display_startup_errors=0 -d log_errors=1 -d error_log=D:/RS_Own/logs/php_errors.log -S 127.0.0.1:%%p -t "%APP%" "%APP%\router.php"
)
exit /b 0