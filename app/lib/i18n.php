<?php
if (!function_exists('i18n_langs')) {
  if (!function_exists('cfg_get')) require_once __DIR__ . '/cfg.php';
  if (!function_exists('app_session_start')) require_once __DIR__ . '/session.php';

  function i18n_langs(): array {
    return [
      'ar' => 'العربية',
      'en' => 'English',
      'ur' => 'اردو',
      'hi' => 'हिन्दी',
      'bn' => 'বাংলা',
    ];
  }

  function i18n_valid(string $code): bool {
    return array_key_exists($code, i18n_langs());
  }

  function i18n_dir(string $code = ''): string {
    if ($code === '') $code = i18n_current();
    return in_array($code, ['ar', 'ur'], true) ? 'rtl' : 'ltr';
  }

  function i18n_current(): string {
    app_session_start();
    $code = (string)($_SESSION['lang'] ?? '');
    if (i18n_valid($code)) return $code;

    $uid = (int)($_SESSION['uid'] ?? 0);
    if ($uid > 0) {
      $saved = (string)cfg_get('lang_user_' . $uid, '');
      if (i18n_valid($saved)) return $saved;
    }

    $cookie = (string)($_COOKIE['rs_lang'] ?? '');
    if (i18n_valid($cookie)) return $cookie;

    $global = (string)cfg_get('ui_lang', 'ar');
    return i18n_valid($global) ? $global : 'ar';
  }

  function i18n_save_user(string $code): void {
    if (!i18n_valid($code)) return;
    app_session_start();
    $_SESSION['lang'] = $code;
    $uid = (int)($_SESSION['uid'] ?? 0);
    if ($uid > 0) {
      try { cfg_set('lang_user_' . $uid, $code); } catch (Throwable $t) {}
    }
  }

  function i18n_global_save(string $code): void {
    if (!i18n_valid($code)) return;
    try { cfg_set('ui_lang', $code); } catch (Throwable $t) {}
  }

  function i18n_dict(string $code): array {
    static $cache = [];
    if (isset($cache[$code])) return $cache[$code];
    if (!i18n_valid($code) || $code === 'ar') return $cache[$code] = [];
    $file = __DIR__ . '/../assets/i18n/' . $code . '.json';
    $raw = is_file($file) ? @file_get_contents($file) : false;
    $data = $raw === false ? null : json_decode($raw, true);
    return $cache[$code] = is_array($data) ? $data : [];
  }

  function i18n_t(string $text, string $code = ''): string {
    if ($text === '') return $text;
    if ($code === '') $code = i18n_current();
    if (!i18n_valid($code) || $code === 'ar') return $text;
    $dict = i18n_dict($code);
    if (array_key_exists($text, $dict)) return (string)$dict[$text];
    $base = rtrim($text, ":： \t\n\r\0\x0B");
    if ($base !== $text && array_key_exists($base, $dict)) {
      return (string)$dict[$base] . (substr($text, -1) === '：' ? '：' : ':');
    }
    return $text;
  }

  function i18n_inject_html(string $html): string {
    if (stripos($html, '<html') === false || stripos($html, '</html>') === false) return $html;
    if (stripos($html, 'assets/i18n.js') !== false) return $html;

    $lang = i18n_current();
    $dir = i18n_dir($lang);
    $langs = json_encode(i18n_langs(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $html = preg_replace('/<html\b[^>]*>/i', '<html lang="' . htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') . '" dir="' . $dir . '">', $html, 1);

    $boot = '<link rel="stylesheet" href="/assets/i18n.css">'
      . '<script>window.__RS_LANG=' . json_encode($lang) . ';window.__RS_LANGS=' . $langs . ';window.__RS_I18N_BASE="/assets/i18n/";'
      . 'document.documentElement.lang=window.__RS_LANG;document.documentElement.dir=' . json_encode($dir) . ';</script>';
    if (stripos($html, '</head>') !== false) {
      $html = preg_replace('/<\/head>/i', $boot . '</head>', $html, 1);
    } else {
      $html = $boot . $html;
    }

    if (stripos($html, '</body>') !== false) {
      $html = preg_replace('/<\/body>/i', '<script src="/assets/i18n.js" defer></script></body>', $html, 1);
    }
    return $html;
  }
}
