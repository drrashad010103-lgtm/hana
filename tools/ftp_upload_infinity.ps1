param(
  [Parameter(Mandatory = $true)][string]$Local,
  [string]$RemoteRoot = '',
  [string]$FtpHost = $env:RS_FTP_HOST,
  [string]$FtpUser = $env:RS_FTP_USER,
  [string]$FtpPass = $env:RS_FTP_PASS
)
$ErrorActionPreference = 'Stop'
if (-not $FtpHost -or -not $FtpUser -or -not $FtpPass) { throw 'FTP credentials missing (set RS_FTP_HOST/RS_FTP_USER/RS_FTP_PASS)' }
if (-not (Test-Path -LiteralPath $Local)) { throw "local path not found: $Local" }
$base = 'ftp://' + $FtpHost.TrimEnd('/')
if ($RemoteRoot) { $base += '/' + $RemoteRoot.Trim('/') }

function Ftp-Request([string]$url, [string]$method) {
  $r = [System.Net.FtpWebRequest]::Create($url)
  $r.Method = $method
  $r.Credentials = New-Object System.Net.NetworkCredential($FtpUser, $FtpPass)
  $r.UseBinary = $true
  $r.UsePassive = $true
  $r.KeepAlive = $false
  $r.Timeout = 30000
  return $r
}

function Ensure-Dir([string]$rel) {
  if ($rel -eq '') { return }
  $parts = $rel -split '/'
  $cur = ''
  foreach ($p in $parts) {
    $cur = if ($cur) { $cur + '/' + $p } else { $p }
    try {
      $r = Ftp-Request ($base + '/' + $cur) ([System.Net.WebRequestMethods+Ftp]::MakeDirectory)
      $r.GetResponse().Close()
    } catch { }
  }
}

$files = Get-ChildItem -LiteralPath $Local -Recurse -File -Force
$ok = 0; $fail = 0
foreach ($f in $files) {
  $rel = $f.FullName.Substring((Resolve-Path -LiteralPath $Local).Path.Length).TrimStart('\', '/') -replace '\\', '/'
  $dir = ($rel -split '/')[0..([math]::Max(0, ($rel -split '/').Count - 2))] -join '/'
  if (($rel -split '/').Count -eq 1) { $dir = '' }
  Ensure-Dir $dir
  try {
    $r = Ftp-Request ($base + '/' + $rel) ([System.Net.WebRequestMethods+Ftp]::UploadFile)
    $r.ContentLength = $f.Length
    $s = $r.GetRequestStream()
    $fs = [System.IO.File]::OpenRead($f.FullName)
    $fs.CopyTo($s)
    $fs.Close(); $s.Close()
    $resp = $r.GetResponse(); $resp.Close()
    $ok++
    Write-Output ("OK   " + $rel + " (" + $f.Length + ")")
  } catch {
    $fail++
    Write-Output ("FAIL " + $rel + " :: " + $_.Exception.Message)
  }
}
Write-Output ("DONE ok=" + $ok + " fail=" + $fail)
if ($fail -gt 0) { exit 1 }
