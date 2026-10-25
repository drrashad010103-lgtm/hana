<?php
require_once __DIR__ . '/db.php';

if (!function_exists('loyalty_earn')) {
  function loyalty_earn(int $cust, int $pts, int $inv = 0, string $note = 'شراء'): bool {
    if ($cust <= 0 || $pts <= 0) return false;
    $db = db();
    $st = $db->prepare("INSERT INTO loyalty (cust_id, points, total_earned, total_redeemed) VALUES (?,?,?,0)
      ON DUPLICATE KEY UPDATE points=points+VALUES(points), total_earned=total_earned+VALUES(total_earned)");
    $st->execute([$cust, $pts, $pts]);
    $db->prepare("INSERT INTO loyalty_log (cust_id, points, type, inv_id, note) VALUES (?,?,'earn',?,?)")
      ->execute([$cust, $pts, $inv, mb_substr($note, 0, 128)]);
    return true;
  }

  function loyalty_redeem(int $cust, int $pts, int $inv = 0, string $note = 'استبدال'): bool {
    if ($cust <= 0 || $pts <= 0) return false;
    $db = db();
    $st = $db->prepare("UPDATE loyalty SET points=points-?, total_redeemed=total_redeemed+? WHERE cust_id=? AND points>=?");
    $st->execute([$pts, $pts, $cust, $pts]);
    if ($st->rowCount() === 0) return false;
    $db->prepare("INSERT INTO loyalty_log (cust_id, points, type, inv_id, note) VALUES (?,?,'redeem',?,?)")
      ->execute([$cust, $pts, $inv, mb_substr($note, 0, 128)]);
    return true;
  }

  function loyalty_adjust(int $cust, int $pts, string $note = 'تعديل يدوي'): bool {
    if ($cust <= 0 || $pts === 0) return false;
    $db = db();
    if ($pts > 0) {
      $st = $db->prepare("INSERT INTO loyalty (cust_id, points, total_earned, total_redeemed) VALUES (?,?,?,0)
        ON DUPLICATE KEY UPDATE points=points+VALUES(points), total_earned=total_earned+VALUES(total_earned)");
      $st->execute([$cust, $pts, $pts]);
    } else {
      $st = $db->prepare("INSERT INTO loyalty (cust_id, points, total_earned, total_redeemed) VALUES (?,0,0,0)
        ON DUPLICATE KEY UPDATE points=GREATEST(0, points+?)");
      $st->execute([$cust, $pts]);
    }
    $db->prepare("INSERT INTO loyalty_log (cust_id, points, type, note) VALUES (?,?,'adjust',?)")
      ->execute([$cust, $pts, mb_substr($note, 0, 128)]);
    return true;
  }

  function loyalty_zero(int $cust, string $note = 'تصفير نقاط'): int {
    if ($cust <= 0) return 0;
    $db = db();
    $st = $db->prepare("SELECT IFNULL(points,0) FROM loyalty WHERE cust_id=?");
    $st->execute([$cust]);
    $old = (int)$st->fetchColumn();
    $db->prepare("INSERT INTO loyalty (cust_id, points, total_earned, total_redeemed) VALUES (?,0,0,0)
      ON DUPLICATE KEY UPDATE points=0")->execute([$cust]);
    $db->prepare("INSERT INTO loyalty_log (cust_id, points, type, note) VALUES (?,?,'adjust',?)")
      ->execute([$cust, -$old, mb_substr($note, 0, 128)]);
    return $old;
  }

  function loyalty_enroll_all(): array {
    $db = db();
    $db->exec("INSERT INTO loyalty (cust_id, points, total_earned, total_redeemed, updated_at)
      SELECT c.id, 0, 0, 0, NOW() FROM customers c
      WHERE IFNULL(c.IS_Deleted,0)=0
        AND NOT EXISTS (SELECT 1 FROM loyalty l WHERE l.cust_id=c.id)");
    $total = (int)$db->query("SELECT COUNT(*) FROM customers c WHERE IFNULL(c.IS_Deleted,0)=0")->fetchColumn();
    $dupes = (int)$db->query("SELECT COUNT(*) - COUNT(DISTINCT cust_id) FROM loyalty")->fetchColumn();
    return ['total' => $total, 'dupes' => $dupes];
  }
}
