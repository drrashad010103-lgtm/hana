# restore_test.ps1 — اختبار استرجاع حقيقي لآخر نسخة احتياطية في قاعدة مؤقتة ثم حذفها
# لا يلمس rs_own إطلاقاً. exit 0 = نجح
$ErrorActionPreference = 'Stop'
$log  = 'D:\RS_Own\backup.log'
$dir  = 'D:\RS_Own\backups'
$mysql = 'C:\Program Files\MariaDB 13.0\bin\mysql.exe'
$ts = Get-Date -Format '[ddd MM/dd/yyyy HH:mm:ss.fff]'
$stamp = Get-Date -Format 'yyyyMMdd_HHmmss'
$dbName = "rs_own_verify_$stamp"
function LogLine([string]$m) { Add-Content -LiteralPath $log -Value "$ts $m" -Encoding UTF8 }
function Fail([string]$m) {
  try { if ($dbName) { & $mysql -u root -e "DROP DATABASE IF EXISTS ``$dbName``" 2>$null } } catch {}
  LogLine "restore-test-FAIL: $m"
  Set-Content -LiteralPath 'D:\RS_Own\backup.verify.failed' -Value "$ts $m" -Encoding UTF8
  exit 1
}
if (-not (Test-Path -LiteralPath $mysql)) { Fail "mysql.exe not found" }
$file = Get-ChildItem -LiteralPath $dir -Filter 'rs_own_*.sql' -ErrorAction SilentlyContinue |
        Sort-Object LastWriteTime -Descending | Select-Object -First 1
if (-not $file) { Fail "no dump found" }
LogLine "restore-test-start: $($file.Name)"
try {
  & $mysql -u root -e "CREATE DATABASE ``$dbName`` CHARACTER SET utf8mb4" 2>$null
  if ($LASTEXITCODE -ne 0) { Fail "create db failed" }
  $tmpBat = 'D:\RS_Own\tools\_restore_tmp.bat'
  $batBody = "@echo off`r`n`"$mysql`" -u root `"$dbName`" < `"$($file.FullName)`"`r`nexit /b %errorlevel%`r`n"
  [IO.File]::WriteAllText($tmpBat, $batBody, (New-Object System.Text.ASCIIEncoding))
  try {
    $p = Start-Process -FilePath 'cmd.exe' -ArgumentList '/c', "`"$tmpBat`"" -Wait -PassThru -WindowStyle Hidden
  } finally {
    Remove-Item -LiteralPath $tmpBat -Force -ErrorAction SilentlyContinue
  }
  if ($p.ExitCode -ne 0) { Fail "import exit=$($p.ExitCode)" }
  $q = "SELECT CONCAT((SELECT COUNT(*) FROM currencies),',',(SELECT COUNT(*) FROM inv),',',(SELECT COUNT(*) FROM inv_sub),',',(SELECT COUNT(*) FROM item_bal));"
  $counts = (& $mysql -u root -N -D $dbName -e $q 2>$null) -join ''
  if ($LASTEXITCODE -ne 0 -or $counts -notmatch '^(\d+),(\d+),(\d+),(\d+)$') { Fail "counts query failed: $counts" }
  $parts = $counts.Split(',')
  if ([int]$parts[0] -lt 1 -or [int]$parts[1] -lt 1 -or [int]$parts[2] -lt 1) { Fail "counts too low: $counts" }
  LogLine "restore-test-OK: file=$($file.Name) currencies=$($parts[0]) inv=$($parts[1]) inv_sub=$($parts[2]) item_bal=$($parts[3])"
  Remove-Item 'D:\RS_Own\backup.verify.failed' -Force -ErrorAction SilentlyContinue
} catch {
  try { & $mysql -u root -e "DROP DATABASE IF EXISTS ``$dbName``" 2>$null } catch {}
  Fail $_.Exception.Message
}
try { & $mysql -u root -e "DROP DATABASE IF EXISTS ``$dbName``" 2>$null } catch {}
exit 0
