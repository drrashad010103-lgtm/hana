@echo off
setlocal
set PHP=C:\phplocal\php\php.exe
set ROOT=D:\RS_Own\app
set FAIL=0
for /r "%ROOT%" %%f in (*.php) do (
  "%PHP%" -l "%%f" >nul 2>&1
  if errorlevel 1 ( set /a FAIL+=1 & echo FAIL: %%f )
)
echo LINT_FAIL=%FAIL%
exit /b %FAIL%
