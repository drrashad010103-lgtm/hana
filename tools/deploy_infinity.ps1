param(
  [string]$Root = 'D:\RS_Own\deploy'
)
$ErrorActionPreference = 'Stop'
$app = 'D:\RS_Own\app'
$ts = Get-Date -Format 'yyyyMMdd_HHmm'
$stage = Join-Path $Root ("infinity_" + $ts)
$htdocs = Join-Path $stage 'htdocs'
New-Item -ItemType Directory -Path $htdocs -Force | Out-Null

$xd = @('logs', 'sql', '.git')
$xf = @('config.php', 'config.sample.php', 'README_infinity.md', 'install_inst.php')
$args = @($app, $htdocs, '/E', '/NFL', '/NDL', '/NJH', '/NJS', '/NP', '/XD') + $xd + @('/XF') + $xf
$p = Start-Process -FilePath robocopy.exe -ArgumentList $args -Wait -PassThru -WindowStyle Hidden
if ($p.ExitCode -ge 8) { throw "robocopy failed: $($p.ExitCode)" }

Get-ChildItem $htdocs -Recurse -File -Include *.log,*.sql,*.bak | Remove-Item -Force -ErrorAction SilentlyContinue
New-Item -ItemType Directory -Path (Join-Path $htdocs 'uploads\items') -Force | Out-Null

$zip = Join-Path $stage 'htdocs.zip'
Compress-Archive -Path (Join-Path $htdocs '*') -DestinationPath $zip -CompressionLevel Optimal
$files = (Get-ChildItem $htdocs -Recurse -File | Measure-Object Length -Sum)
Write-Output ("STAGE " + $htdocs)
Write-Output ("ZIP   " + $zip)
Write-Output ("FILES " + $files.Count + " SIZE_MB " + [math]::Round($files.Sum / 1MB, 2))
