<?php
include('db_connect.php');
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { echo '<div class="alert alert-danger">Invalid record.</div>'; exit; }

$stmt = $conn->prepare(
    "SELECT c.*, r.room, r.id AS room_id, rc.name AS category, rc.price,
            g.full_name AS guest_name, g.email, g.phone, g.is_vip, g.vip_note, g.id_number AS guest_id_number,
            i.id AS invoice_id, i.invoice_no, i.total, i.amount_paid, i.balance, i.status AS inv_status
     FROM checked c
     LEFT JOIN rooms r ON c.room_id = r.id
     LEFT JOIN room_categories rc ON r.category_id = rc.id
     LEFT JOIN guests g ON c.guest_id = g.id
     LEFT JOIN invoices i ON i.checked_id = c.id
     WHERE c.id = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$meta = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$meta) { echo '<div class="alert alert-warning">Record not found.</div>'; exit; }

$nights = max(1,(int)round(abs(strtotime($meta['date_out'])-strtotime($meta['date_in']))/86400));
$gname  = $meta['guest_name'] ?: $meta['name'];
// Reservation ID number, falling back to the linked guest profile.
$gid_no = ($meta['id_number'] ?? '') ?: (($meta['guest_id_number'] ?? '') ?: '—');
?>
<style>
#uni_modal .modal-footer { display:none; }
</style>
<div class="container-fluid">

  <!-- Guest Summary -->
  <div class="alert border mb-3" style="<?php echo $meta['is_vip']?'background:rgba(196,145,58,0.12);border-color:rgba(196,145,58,0.35)!important':'background:var(--bg-elevated);border-color:var(--border-subtle)!important'; ?>">
    <div class="row">
      <div class="col-md-6">
        <div class="text-muted small">Guest</div>
        <strong><?php echo htmlspecialchars($gname); ?></strong>
        <?php if ($meta['is_vip']): ?><span class="badge ml-1" style="background:#ffc107;color:#000">VIP</span><?php endif; ?>
        <?php if ($meta['vip_note']): ?><div class="small text-warning mt-1"><?php echo htmlspecialchars($meta['vip_note']); ?></div><?php endif; ?>
        <div class="text-muted small mt-1"><i class="fa fa-phone mr-1"></i><?php echo htmlspecialchars($meta['contact_no'] ?: '—'); ?></div>
        <div class="text-muted small"><i class="fa fa-id-card mr-1"></i>ID: <?php echo htmlspecialchars($gid_no); ?></div>
      </div>
      <div class="col-md-3">
        <div class="text-muted small">Room</div>
        <strong><?php echo htmlspecialchars($meta['room']??'—'); ?></strong>
        <div class="small text-muted"><?php echo htmlspecialchars($meta['category']??''); ?></div>
        <div class="small text-muted">KES <?php echo number_format($meta['price']??0,2); ?>/night</div>
      </div>
      <div class="col-md-3">
        <div class="text-muted small">Stay Duration</div>
        <strong><?php echo $nights; ?> night<?php echo $nights>1?'s':''; ?></strong>
        <div class="small text-muted">
          In: <?php echo date('M d, Y \a\t H:i',strtotime($meta['date_in'])); ?><br>
          Out: <?php echo date('M d, Y \a\t H:i',strtotime($meta['date_out'])); ?>
        </div>
        <div class="small text-muted">Ref: <code><?php echo htmlspecialchars(trim($meta['ref_no'])); ?></code></div>
      </div>
    </div>
    <?php if ($meta['special_requests']): ?>
    <div class="mt-2 border-top pt-2">
      <small><i class="fa fa-comment-alt mr-1 text-info"></i><strong>Additional Notes:</strong> <?php echo nl2br(htmlspecialchars($meta['special_requests'])); ?></small>
    </div>
    <?php endif; ?>
  </div>

  <!-- Invoice Summary -->
  <?php if ($meta['invoice_id']): ?>
  <div class="card mb-3">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
      <strong><i class="fa fa-file-invoice-dollar mr-1 text-success"></i>Invoice <?php echo htmlspecialchars($meta['invoice_no']); ?></strong>
      <div>
        <span class="badge badge-<?php echo $meta['inv_status']??'secondary'; ?>"><?php echo ucfirst($meta['inv_status']??''); ?></span>
        <a href="invoice_view.php?id=<?php echo $meta['invoice_id']; ?>" target="_blank" class="btn btn-xs btn-outline-secondary ml-2" style="font-size:.72rem;padding:2px 8px">
          <i class="fa fa-print mr-1"></i>Print
        </a>
      </div>
    </div>
    <div class="card-body py-2">
      <div class="row text-center">
        <div class="col-4"><div class="text-muted small">Total</div><strong>KES <?php echo number_format($meta['total']??0,2); ?></strong></div>
        <div class="col-4"><div class="text-muted small">Paid</div><strong class="text-success">KES <?php echo number_format($meta['amount_paid']??0,2); ?></strong></div>
        <div class="col-4"><div class="text-muted small">Balance</div>
          <strong class="<?php echo ($meta['balance']??0)>0?'text-danger':'text-success'; ?>">
            KES <?php echo number_format($meta['balance']??0,2); ?>
          </strong>
        </div>
      </div>

      <?php if (($meta['balance']??0) > 0): ?>
      <div class="text-center mt-2">
        <button class="btn btn-sm btn-success" onclick="uni_modal('Record Payment','manage_payment.php?invoice_id=<?php echo $meta['invoice_id']; ?>')">
          <i class="fa fa-money-bill-wave mr-1"></i>Collect Payment (KES <?php echo number_format($meta['balance'],2); ?> due)
        </button>
      </div>
      <?php else: ?>
      <div class="alert alert-success py-2 mt-2 mb-0 text-center"><i class="fa fa-check-circle mr-1"></i>Fully settled</div>
      <?php endif; ?>
    </div>
  </div>
  <?php else: ?>
  <div class="alert alert-warning mb-3">
    <i class="fa fa-exclamation-triangle mr-1"></i>No invoice found for this stay.
    Amount due (estimated): <strong>KES <?php echo number_format(($meta['price']??0)*$nights,2); ?></strong>
  </div>
  <?php endif; ?>

  <?php if ($meta['status'] == 0): ?>
  <div class="alert alert-info mb-0">
    <i class="fa fa-info-circle mr-1"></i>This is a pending booking. Check the guest in from the
    <strong>Check-In</strong> page when they arrive — an invoice is generated at check-in.
  </div>
  <?php elseif ($meta['status'] == 1): ?>
  <!-- Check-out Confirm -->
  <div class="card">
    <div class="card-body">
      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" id="confirm-checkout">
        <label class="form-check-label" for="confirm-checkout">
          I confirm that <strong><?php echo htmlspecialchars($gname); ?></strong> is departing room <strong><?php echo htmlspecialchars($meta['room']??''); ?></strong>.
        </label>
      </div>
      <div class="d-flex justify-content-between">
        <button class="btn btn-outline-primary btn-sm" onclick="uni_modal('Edit Check-In','manage_check_in.php?id=<?php echo $id; ?>&rid=<?php echo $meta['room_id']; ?>')">
          <i class="fa fa-edit mr-1"></i>Edit Stay
        </button>
        <button class="btn btn-danger" id="checkout-btn">
          <i class="fa fa-sign-out-alt mr-1"></i>Confirm Check-Out
        </button>
      </div>
    </div>
  </div>
  <?php else: ?>
  <div class="alert alert-success"><i class="fa fa-check-circle mr-1"></i>This guest has already checked out.</div>
  <?php endif; ?>

</div>

<script>
$('#checkout-btn').click(function() {
  if (!$('#confirm-checkout').prop('checked')) {
    alert_toast('Please tick the confirmation checkbox', 'warning');
    return;
  }
  start_load();
  $.ajax({
    url: 'ajax.php?action=save_checkout',
    method: 'POST',
    data: { id: '<?php echo $id; ?>', rid: '<?php echo $meta['room_id']; ?>' },
    success: function(resp) {
      end_load();
      // jQuery auto-parses application/json into an object; only JSON.parse a string.
      var r;
      if (resp && typeof resp === 'object') { r = resp; }
      else { try { r = JSON.parse(resp); } catch(e) { r = {status: resp==1?'ok':'error'}; } }
      if (r.status === 'ok') {
        alert_toast('Check-out completed! Housekeeping task created.', 'success');
        setTimeout(function() { location.reload(); }, 1600);
      } else {
        alert_toast(r.message || 'Check-out failed', 'danger');
      }
    }
  });
});
</script>
