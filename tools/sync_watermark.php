<?php
if (!function_exists('sync_retry_watermark')) {
  function sync_retry_watermark(int $previous, int $candidate, array $failedProcIds): int {
    if (!$failedProcIds) return max($previous, $candidate);
    $firstFailedProc = min($failedProcIds);
    return max($previous, min($candidate, $firstFailedProc - 1));
  }
}
