# D:\RS_Own\desktop\keep_server.ps1 - keep the PHP app server running (port 8082)
$php = 'C:\phplocal\php\php.exe'
$dir = 'D:\RS_Own\app'
function Test-Port([int]$p) {
  $c = New-Object Net.Sockets.TcpClient
  $ok = $c.ConnectAsync('127.0.0.1', $p).Wait(400) -and $c.Connected
  $c.Close(); return $ok
}
if (-not (Test-Port 3306)) {
  $svc = Get-Service | Where-Object { $_.Name -like '*mysql*' -or $_.Name -like '*maria*' -or $_.DisplayName -like '*mysql*' } | Select-Object -First 1
  if ($svc -and $svc.Status -eq 'Stopped') { Start-Service $svc.Name }
  for ($i = 0; $i -lt 24 -and -not (Test-Port 3306); $i++) { Start-Sleep -Milliseconds 500 }
  if (-not (Test-Port 3306)) {
    Start-Process -FilePath 'C:\Program Files\MariaDB 13.0\bin\mysqld.exe' -ArgumentList '--defaults-file="C:\Program Files\MariaDB 13.0\data\my.ini"','--console' -WindowStyle Hidden
    for ($i = 0; $i -lt 48 -and -not (Test-Port 3306); $i++) { Start-Sleep -Milliseconds 500 }
  }
}
if (-not (Test-Port 8082)) {
  Start-Process -FilePath $php -ArgumentList '-S','0.0.0.0:8082','-t',$dir -WorkingDirectory $dir -WindowStyle Hidden
  for ($i = 0; $i -lt 24 -and -not (Test-Port 8082); $i++) { Start-Sleep -Milliseconds 500 }
}

# Ensure EasyOrder local proxy (3456) is up
if (-not (Test-Port 3456)) {
  Start-Process -FilePath 'C:\Windows\System32\conhost.exe' -ArgumentList '--headless','node','"D:\Projects\EasyOrder-Merged\server.js"' -WorkingDirectory 'D:\Projects\EasyOrder' -WindowStyle Hidden
  for ($i = 0; $i -lt 20 -and -not (Test-Port 3456); $i++) { Start-Sleep -Milliseconds 500 }
}
