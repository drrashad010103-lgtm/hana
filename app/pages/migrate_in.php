<?php
ini_set('display_errors', '0');
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/push.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'method']);
  exit;
}
if (!push_token_ok((string)($_SERVER['HTTP_X_RS_TOKEN'] ?? ''))) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'error' => 'auth']);
  exit;
}
$mode = (string)($_GET['mode'] ?? 'apply');
$man = @include __DIR__ . '/../lib/schema_manifest.php';
if (!is_array($man) || empty($man['tables'])) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'manifest_missing']);
  exit;
}
$db = db();
$existing = [];
foreach ($db->query("SHOW FULL TABLES WHERE Table_type='BASE TABLE'")->fetchAll(PDO::FETCH_NUM) as $r) $existing[$r[0]] = true;

$report = ['created' => [], 'columns' => [], 'skipped' => [], 'errors' => []];
foreach ($man['tables'] as $t => $def) {
  if (!isset($existing[$t])) {
    $report['created'][] = $t;
    if ($mode === 'apply') {
      try { $db->exec($def['ddl']); } catch (Throwable $e) { $report['errors'][] = $t . ': create: ' . $e->getMessage(); }
    }
    continue;
  }
  $have = [];
  try {
    foreach ($db->query('SHOW COLUMNS FROM `' . $t . '`')->fetchAll(PDO::FETCH_ASSOC) as $c) $have[$c['Field']] = true;
  } catch (Throwable $e) { $report['errors'][] = $t . ': show: ' . $e->getMessage(); continue; }
  foreach ($def['cols'] as $c => $m) {
    if (isset($have[$c])) continue;
    if (!empty($m['gen'])) { $report['skipped'][] = $t . '.' . $c; continue; }
    $type = preg_replace('/\s+COLLATE\s+\S+/i', '', (string)$m['type']);
    $sql = 'ALTER TABLE `' . $t . '` ADD COLUMN `' . $c . '` ' . $type;
    if (strtoupper((string)$m['null']) === 'NO') $sql .= ' NOT NULL';
    else $sql .= ' NULL';
    if ($m['default'] !== null) {
      $d = (string)$m['default'];
      if (preg_match('/^(CURRENT_TIMESTAMP(\(\d*\))?|NULL)$/i', $d)) $sql .= ' DEFAULT ' . $d;
      else $sql .= ' DEFAULT ' . $db->quote($d);
    }
    if (!empty($m['extra']) && stripos((string)$m['extra'], 'auto_increment') !== false) $sql .= ' AUTO_INCREMENT';
    $report['columns'][] = $t . '.' . $c;
    if ($mode === 'apply') {
      try { $db->exec($sql); } catch (Throwable $e) { $report['errors'][] = $t . '.' . $c . ': ' . $e->getMessage(); }
    }
  }
}
$report['ok'] = empty($report['errors']);
$report['mode'] = $mode;
push_log('migrate mode=' . $mode . ' created=' . count($report['created']) . ' cols=' . count($report['columns']) . ' errors=' . count($report['errors']));
echo json_encode($report, JSON_UNESCAPED_UNICODE);
