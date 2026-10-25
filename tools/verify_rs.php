<?php
// tools/verify_rs.php — فحص مطابقة rs_own مع برنامج المهندس (RS_RS) بدون أي كتابة
// يكتب: reports/verify_YYYYmmdd_HHMMSS.txt + status/verify.json
date_default_timezone_set('Africa/Cairo');
$SQLCMD = 'C:\Program Files\Microsoft SQL Server\100\Tools\Binn\SQLCMD.EXE';
$SRV = '.\SQLEXPRESS';
$ROOT = 'D:/RS_Own';
if (!is_dir($ROOT . '/reports')) @mkdir($ROOT . '/reports', 0775, true);
if (!is_dir($ROOT . '/status')) @mkdir($ROOT . '/status', 0775, true);
require_once $ROOT . '/app/lib/db.php';
$db = db();
$out = [];
$say = function (string $m) use (&$out) { $out[] = $m; };

function qrs_v(string $sql, string $SQLCMD, string $SRV): ?array {
  $tmp = sys_get_temp_dir() . '/rsv_' . getmypid() . '.txt';
  $cmd = '"' . $SQLCMD . '" -S "' . $SRV . '" -E -u -W -k -s"~" -h-1 -Q "SET NOCOUNT ON;' . str_replace('"', '', $sql) . '" -o "' . $tmp . '"';
  exec($cmd, $o, $rc);
  if ($rc !== 0 || !is_file($tmp)) return null;
  $raw = (string)file_get_contents($tmp);
  @unlink($tmp);
  $txt = mb_convert_encoding($raw, 'UTF-8', 'UTF-16');
  $txt = preg_replace('/^\xEF\xBB\xBF/', '', $txt);
  $rows = [];
  foreach (preg_split('/\r?\n/', (string)$txt) as $ln) {
    if (trim($ln) === '') continue;
    $rows[] = array_map(fn($v) => $v === 'NULL' ? null : trim((string)$v), explode('~', rtrim((string)$ln)));
  }
  return $rows;
}

$ok = true;
$issues = [];

// 1) رؤوس الفواتير (كل الأنواع، بدون branch 99)
$rsH = qrs_v("SELECT proc_type, IS_Buy, IS_Deleted, ISNULL(IS_Temp,0), COUNT(*), ISNULL(SUM(tot_net),0) FROM RS_RS.dbo.Inv WHERE ISNULL(branch,1)<>99 GROUP BY proc_type, IS_Buy, IS_Deleted, ISNULL(IS_Temp,0)", $SQLCMD, $SRV);
$loH = $db->query("SELECT proc_type, IS_Buy, IS_Deleted, IFNULL(IS_Temp,0) t, COUNT(*) c, IFNULL(SUM(tot_net),0) s FROM inv WHERE IFNULL(branch,1)<>99 GROUP BY proc_type, IS_Buy, IS_Deleted, IFNULL(IS_Temp,0)")->fetchAll(PDO::FETCH_ASSOC);
if (!is_array($rsH)) {
  $say('❌ تعذر الاتصال ببرنامج المهندس (sqlcmd) — الفحص لم يكتمل');
  $ok = false; $issues[] = 'sqlcmd';
} else {
  $key = fn($pt, $buy, $del, $tmp) => "$pt|$buy|$del|$tmp";
  $rsM = [];
  foreach ($rsH as $r) if (count($r) >= 6) $rsM[$key((int)$r[0], (int)$r[1], (int)$r[2], (int)$r[3])] = [(int)$r[4], (float)$r[5]];
  $loM = [];
  foreach ($loH as $r) $loM[$key((int)$r['proc_type'], (int)$r['IS_Buy'], (int)$r['IS_Deleted'], (int)$r['t'])] = [(int)$r['c'], (float)$r['s']];
  $say('— رؤوس الفواتير (النوع|شراء|محذوف|مؤقت → عدد / إجمالي)');
  $allKeys = array_unique(array_merge(array_keys($rsM), array_keys($loM)));
  sort($allKeys);
  $headDiff = 0;
  foreach ($allKeys as $k) {
    $a = $rsM[$k] ?? [0, 0.0];
    $b = $loM[$k] ?? [0, 0.0];
    $same = ($a[0] === $b[0]) && (abs($a[1] - $b[1]) < 0.01);
    if (!$same) { $headDiff++; $issues[] = "heads $k"; }
    $say(($same ? '✅ ' : '❌ ') . $k . ' → مهندس ' . $a[0] . ' / ' . number_format($a[1], 2, '.', ',') . ' | عندنا ' . $b[0] . ' / ' . number_format($b[1], 2, '.', ','));
  }
  if ($headDiff === 0) $say('✅ الرؤوس مطابقة تماماً (' . array_sum(array_column($rsM, 0)) . ' فاتورة)');
}

// 2) البنود (عدد + مجموع الكميات) لكل نوع
$rsD = qrs_v("SELECT i.proc_type, i.IS_Buy, COUNT(*), ISNULL(SUM(s.val),0) FROM RS_RS.dbo.Inv_Sub s JOIN RS_RS.dbo.Inv i ON i.proc_id=s.proc_id WHERE ISNULL(i.branch,1)<>99 GROUP BY i.proc_type, i.IS_Buy", $SQLCMD, $SRV);
$loD = $db->query("SELECT i.proc_type, i.IS_Buy, COUNT(*) c, IFNULL(SUM(s.val),0) q FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id WHERE IFNULL(i.branch,1)<>99 GROUP BY i.proc_type, i.IS_Buy")->fetchAll(PDO::FETCH_ASSOC);
if (is_array($rsD)) {
  $rsM = []; foreach ($rsD as $r) if (count($r) >= 4) $rsM[$r[0] . '|' . $r[1]] = [(int)$r[2], (float)$r[3]];
  $loM = []; foreach ($loD as $r) $loM[$r['proc_type'] . '|' . $r['IS_Buy']] = [(int)$r['c'], (float)$r['q']];
  $say('— البنود (النوع|شراء → عدد / مجموع الكميات)');
  $linesDiff = 0;
  foreach (array_unique(array_merge(array_keys($rsM), array_keys($loM))) as $k) {
    $a = $rsM[$k] ?? [0, 0.0]; $b = $loM[$k] ?? [0, 0.0];
    $same = ($a[0] === $b[0]) && (abs($a[1] - $b[1]) < 0.0001);
    if (!$same) { $linesDiff++; $issues[] = "lines $k"; }
    $say(($same ? '✅ ' : '❌ ') . $k . ' → مهندس ' . $a[0] . ' / ' . $a[1] . ' | عندنا ' . $b[0] . ' / ' . $b[1]);
  }
  if ($linesDiff === 0) $say('✅ البنود مطابقة تماماً');
} else $say('⚠️ تعذر فحص البنود');

// 3) أرصدة الأصناف (نفس معادلة المهندس)
$sqlBal = "SELECT s.currency_from, ROUND(SUM(CASE WHEN (i.IS_Buy=1 AND i.proc_type=1) OR i.proc_type=3 OR (i.IS_Buy=0 AND i.proc_type=2) THEN s.val ELSE -s.val END),4) b FROM RS_RS.dbo.Inv_Sub s JOIN RS_RS.dbo.Inv i ON i.proc_id=s.proc_id AND i.IS_Buy=(CASE WHEN s.proc_type=1 THEN 1 ELSE 0 END) WHERE i.IS_Deleted=0 AND ISNULL(i.IS_Temp,0)=0 AND s.proc_type IN (1,2) GROUP BY s.currency_from";
$rsB = qrs_v($sqlBal, $SQLCMD, $SRV);
if (is_array($rsB)) {
  $rsMap = []; foreach ($rsB as $r) if (count($r) >= 2) $rsMap[(int)$r[0]] = (float)$r[1];
  $loMap = [];
  foreach ($db->query("SELECT id, bal FROM item_bal")->fetchAll(PDO::FETCH_ASSOC) as $r) $loMap[(int)$r['id']] = (float)$r['bal'];
  $diff = [];
  foreach (array_unique(array_merge(array_keys($rsMap), array_keys($loMap))) as $id) {
    $a = $rsMap[$id] ?? 0.0; $b = $loMap[$id] ?? 0.0;
    if (abs($a - $b) > 0.0001) $diff[$id] = [$a, $b];
  }
  $say('— أرصدة الأصناف: ' . count($rsMap) . ' صنف عند المهندس / ' . count($loMap) . ' عندنا → فروق: ' . count($diff));
  if ($diff) {
    $ok = false; $issues[] = 'balances:' . count($diff);
    $i = 0;
    foreach ($diff as $id => $v) { $say("❌ صنف $id → مهندس {$v[0]} | عندنا {$v[1]}"); if (++$i >= 20) { $say('… (أول 20 فرق فقط)'); break; } }
  } else $say('✅ كل الأرصدة مطابقة');
} else $say('⚠️ تعذر فحص الأرصدة');

if ($issues) $ok = false;
$say('');
$say($ok ? 'النتيجة النهائية: ✅ مطابق' : 'النتيجة النهائية: ❌ فيه فروق — التفاصيل فوق');
$text = 'فحص مطابقة rs_own مع المهندس — ' . date('Y-m-d H:i:s') . "\n" . implode("\n", $out) . "\n";
$stamp = date('Ymd_His');
@file_put_contents($ROOT . '/reports/verify_' . $stamp . '.txt', $text);
@file_put_contents($ROOT . '/reports/verify_latest.txt', $text);
@file_put_contents($ROOT . '/status/verify.json', json_encode(['ts' => date('c'), 'ok' => $ok, 'issues' => $issues], JSON_UNESCAPED_UNICODE));
echo $text;
exit($ok ? 0 : 2);
