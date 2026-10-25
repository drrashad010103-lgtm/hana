@echo off
REM RS_Own launcher: nginx (8082) + 4 PHP workers (8083-8086) with router.php
cd /d "D:\RS_Own"
REM start workers if none listening
netstat -ano | findstr 127.0.0.1:8083 | findstr LISTENING >nul || call "D:\RS_Own\tools\start_workers.bat"
timeout /t 2 /nobreak >nul
REM start nginx if not running
tasklist /FI "IMAGENAME eq nginx.exe" 2>nul | find /I "nginx.exe" >nul || start "" /min "C:\phplocal\nginx\nginx.exe" -p "C:\phplocal\nginx"
exit /b 0
