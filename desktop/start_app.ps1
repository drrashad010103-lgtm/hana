# D:\RS_Own\desktop\start_app.ps1 - launcher for DOCTOR BEAUTY POS (desktop-style window)
$ErrorActionPreference = 'SilentlyContinue'
$php    = 'C:\phplocal\php\php.exe'
$chrome = 'C:\Program Files\Google\Chrome\Application\chrome.exe'
if (-not (Test-Path $chrome)) { $chrome = 'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe' }
$appDir = 'D:\RS_Own\app'
$here   = 'D:\RS_Own\desktop'
$url    = 'http://127.0.0.1:8082/pages/main.php'

function Test-Port([int]$p) {
  $c = New-Object Net.Sockets.TcpClient
  $ok = $c.ConnectAsync('127.0.0.1', $p).Wait(400) -and $c.Connected
  $c.Close(); return $ok
}

# 1) MySQL/MariaDB on 3306 (best effort - try to start a stopped service if port closed)
if (-not (Test-Port 3306)) {
  $svc = Get-Service | Where-Object { $_.Name -like '*mysql*' -or $_.Name -like '*maria*' -or $_.DisplayName -like '*mysql*' } | Select-Object -First 1
  if ($svc -and $svc.Status -eq 'Stopped') { Start-Service $svc.Name }
  for ($i = 0; $i -lt 20 -and -not (Test-Port 3306); $i++) { Start-Sleep -Milliseconds 500 }
}

# 2) PHP server on 8082 (start hidden if not already listening)
if (-not (Test-Port 8082)) {
  Start-Process -FilePath $php -ArgumentList '-S','0.0.0.0:8082','-t',$appDir -WorkingDirectory $appDir -WindowStyle Hidden
  for ($i = 0; $i -lt 20 -and -not (Test-Port 8082); $i++) { Start-Sleep -Milliseconds 300 }
}

if (-not (Test-Port 8082)) { throw 'PHP server failed to start on 8082' }

# 3) One-time desktop shortcut with icon
$lnk = Join-Path ([Environment]::GetFolderPath('Desktop')) 'DOCTOR BEAUTY.lnk'
if (-not (Test-Path $lnk)) {
  $ws = New-Object -ComObject WScript.Shell
  $sc = $ws.CreateShortcut($lnk)
  $sc.TargetPath = 'powershell.exe'
  $sc.Arguments  = '-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File "D:\RS_Own\desktop\start_app.ps1"'
  $sc.IconLocation = 'D:\RS_Own\desktop\app.ico,0'
  $sc.WorkingDirectory = 'D:\RS_Own\desktop'
  $sc.Description = 'Doctor Beauty POS'
  $sc.Save()
}

# 4) Launch app window (no tabs, no address bar - dedicated profile keeps shop session separate)
Start-Process -FilePath $chrome -ArgumentList ('--app=' + $url), '--user-data-dir=D:\RS_Own\desktop\chrome_profile', '--start-maximized', '--no-first-run', '--no-default-browser-check'
