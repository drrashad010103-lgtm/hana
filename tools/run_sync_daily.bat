@echo off
REM RS_RS -> rs_own manual-safe invoice sync (waits up to 5 min for MariaDB 3306)
set tries=0
:waitdb
powershell -NoProfile -Command "$c=New-Object Net.Sockets.TcpClient; try{$c.Connect('127.0.0.1',3306);$c.Close();exit 0}catch{exit 1}"
if not errorlevel 1 goto run
set /a tries=tries+1
if %tries% GEQ 60 goto fail
timeout /t 5 /nobreak >nul
goto waitdb
:fail
echo [%date% %time%] SKIP: MariaDB 3306 not up after 5 min >> "D:\RS_Own\tools\sync.log"
exit /b 1
:run
echo [%date% %time%] sync start >> "D:\RS_Own\tools\sync.log"
"C:\phplocal\php\php.exe" "D:\RS_Own\tools\sync_rs_to_own.php" --invoices-only >> "D:\RS_Own\tools\sync.log" 2>&1
:end
