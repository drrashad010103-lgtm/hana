# start_dashboard_hidden.ps1 — starts rs_dashboard.js only if not already running
$already = Get-CimInstance Win32_Process -Filter "Name='node.exe'" | Where-Object { $_.CommandLine -match 'rs_dashboard\.js' }
if ($already) { exit 0 }
Start-Process node -ArgumentList '"D:\Projects\RS_Dashboard\rs_dashboard.js"' -WorkingDirectory 'D:\Projects\RS_Dashboard' -WindowStyle Hidden
