@echo off
REM Stop rs_own workers + nginx
taskkill /F /IM nginx.exe >nul 2>&1
for %%p in (8083 8084 8085 8086) do (
  for /f "tokens=5" %%a in ('netstat -ano ^| findstr 127.0.0.1:%%p ^| findstr LISTENING') do taskkill /F /PID %%a >nul 2>&1
)
exit /b 0
