<?php
require __DIR__ . '/../app/lib/cfg.php';

$cases = [
  'database read failure denies every sensitive action' => [
    cfg_invoice_flags_from_row(null, false),
    ['edit_inv' => 0, 'edit_disc' => 0, 'edit_price' => 0, 'edit_date' => 0],
  ],
  'unknown user denies every sensitive action' => [
    cfg_invoice_flags_from_row(null, true),
    ['edit_inv' => 0, 'edit_disc' => 0, 'edit_price' => 0, 'edit_date' => 0],
  ],
  'legacy null flags denied (fail-closed)' => [
    cfg_invoice_flags_from_row(['edit_inv' => null, 'edit_disc' => null, 'edit_price' => null, 'edit_date' => null], true),
    ['edit_inv' => 0, 'edit_disc' => 0, 'edit_price' => 0, 'edit_date' => 0],
  ],
  'explicit restrictions remain enforced' => [
    cfg_invoice_flags_from_row(['edit_inv' => 0, 'edit_disc' => 1, 'edit_price' => 0, 'edit_date' => 1], true),
    ['edit_inv' => 0, 'edit_disc' => 1, 'edit_price' => 0, 'edit_date' => 1],
  ],
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
echo "All invoice permission tests passed.\n";
