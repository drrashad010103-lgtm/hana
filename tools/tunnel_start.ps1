$cf = 'C:\phplocal\cloudflared\cloudflared.exe'
$log = 'D:\RS_Own\logs\cloudflared.err'
$out = 'D:\RS_Own\logs\cloudflared.log'
New-Item -ItemType Directory -Path 'D:\RS_Own\logs' -Force | Out-Null
New-Item -ItemType Directory -Path 'D:\RS_Own\status' -Force | Out-Null
$p = Get-Process cloudflared -ErrorAction SilentlyContinue
if (-not $p) {
  Remove-Item $log, $out -Force -ErrorAction SilentlyContinue
  Start-Process -FilePath $cf -ArgumentList 'tunnel','--url','http://127.0.0.1:8082','--no-autoupdate' -WindowStyle Hidden -RedirectStandardOutput $out -RedirectStandardError $log
}
$u = ''
for ($i = 0; $i -lt 40; $i++) {
  Start-Sleep -Seconds 1
  $t = (Get-Content $log -Raw -ErrorAction SilentlyContinue) + (Get-Content $out -Raw -ErrorAction SilentlyContinue)
  if ($t -match 'https://[a-z0-9-]+\.trycloudflare\.com') { $u = $Matches[0]; break }
}
if ($u) { Set-Content -LiteralPath 'D:\RS_Own\status\tunnel_url.txt' -Value $u -Encoding ASCII; Write-Output "TUNNEL $u" }
else { Write-Output 'TUNNEL failed (see D:\RS_Own\logs\cloudflared.err)' }