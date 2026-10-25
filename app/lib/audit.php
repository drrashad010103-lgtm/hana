<?php
// lib/audit.php — سجل تدقيق العمليات الحساسة (فواتير/سندات)
// القاعدة: ما يرميش أخطاء أبدًا — فشل الكتابة ما يكسرش العملية الأساسية
if (!function_exists('db')) require_once __DIR__ . '/db.php';

function audit_ensure(): void {
  static $done = false;
  if ($done) return;
  $exists = db()->query("SHOW TABLES LIKE 'audit_log'")->fetchColumn();
  if ($exists === false) {
    db()->exec("CREATE TABLE IF NOT EXISTS audit_log (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    ts DATETIME NOT NULL,
    uid INT NOT NULL DEFAULT 0,
    uname VARCHAR(64) NOT NULL DEFAULT '',
    action VARCHAR(16) NOT NULL,
    entity VARCHAR(32) NOT NULL,
    entity_id BIGINT NOT NULL DEFAULT 0,
    detail VARCHAR(500) NOT NULL DEFAULT '',
    ip VARCHAR(45) NOT NULL DEFAULT '',
    KEY audit_ts (ts),
    KEY audit_ent (entity, entity_id),
    KEY audit_uid (uid)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  }
  $done = true;
}

function audit_log(string $action, string $entity, int $entityId, string $detail = ''): void {
  try {
    audit_ensure();
    db()->prepare("INSERT INTO audit_log (ts, uid, uname, action, entity, entity_id, detail, ip) VALUES (NOW(),?,?,?,?,?,?,?)")
      ->execute([
        (int)($_SESSION['uid'] ?? 0),
        mb_substr((string)($_SESSION['u'] ?? ''), 0, 64),
        mb_substr($action, 0, 16),
        mb_substr($entity, 0, 32),
        $entityId,
        mb_substr($detail, 0, 500),
        mb_substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
      ]);
  } catch (Throwable $t) {
    // سجل التدقيق خدمة مساعدة — نكتملها ولا نكسر الحفظ
  }
}
