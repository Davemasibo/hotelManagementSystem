<?php
include('db_connect.php');
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { echo '<div class="alert alert-danger">Invalid invoice.</div>'; exit; }

$stmt = $conn->prepare(
    "SELECT i.*, c.ref_no, c.name AS guest_raw, c.date_in, c.date_out, c.room_id,
            g.full_name AS guest_name, g.is_vip, g.email AS guest_email,
            r.room, rc.name AS category, rc.price
     FROM invoices i
     JOIN checked c ON i.checked_id = c.id
     LEFT JOIN guests g ON i.guest_id = g.id
     LEFT JOIN rooms r ON c.room_id = r.id
     LEFT JOIN room_categories rc ON r.category_id = rc.id
     WHERE i.id=?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$inv = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$inv) { echo '<div class="alert alert-warning">Invoice not found.</div>'; exit; }

$items = $conn->query("SELECT * FROM invoice_items WHERE invoice_id=$id ORDER BY id");
$payments = $conn->query(
    "SELECT p.*, u.name AS staff FROM payments p LEFT JOIN users u ON p.processed_by=u.id WHERE p.invoice_id=$id ORDER BY p.created_at"
);
$gname = $inv['guest_name'] ?: $inv['guest_raw'];
$status_colors=['draft'=>'secondary','issued'=>'warning','partial'=>'info','paid'=>'success','cancelled'=>'danger','refunded'=>'purple'];
?>
<div class="container-fluid" style="max-height:80vh;overflow-y:auto">

  <!-- Header -->
  <div class="d-flex justify-content-between align-items-start mb-3">
    <div>
      <h6 class="mb-0"><code><?php echo htmlspecialchars($inv['invoice_no']); ?></code>
        <span class="badge badge-<?php echo $inv['status']; ?> ml-1"><?php echo ucfirst($inv['status']); ?></span>
      </h6>
      <small class="text-muted">
        <?php if ($inv['is_vip']): ?><span class="badge" style="background:#ffc107;color:#000">VIP</span> <?php endif; ?>
        <?php echo htmlspecialchars($gname); ?>
        &bull; <?php echo htmlspecialchars($inv['room']??''); ?>
        &bull; <?php echo substr($inv['date_in']??'',0,10); ?> → <?php echo substr($inv['date_out']??'',0,10); ?>
      </small>
    </div>
    <div>
      <a href="invoice_view.php?id=<?php echo $id; ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
        <i class="fa fa-print mr-1"></i>Print
      </a>
    </div>
  </div>

  <!-- Invoice Settings -->
  <div class="card mb-3">
    <div class="card-header py-2 d-flex justify-content-between">
      <strong>Invoice Settings</strong>
    </div>
    <div class="card-body py-2">
      <form id="inv-settings-form">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <div class="row">
          <div class="col-md-4">
            <label class="small">Tax Rate (%)</label>
            <input type="number" name="tax_rate" class="form-control form-control-sm" min="0" max="100" step="0.01"
                   value="<?php echo number_format($inv['tax_rate'],2); ?>">
          </div>
          <div class="col-md-4">
            <label class="small">Due Date</label>
            <input type="date" name="due_date" class="form-control form-control-sm"
                   value="<?php echo $inv['due_date']??''; ?>">
          </div>
          <div class="col-md-4 d-flex align-items-end">
            <button type="submit" class="btn btn-sm btn-primary w-100">Update</button>
          </div>
        </div>
        <div class="form-group mt-2 mb-0">
          <input type="text" name="notes" class="form-control form-control-sm" placeholder="Invoice notes..."
                 value="<?php echo htmlspecialchars($inv['notes']??''); ?>">
        </div>
      </form>
    </div>
  </div>

  <!-- Line Items -->
  <div class="card mb-3">
    <div class="card-header py-2"><strong>Line Items</strong></div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0" id="items-table">
        <thead>
          <tr><th>Description</th><th>Type</th><th>Qty</th><th>Unit Price</th><th>Amount</th><th></th></tr>
        </thead>
        <tbody id="items-body">
          <?php while ($item = $items->fetch_assoc()): ?>
          <tr id="item-row-<?php echo $item['id']; ?>">
            <td><?php echo htmlspecialchars($item['description']); ?></td>
            <td><span class="badge badge-secondary"><?php echo ucwords(str_replace('_',' ',$item['item_type'])); ?></span></td>
            <td><?php echo number_format($item['quantity'],2); ?></td>
            <td>KES <?php echo number_format($item['unit_price'],2); ?></td>
            <td class="<?php echo $item['item_type']==='discount'?'text-danger':''; ?>">
              <?php echo $item['item_type']==='discount'?'-':''; ?>KES <?php echo number_format($item['amount'],2); ?>
            </td>
            <td>
              <?php if (!in_array($inv['status'],['paid','cancelled','refunded'])): ?>
              <button class="btn btn-xs btn-outline-danger" style="font-size:.7rem"
                onclick="deleteItem(<?php echo $item['id']; ?>,<?php echo $id; ?>)">
                <i class="fa fa-times"></i>
              </button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
        <tfoot class="font-weight-bold" style="background:var(--bg-elevated)">
          <tr><td colspan="4" class="text-right">Subtotal</td><td id="foot-subtotal">KES <?php echo number_format($inv['subtotal'],2); ?></td><td></td></tr>
          <?php if ($inv['discount_amount']>0): ?>
          <tr><td colspan="4" class="text-right text-danger">Discount</td><td class="text-danger" id="foot-discount">-KES <?php echo number_format($inv['discount_amount'],2); ?></td><td></td></tr>
          <?php endif; ?>
          <?php if ($inv['tax_rate']>0): ?>
          <tr><td colspan="4" class="text-right">Tax (<?php echo $inv['tax_rate']; ?>%)</td><td id="foot-tax">KES <?php echo number_format($inv['tax_amount'],2); ?></td><td></td></tr>
          <?php endif; ?>
          <tr style="background:rgba(196,145,58,0.12)"><td colspan="4" class="text-right font-weight-bold">TOTAL</td><td id="foot-total" style="color:var(--brand)">KES <?php echo number_format($inv['total'],2); ?></td><td></td></tr>
          <tr><td colspan="4" class="text-right text-success">Paid</td><td class="text-success" id="foot-paid">KES <?php echo number_format($inv['amount_paid'],2); ?></td><td></td></tr>
          <tr><td colspan="4" class="text-right text-danger">Balance Due</td><td class="text-danger font-weight-bold" id="foot-balance">KES <?php echo number_format($inv['balance'],2); ?></td><td></td></tr>
        </tfoot>
      </table>
    </div>

    <?php if (!in_array($inv['status'],['paid','cancelled','refunded'])): ?>
    <!-- Add Item Form -->
    <div class="card-footer">
      <form id="add-item-form" class="form-inline">
        <input type="hidden" name="invoice_id" value="<?php echo $id; ?>">
        <input type="text" name="description" class="form-control form-control-sm mr-1 mb-1" style="flex:1" placeholder="Description" required>
        <select name="item_type" class="custom-select custom-select-sm mr-1 mb-1">
          <?php foreach (['service','food_beverage','laundry','other','discount'] as $t): ?>
          <option value="<?php echo $t; ?>"><?php echo ucwords(str_replace('_',' ',$t)); ?></option>
          <?php endforeach; ?>
        </select>
        <input type="number" name="quantity" class="form-control form-control-sm mr-1 mb-1" style="width:70px" value="1" min="0.01" step="0.01" placeholder="Qty">
        <input type="number" name="unit_price" class="form-control form-control-sm mr-1 mb-1" style="width:90px" step="0.01" placeholder="Price" required>
        <button type="submit" class="btn btn-sm btn-success mb-1"><i class="fa fa-plus mr-1"></i>Add</button>
      </form>
    </div>
    <?php endif; ?>
  </div>

  <!-- Payments -->
  <div class="card mb-3">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
      <strong>Payment History</strong>
      <?php if (!in_array($inv['status'],['paid','cancelled','refunded'])): ?>
      <button class="btn btn-sm btn-success" onclick="uni_modal('Record Payment','manage_payment.php?invoice_id=<?php echo $id; ?>')">
        <i class="fa fa-plus mr-1"></i>Add Payment
      </button>
      <?php endif; ?>
    </div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0">
        <thead><tr><th>Date</th><th>Method</th><th>Amount</th><th>Reference</th><th>Staff</th></tr></thead>
        <tbody>
        <?php
        $has_payments = false;
        while ($pay = $payments->fetch_assoc()):
            $has_payments = true;
        ?>
          <tr>
            <td><?php echo substr($pay['created_at'],0,16); ?></td>
            <td><span class="badge badge-secondary"><?php echo ucwords(str_replace('_',' ',$pay['payment_method'])); ?></span></td>
            <td class="text-success font-weight-bold">KES <?php echo number_format($pay['amount'],2); ?></td>
            <td><?php echo htmlspecialchars($pay['reference']??'—'); ?></td>
            <td><?php echo htmlspecialchars($pay['staff']??'—'); ?></td>
          </tr>
        <?php endwhile; ?>
        <?php if (!$has_payments): ?>
        <tr><td colspan="5" class="text-center text-muted py-2">No payments recorded</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<script>
$('#inv-settings-form').submit(function(e) {
  e.preventDefault();
  start_load();
  $.post('ajax.php?action=update_invoice', $(this).serialize(), function(resp) {
    end_load();
    var r = (typeof resp === 'string') ? JSON.parse(resp) : resp;
    if (r.status === 'ok') { updateTotals(r.totals); alert_toast('Invoice updated','success'); }
  });
});

$('#add-item-form').submit(function(e) {
  e.preventDefault();
  start_load();
  $.post('ajax.php?action=add_invoice_item', $(this).serialize(), function(resp) {
    end_load();
    var r = (typeof resp === 'string') ? JSON.parse(resp) : resp;
    if (r.status === 'ok') {
      alert_toast('Item added','success');
      setTimeout(function(){ location.reload(); }, 800);
    } else {
      alert_toast(r.message || 'Error','danger');
    }
  });
});

function deleteItem(itemId, invoiceId) {
  if (!confirm('Remove this line item?')) return;
  start_load();
  $.post('ajax.php?action=delete_invoice_item', {id: itemId, invoice_id: invoiceId}, function(resp) {
    end_load();
    var r = (typeof resp === 'string') ? JSON.parse(resp) : resp;
    if (r.status === 'ok') {
      $('#item-row-'+itemId).remove();
      updateTotals(r.totals);
    }
  });
}

function updateTotals(t) {
  $('#foot-subtotal').text('KES '+parseFloat(t.subtotal).toFixed(2));
  $('#foot-tax').text('KES '+parseFloat(t.tax_amount).toFixed(2));
  $('#foot-discount').text('-KES '+parseFloat(t.discount).toFixed(2));
  $('#foot-total').text('KES '+parseFloat(t.total).toFixed(2));
  $('#foot-paid').text('KES '+parseFloat(t.paid).toFixed(2));
  $('#foot-balance').text('KES '+parseFloat(t.balance).toFixed(2));
}
</script>
