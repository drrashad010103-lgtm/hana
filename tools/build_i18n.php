<?php
$dir = __DIR__ . '/../app/assets/i18n';
$langs = ['en', 'ur', 'hi', 'bn'];

$curated = [];
foreach (file($dir . '/source.tsv', FILE_IGNORE_NEW_LINES) as $no => $line) {
  if ($no === 0 || trim($line) === '') continue;
  $p = explode("\t", $line, 5);
  if (count($p) !== 5) {
    fwrite(STDERR, "Bad TSV line " . ($no + 1) . "\n");
    exit(1);
  }
  $ar = trim($p[0]);
  if ($ar === '') continue;
  $curated[$ar] = [
    'en' => trim($p[1]),
    'ur' => trim($p[2]),
    'hi' => trim($p[3]),
    'bn' => trim($p[4]),
  ];
}

foreach ($langs as $lang) {
  $autoFile = $dir . '/auto_' . $lang . '.json';
  $dict = [];
  if (is_file($autoFile)) {
    $tmp = json_decode(file_get_contents($autoFile), true);
    if (is_array($tmp)) $dict = $tmp;
  }
  foreach ($curated as $ar => $vals) {
    if (!empty($vals[$lang])) $dict[$ar] = $vals[$lang];
  }
  ksort($dict, SORT_STRING);
  file_put_contents(
    $dir . '/' . $lang . '.json',
    json_encode($dict, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n"
  );
  echo $lang . ': ' . count($dict) . " keys\n";
}
