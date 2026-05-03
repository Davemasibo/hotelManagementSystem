<?php
include('db_connect.php');
$id         = (int)($_GET['id']         ?? 0);
$checked_id = (int)($_GET['checked_id'] ?? 0);

if ($id > 0) {
    $inv = $conn->query("SELECT i.*, c.ref_no, c.name AS guest_raw, c.date_in, c.date_out, c.contact_no,
        g.full_name AS guest_name, g.email, g.phone, g.address, g.city, g.country, g.nationality,
        r.room, rc.name AS category,
        ss.hotel_name, ss.email AS hotel_email, ss.contact AS hotel_contact
        FROM invoices i JOIN checked c ON i.checked_id=c.id
        LEFT JOIN guests g ON i.guest_id=g.id
        LEFT JOIN rooms r ON c.room_id=r.id
        LEFT JOIN room_categories rc ON r.category_id=rc.id
        CROSS JOIN system_settings ss
        WHERE i.id=$id LIMIT 1")->fetch_assoc();
} elseif ($checked_id > 0) {
    $inv = $conn->query("SELECT i.*, c.ref_no, c.name AS guest_raw, c.date_in, c.date_out, c.contact_no,
        g.full_name AS guest_name, g.email, g.phone, g.address, g.city, g.country, g.nationality,
        r.room, rc.name AS category,
        ss.hotel_name, ss.email AS hotel_email, ss.contact AS hotel_contact
        FROM invoices i JOIN checked c ON i.checked_id=c.id
        LEFT JOIN guests g ON i.guest_id=g.id
        LEFT JOIN rooms r ON c.room_id=r.id
        LEFT JOIN room_categories rc ON r.category_id=rc.id
        CROSS JOIN system_settings ss
        WHERE c.id=$checked_id LIMIT 1")->fetch_assoc();
}

if (!$inv) { echo '<p>Invoice not found.</p>'; exit; }
$items = $conn->query("SELECT * FROM invoice_items WHERE invoice_id={$inv['id']} ORDER BY id");
$payments = $conn->query("SELECT * FROM payments WHERE invoice_id={$inv['id']} ORDER BY created_at");
$gname = $inv['guest_name'] ?: $inv['guest_raw'];
$nights = max(1, (int)round((strtotime($inv['date_out']) - strtotime($inv['date_in'])) / 86400));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Invoice <?php echo htmlspecialchars($inv['invoice_no']); ?></title>
<style>
  * { box-sizing: border-box; }
  body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 13px; color: #333; margin: 0; padding: 20px; background: #f5f5f5; }
  .invoice-wrap { max-width: 800px; margin: 0 auto; background: #fff; padding: 40px; box-shadow: 0 2px 20px rgba(0,0,0,.08); border-radius: 8px; }
  .hotel-name { font-size: 22px; font-weight: 700; color: #1a1a2e; }
  .invoice-title { font-size: 28px; font-weight: 700; color: #2563eb; letter-spacing: .05em; }
  .divider { border: none; border-top: 2px solid #e5e7eb; margin: 20px 0; }
  table { width: 100%; border-collapse: collapse; }
  th { background: #1a1a2e; color: #fff; padding: 10px 12px; text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: .05em; }
  td { padding: 9px 12px; border-bottom: 1px solid #f0f0f0; }
  .totals-table td { border: none; padding: 5px 12px; }
  .total-row td { font-size: 15px; font-weight: 700; background: #1a1a2e; color: #fff; padding: 10px 12px; }
  .badge-paid { background: #22c55e; color: #fff; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
  .badge-issued { background: #f59e0b; color: #000; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
  .badge-partial { background: #3b82f6; color: #fff; padding: 3px 10px; border-radius: 20px; font-size: 11px; }
  .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
  .info-label { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: .05em; }
  .info-value { font-weight: 600; margin-top: 2px; }
  .footer-note { font-size: 11px; color: #9ca3af; text-align: center; margin-top: 30px; }
  @media print {
    body { background: #fff; padding: 0; }
    .invoice-wrap { box-shadow: none; }
    .no-print { display: none !important; }
  }
</style>
</head>
<body>
<div class="no-print text-center mb-3">
  <button onclick="window.print()" style="background:#2563eb;color:#fff;border:none;padding:8px 20px;border-radius:6px;cursor:pointer;margin-right:8px">
    &#128424; Print / Save PDF
  </button>
  <button onclick="window.close()" style="background:#6b7280;color:#fff;border:none;padding:8px 20px;border-radius:6px;cursor:pointer">
    ✕ Close
  </button>
</div>

<div class="invoice-wrap">

  <!-- Header -->
  <div style="display:flex;justify-content:space-between;align-items:flex-start">
    <div>
      <div class="hotel-name">&#127970; <?php echo htmlspecialchars($inv['hotel_name']); ?></div>
      <div style="color:#6b7280;margin-top:4px">
        <?php echo htmlspecialchars($inv['hotel_email']); ?><br>
        <?php echo htmlspecialchars($inv['hotel_contact']); ?>
      </div>
    </div>
    <div style="text-align:right">
      <div class="invoice-title">INVOICE</div>
      <div style="font-size:15px;font-weight:700;color:#333"><?php echo htmlspecialchars($inv['invoice_no']); ?></div>
      <div style="margin-top:4px">
        <span class="badge-<?php echo $inv['status']; ?>"><?php echo strtoupper($inv['status']); ?></span>
      </div>
    </div>
  </div>

  <hr class="divider">

  <!-- Guest & Stay Info -->
  <div class="info-grid" style="margin-bottom:24px">
    <div>
      <div class="info-label">Bill To</div>
      <div class="info-value" style="font-size:15px"><?php echo htmlspecialchars($gname); ?></div>
      <?php if ($inv['email']): ?><div><?php echo htmlspecialchars($inv['email']); ?></div><?php endif; ?>
      <?php if ($inv['phone']||$inv['contact_no']): ?><div><?php echo htmlspecialchars($inv['phone']??$inv['contact_no']); ?></div><?php endif; ?>
      <?php if ($inv['address']): ?><div><?php echo htmlspecialchars($inv['address'].', '.($inv['city']??'')); ?></div><?php endif; ?>
    </div>
    <div style="text-align:right">
      <table style="width:auto;margin-left:auto">
        <tr><td class="info-label" style="border:none;padding:2px 0 2px 20px">Booking Ref</td><td style="border:none;padding:2px 0;font-weight:600"><?php echo htmlspecialchars(trim($inv['ref_no'])); ?></td></tr>
        <tr><td class="info-label" style="border:none;padding:2px 0 2px 20px">Room</td><td style="border:none;padding:2px 0"><?php echo htmlspecialchars($inv['room']??''); ?> — <?php echo htmlspecialchars($inv['category']??''); ?></td></tr>
        <tr><td class="info-label" style="border:none;padding:2px 0 2px 20px">Check-In</td><td style="border:none;padding:2px 0"><?php echo date('D, d M Y', strtotime($inv['date_in'])); ?></td></tr>
        <tr><td class="info-label" style="border:none;padding:2px 0 2px 20px">Check-Out</td><td style="border:none;padding:2px 0"><?php echo date('D, d M Y', strtotime($inv['date_out'])); ?></td></tr>
        <tr><td class="info-label" style="border:none;padding:2px 0 2px 20px">Nights</td><td style="border:none;padding:2px 0"><?php echo $nights; ?></td></tr>
        <?php if ($inv['due_date']): ?><tr><td class="info-label" style="border:none;padding:2px 0 2px 20px">Due Date</td><td style="border:none;padding:2px 0;color:#dc2626;font-weight:600"><?php echo date('d M Y', strtotime($inv['due_date'])); ?></td></tr><?php endif; ?>
      </table>
    </div>
  </div>

  <!-- Items Table -->
  <table>
    <thead><tr><th>#</th><th>Description</th><th>Type</th><th style="text-align:right">Qty</th><th style="text-align:right">Unit Price</th><th style="text-align:right">Amount</th></tr></thead>
    <tbody>
      <?php $n=1; while ($item = $items->fetch_assoc()): ?>
      <tr>
        <td><?php echo $n++; ?></td>
        <td><?php echo htmlspecialchars($item['description']); ?></td>
        <td><?php echo ucwords(str_replace('_',' ',$item['item_type'])); ?></td>
        <td style="text-align:right"><?php echo number_format($item['quantity'],2); ?></td>
        <td style="text-align:right">$<?php echo number_format($item['unit_price'],2); ?></td>
        <td style="text-align:right<?php echo $item['item_type']==='discount'?';color:#dc2626':''; ?>">
          <?php echo $item['item_type']==='discount'?'-':''; ?>$<?php echo number_format($item['amount'],2); ?>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <!-- Totals -->
  <div style="display:flex;justify-content:flex-end;margin-top:10px">
    <table class="totals-table" style="width:280px">
      <tr><td style="color:#6b7280">Subtotal</td><td style="text-align:right">$<?php echo number_format($inv['subtotal'],2); ?></td></tr>
      <?php if ($inv['discount_amount']>0): ?><tr><td style="color:#dc2626">Discount</td><td style="text-align:right;color:#dc2626">-$<?php echo number_format($inv['discount_amount'],2); ?></td></tr><?php endif; ?>
      <?php if ($inv['tax_rate']>0): ?><tr><td style="color:#6b7280">Tax (<?php echo $inv['tax_rate']; ?>%)</td><td style="text-align:right">$<?php echo number_format($inv['tax_amount'],2); ?></td></tr><?php endif; ?>
      <tr class="total-row"><td>TOTAL</td><td style="text-align:right">$<?php echo number_format($inv['total'],2); ?></td></tr>
      <tr><td style="color:#22c55e;font-weight:600">Amount Paid</td><td style="text-align:right;color:#22c55e;font-weight:600">$<?php echo number_format($inv['amount_paid'],2); ?></td></tr>
      <tr><td style="color:#dc2626;font-weight:700">Balance Due</td><td style="text-align:right;color:#dc2626;font-weight:700">$<?php echo number_format($inv['balance'],2); ?></td></tr>
    </table>
  </div>

  <!-- Payments -->
  <?php
  $pay_rows = [];
  while ($p = $payments->fetch_assoc()) $pay_rows[] = $p;
  if (!empty($pay_rows)):
  ?>
  <hr class="divider">
  <div style="font-size:12px"><strong>Payment History</strong></div>
  <table style="margin-top:8px;font-size:12px">
    <thead><tr><th>Date</th><th>Method</th><th>Reference</th><th style="text-align:right">Amount</th></tr></thead>
    <tbody>
      <?php foreach ($pay_rows as $p): ?>
      <tr>
        <td><?php echo date('d M Y H:i', strtotime($p['created_at'])); ?></td>
        <td><?php echo ucwords(str_replace('_',' ',$p['payment_method'])); ?></td>
        <td><?php echo htmlspecialchars($p['reference']??'—'); ?></td>
        <td style="text-align:right;color:#22c55e">$<?php echo number_format($p['amount'],2); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <?php if ($inv['notes']): ?>
  <hr class="divider">
  <strong>Notes:</strong> <?php echo htmlspecialchars($inv['notes']); ?>
  <?php endif; ?>

  <div class="footer-note">
    Thank you for staying at <?php echo htmlspecialchars($inv['hotel_name']); ?>.<br>
    Invoice generated: <?php echo date('d M Y H:i'); ?>
  </div>
</div>
</body>
</html>
