<?php
include('db_connect.php');
$invoice_id = (int)($_GET['invoice_id'] ?? 0);
if ($invoice_id <= 0) { echo '<div class="alert alert-danger">Invalid invoice.</div>'; exit; }
$inv = $conn->query("SELECT i.*, c.name AS guest_raw, g.full_name AS guest_name FROM invoices i JOIN checked c ON i.checked_id=c.id LEFT JOIN guests g ON i.guest_id=g.id WHERE i.id=$invoice_id")->fetch_assoc();
if (!$inv) { echo '<div class="alert alert-warning">Invoice not found.</div>'; exit; }
$gname = $inv['guest_name'] ?: $inv['guest_raw'];
?>
<div class="container-fluid">
  <div class="alert alert-light border mb-3">
    <div class="row text-center">
      <div class="col-4"><div class="text-muted small">Invoice</div><strong><?php echo htmlspecialchars($inv['invoice_no']); ?></strong></div>
      <div class="col-4"><div class="text-muted small">Total</div><strong class="text-dark">$<?php echo number_format($inv['total'],2); ?></strong></div>
      <div class="col-4"><div class="text-muted small">Balance Due</div><strong class="text-danger">$<?php echo number_format($inv['balance'],2); ?></strong></div>
    </div>
    <div class="text-center mt-1"><small class="text-muted"><?php echo htmlspecialchars($gname); ?></small></div>
  </div>

  <form id="payment-form">
    <input type="hidden" name="invoice_id" value="<?php echo $invoice_id; ?>">
    <div class="form-group">
      <label>Amount <span class="text-danger">*</span></label>
      <div class="input-group">
        <div class="input-group-prepend"><span class="input-group-text">$</span></div>
        <input type="number" name="amount" class="form-control" step="0.01" min="0.01"
               max="<?php echo $inv['balance']; ?>" value="<?php echo number_format($inv['balance'],2); ?>" required>
      </div>
      <small class="text-muted">Max: $<?php echo number_format($inv['balance'],2); ?></small>
    </div>
    <div class="form-group">
      <label>Payment Method</label>
      <select name="payment_method" class="custom-select">
        <?php foreach (['cash','credit_card','debit_card','bank_transfer','online','cheque','other'] as $m): ?>
        <option value="<?php echo $m; ?>"><?php echo ucwords(str_replace('_',' ',$m)); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Reference / Transaction ID</label>
      <input type="text" name="reference" class="form-control" placeholder="e.g. receipt #, card last 4">
    </div>
    <div class="form-group">
      <label>Notes</label>
      <input type="text" name="notes" class="form-control" placeholder="Optional notes">
    </div>

    <!-- Quick amount buttons -->
    <div class="mb-3">
      <small class="text-muted">Quick amounts:</small><br>
      <?php
      $bal = (float)$inv['balance'];
      $suggestions = array_filter([$bal, round($bal/2,2), 100, 50], fn($v) => $v > 0 && $v <= $bal);
      $suggestions = array_unique($suggestions);
      foreach ($suggestions as $s):
      ?>
      <button type="button" class="btn btn-sm btn-outline-secondary mr-1 mb-1 quick-amt" data-amount="<?php echo $s; ?>">
        $<?php echo number_format($s,2); ?>
      </button>
      <?php endforeach; ?>
    </div>
  </form>
</div>

<script>
$('.quick-amt').click(function() {
  $('[name="amount"]').val($(this).data('amount'));
});

$('#payment-form').submit(function(e) {
  e.preventDefault();
  start_load();
  $.post('ajax.php?action=save_payment', $(this).serialize(), function(resp) {
    end_load();
    var r = JSON.parse(resp);
    if (r.status === 'ok') {
      alert_toast('Payment recorded successfully', 'success');
      setTimeout(function() {
        $('#uni_modal').modal('hide');
        location.reload();
      }, 1200);
    } else {
      alert_toast(r.message || 'Error recording payment', 'danger');
    }
  });
});
</script>
