<?php
if (!function_exists('archive_year_bounds')) {
  function archive_year_bounds(int $year): array {
    if ($year < 1000 || $year > 9998) {
      throw new InvalidArgumentException('سنة الأرشفة يجب أن تكون من 1000 إلى 9998');
    }
    return [
      sprintf('%04d-01-01 00:00:00', $year),
      sprintf('%04d-01-01 00:00:00', $year + 1),
    ];
  }

  function archive_year(PDO $db, int $year): array {
    [$from, $to] = archive_year_bounds($year);
    $source = (string)$db->query('SELECT DATABASE()')->fetchColumn();
    if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $source)) {
      throw new RuntimeException('اسم قاعدة البيانات الحالية غير صالح للأرشفة');
    }
    $archive = $source . '_' . $year;
    if (strlen($archive) > 64) {
      throw new RuntimeException('اسم قاعدة الأرشيف أطول من الحد المسموح');
    }
    $src = '`' . $source . '`';
    $dst = '`' . $archive . '`';
    $tables = ['inv', 'inv_sub', 'restrictions', 'restrictions_sub', 'currencies'];

    $db->exec("CREATE DATABASE IF NOT EXISTS $dst CHARACTER SET utf8mb4");
    foreach ($tables as $table) {
      $quoted = '`' . $table . '`';
      $db->exec("CREATE TABLE IF NOT EXISTS $dst.$quoted LIKE $src.$quoted");
    }

    $db->beginTransaction();
    try {
      foreach (['inv_sub', 'restrictions_sub', 'inv', 'restrictions', 'currencies'] as $table) {
        $db->exec("DELETE FROM $dst.`$table`");
      }

      $st = $db->prepare("INSERT INTO $dst.`inv` SELECT * FROM $src.`inv` WHERE `date`>=? AND `date`<?");
      $st->execute([$from, $to]);
      $invoiceCount = $st->rowCount();

      $st = $db->prepare(
        "INSERT INTO $dst.`inv_sub` SELECT s.* FROM $src.`inv_sub` s "
        . "INNER JOIN $src.`inv` i ON i.`proc_id`=s.`proc_id` "
        . "WHERE i.`date`>=? AND i.`date`<?"
      );
      $st->execute([$from, $to]);
      $invoiceLineCount = $st->rowCount();

      $st = $db->prepare("INSERT INTO $dst.`restrictions` SELECT * FROM $src.`restrictions` WHERE `date`>=? AND `date`<?");
      $st->execute([$from, $to]);
      $restrictionCount = $st->rowCount();

      $st = $db->prepare(
        "INSERT INTO $dst.`restrictions_sub` SELECT s.* FROM $src.`restrictions_sub` s "
        . "INNER JOIN $src.`restrictions` r ON r.`id`=s.`res_id` "
        . "WHERE r.`date`>=? AND r.`date`<?"
      );
      $st->execute([$from, $to]);
      $restrictionLineCount = $st->rowCount();

      $db->exec("INSERT INTO $dst.`currencies` SELECT * FROM $src.`currencies`");
      $currencyCount = $db->query("SELECT COUNT(*) FROM $dst.`currencies`")->fetchColumn();
      $db->commit();
    } catch (Throwable $e) {
      if ($db->inTransaction()) $db->rollBack();
      throw $e;
    }

    return [
      'database' => $archive,
      'invoices' => $invoiceCount,
      'invoice_lines' => $invoiceLineCount,
      'restrictions' => $restrictionCount,
      'restriction_lines' => $restrictionLineCount,
      'currencies' => (int)$currencyCount,
    ];
  }
}
