# run_all.ps1 — smoke + E2E gate for rs_own
$ErrorActionPreference = 'Stop'
$root = 'D:\RS_Own'
$app  = "$root\app"
$php  = 'C:\phplocal\php\php.exe'
$mysql = 'C:\Program Files\MariaDB 13.0\bin\mysql.exe'
$latest = (Get-ChildItem "$root\backups" -Filter 'rs_own_*.sql' | Sort-Object LastWriteTime -Descending | Select-Object -First 1).FullName
$curl = 'C:\Windows\System32\curl.exe'
$base = 'http://127.0.0.1:8099'
$jar  = "$env:TEMP\rsown_test.jar"
$fail = 0
function Fail($m){ $script:fail++; Write-Host "FAIL: $m" -ForegroundColor Red }
function Ok($m){ Write-Host "OK:   $m" -ForegroundColor Green }

$testDb = 'rs_own_test_' + [Guid]::NewGuid().ToString('N')
$previousRsDb = $env:RS_DB
$proc = $null

try {
# 1) lint
Write-Host "=== 1. PHP lint ==="
$lintOut = cmd /c "D:\RS_Own\tests\lint_all.bat" 2>&1
$lintExit = $LASTEXITCODE
$lintBad = 0
if ($lintOut -match 'LINT_FAIL=(\d+)') { $lintBad = [int]$Matches[1] }
$phpCount = (Get-ChildItem $app -Recurse -Filter *.php).Count
if ($lintBad -eq 0) { Ok "lint $phpCount files" } else { Fail "lint $lintBad failures"; $lintOut | Where-Object { $_ -match 'FAIL:' } | ForEach-Object { Write-Host $_ -ForegroundColor Red } }

# 1b) focused PHP tests
Write-Host "=== 1b. focused PHP tests ==="
$unitTests = Get-ChildItem "$root\tests" -Filter '*_test.php'
foreach ($test in $unitTests) {
  & $php $test.FullName
  if ($LASTEXITCODE -ne 0) { Fail "unit test $($test.Name)" }
  else { Ok "unit test $($test.Name)" }
}

# 2) restore latest backup into an isolated, uniquely named test database
Write-Host "=== 2. restore test DB ==="
$existing = & $mysql -u root -N -B -e "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME='$testDb'" 2>$null
if ($LASTEXITCODE -ne 0) { Fail "check test db name"; throw "db check failed" }
if ($existing) { Fail "generated test database already exists"; throw "db collision" }
& $mysql -u root -e "CREATE DATABASE ``$testDb`` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;" 2>&1 | Out-Null
if ($LASTEXITCODE -ne 0) { Fail "create test db"; throw "db create failed" }
# source
$cmd = "`"$mysql`" -u root $testDb < `"$latest`""
cmd /c $cmd 2>&1 | Out-Null
if ($LASTEXITCODE -ne 0) { Fail "restore backup"; throw "restore failed" }
Ok "restored $latest into $testDb"

  # 2b) suite-owned test user (لا اعتماد على حسابات متبقية في النسخة الحية)
  $regHash = '$2y$10$BfnbVKmX6fLiegbEY68z/Ob.3CIhdsujf4.09u8HDEb3iWPXmPkLS'
  & $mysql -u root $testDb -e "INSERT INTO users (id, username, pwd, IS_Deleted, edit_inv, edit_disc, edit_price, edit_date) SELECT COALESCE(MAX(id),0)+1, 'regrtest', '$regHash', 0, 1, 1, 1, 1 FROM users;" 2>&1 | Out-Null
  if ($LASTEXITCODE -ne 0) { Fail "create regrtest in test db"; throw "regrtest" }
  Ok "created regrtest test user"
  # guard صار fail-closed: مستخدم بلا user_perms = 403 — نمنحه كل الصلاحيات
  $perms = "SELECT 'm_sale' AS p UNION ALL SELECT 'm_buy' UNION ALL SELECT 'm_item' UNION ALL SELECT 'm_stock' UNION ALL SELECT 'm_reports' UNION ALL SELECT 'm_money' UNION ALL SELECT 'm_cust' UNION ALL SELECT 'm_admin'"
  & $mysql -u root $testDb -e "CREATE TABLE IF NOT EXISTS user_perms (user_id INT NOT NULL, perm VARCHAR(32) NOT NULL, val TINYINT NOT NULL DEFAULT 1, PRIMARY KEY (user_id, perm)); INSERT INTO user_perms (user_id, perm, val) SELECT u.id, x.p, 1 FROM users u JOIN ($perms) x WHERE u.username='regrtest';" 2>&1 | Out-Null
  if ($LASTEXITCODE -ne 0) { Fail "grant regrtest perms"; throw "regrperms" }
  Ok "granted regrtest all perms"

# 3) start test server
Write-Host "=== 3. start test server 8099 ==="
  # (jar will be overwritten by curl -c)
  # اقتل أي سيرفر قديم متبقٍ على 8099 (php -S قد ينجو من إيقاف cmd الأب)
  cmd /c "for /f `"tokens=5`" %a in ('netstat -ano ^| findstr 127.0.0.1:8099 ^| findstr LISTENING') do taskkill /F /PID %a 2>nul" 2>$null | Out-Null
  Start-Sleep -Milliseconds 500
  # RS_DB تُضبط داخل cmd صراحةً — Start-Process للـ.bat لا يورّث متغيرات البيئة المعدّلة للـPHP
  $proc = Start-Process -FilePath "cmd.exe" -ArgumentList '/c',"set RS_DB=$testDb&& `"D:\RS_Own\tests\serve_test.bat`"" -WindowStyle Hidden -PassThru
$ready = $false
for ($i=0; $i -lt 40; $i++) {
  Start-Sleep -Milliseconds 500
  try {
    $r = & $curl -s -o NUL -w "%{http_code}" "$base/pages/login.php" 2>$null
    if ($r -eq '200') { $ready = $true; break }
  } catch {}
}
if (-not $ready) { Fail "server not ready"; throw "server fail" }
Ok "server ready"

  # 3b) تحقق صريح: هوية قاعدة الاتصال عبر config = قاعدة الاختبار (RS_DB تتغلب على db.name)
  $probe = "$env:TEMP\rsown_dbprobe.php"
  Set-Content -LiteralPath $probe -Value '<?php $c = require "D:/RS_Own/app/config.php"; echo $c["db"]["name"];' -Encoding ASCII
  $envCheck = cmd /c "set RS_DB=$testDb&& `"$php`" `"$probe`"" 2>&1
  if ($envCheck -ne $testDb) { Fail "DB identity mismatch: expected $testDb got $envCheck"; throw "db identity" }
  Ok "DB identity=$envCheck (RS_DB overrides db.name)"

  # login
  & $curl -s -o NUL -c $jar -d "u=regrtest&p=RegTest2026%23&go=1" "$base/pages/login.php" | Out-Null
  $me = & $curl -s -o NUL -w "%{http_code}" -b $jar "$base/pages/main.php"
  if ($me -ne '200') { Fail "login failed (main=$me)"; throw "login" }
  Ok "login regrtest"

  # 3c) mutation endpoints reject GET / POST without CSRF and accept the session token
  $gardPage = "$env:TEMP\gardscan_test.html"
  $gardCode = & $curl -s -o $gardPage -w "%{http_code}" -b $jar "$base/pages/gardscan.php"
  $gardHtml = Get-Content $gardPage -Raw
  $tokenMatch = [regex]::Match($gardHtml, '<meta name="csrf-token" content="([^"]+)"')
  if ($gardCode -ne '200' -or -not $tokenMatch.Success) { Fail "gardscan CSRF token missing"; throw "csrf token" }
  $csrf = [Net.WebUtility]::HtmlDecode($tokenMatch.Groups[1].Value)
  $apiBody = "$env:TEMP\csrf_api_body.json"
  $getCode = & $curl -s -o $apiBody -w "%{http_code}" -b $jar "$base/pages/api_gard.php?act=clear"
  $getResp = Get-Content $apiBody -Raw | ConvertFrom-Json
  if ($getCode -eq '403' -and $getResp.error -eq 'csrf') { Ok "garde clear rejects GET" } else { Fail "garde clear GET was not rejected (code=$getCode)" }
  $noTokenCode = & $curl -s -o $apiBody -w "%{http_code}" -b $jar -X POST "$base/pages/api_gard.php?act=clear"
  $noTokenResp = Get-Content $apiBody -Raw | ConvertFrom-Json
  if ($noTokenCode -eq '403' -and $noTokenResp.error -eq 'csrf') { Ok "garde clear rejects POST without token" } else { Fail "garde clear accepted missing CSRF token (code=$noTokenCode)" }
  $okCode = & $curl -s -o $apiBody -w "%{http_code}" -b $jar -X POST -H "X-CSRF-Token: $csrf" "$base/pages/api_gard.php?act=clear"
  $okResp = Get-Content $apiBody -Raw | ConvertFrom-Json
  if ($okCode -eq '200' -and $okResp.ok) { Ok "garde clear accepts valid CSRF token" } else { Fail "garde clear with valid CSRF failed (code=$okCode)" }
  $itemGetCode = & $curl -s -o $apiBody -w "%{http_code}" -b $jar "$base/pages/api_item_add.php?act=del&id=99999999"
  $itemGetResp = Get-Content $apiBody -Raw | ConvertFrom-Json
  if ($itemGetCode -eq '403' -and $itemGetResp.error -eq 'csrf') { Ok "item delete rejects GET" } else { Fail "item delete GET was not rejected (code=$itemGetCode)" }

  foreach ($page in @('admin.php', 'salary.php')) {
    $pageFile = "$env:TEMP\csrf_$page.html"
    $pageCode = & $curl -s -o $pageFile -w "%{http_code}" -b $jar "$base/pages/$page"
    $pageHtml = Get-Content $pageFile -Raw
    if ($pageCode -eq '200' -and $pageHtml -match '<input type="hidden" name="csrf" value="[a-f0-9]+">') {
      Ok "$page POST forms include CSRF token"
    } else { Fail "$page POST form missing CSRF token (code=$pageCode)" }
  }

  # 4) smoke all pages
  Write-Host "=== 4. smoke pages ==="
  $pages = @()
  $pages += Get-ChildItem "$app\pages" -Filter *.php | Where-Object { $_.Name -ne 'logout.php' } | ForEach-Object { $_.Name }
  $pages += 'index.php'
  $smokeBad = 0
  foreach ($pg in $pages) {
    $url = if ($pg -eq 'index.php') { "$base/index.php" } else { "$base/pages/$pg" }
    $bodyFile = "$env:TEMP\smoke_body.html"
    $code = & $curl -s -o $bodyFile -w "%{http_code}" -b $jar $url 2>$null
    $body = if (Test-Path $bodyFile) { Get-Content $bodyFile -Raw } else { '' }
    if ($null -eq $body) { $body = '' }  # ملف فارغ = ردّ فارغ (Get-Content -Raw يرجّع null)
    # محتوى السجلات داخل <pre> (مثل sync.log) ليس خطأ في الصفحة — نستبعده قبل الفحص
    $fatalScan = [regex]::Replace($body, '<pre\b[^>]*>[\s\S]*?</pre>', '')
    $fatal = ($fatalScan -match 'Fatal error|Parse error|Warning</b>|Notice</b>')
    # api_* قد يرجع 400 على طلب ناقص (unknown act) — سلوك صحيح
    $codeOk = ($code -eq '200' -or $code -eq '403' -or ($code -eq '400' -and $pg.StartsWith('api_')))
    # item_img.php بلا ?id= يرجّع 404 بالتصميم (image endpoint مش صفحة)
    if ($pg -eq 'item_img.php' -and $code -eq '404') { $codeOk = $true }
    # lang.php يحوّل بعد الحفظ، وsync_in/migrate_in نقاط POST فقط (405 على GET)
    if ($pg -eq 'lang.php' -and $code -eq '302') { $codeOk = $true }
    if (($pg -eq 'sync_in.php' -or $pg -eq 'migrate_in.php') -and $code -eq '405') { $codeOk = $true }
    if (-not $codeOk -or $fatal) {
      $smokeBad++
      Write-Host "SMOKE FAIL: $pg code=$code fatal=$fatal" -ForegroundColor Red
    }
  }
  if ($smokeBad -eq 0) { Ok "smoke $($pages.Count) pages" } else { Fail "smoke $smokeBad failures" }

  # re-login before E2E (smoke may have expired session)
  & $curl -s -o NUL -c $jar -d "u=regrtest&p=RegTest2026%23&go=1" "$base/pages/login.php" | Out-Null
  $q = & $mysql -u root -N -B $testDb -e "SELECT id, sale_price, purch_price FROM currencies WHERE IS_Deleted=0 OR IS_Deleted IS NULL LIMIT 1" 2>$null
  $parts = $q -split "`t"
  $cid = [int]$parts[0]; $salePrice = [double]$parts[1]; $purchPrice = [double]$parts[2]
  if ($cid -le 0) { Fail "no currency in test db"; throw "nocur" }
  Ok "test currency id=$cid price=$salePrice"

  # 5) E2E sale
  Write-Host "=== 5. E2E sale create/edit/delete ==="
  $saleBody = "{`"lines`":[{`"id`":$cid,`"qty`":2,`"price`":$salePrice,`"disc`":0}],`"cust`":0,`"sman`":0,`"pay`":1,`"paid`":0,`"hdisc`":0,`"temp`":0,`"pt`":1}"
  Set-Content -LiteralPath "$env:TEMP\sale_create.json" -Value $saleBody -Encoding ASCII
  $respFile = "$env:TEMP\sale_resp.json"
  $code = & $curl -s -o $respFile -w "%{http_code}" -b $jar -H "Content-Type: application/json" --data-binary "@$env:TEMP\sale_create.json" "$base/pages/api_sale_save.php" 2>$null
  $resp = Get-Content $respFile -Raw | ConvertFrom-Json
  if ($code -ne '200' -or -not $resp.ok) { Fail "sale create (code=$code resp=$($resp.error))" } else {
    $saleId = [int]$resp.id; Ok "sale create id=$saleId"
    # edit
    $saleEdit = "{`"id`":$saleId,`"lines`":[{`"id`":$cid,`"qty`":3,`"price`":$salePrice,`"disc`":0}],`"cust`":0,`"sman`":0,`"pay`":1,`"paid`":0,`"hdisc`":0,`"temp`":0,`"pt`":1,`"date`":`"2026-01-15`"}"
    Set-Content -LiteralPath "$env:TEMP\sale_edit.json" -Value $saleEdit -Encoding ASCII
    $code2 = & $curl -s -o $respFile -w "%{http_code}" -b $jar -H "Content-Type: application/json" --data-binary "@$env:TEMP\sale_edit.json" "$base/pages/api_sale_save.php" 2>$null
    $resp2 = Get-Content $respFile -Raw | ConvertFrom-Json
    if ($code2 -ne '200' -or -not $resp2.ok) { Fail "sale edit (code=$code2)" } else { Ok "sale edit" }
    # delete
    $delBody = "{`"del`":$saleId,`"pt`":1}"
    Set-Content -LiteralPath "$env:TEMP\sale_del.json" -Value $delBody -Encoding ASCII
    $code3 = & $curl -s -o $respFile -w "%{http_code}" -b $jar -H "Content-Type: application/json" --data-binary "@$env:TEMP\sale_del.json" "$base/pages/api_sale_save.php" 2>$null
    $resp3 = Get-Content $respFile -Raw | ConvertFrom-Json
    if ($code3 -ne '200' -or -not $resp3.ok) { Fail "sale delete (code=$code3)" } else { Ok "sale delete" }
  }

  # 6) E2E buy
  Write-Host "=== 6. E2E buy create/delete ==="
  $buyBody = "{`"lines`":[{`"id`":$cid,`"qty`":1,`"price`":$purchPrice,`"disc`":0,`"exp`":null}],`"hdisc`":0,`"paid`":0,`"temp`":0,`"pt`":1}"
  Set-Content -LiteralPath "$env:TEMP\buy_create.json" -Value $buyBody -Encoding ASCII
  $code = & $curl -s -o $respFile -w "%{http_code}" -b $jar -H "Content-Type: application/json" --data-binary "@$env:TEMP\buy_create.json" "$base/pages/api_buy_save.php" 2>$null
  $resp = Get-Content $respFile -Raw | ConvertFrom-Json
  if ($code -ne '200' -or -not $resp.ok) { Fail "buy create (code=$code resp=$($resp.error))" } else {
    $buyId = [int]$resp.id; Ok "buy create id=$buyId"
    $delBody = "{`"del`":$buyId,`"pt`":1}"
    Set-Content -LiteralPath "$env:TEMP\buy_del.json" -Value $delBody -Encoding ASCII
    $code2 = & $curl -s -o $respFile -w "%{http_code}" -b $jar -H "Content-Type: application/json" --data-binary "@$env:TEMP\buy_del.json" "$base/pages/api_buy_save.php" 2>$null
    $resp2 = Get-Content $respFile -Raw | ConvertFrom-Json
    if ($code2 -ne '200' -or -not $resp2.ok) { Fail "buy delete (code=$code2)" } else { Ok "buy delete" }
  }

  # 7) audit rows present
  Write-Host "=== 7. audit check ==="
  $ac = & $mysql -u root -N -B $testDb -e "SELECT COUNT(*) FROM audit_log WHERE entity IN ('sale','buy') AND action IN ('create','edit','delete')" 2>$null
  $ac = [int]($ac -replace '\s','')
  if ($ac -ge 3) { Ok "audit rows=$ac" } else { Fail "audit rows=$ac (expect >=3)" }

} finally {
  # $proc هو cmd.exe الأب — نقتل الـphp الفعلي عبر المنفذ 8099 (2>nul داخل cmd حتى لا يرمي RPC error لو خرج بالفعل)
  $ErrorActionPreference = 'SilentlyContinue'
  cmd /c "for /f `"tokens=5`" %a in ('netstat -ano ^| findstr 127.0.0.1:8099 ^| findstr LISTENING') do taskkill /F /PID %a 2>nul" | Out-Null
  if ($proc) { Stop-Process -Id $proc.Id -Force -ErrorAction SilentlyContinue }
  if ($testDb -match '^rs_own_test_[a-f0-9]{32}$') {
    & $mysql -u root -e "DROP DATABASE IF EXISTS ``$testDb``;" 2>&1 | Out-Null
  }
  if ($null -eq $previousRsDb) { Remove-Item Env:RS_DB -ErrorAction SilentlyContinue }
  else { $env:RS_DB = $previousRsDb }
}

Write-Host ""
if ($fail -eq 0) { Write-Host "ALL PASS" -ForegroundColor Green; exit 0 } else { Write-Host "FAILURES: $fail" -ForegroundColor Red; exit 1 }