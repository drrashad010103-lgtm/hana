<?php
require __DIR__ . '/../app/lib/invoice_math.php';

$cases = [
  'line discount below gross' => [invoice_line_totals(2, 10, 5), ['gross' => 20.0, 'discount' => 5.0, 'net' => 15.0]],
  'line discount capped at gross' => [invoice_line_totals(2, 10, 25), ['gross' => 20.0, 'discount' => 20.0, 'net' => 0.0]],
  'negative line discount ignored' => [invoice_line_totals(2, 10, -5), ['gross' => 20.0, 'discount' => 0.0, 'net' => 20.0]],
  'header discount capped at subtotal' => [invoice_clamp_header_discount(7, 10), 7.0],
  'negative header discount ignored' => [invoice_clamp_header_discount(7, -2), 0.0],
  'negative subtotal remains nonnegative' => [invoice_clamp_header_discount(-7, 2), 0.0],
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

if ($failures > 0) exit(1);
echo "All invoice math tests passed.\n";
