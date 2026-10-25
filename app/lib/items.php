<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/eo_item.php';
require_once __DIR__ . '/audit.php';

if (!function_exists('item_next_code')) {
  function item_next_code(): int {
    return (int)db()->query("SELECT IFNULL(MAX(id),0)+1 FROM currencies")->fetchColumn();
  }

  function item_seq_sync(int $code): void {
    try {
      db()->prepare("INSERT INTO rs_seq (name,val) VALUES ('tbl_currencies_id',?) ON DUPLICATE KEY UPDATE val=GREATEST(val,VALUES(val))")
        ->execute([$code]);
    } catch (Throwable $t) {}
  }

  function item_save(array $in, int $id = 0): array {
    $db = db();
    $name = mb_substr(trim((string)($in['name'] ?? '')), 0, 255);
    if ($name === '') return ['ok' => false, 'error' => 'اسم الصنف مطلوب'];

    $f = [
      'symbol' => $name,
      'nameEN' => mb_substr(trim((string)($in['en'] ?? '')), 0, 255),
      'barcode' => trim((string)($in['barcode'] ?? '')),
      'group_id' => (int)($in['grp'] ?? 0),
      'unit' => (int)($in['unit'] ?? 1),
      'purch_price' => (float)($in['cost'] ?? 0),
      'sale_price' => (float)($in['sale'] ?? 0),
      'whole_price' => (float)($in['whole'] ?? ($in['sale'] ?? 0)),
      'discount' => (float)($in['disc'] ?? 0),
      'tax' => (float)($in['tax'] ?? 0),
      'limit' => (float)($in['lim'] ?? 0),
      'minprice' => (float)($in['minp'] ?? 0),
      'notes' => mb_substr(trim((string)($in['notes'] ?? '')), 0, 255),
      'type' => (int)($in['type'] ?? 1),
    ];
    if ($f['barcode'] === '') $f['barcode'] = '0';

    $imgData = trim((string)($in['img'] ?? ''));
    $eoUp = !empty($in['eo']);
    $out = [];

    if ($id > 0) {
      $cols = 'symbol=?, nameEN=?, barcode=?, group_id=?, unit=?, purch_price=?, sale_price=?, whole_price=?, discount=?, tax=?, `limit`=?, minprice=?, notes=?, type=?';
      $db->prepare("UPDATE currencies SET $cols WHERE id=?")->execute([...array_values($f), $id]);
      $out = ['ok' => true, 'id' => $id, 'edited' => true];
      try { audit_log('edit', 'currencies', $id, 'sale=' . $f['sale_price'] . ' cost=' . $f['purch_price']); } catch (Throwable $t) {}
    } else {
      $code = 0;
      $cols = 'id, symbol, nameEN, barcode, group_id, unit, purch_price, sale_price, whole_price, discount, tax, `limit`, minprice, notes, type, IS_Deleted';
      $ph = '?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,0';
      for ($try = 1; $try <= 5; $try++) {
        $code = item_next_code();
        try {
          $db->prepare("INSERT INTO currencies ($cols) VALUES ($ph)")->execute([$code, ...array_values($f)]);
          break;
        } catch (Throwable $e) {
          if ($try === 5 || stripos($e->getMessage(), 'Duplicate') === false) throw $e;
          usleep(120000);
        }
      }
      item_seq_sync($code);
      $id = $code;
      $out = ['ok' => true, 'id' => $code, 'edited' => false];
      try { audit_log('add', 'currencies', $code, $name); } catch (Throwable $t) {}
    }

    if ($imgData !== '') {
      $ir = eo_item_image_store($db, $id, $imgData);
      $out['img'] = $ir;
      if (!$ir['ok']) $out['img_error'] = $ir['error'];
    }
    if ($eoUp) {
      $sk = (string)$db->query("SELECT web_sku FROM currencies WHERE id=" . (int)$id)->fetchColumn();
      if ($sk !== '' && $imgData === '') {
        $out['eo'] = ['ok' => true, 'skipped' => true, 'id' => $sk, 'note' => 'الصنف مرفوع بالفعل'];
      } else {
        $er = eo_item_upload($db, $id);
        $out['eo'] = $er;
        if (!$er['ok']) $out['eo_error'] = $er['error'];
      }
    }
    return $out;
  }

  function item_price_save(int $id, array $in): array {
    if ($id <= 0) return ['ok' => false, 'error' => 'صنف غير موجود'];
    $sale = (float)($in['sale'] ?? 0);
    $purch = (float)($in['purch'] ?? 0);
    $whole = array_key_exists('whole', $in) ? (float)$in['whole'] : $sale;
    $bc = trim((string)($in['barcode'] ?? ''));
    if ($bc === '') $bc = '0';
    $bc = mb_substr($bc, 0, 64);
    $st = db()->prepare("UPDATE currencies SET sale_price=?, purch_price=?, whole_price=?, barcode=? WHERE id=? AND IS_Deleted=0");
    $st->execute([$sale, $purch, $whole, $bc, $id]);
    if ($st->rowCount() === 0) {
      $chk = db()->prepare("SELECT COUNT(*) FROM currencies WHERE id=? AND IS_Deleted=0");
      $chk->execute([$id]);
      if (!(int)$chk->fetchColumn()) return ['ok' => false, 'error' => 'الصنف غير موجود أو محذوف'];
    }
    try { audit_log('edit', 'currencies', $id, 'quick sale=' . $sale . ' cost=' . $purch); } catch (Throwable $t) {}
    return ['ok' => true, 'id' => $id];
  }

  /**
   * تحديث رصيد المخزون اللحظي في item_bal للأصناف المحددة فوراً داخل الترانزاكشن
   */
  function stock_recalc_items(PDO $db, array $itemIds): void {
    $itemIds = array_values(array_unique(array_filter(array_map('intval', $itemIds), fn($id) => $id > 0)));
    if (!$itemIds) return;
    $in = implode(',', $itemIds);
    $sql = "INSERT INTO item_bal (id, bal, updated)
            SELECT 
              c.id, 
              ROUND(IFNULL(SUM(CASE 
                WHEN i.proc_id IS NULL THEN 0
                WHEN (i.IS_Buy=1 AND i.proc_type=1) OR i.proc_type=3 OR (i.IS_Buy=0 AND i.proc_type=2) THEN s.val 
                ELSE -s.val 
              END), 0), 4),
              NOW()
            FROM currencies c
            LEFT JOIN inv_sub s ON s.currency_from = c.id
            LEFT JOIN inv i ON i.proc_id = s.proc_id AND i.IS_Buy = IF(s.proc_type=1, 1, 0) AND i.IS_Deleted = 0 AND IFNULL(i.IS_Temp, 0) = 0 AND s.proc_type IN (1, 2)
            WHERE c.id IN ($in)
            GROUP BY c.id
            ON DUPLICATE KEY UPDATE bal = VALUES(bal), updated = NOW()";
    $db->exec($sql);
  }
}
