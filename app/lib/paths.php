<?php
if (!function_exists('app_data_dir')) {
  function app_data_dir(): string {
    static $d = null;
    if ($d !== null) return $d;
    if (DIRECTORY_SEPARATOR === '\\') {
      $d = 'D:/RS_Own/data';
    } else {
      $d = rtrim(sys_get_temp_dir(), '/\\') . '/rs_own_data';
    }
    if (!is_dir($d)) @mkdir($d, 0777, true);
    if (!is_dir($d) || !is_writable($d)) $d = rtrim(sys_get_temp_dir(), '/\\');
    return $d;
  }
}
