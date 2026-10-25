<?php
// lib/layout.php — هيكل الواجهة الموحد (ملىء الشاشة — بدون سايدبار)
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/cfg.php';
function layout_open(string $title, string $active = ''): void {
  auth_need();
  // إعدادات الخط واللون من ثيم المحل (ui_on يفعّل التطبيق — الافتراضي كايرو + ألوان البراند)
  $__font = cfg_get('ui_font', 'Cairo');
  $__fam = in_array($__font, ['Cairo', 'Tahoma', 'Arial'], true) ? $__font : 'Cairo';
  $__fg = cfg_get('ui_fg', '');
  $__col = (cfg_get('ui_on', '') && preg_match('/^#[0-9a-fA-F]{6}$/', $__fg)) ? $__fg : '#1e1b4b';
  $__fs = ctype_digit((string)cfg_get('ui_size', '14')) ? (int)cfg_get('ui_size', '14') : 14;
  $__bw = (cfg_get('ui_on', '') && cfg_get('ui_bold', '0') === '1') ? '700' : '400';
  echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
  echo '<meta name="csrf-token" content="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
  echo '<title>' . htmlspecialchars($title) . ' — ' . htmlspecialchars(cfg_shop_name()) . '</title>';
  echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">';
  echo '<link rel="stylesheet" href="../assets/app.css">';
  echo '<style>
  *{box-sizing:border-box}
  body{margin:0;background:linear-gradient(180deg,#faf5ff 0%,#f1eafe 100%);font-family:\'' . $__fam . '\',Tahoma,Arial,sans-serif;min-height:100vh;color:' . $__col . ';font-size:' . $__fs . 'px;font-weight:' . $__bw . '}
  main{max-width:1400px;margin:0 auto;padding:20px 28px}
  main h2{margin:0 0 18px;padding:14px 22px;background:linear-gradient(135deg,#6b21a8,#a855f7);color:#fff;border-radius:14px;font-size:20px;box-shadow:0 6px 24px #a855f744;display:flex;align-items:center;gap:10px}
  .card{background:#fff;border:1px solid #e9d5ff;border-radius:16px;padding:18px 20px;margin-bottom:16px;box-shadow:0 4px 20px #6b21a812;transition:box-shadow .2s}
  .card:hover{box-shadow:0 8px 32px #6b21a822}
  .card h3{margin:0 0 12px;font-size:16px;color:#6b21a8;border-bottom:2px solid #e9d5ff;padding-bottom:8px}
  table{width:100%;border-collapse:collapse;font-size:13.5px}
  th{background:linear-gradient(135deg,#f5f3ff,#ede9fe);color:#4c1d95;padding:10px 8px;border:1px solid #e9d5ff;font-weight:800;font-size:12.5px;text-align:center}
  td{padding:8px;border:1px solid #f3e8ff;text-align:center}
  tr:nth-child(even) td{background:#faf5ff}
  tr:hover td{background:#f5f3ff}
  input,select,button,textarea{font-family:inherit;font-size:13.5px;padding:7px 10px;border:1px solid #d8b4fe;border-radius:8px;background:#fff;color:inherit}
  input:focus,select:focus{outline:none;border-color:#a855f7;box-shadow:0 0 0 3px #a855f722}
  button{background:linear-gradient(135deg,#a855f7,#7c3aed);color:#fff;border:none;padding:8px 18px;cursor:pointer;font-weight:700;border-radius:8px;transition:all .2s;box-shadow:0 3px 12px #a855f744}
  button:hover{transform:translateY(-1px);box-shadow:0 6px 20px #a855f766}
  .row{display:flex;gap:12px;flex-wrap:wrap;align-items:end}
  .row label{font-size:12px;font-weight:700;color:#6b21a8}
  .note{background:#fef9c3;border:1px solid #fde047;border-radius:10px;padding:10px 14px;font-size:13px;color:#854d0e;margin-top:10px}
  .cards{display:flex;gap:10px;flex-wrap:wrap}
  a{color:#7c3aed;font-weight:600}
  a:hover{color:#a855f7}
  </style></head><body>';
  echo '<main><h2>' . htmlspecialchars($title) . '</h2>';
}
function layout_close(): void {
  echo '</main></body></html>';
}
