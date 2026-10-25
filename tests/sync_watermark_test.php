<?php
require __DIR__ . '/../tools/sync_watermark.php';

$cases = [
  'successful batch advances to source maximum' => [sync_retry_watermark(100, 150, []), 150],
  'failed invoice holds watermark before it' => [sync_retry_watermark(100, 150, [120]), 119],
  'earliest failed invoice determines retry point' => [sync_retry_watermark(100, 150, [130, 120, 140]), 119],
  'watermark never moves backward' => [sync_retry_watermark(100, 90, []), 100],
  'failure at first pending invoice keeps previous watermark' => [sync_retry_watermark(100, 150, [101]), 100],
];

$failures = 0;
foreach ($cases as $name => [$actual, $expected]) {
  if ($actual !== $expected) {
    ++$failures;
    fwrite(STDERR, "FAIL: $name (expected $expected, got $actual)\n");
    continue;
  }
  echo "PASS: $name\n";
}

if ($failures > 0) exit(1);
echo "All sync watermark tests passed.\n";
