<?php
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
require_once __DIR__ . '/../lib/bridge.php';

$db = db();
$flash = $_SESSION['bridge_flash'] ?? null;
unset($_SESSION['bridge_flash']);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
  $action = (string)($_POST['action'] ?? '');
  set_time_limit(300);
  try {
    if ($action === 'export') {
      $result = bridge_run_export(false);
    } elseif ($action === 'import') {
      $result = bridge_run_import(false);
    } else {
      $result = ['ok' => false, 'errors' => ['إجراء غير معروف']];
    }
  } catch (Throwable $e) {
    $result = ['ok' => false, 'errors' => [$e->getMessage()], 'output' => $e->getMessage()];
  }
  $_SESSION['bridge_flash'] = ['action' => $action, 'result' => $result];
  header('Location: bridge.php');
  exit;
}

$pendingInv = (int)$db->query("SELECT COUNT(*) FROM inv WHERE branch=99 AND IFNULL(IS_Temp,0)=0 AND IFNULL(IS_Deleted,0)=0")->fetchColumn();
$pendingItems = (int)$db->query("SELECT COUNT(*) FROM currencies c LEFT JOIN bridge_map m ON m.kind='item' AND m.local_id=c.id WHERE m.local_id IS NULL AND IFNULL(c.IS_Deleted,0)=0")->fetchColumn();
$pendingCust = (int)$db->query("SELECT COUNT(*) FROM customers c LEFT JOIN bridge_map m ON m.kind='customer' AND m.local_id=c.id WHERE m.local_id IS NULL AND IFNULL(c.IS_Deleted,0)=0")->fetchColumn();
$lastExport = json_decode((string)bridge_state_get('ui_last_export', ''), true);
$lastImport = json_decode((string)bridge_state_get('ui_last_import', ''), true);
$logRows = $db->query("SELECT ts,level,msg FROM bridge_log ORDER BY id DESC LIMIT 8")->fetchAll();

layout_open('الكوبري - التصدير والاستيراد', '');
?>
<style>
.bridge-grid{display:flex;gap:16px;flex-wrap:wrap}
.bridge-box{flex:1;min-width:300px}
.bridge-box .big{display:block;width:100%;padding:16px;font-size:18px;border-radius:12px;margin:14px 0}
.bridge-export{background:linear-gradient(135deg,#16a34a,#15803d)!important}
.bridge-import{background:linear-gradient(135deg,#2563eb,#1d4ed8)!important}
.bridge-ok{background:#dcfce7;border:1px solid #16a34a;color:#166534;padding:10px 14px;border-radius:10px;margin-bottom:12px}
.bridge-bad{background:#fee2e2;border:1px solid #dc2626;color:#991b1b;padding:10px 14px;border-radius:10px;margin-bottom:12px}
.bridge-pre{direction:ltr;text-align:left;background:#0f172a;color:#e2e8f0;padding:10px;border-radius:10px;max-height:260px;overflow:auto;font-size:12px;white-space:pre-wrap}
.bridge-kpi{display:flex;gap:10px;flex-wrap:wrap}
.bridge-kpi div{background:#faf5ff;border:1px solid #e9d5ff;border-radius:10px;padding:10px 14px;min-width:110px}
.bridge-kpi b{display:block;font-size:22px;color:#6b21a8}
</style>
<?php if ($flash): $r = $flash['result'] ?? []; $ok = !empty($r['ok']); ?>
  <div class="<?=$ok ? 'bridge-ok' : 'bridge-bad'?>">
    <b><?=($flash['action'] ?? '') === 'export' ? 'نتيجة التصدير' : 'نتيجة الاستيراد'?>:</b>
    <?=$ok ? 'تمت العملية بنجاح' : 'لم تكتمل العملية'?>
    <?php if (isset($r['seconds'])): ?> — <?=e((string)$r['seconds'])?> ثانية<?php endif; ?>
    <?php if (!empty($r['busy'])): ?> — <?=e((string)($r['output'] ?? 'مشغول'))?><?php endif; ?>
    <?php if (!empty($r['errors'])): ?>
      <ul style="margin:8px 0 0 18px"><?php foreach ((array)$r['errors'] as $er): ?><li><?=e((string)$er)?></li><?php endforeach; ?></ul>
    <?php endif; ?>
  </div>
  <?php if (($flash['action'] ?? '') === 'export'): ?>
    <div class="card"><h3>ملخص التصدير</h3><div class="bridge-kpi">
      <div>أصناف جديدة<b><?=e((string)($r['items'] ?? 0))?></b></div>
      <div>عملاء جدد<b><?=e((string)($r['customers'] ?? 0))?></b></div>
      <div>فواتير<b><?=e((string)($r['invoices'] ?? 0))?></b></div>
      <div>تم ربطها<b><?=e((string)($r['mapped'] ?? 0))?></b></div>
    </div></div>
  <?php elseif (($flash['action'] ?? '') === 'import'): ?>
    <div class="card"><h3>ملخص الاستيراد</h3><div class="bridge-kpi">
      <div>أصناف جديدة<b><?=e((string)($r['items'] ?? 0))?></b></div>
      <div>عملاء جدد<b><?=e((string)($r['customers'] ?? 0))?></b></div>
      <div>فواتير جديدة<b><?=e((string)($r['added'] ?? 0))?></b></div>
      <div>فواتير معدلة<b><?=e((string)($r['updated'] ?? 0))?></b></div>
    </div></div>
    <?php if (!empty($r['output'])): ?><div class="card"><h3>سجل عملية الاستيراد</h3><pre class="bridge-pre"><?=e((string)$r['output'])?></pre></div><?php endif; ?>
  <?php endif; ?>
<?php endif; ?>

<div class="card">
  <h3>التصدير والاستيراد اليدوي</h3>
  <div class="note">التشغيل الآن يدوي فقط. لا يوجد سكريبت يعمل في الخلفية. الأفضل: استيراد قبل بداية العمل، وتصدير بعد آخر فاتورة في اليوم. العملية قد تستغرق 10–20 ثانية.</div>
  <div class="bridge-grid">
    <div class="bridge-box">
      <h3 style="color:#166534">تصدير إلى برنامج المهندس</h3>
      <p>يرفع الفواتير الجديدة أو المعدلة في فرع 99، والأصناف والعملاء الجدد فقط إلى <b>RS_RS</b>.</p>
      <form method="post" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='جاري التصدير...';">
        <input type="hidden" name="action" value="export">
        <button class="big bridge-export" type="submit">تصدير الآن</button>
      </form>
      <div class="note">الفواتير الحالية: <b><?=number_format($pendingInv)?></b> — أصناف غير مربوطة: <b><?=number_format($pendingItems)?></b> — عملاء غير مربوطين: <b><?=number_format($pendingCust)?></b></div>
    </div>
    <div class="bridge-box">
      <h3 style="color:#1e40af">استيراد من برنامج المهندس</h3>
      <p>يسحب الفواتير الجديدة أو المعدلة، والأصناف والعملاء الجدد من <b>RS_RS</b> إلى برنامجك.</p>
      <form method="post" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='جاري الاستيراد...';">
        <input type="hidden" name="action" value="import">
        <button class="big bridge-import" type="submit">استيراد الآن</button>
      </form>
      <div class="note">آخر تصدير: <b><?=e((string)($lastExport['at'] ?? 'لا يوجد'))?></b><br>آخر استيراد: <b><?=e((string)($lastImport['at'] ?? 'لا يوجد'))?></b></div>
    </div>
  </div>
</div>

<div class="card">
  <h3>آخر عمليات الكوبري</h3>
  <table>
    <tr><th>الوقت</th><th>الحالة</th><th>التفاصيل</th></tr>
    <?php foreach ($logRows as $row): ?>
      <tr><td><?=e((string)$row['ts'])?></td><td><?=e((string)$row['level'])?></td><td style="text-align:right"><?=e((string)$row['msg'])?></td></tr>
    <?php endforeach; ?>
    <?php if (!$logRows): ?><tr><td colspan="3">لا توجد عمليات مسجلة بعد.</td></tr><?php endif; ?>
  </table>
</div>
<?php layout_close(); ?>
