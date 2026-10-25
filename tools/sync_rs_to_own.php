<?php
// tools/sync_rs_to_own.php — كوبري قراءة فقط: RS_RS (SQL Server) → rs_own (MySQL)
// الاستخدام: php sync_rs_to_own.php [--dry] — قراءة فقط من الأصل، لا يكتب فيه أبداً
// يتطلب: sqlcmd + Windows auth على .\SQLEXPRESS (نفس جهاز المحل)
require_once __DIR__ . '/sync_watermark.php';
$dry = in_array('--dry', $argv ?? []);
$invoicesOnly = in_array('--invoices-only', $argv ?? []);
$oneProc = 0;
foreach ($argv ?? [] as $_a) if (preg_match('/^--proc=(\d+)$/', (string)$_a, $_m)) $oneProc = (int)$_m[1];
$SQLCMD = 'C:\Program Files\Microsoft SQL Server\100\Tools\Binn\SQLCMD.EXE';
$SRV = '.\SQLEXPRESS';
$sqlAuth = in_array('--sql-auth', $argv ?? [], true);
$sqlUser = '';
if ($sqlAuth) {
  $sec = @include 'C:\\ProgramData\\RS_Own\\secrets.php';
  if (is_array($sec) && !empty($sec['rs_bridge_user']) && !empty($sec['rs_bridge_pass'])) {
    $sqlUser = (string)$sec['rs_bridge_user'];
    putenv('SQLCMDPASSWORD=' . (string)$sec['rs_bridge_pass']);
  } else {
    $sqlAuth = false;
  }
}
$logf = __DIR__ . '/sync.log';
$log = function ($m) use ($logf) { $l = date('Y-m-d H:i:s') . ' ' . $m . "\n"; @file_put_contents($logf, $l, FILE_APPEND); echo $l; };
if (!function_exists('db')) require __DIR__ . '/../app/lib/db.php';
$db = db();
$hasSyncTable = (bool)$db->query("SHOW TABLES LIKE 'rs_sync'")->fetchColumn();
if (!$dry && !$hasSyncTable) {
  $db->exec("CREATE TABLE rs_sync (k VARCHAR(64) PRIMARY KEY, v TEXT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  $hasSyncTable = true;
}
$get = function ($k, $d = '') use ($db, &$hasSyncTable) {
  if (!$hasSyncTable) return $d;
  $r = $db->prepare("SELECT v FROM rs_sync WHERE k=?"); $r->execute([$k]);
  return ($x = $r->fetch()) ? $x['v'] : $d;
};
$set = function ($k, $v) use ($db) { $db->prepare("INSERT INTO rs_sync (k,v) VALUES (?,?) ON DUPLICATE KEY UPDATE v=VALUES(v)")->execute([$k, $v]); };
// sqlcmd → مصفوفة صفوف (UTF-16 → UTF-8، فاصل ~، بدون هيدر)
function qrs($sql, $log) {
  global $SQLCMD, $SRV, $sqlAuth, $sqlUser;
  $tmp = sys_get_temp_dir() . '/rsq_' . getmypid() . '.txt';
  $auth = $sqlAuth ? '-U "' . $sqlUser . '" ' : '-E ';
  $cmd = '"' . $SQLCMD . '" -S "' . $SRV . '" ' . $auth . '-u -W -k -s"~" -h-1 -Q "SET NOCOUNT ON;' . str_replace('"', '', $sql) . '" -o "' . $tmp . '"';
  exec($cmd, $o, $rc);
  if ($rc !== 0 || !is_file($tmp)) { $log("sqlcmd failed rc=$rc"); return null; }
  $raw = file_get_contents($tmp); @unlink($tmp);
  $txt = mb_convert_encoding($raw, 'UTF-8', 'UTF-16');
  $txt = preg_replace('/^\xEF\xBB\xBF/', '', $txt);
  $rows = [];
  foreach (preg_split('/\r?\n/', $txt) as $ln) {
    if (trim($ln) === '') continue;
    $rows[] = array_map(fn($v) => $v === 'NULL' ? null : $v, explode('~', rtrim($ln)));
  }
  return $rows;
}
// ترتيب الأعمدة المشتركة (بترتيب MySQL) + أعمدة التواريخ من RS
function cols($rsTable, $myTable, $log) {
  global $db;
  $rs = qrs("SELECT COLUMN_NAME,DATA_TYPE FROM RS_RS.INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='$rsTable' ORDER BY ORDINAL_POSITION", $log);
  if (!$rs) return [null, null];
  $rsMap = []; $rsDate = [];
  foreach ($rs as $r) { if (count($r) < 2) continue; $rsMap[strtolower(trim($r[0]))] = trim($r[0]); if (preg_match('/date|time/i', $r[1])) $rsDate[strtolower(trim($r[0]))] = 1; }
  $my = $db->query("DESCRIBE `$myTable`")->fetchAll();
  $sel = []; $ins = [];
  foreach ($my as $c) {
    $f = $c['Field']; $k = strtolower($f);
    if (!isset($rsMap[$k])) { $log("skip $myTable.$f (not in RS)"); continue; }
    $rf = $rsMap[$k];
    $sel[] = isset($rsDate[$k]) ? "CONVERT(varchar(19),[$rf],120) AS [$rf]" : "[$rf]";
    $ins[] = "`$f`";
  }
  return [$sel, $ins];
}
$last = (int)$get('rs_last_proc', 0);
if ($last === 0) $last = (int)$db->query("SELECT IFNULL(MAX(proc_id),0) m FROM inv WHERE IFNULL(branch,1)<>99")->fetch()['m'];
$r = qrs("SELECT MAX(proc_id) FROM RS_RS.dbo.Inv WHERE ISNULL(branch,1)<>99", $log);
$sourceMetaOk = is_array($r) && isset($r[0][0]);
$rsMax = $sourceMetaOk ? (int)trim((string)$r[0][0]) : 0;
$ri = qrs("SELECT MAX(id) FROM RS_RS.dbo.Inv WHERE ISNULL(branch,1)<>99", $log);
$sourceMetaOk = $sourceMetaOk && is_array($ri) && isset($ri[0][0]);
$rsMaxId = is_array($ri) && isset($ri[0][0]) ? (int)trim((string)$ri[0][0]) : 0;
$log("last=$last rsMax=$rsMax" . ($dry ? ' DRY' : ''));
if ($rsMax <= $last) { $log('nothing new'); }
$newIds = [];
$failedProcIds = [];
$invoiceBatchOk = true;
[$selS, $insS] = cols('Inv_Sub', 'inv_sub', $log);
$lineProcIdx = $insS ? array_search('`proc_id`', $insS, true) : false;
if ($oneProc || $rsMax > $last) {
  [$selH, $insH] = cols('Inv', 'inv', $log);
  if (!$selH || !$selS) { $log('cols failed'); exit(1); }
  $procIdx = array_search('`proc_id`', $insH, true);
  $idIdx = array_search('`id`', $insH, true);
  if ($procIdx === false || $idIdx === false || $lineProcIdx === false) {
    $log('required invoice key columns missing; watermark unchanged');
    exit(1);
  }
  $heads = $oneProc
    ? qrs("SELECT " . implode(',', $selH) . " FROM RS_RS.dbo.Inv WHERE proc_id=$oneProc AND ISNULL(branch,1)<>99", $log)
    : qrs("SELECT " . implode(',', $selH) . " FROM RS_RS.dbo.Inv WHERE proc_id>$last AND ISNULL(branch,1)<>99 ORDER BY proc_id", $log);
  if (!is_array($heads)) {
    $log('head fetch failed; watermark will not advance');
    $invoiceBatchOk = false;
    $heads = [];
  } elseif (!$heads) {
    $log('no invoice heads returned despite source maximum advancing; watermark will not advance');
    $invoiceBatchOk = false;
  }
  $nH = count($heads);
  $log("new heads=$nH");
  $added = 0;
  if ($nH) {
    $ids = [];
    foreach ($heads as $h) {
      if (count($h) !== count($insH) || !isset($h[$procIdx], $h[$idIdx]) || !is_numeric($h[$procIdx])) {
        $log('head row invalid; watermark will not advance');
        $invoiceBatchOk = false;
        break;
      }
      $ids[] = (int)$h[$procIdx];
    }
    $lines = null;
    if ($invoiceBatchOk) {
      $in = implode(',', $ids);
      $lines = qrs("SELECT " . implode(',', $selS) . " FROM RS_RS.dbo.Inv_Sub WHERE proc_id IN ($in) ORDER BY proc_id", $log);
      if (!is_array($lines)) {
        $log('detail fetch failed; watermark will not advance');
        $invoiceBatchOk = false;
      } else {
        $requestedProcIds = array_fill_keys($ids, true);
        foreach ($lines as $ln) {
          if (count($ln) !== count($insS)
            || !isset($ln[$lineProcIdx])
            || !is_numeric($ln[$lineProcIdx])
            || !isset($requestedProcIds[(int)$ln[$lineProcIdx]])) {
            $log('detail row invalid; watermark will not advance');
            $invoiceBatchOk = false;
            break;
          }
        }
      }
    }
    $byProc = [];
    if ($invoiceBatchOk) foreach ($lines as $ln) $byProc[(int)$ln[$lineProcIdx]][] = $ln;
    if ($invoiceBatchOk && !$dry) {
      $stH = $db->prepare("INSERT INTO inv (" . implode(',', $insH) . ") VALUES (" . rtrim(str_repeat('?,', count($insH)), ',') . ")");
      $stS = $db->prepare("INSERT INTO inv_sub (" . implode(',', $insS) . ") VALUES (" . rtrim(str_repeat('?,', count($insS)), ',') . ")");
    // رقم شاغر من مسلسلنا (يتجاوز أي رقم مستعمل عندنا أو عند المهندس)
    $freeFrom = function (string $seq) use ($db) {
      $col = $seq === 'seq_proc' ? 'proc_id' : 'id';
      for ($i = 0; $i < 10000; $i++) {
        $db->exec("UPDATE rs_seq SET val=val+1 WHERE name='" . $seq . "'");
        $v = (int)$db->query("SELECT val FROM rs_seq WHERE name='" . $seq . "'")->fetchColumn();
        $q = $db->prepare("SELECT 1 FROM inv WHERE `$col`=? LIMIT 1"); $q->execute([$v]);
        if (!$q->fetch()) return $v;
      }
      throw new RuntimeException('seq: لا يوجد رقم شاغر');
    };
    // إعادة ترقيم فاتورة لنا احتلت رقماً جديداً من فواتير المهندس (حتى لا تضيع فاتورته)
    $renumber = function (int $oldP, int $oldI) use ($db, $log, $freeFrom) {
      $np = $freeFrom('seq_proc'); $ni = $freeFrom('seq_inv');
      $db->beginTransaction();
      try {
        $db->prepare("UPDATE inv SET proc_id=?, id=? WHERE proc_id=? AND branch=99")->execute([$np, $ni, $oldP]);
        foreach (['inv_sub', '_orphan_sub', 'sandd', 'sandq', 'twreed_srf_sub'] as $t)
          $db->prepare("UPDATE `$t` SET proc_id=? WHERE proc_id=?")->execute([$np, $oldP]);
        $db->prepare("UPDATE loyalty_log SET inv_id=? WHERE inv_id=?")->execute([$ni, $oldI]);
        $db->commit();
        $log("RENUMBER proc=$oldP->$np id=$oldI->$ni");
      } catch (Throwable $t) {
        if ($db->inTransaction()) $db->rollBack();
        throw $t;
      }
    };
    foreach ($heads as $h) {
      $proc = (int)$h[$procIdx];
      $hisId = (int)($h[$idIdx] ?? 0);
      $ex = $db->prepare("SELECT 1 FROM inv WHERE proc_id=? AND IFNULL(branch,1)<>99"); $ex->execute([$proc]);
      if ($ex->fetch()) continue;                       // فاتورة المهندس مستوردة أصلاً
      $skip = false;
      for ($g = 0; $g < 50; $g++) {                     // تصادم مع فاتورة لنا (فرع 99)
        $cf = $db->prepare("SELECT id, proc_id FROM inv WHERE (proc_id=? OR id=?) AND branch=99 LIMIT 1");
        $cf->execute([$proc, $hisId]);
        if (!($own = $cf->fetch())) break;
        try { $renumber((int)$own['proc_id'], (int)$own['id']); }
        catch (Throwable $t) { $log("renumber fail proc=$proc " . substr($t->getMessage(), 0, 120)); $skip = true; break; }
      }
      if ($skip) {
        $failedProcIds[] = $proc;
        continue;
      }
      try {
        $db->beginTransaction();
        $stH->execute($h);
        foreach ($byProc[$proc] ?? [] as $ln) $stS->execute($ln);
        $db->commit();
        $added++; $newIds[] = $proc;
      } catch (Throwable $t) {
        if ($db->inTransaction()) $db->rollBack();
        $failedProcIds[] = $proc;
        $log("fail proc=$proc " . substr($t->getMessage(), 0, 120));
      }
    }
    }
    $log("added=$added");
  } elseif ($dry) {
    $log('would add heads: ' . $nH);
  }
}
// حذف/تعديل آخر 7 أيام (رؤوس فقط + بنود المتغير tot)
$chg = qrs("SELECT proc_id,IS_Deleted,tot_net,paid,IS_Temp FROM RS_RS.dbo.Inv WHERE proc_id<=$last AND ISNULL(branch,1)<>99 AND ([date]>=DATEADD(day,-7,GETDATE()) OR IS_Deleted=1)", $log);
$upd = 0;
$headDiff = [];
$localProcs = [];
if (is_array($chg)) foreach ($chg as $c) {
  if (count($c) < 5) continue;
  [$p, $del, $net, $paid, $tmp] = [(int)$c[0], (int)$c[1], (float)$c[2], (float)$c[3], (int)$c[4]];
  $l = $db->prepare("SELECT IS_Deleted,tot_net,paid,IS_Temp FROM inv WHERE proc_id=? AND IFNULL(branch,1)<>99"); $l->execute([$p]);
  $row = $l->fetch();
  if (!$row) continue;
  $localProcs[$p] = true;
  if ((int)$row['IS_Deleted'] !== $del || (float)$row['tot_net'] !== $net || (float)$row['paid'] !== $paid || (int)$row['IS_Temp'] !== $tmp) {
    $log("diff proc=$p rs(del=$del,net=$net) local(del={$row['IS_Deleted']},net={$row['tot_net']})");
    $headDiff[$p] = true;
    if (!$dry) {
      $db->prepare("UPDATE inv SET IS_Deleted=?, tot_net=?, paid=?, IS_Temp=? WHERE proc_id=? AND IFNULL(branch,1)<>99")->execute([$del, $net, $paid, $tmp, $p]);
      $upd++;
    } else $upd++;
  }
}
$log("updated=$upd");
// ---- تحديث بنود الفواتير المعدّلة خلال آخر 7 أيام (حذف + إعادة إدخال من RS) ----
$linesFixed = 0;
if ($localProcs && $selS && $insS && $lineProcIdx !== false) {
  $pids = array_keys($localProcs);
  $need = $headDiff;
  $sigOk = true;
  foreach (array_chunk($pids, 300) as $chunk) {
    $in = implode(',', array_map('intval', $chunk));
    $rsg = qrs("SELECT proc_id, COUNT(*), ISNULL(SUM(val),0) FROM RS_RS.dbo.Inv_Sub WHERE proc_id IN ($in) GROUP BY proc_id", $log);
    if (!is_array($rsg)) { $log('line signature fetch failed'); $sigOk = false; break; }
    $rssig = [];
    foreach ($rsg as $g) {
      if (count($g) < 3) continue;
      $rssig[(int)trim((string)$g[0])] = [(int)trim((string)$g[1]), (float)trim((string)$g[2])];
    }
    $locsig = [];
    foreach ($db->query("SELECT proc_id, COUNT(*) c, IFNULL(SUM(val),0) sv FROM inv_sub WHERE proc_id IN ($in) GROUP BY proc_id")->fetchAll() as $g)
      $locsig[(int)$g['proc_id']] = [(int)$g['c'], (float)$g['sv']];
    foreach ($chunk as $p) {
      if (isset($need[$p])) continue;
      $a = $rssig[$p] ?? [0, 0.0];
      $b = $locsig[$p] ?? [0, 0.0];
      if ($a[0] !== $b[0] || abs($a[1] - $b[1]) > 0.0001) $need[$p] = true;
    }
  }
  if (!$sigOk) {
    $log('lines refresh skipped (signature query failed)');
  } elseif ($need) {
    if ($dry) {
      $linesFixed = count($need);
      $log('would refresh lines for: ' . implode(',', array_keys($need)));
    } else {
      $needSet = $need;
      $byProc = [];
      $linesOk = true;
      foreach (array_chunk(array_keys($need), 300) as $chunk) {
        $in = implode(',', array_map('intval', $chunk));
        $lines = qrs("SELECT " . implode(',', $selS) . " FROM RS_RS.dbo.Inv_Sub WHERE proc_id IN ($in) ORDER BY proc_id", $log);
        if (!is_array($lines)) { $linesOk = false; break; }
        foreach ($lines as $ln) {
          $lp = (isset($ln[$lineProcIdx]) && is_numeric($ln[$lineProcIdx])) ? (int)$ln[$lineProcIdx] : 0;
          if (count($ln) !== count($insS) || !$lp || !isset($needSet[$lp])) { $linesOk = false; break 2; }
          $byProc[$lp][] = $ln;
        }
      }
      if (!$linesOk) {
        $log('lines refresh aborted (bad source data)');
      } else {
        $db->beginTransaction();
        try {
          $delS = $db->prepare("DELETE FROM inv_sub WHERE proc_id=?");
          $insSst = $db->prepare("INSERT INTO inv_sub (" . implode(',', $insS) . ") VALUES (" . rtrim(str_repeat('?,', count($insS)), ',') . ")");
          foreach (array_keys($need) as $p) {
            $delS->execute([$p]);
            foreach (($byProc[$p] ?? []) as $ln) $insSst->execute($ln);
            $linesFixed++;
          }
          $db->commit();
          foreach (array_keys($need) as $p) $log("lines refreshed proc=$p count=" . count($byProc[$p] ?? []));
        } catch (Throwable $t) {
          $db->rollBack();
          $linesFixed = 0;
          $log('lines refresh failed: ' . $t->getMessage());
        }
      }
    }
  }
}
$log("lines_refreshed=$linesFixed");
// ---- مزامنة الأصناف (Currencies → currencies): جديد + تعديلات كاملة + حذف ----
if (!$invoicesOnly) {
[$selC, $insC] = cols('Currencies', 'currencies', $log);
if (!$selC) {
  $log('item cols failed');
} else {
  $all = qrs("SELECT " . implode(',', $selC) . " FROM RS_RS.dbo.Currencies ORDER BY id", $log);
  if (!is_array($all)) {
    $log('item fetch failed');
  } else {
    $have = array_map('intval', $db->query("SELECT id FROM currencies")->fetchAll(PDO::FETCH_COLUMN));
    $haveSet = array_flip($have);
    $idPos = array_search('`id`', $insC); if ($idPos === false) $idPos = 0;
    $colsNoId = array_values(array_diff($insC, ['`id`', '`barcode`']));
    $updSql = implode(', ', array_map(fn($c) => "$c=VALUES($c)", $colsNoId));
    $stC = $db->prepare("INSERT INTO currencies (" . implode(',', $insC) . ") VALUES ("
      . rtrim(str_repeat('?,', count($insC)), ',') . ") ON DUPLICATE KEY UPDATE " . $updSql);
    $hasBridgeMap = (bool)$db->query("SHOW TABLES LIKE 'bridge_map'")->fetchColumn();
    $bmq = $hasBridgeMap ? $db->prepare("SELECT remote_id FROM bridge_map WHERE kind='item' AND local_id=? LIMIT 1") : null;
    $itNew = $itUpd = $itFail = 0;
    $rsIds = [];
    foreach ($all as $r) {
      if (count($r) !== count($insC)) { $itFail++; continue; }
      $rid = (int)$r[$idPos]; $rsIds[] = $rid;
      if (isset($haveSet[$rid])) {
        if ($bmq) {
          $bmq->execute([$rid]); $bm = $bmq->fetch();
          if ($bm && (int)$bm['remote_id'] !== $rid) { $itUpd++; $log("item collision id=$rid mapped=$bm[remote_id]"); continue; }
        }
        $itUpd++;
      } else $itNew++;
      if ($dry) continue;
      try { $stC->execute($r); } catch (Throwable $t) { $itFail++; $log("item fail id=$rid " . substr($t->getMessage(), 0, 120)); }
    }
    // أصناف عندنا ومش عنده — نسجّلها فقط (بياناتنا لا تُمسح)
    $orph = array_values(array_diff($have, $rsIds));
    if ($orph) $log('local-only items: ' . count($orph) . (count($orph) <= 12 ? ' [' . implode(',', $orph) . ']' : ''));
    $log("items new=$itNew upd=$itUpd fail=$itFail" . ($dry ? ' DRY' : ''));
  }
}
}
if (!$dry) {
  if ($oneProc) {
    $log("backfill --proc=$oneProc: sync markers untouched");
  } else {
  if ($sourceMetaOk) {
    if ($rsMax > $last) {
      $safeWatermark = $invoiceBatchOk
        ? sync_retry_watermark($last, $rsMax, $failedProcIds)
        : $last;
      if ($safeWatermark > $last) $set('rs_last_proc', (string)$safeWatermark);
      if ($safeWatermark < $rsMax) $log("watermark held at $safeWatermark; retry starts after $safeWatermark");
    }
    $set('rs_max_id', (string)$rsMaxId);
  } else {
    $log('source metadata fetch failed; synchronization markers unchanged');
  }
  $set('rs_last_run', date('Y-m-d H:i:s'));
  $set('rs_last_added', (string)count($newIds));
  }
  if (!$invoicesOnly || count($newIds) > 0 || $linesFixed > 0 || $upd > 0) {
  $db->exec("REPLACE INTO item_bal SELECT s.currency_from, ROUND(SUM(CASE WHEN (i.IS_Buy=1 AND i.proc_type=1) OR i.proc_type=3 OR (i.IS_Buy=0 AND i.proc_type=2) THEN s.val ELSE -s.val END), 4), NOW() FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id AND i.IS_Buy=IF(s.proc_type=1,1,0) WHERE i.IS_Deleted=0 AND IFNULL(i.IS_Temp,0)=0 AND s.proc_type IN (1,2) GROUP BY s.currency_from");
  $db->exec("INSERT IGNORE INTO item_bal (id, bal, updated) SELECT id, 0, NOW() FROM currencies");
  $log('item_bal refreshed');
  }
}
$log('done');
