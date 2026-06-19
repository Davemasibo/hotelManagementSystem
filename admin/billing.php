<?php include('db_connect.php'); ?>
<div class="container-fluid py-3">

  <div class="page-header mb-4">
    <h5 class="page-title"><i class="fa fa-file-invoice-dollar mr-2"></i>Billing &amp; Invoices</h5>
    <div class="d-flex align-items-center">
      <select id="status-filter" class="custom-select custom-select-sm" style="width:auto">
        <option value="">All Statuses</option>
        <?php foreach (['draft', 'issued', 'partial', 'paid', 'cancelled', 'refunded'] as $s): ?>
        <option value="<?php echo $s; ?>"><?php echo ucfirst($s); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <!-- Revenue Summary -->
  <?php
  $today     = date('Y-m-d');
  $month     = date('Y-m');
  $rev_today = $conn->query("SELECT COALESCE(SUM(amount),0) AS r FROM payments WHERE DATE(created_at)='$today'")->fetch_assoc()['r'];
  $rev_month = $conn->query("SELECT COALESCE(SUM(amount),0) AS r FROM payments WHERE DATE_FORMAT(created_at,'%Y-%m')='$month'")->fetch_assoc()['r'];
  $unpaid    = $conn->query("SELECT COUNT(*) AS c, COALESCE(SUM(balance),0) AS b FROM invoices WHERE status IN ('issued','partial')")->fetch_assoc();
  ?>
  <div class="row mb-3">
    <div class="col-6 col-md-3 mb-2">
      <div class="card" style="border-left: 3px solid #22c55e !important">
        <div class="card-body py-2 text-center">
          <div class="font-weight-bold" style="font-size:1.3rem;color:#4ade80">KES <?php echo number_format($rev_today, 2); ?></div>
          <small class="text-muted">Today's Revenue</small>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
      <div class="card" style="border-left: 3px solid #3b82f6 !important">
        <div class="card-body py-2 text-center">
          <div class="font-weight-bold" style="font-size:1.3rem;color:#93c5fd">KES <?php echo number_format($rev_month, 2); ?></div>
          <small class="text-muted">This Month</small>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
      <div class="card" style="border-left: 3px solid #f97316 !important">
        <div class="card-body py-2 text-center">
          <div class="font-weight-bold" style="font-size:1.3rem;color:#fdba74"><?php echo $unpaid['c']; ?></div>
          <small class="text-muted">Unpaid Invoices</small>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
      <div class="card" style="border-left: 3px solid #ef4444 !important">
        <div class="card-body py-2 text-center">
          <div class="font-weight-bold" style="font-size:1.3rem;color:#fca5a5">KES <?php echo number_format($unpaid['b'], 2); ?></div>
          <small class="text-muted">Outstanding Balance</small>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <table class="table table-hover mb-0" id="billing-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Invoice No</th>
            <th>Guest / Ref</th>
            <th>Room</th>
            <th>Period</th>
            <th>Total</th>
            <th>Paid</th>
            <th>Balance</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $invs = $conn->query(
            "SELECT i.*, c.ref_no, c.name AS guest_raw, c.date_in, c.date_out,
                    g.full_name AS guest_name, g.is_vip,
                    r.room, rc.name AS category
             FROM invoices i
             JOIN checked c ON i.checked_id = c.id
             LEFT JOIN guests g ON i.guest_id = g.id
             LEFT JOIN rooms r ON c.room_id = r.id
             LEFT JOIN room_categories rc ON r.category_id = rc.id
             ORDER BY i.created_at DESC"
        );
        $n = 1;
        while ($inv = $invs->fetch_assoc()):
            $gname = $inv['guest_name'] ?: $inv['guest_raw'];
        ?>
        <tr>
          <td><?php echo $n++; ?></td>
          <td><code><?php echo htmlspecialchars($inv['invoice_no']); ?></code></td>
          <td>
            <?php if ($inv['is_vip']): ?><span class="badge badge-vip">VIP</span> <?php endif; ?>
            <?php echo htmlspecialchars($gname); ?>
            <br><small class="text-muted"><?php echo htmlspecialchars($inv['ref_no']); ?></small>
          </td>
          <td>
            <?php echo htmlspecialchars($inv['room'] ?? '—'); ?>
            <br><small class="text-muted"><?php echo htmlspecialchars($inv['category'] ?? ''); ?></small>
          </td>
          <td><small><?php echo substr($inv['date_in'] ?? '', 0, 10); ?> &rarr; <?php echo substr($inv['date_out'] ?? '', 0, 10); ?></small></td>
          <td class="font-weight-bold">KES <?php echo number_format($inv['total'], 2); ?></td>
          <td style="color:#4ade80">KES <?php echo number_format($inv['amount_paid'], 2); ?></td>
          <td style="color:<?php echo $inv['balance'] > 0 ? '#fca5a5' : '#4ade80'; ?>">
            KES <?php echo number_format($inv['balance'], 2); ?>
          </td>
          <td><span class="badge badge-<?php echo $inv['status']; ?>"><?php echo ucfirst($inv['status']); ?></span></td>
          <td>
            <button class="btn btn-sm btn-primary" onclick="uni_modal('Invoice Details','manage_billing.php?id=<?php echo $inv['id']; ?>')">
              <i class="fa fa-eye"></i>
            </button>
            <a href="invoice_view.php?id=<?php echo $inv['id']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
              <i class="fa fa-print"></i>
            </a>
            <?php if (in_array($inv['status'], ['issued', 'partial'])): ?>
            <button class="btn btn-sm btn-success" onclick="uni_modal('Record Payment','manage_payment.php?invoice_id=<?php echo $inv['id']; ?>')">
              <i class="fa fa-money-bill-wave"></i>
            </button>
            <?php endif; ?>
          </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<script>
$(document).ready(function() {
  var table = $('#billing-table').DataTable({order: [[0, 'desc']]});
  $('#status-filter').change(function() {
    table.column(8).search($(this).val()).draw();
  });
});
</script>
