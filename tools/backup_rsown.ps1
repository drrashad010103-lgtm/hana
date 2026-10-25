# backup_rsown.ps1 — nightly scheduled backup for rs_own (MariaDB)
# Safe: dumps rs_own only, never RS_RS. Exit code 0=OK 1=FAIL (task scheduler captures)
$ErrorActionPreference = 'Stop'
$stamp = Get-Date -Format 'yyyyMMdd_HHmmss'
$bkDir = 'D:\RS_Own\backups'
$bkBin = 'C:\Program Files\MariaDB 13.0\bin\mysqldump.exe'
$log   = 'D:\RS_Own\backup.log'
$keep  = 14
$ts    = Get-Date -Format '[ddd MM/dd/yyyy HH:mm:ss.fff]'
$fail  = { param($msg) $line = "$ts backup-FAIL: $msg"; Add-Content -LiteralPath $log -Value $line -Encoding UTF8; Set-Content -LiteralPath 'D:\RS_Own\backup.failed' -Value "$ts $msg" -Encoding UTF8; exit 1 }
if (-not (Test-Path -LiteralPath $bkDir)) { New-Item -ItemType Directory -Path $bkDir -Force | Out-Null }
if (-not (Test-Path -LiteralPath $bkBin)) { & $fail "mysqldump not found at $bkBin" }
# self-heal: mysqld is NOT a Windows service here — if port 3306 is down, start it and wait before dumping
if (-not (netstat -ano | Select-String '127\.0\.0\.1:3306.*LISTENING')) {
  $mysqld = 'C:\Program Files\MariaDB 13.0\bin\mysqld.exe'
  Add-Content -LiteralPath $log -Value "$ts backup-note: mysqld not listening on 3306, attempting auto-start" -Encoding UTF8
  if (-not (Test-Path -LiteralPath $mysqld)) { & $fail "mysqld not found at $mysqld and port 3306 is down" }
  Start-Process -FilePath $mysqld -ArgumentList '--console' -WindowStyle Hidden
  $up = $false
  for ($i = 0; $i -lt 30; $i++) {
    Start-Sleep -Seconds 2
    if (netstat -ano | Select-String '127\.0\.0\.1:3306.*LISTENING') { $up = $true; break }
  }
  if (-not $up) { & $fail "mysqld auto-start failed (port 3306 still down after 60s)" }
  Add-Content -LiteralPath $log -Value "$ts backup-note: mysqld auto-started OK" -Encoding UTF8
}
$file = Join-Path $bkDir "rs_own_$stamp.sql"
try {
  $p = Start-Process -FilePath $bkBin -ArgumentList '-u','root','--skip-lock-tables','rs_own' -RedirectStandardOutput $file -RedirectStandardError "$file.err" -Wait -PassThru -WindowStyle Hidden
  $err = if (Test-Path "$file.err") { (Get-Content "$file.err" -Raw) } else { '' }
  Remove-Item "$file.err" -Force -ErrorAction SilentlyContinue
  if ($p.ExitCode -ne 0 -or -not (Test-Path $file) -or (Get-Item $file).Length -lt 1024) {
    if (Test-Path $file) { Remove-Item $file -Force }
    & $fail "exit=$($p.ExitCode) size=$($(if(Test-Path $file){(Get-Item $file).Length}else{0})) stderr=$err"
  }
} catch { & $fail $_.Exception.Message }
# prune: keep newest $keep, drop older
Get-ChildItem -LiteralPath $bkDir -Filter 'rs_own_*.sql' | Sort-Object LastWriteTime -Descending | Select-Object -Skip $keep | Remove-Item -Force
# صور الأصناف والمرفقات (لو موجودة)
$upDir = 'D:\RS_Own\app\uploads'
if (Test-Path -LiteralPath $upDir) {
  $zip = Join-Path $bkDir "rs_own_uploads_$stamp.zip"
  try {
    Compress-Archive -Path (Join-Path $upDir '*') -DestinationPath $zip -Force
    Get-ChildItem -LiteralPath $bkDir -Filter 'rs_own_uploads_*.zip' | Sort-Object LastWriteTime -Descending | Select-Object -Skip 7 | Remove-Item -Force
  } catch {
    Add-Content -LiteralPath $log -Value "$ts backup-warn: uploads zip failed: $($_.Exception.Message)" -Encoding UTF8
  }
}
$size = (Get-Item $file).Length
$line = "$ts backup-status: 200 {`"ok`":true,`"file`":`"$([IO.Path]::GetFileName($file))`",`"size`":$size}"
Add-Content -LiteralPath $log -Value $line -Encoding UTF8
Remove-Item 'D:\RS_Own\backup.failed' -Force -ErrorAction SilentlyContinue
exit 0
