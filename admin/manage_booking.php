<?php
include('db_connect.php');
// New booking form. Submits to ajax.php?action=save_book which creates a
// `checked` row with status=0 (booked) and booked_cid = chosen category.
$cats = $conn->query("SELECT id, name, price FROM room_categories ORDER BY name ASC");
?>
<div class="container-fluid">
<form id="manage-booking">

  <!-- Guest Search / link -->
  <div class="form-group">
    <label>Link Guest Profile <small class="text-muted">(optional — search by name, email, or phone)</small></label>
    <div class="input-group">
      <input type="text" id="bk-guest-search" class="form-control" placeholder="Search existing guest..." autocomplete="off">
      <div class="input-group-append">
        <button type="button" class="btn btn-outline-secondary" onclick="bkClearGuest()"><i class="fa fa-times"></i></button>
        <button type="button" class="btn btn-outline-primary" onclick="uni_modal('New Guest','manage_guest.php')"><i class="fa fa-plus"></i></button>
      </div>
    </div>
    <input type="hidden" name="guest_id" id="bk-guest-id" value="">
    <div id="bk-guest-results" class="list-group mt-1" style="position:absolute;z-index:1000;width:94%;display:none"></div>
  </div>

  <div class="form-group">
    <label>Guest Name <span class="text-danger">*</span></label>
    <input type="text" name="name" class="form-control" required>
  </div>

  <div class="form-group">
    <label>Contact #</label>
    <input type="text" name="contact" class="form-control">
  </div>

  <div class="form-group">
    <label>Room Category <span class="text-danger">*</span></label>
    <select name="cid" id="bk-cid" class="custom-select" required>
      <option value="">— Select category —</option>
      <?php while ($c = $cats->fetch_assoc()): ?>
      <option value="<?php echo $c['id']; ?>" data-price="<?php echo (float)$c['price']; ?>">
        <?php echo htmlspecialchars($c['name']); ?> — KES <?php echo number_format($c['price'], 2); ?>/night
      </option>
      <?php endwhile; ?>
    </select>
  </div>

  <div class="row">
    <div class="col-md-6">
      <div class="form-group">
        <label>Check-In Date</label>
        <input type="date" name="date_in" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-group">
        <label>Check-In Time</label>
        <input type="time" name="date_in_time" class="form-control" value="14:00" required>
      </div>
    </div>
  </div>

  <div class="form-group">
    <label>Nights of Stay</label>
    <input type="number" name="days" id="bk-days" min="1" class="form-control" value="1" required>
  </div>

  <div class="form-group">
    <label>Special Requests</label>
    <textarea name="special_requests" class="form-control" rows="2" placeholder="Any special requests or notes..."></textarea>
  </div>

  <div id="bk-cost-preview" class="alert border" style="display:none;background:var(--bg-elevated);border-color:var(--border-subtle)!important;color:var(--text-primary)">
    <i class="fa fa-calculator mr-1"></i> Estimated room charge: <strong id="bk-cost-amount">—</strong>
  </div>

  <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-calendar-plus mr-1"></i>Save Booking</button>
</form>
</div>

<script>
function bkUpdateCost() {
  var price = parseFloat($('#bk-cid option:selected').data('price')) || 0;
  var days  = parseInt($('#bk-days').val()) || 1;
  if (price > 0) {
    $('#bk-cost-amount').text('KES ' + (price * days).toFixed(2) + ' (' + days + ' night' + (days > 1 ? 's' : '') + ' × KES ' + price.toFixed(2) + ')');
    $('#bk-cost-preview').show();
  } else {
    $('#bk-cost-preview').hide();
  }
}
$('#bk-cid, #bk-days').on('change input', bkUpdateCost);

// Guest search autocomplete (same endpoint as check-in)
var bkTimer;
$('#bk-guest-search').on('input', function() {
  clearTimeout(bkTimer);
  var q = $(this).val();
  if (q.length < 2) { $('#bk-guest-results').hide(); return; }
  bkTimer = setTimeout(function() {
    $.getJSON('ajax.php?action=search_guests&q=' + encodeURIComponent(q), function(guests) {
      var html = '';
      guests.forEach(function(g) {
        var vip = g.is_vip ? '<span class="badge" style="background:#ffc107;color:#000;font-size:.6rem">VIP</span> ' : '';
        html += '<a href="#" class="list-group-item list-group-item-action py-1 bk-guest-pick" data-id="' + g.id + '" data-name="' + g.full_name + '" data-phone="' + (g.phone || '') + '">' +
                vip + g.full_name + '<small class="text-muted ml-2">' + (g.phone || g.email || '') + '</small></a>';
      });
      if (!html) html = '<div class="list-group-item text-muted">No guests found</div>';
      $('#bk-guest-results').html(html).show();
    });
  }, 300);
});
$(document).on('click', '.bk-guest-pick', function(e) {
  e.preventDefault();
  $('#bk-guest-id').val($(this).data('id'));
  $('#bk-guest-search').val($(this).data('name'));
  $('[name="name"]').val($(this).data('name'));
  if ($(this).data('phone')) $('[name="contact"]').val($(this).data('phone'));
  $('#bk-guest-results').hide();
});
$(document).on('click', function(e) {
  if (!$(e.target).closest('#bk-guest-search, #bk-guest-results').length) $('#bk-guest-results').hide();
});
function bkClearGuest() { $('#bk-guest-id').val(''); $('#bk-guest-search').val(''); }

$('#manage-booking').submit(function(e) {
  e.preventDefault();
  var name = ($('[name="name"]').val() || '').trim();
  var cid  = $('#bk-cid').val();
  if (!name) { alert_toast('Enter the guest name', 'warning'); return; }
  if (!cid)  { alert_toast('Select a room category', 'warning'); return; }
  start_load();
  $.post('ajax.php?action=save_book', $(this).serialize(), function(resp) {
    var r;
    if (resp && typeof resp === 'object') { r = resp; }
    else { try { r = JSON.parse(resp); } catch(e) { r = {status: parseInt(resp) > 0 ? 'ok' : 'error'}; } }
    if (r.status === 'ok' || r.id > 0) {
      alert_toast('Booking saved', 'success');
      setTimeout(function() { $('#uni_modal').modal('hide'); location.reload(); }, 1200);
    } else {
      alert_toast(r.message || 'Could not save booking', 'danger');
    }
  }).fail(function(){ alert_toast('Request failed — please retry', 'danger'); })
    .always(function(){ end_load(); });
});
</script>
