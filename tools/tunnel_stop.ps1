Get-Process cloudflared -ErrorAction SilentlyContinue | Stop-Process -Force
Remove-Item 'D:\RS_Own\status\tunnel_url.txt' -Force -ErrorAction SilentlyContinue
Write-Output 'tunnel stopped'