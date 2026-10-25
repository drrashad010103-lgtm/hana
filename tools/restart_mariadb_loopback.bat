@echo off
REM restart_mariadb_loopback.bat — graceful MariaDB restart to apply bind-address=127.0.0.1 (my.ini)
REM Run as user "Compu Z" (same owner as current mysqld). Does NOT change any data.
set MYSQL=C:\Program Files\MariaDB 13.0\bin\mysql.exe
set MYSQLADMIN=C:\Program Files\MariaDB 13.0\bin\mysqladmin.exe
set MYSQLD=C:\Program Files\MariaDB 13.0\bin\mysqld.exe

echo [1/4] Graceful shutdown...
"%MYSQLADMIN%" -u root shutdown
if errorlevel 1 ( echo SHUTDOWN_FAILED & exit /b 1 )

:wait_down
netstat -ano | findstr /R "0\.0\.0\.0:3306 .*LISTENING" >nul 2>&1
if not errorlevel 1 (
  timeout /t 1 /nobreak >nul
  goto wait_down
)
echo       port released.

echo [2/4] Starting mysqld --console (detached)...
start "" /B "%MYSQLD%" --console

:wait_up
timeout /t 2 /nobreak >nul
netstat -ano | findstr "127.0.0.1:3306" | findstr "LISTENING" >nul 2>&1
if errorlevel 1 (
  set /a TRIES+=1
  if %TRIES% LSS 30 goto wait_up
  echo START_FAILED - check the mysqld console window.
  exit /b 1
)

echo [3/4] Verifying listeners...
netstat -ano | findstr ":3306" | findstr "LISTENING"

echo [4/4] Checks:
netstat -ano | findstr /C:"0.0.0.0:3306" | findstr "LISTENING" >nul 2>&1
if not errorlevel 1 ( echo FAIL: still listening on 0.0.0.0 ) else ( echo OK: no 0.0.0.0:3306 )
netstat -ano | findstr /C:"[::]:3306" | findstr "LISTENING" >nul 2>&1
if not errorlevel 1 ( echo FAIL: still listening on [::] ) else ( echo OK: no [::]:3306 )
"%MYSQL%" -u root -N -e "SHOW VARIABLES WHERE Variable_name='bind_address';"
"%MYSQL%" -u root -N -e "SELECT COUNT(*) FROM rs_own.users;"
echo Done.
