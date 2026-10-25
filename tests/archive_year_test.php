<?php
require __DIR__ . '/../app/lib/archive_year.php';

$cases = [
  'valid year bounds' => [archive_year_bounds(2025), ['2025-01-01 00:00:00', '2026-01-01 00:00:00']],
  'leap year ends at next year boundary' => [archive_year_bounds(2024), ['2024-01-01 00:00:00', '2025-01-01 00:00:00']],
];

$failures = 0;
foreach ($cases as $name => [$actual, $expected]) {
  if ($actual !== $expected) {
    ++$failures;
    fwrite(STDERR, "FAIL: $name\n");
    continue;
  }
  echo "PASS: $name\n";
}

foreach ([999, 9999] as $year) {
  try {
    archive_year_bounds($year);
    ++$failures;
    fwrite(STDERR, "FAIL: invalid year $year accepted\n");
  } catch (InvalidArgumentException $e) {
    echo "PASS: invalid year $year rejected\n";
  }
}

if ($failures > 0) exit(1);
echo "All archive year tests passed.\n";
