<?php
// lib/wa.php — ربط واتساب عبر wa.me (بلا بوابة — يعمل على إنفينيتي والموبايل)
function wa_num($raw): string {
  $d = preg_replace('/\D/', '', (string)$raw);
  if (str_starts_with($d, '002')) $d = substr($d, 3);
  if (preg_match('/^01\d{9}$/', $d)) return '2' . $d; // مصر: 01xxxxxxxxx ← 201xxxxxxxxx
  if (preg_match('/^1\d{9}$/', $d)) return '20' . $d;
  return $d;
}
function wa_link($raw, string $msg): string {
  return 'https://wa.me/' . wa_num($raw) . '?text=' . urlencode($msg);
}
function wa_share(string $msg): string {
  return 'https://wa.me/?text=' . urlencode($msg);
}
function cust_balance(int $cid): float {
  try {
    $r = db()->prepare("SELECT SUM(IF(IS_Buy=0,tot_net-paid,0))-SUM(IF(IS_Buy=1,tot_net-paid,0)) b FROM inv WHERE cust_id=? AND IS_Deleted=0");
    $r->execute([$cid]);
    return (float)($r->fetch()['b'] ?? 0);
  } catch (Throwable $t) { return 0.0; }
}
