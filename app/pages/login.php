<?php
// pages/login.php — تسجيل الدخول (انظر أيضاً lib/auth.php)
require_once __DIR__ . '/../lib/cfg.php';
require_once __DIR__ . '/../lib/i18n.php';
require __DIR__ . '/../lib/auth.php';
$err = '';
if (($_POST['go'] ?? '') !== '') {
  try {
    if (auth_check(trim($_POST['u'] ?? ''), trim($_POST['p'] ?? ''))) {
      header('Location: main.php');
      exit;
    }
    $err = '✗ خطأ في اسم المستخدم أو كلمة المرور';
  } catch (Throwable $t) {
    $err = '✗ ' . $t->getMessage();
  }
}
$__lang = i18n_current();
$__dir = i18n_dir($__lang);
$__langs = json_encode(i18n_langs(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
<!DOCTYPE html><html lang="<?=e($__lang)?>" dir="<?=e($__dir)?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#4a1042"><meta name="apple-mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-status-bar-style" content="black-translucent"><meta name="apple-mobile-web-app-title" content="DOCTOR BEAUTY"><link rel="manifest" href="/manifest.json"><link rel="apple-touch-icon" href="../assets/logo-192.png"><link rel="stylesheet" href="/assets/i18n.css"><title>تسجيل الدخول — <?=e(cfg_shop_name())?></title>
<script>window.__RS_LANG=<?=json_encode($__lang)?>;window.__RS_LANGS=<?=$__langs?>;window.__RS_I18N_BASE="/assets/i18n/";</script>
<style>body{font-family:Tahoma;background:radial-gradient(circle at 50% 30%,#4a1042,#2b0a2e);display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}.box{background:#fff;border-radius:18px;padding:34px;min-width:300px;box-shadow:0 20px 60px #0008;text-align:center}input{font-size:17px;padding:10px;border:1px solid #cbd5e1;border-radius:8px;width:100%;margin:6px 0;font-family:inherit}button{font-size:17px;padding:11px 30px;border:none;border-radius:8px;background:#16a34a;color:#fff;font-weight:800;cursor:pointer;font-family:inherit}.err{color:#dc2626}</style></head>
<body><div class="box"><img src="<?=e(cfg_logo_src('../'))?>" alt="<?=e(cfg_shop_name())?>" style="width:64px;height:64px;object-fit:contain;border-radius:14px;background:#f8fafc;padding:4px"><h2><?=e(cfg_shop_name())?></h2>
<form method="post"><input name="u" placeholder="اسم المستخدم" autocomplete="username"><input name="p" type="password" placeholder="كلمة المرور" autocomplete="current-password"><br><button name="go" value="1">تسجيل الدخول</button></form>
<div class="err"><?= $err ?></div></div><script src="/assets/i18n.js" defer></script></body></html>
