<?php
if (!function_exists('invoice_line_totals')) {
  function invoice_line_totals(float $quantity, float $price, float $discount): array {
    $gross = max(0.0, $quantity * $price);
    $discount = min($gross, max(0.0, $discount));
    return ['gross' => $gross, 'discount' => $discount, 'net' => $gross - $discount];
  }

  function invoice_clamp_header_discount(float $subtotal, float $discount): float {
    return min(max(0.0, $subtotal), max(0.0, $discount));
  }
}
