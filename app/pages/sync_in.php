<?php
ini_set('display_errors', '0');
require_once __DIR__ . '/../lib/push.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'method']);
  exit;
}
$tok = (string)($_SERVER['HTTP_X_RS_TOKEN'] ?? '');
if (!push_token_ok($tok)) {
  http_response_code(403);
  push_log('denied ip=' . (string)($_SERVER['REMOTE_ADDR'] ?? ''));
  echo json_encode(['ok' => false, 'error' => 'auth']);
  exit;
}
$raw = file_get_contents('php://input');
if (strlen((string)$raw) > 6 * 1024 * 1024) {
  http_response_code(413);
  echo json_encode(['ok' => false, 'error' => 'too_large']);
  exit;
}
if (strtolower((string)($_SERVER['HTTP_X_RS_ENC'] ?? '')) === 'gzip') {
  $raw = @gzdecode($raw);
  if ($raw === false) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'gzip']);
    exit;
  }
}
$req = json_decode((string)$raw, true);
if (!is_array($req)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'json']);
  exit;
}
try {
  $res = push_apply($req);
} catch (Throwable $e) {
  $res = ['ok' => false, 'error' => 'fatal: ' . $e->getMessage()];
}
if (empty($res['ok'])) {
  http_response_code(500);
  push_log('op=' . (string)($req['op'] ?? '?') . ' err=' . (string)($res['error'] ?? '?'));
} elseif (!in_array((string)($req['op'] ?? ''), ['ping', 'stats'], true)) {
  push_log('op=' . (string)$req['op'] . ' table=' . (string)($req['table'] ?? '-') . ' ok');
}
echo json_encode($res, JSON_UNESCAPED_UNICODE);
