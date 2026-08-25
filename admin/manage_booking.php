<?php
include('db_connect.php');
require_once('reservation_helpers.php');
// New booking form. Submits to ajax.php?action=save_book which creates a
// `checked` row with status=0 (booked) and booked_cid = chosen category.
$cats  = $conn->query("SELECT id, name, price FROM room_categories ORDER BY name ASC");
$times = reservation_time_defaults($conn);
$today = date('Y-m-d');
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

  <div class="row">
    <div class="col-md-6">
      <div class="form-group">
        <label>Phone Number <span class="text-danger">*</span></label>
        <input type="tel" name="contact" class="form-control" placeholder="e.g. 0712 345 678" required>
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-group">
        <label>ID / Passport Number <span class="text-danger">*</span></label>
        <input type="text" name="id_number" class="form-control" placeholder="e.g. 12345678" required>
      </div>
    </div>
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
        <!-- min = today: the browser's date picker greys out past dates, and
             save_book() re-checks server-side in case the field is bypassed. -->
        <label>Check-In Date <span class="text-danger">*</span></label>
        <input type="date" name="date_in" id="bk-date-in" class="form-control"
               value="<?php echo $today; ?>" min="<?php echo $today; ?>" required>
        <small class="text-muted">Past dates are not allowed.</small>
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-group">
        <label>Check-In Time <span class="text-danger">*</span></label>
        <input type="time" name="date_in_time" class="form-control" value="<?php echo htmlspecialchars($times['in']); ?>" required>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-6">
      <div class="form-group">
        <label>Nights of Stay <span class="text-danger">*</span></label>
        <input type="number" name="days" id="bk-days" min="1" class="form-control" value="1" required>
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-group">
        <label>Check-Out Time <span class="text-danger">*</span></label>
        <input type="time" name="date_out_time" id="bk-out-time" class="form-control" value="<?php echo htmlspecialchars($times['out']); ?>" required>
      </div>
    </div>
  </div>

  <div class="alert border py-2" id="bk-checkout-preview" style="background:var(--bg-elevated);border-color:var(--border-subtle)!important;color:var(--text-primary)">
    <i class="fa fa-sign-out-alt mr-1"></i> Check-out: <strong id="bk-checkout-text">—</strong>
  </div>

  <div class="form-group">
    <label>Additional Notes</label>
    <textarea name="special_requests" class="form-control" rows="4"
              placeholder="Special requests, and any other guests on this reservation — e.g. &quot;Travelling with family: Jane Doe (spouse), Amos Doe (son, 8). Requires an extra bed and a cot.&quot;"></textarea>
    <small class="text-muted">Use this to record accompanying guests / family members, extra beds, allergies or any other request.</small>
  </div>

  <div id="bk-cost-preview" class="alert border" style="display:none;background:var(--bg-elevated);border-color:var(--border-subtle)!important;color:var(--text-primary)">
    <i class="fa fa-calculator mr-1"></i> Estimated room charge: <strong id="bk-cost-amount">—</strong>
  </div>

  <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-calendar-plus mr-1"></i>Save Booking</button>
</form>
</div>

<script>
var BK_TODAY = '<?php echo $today; ?>';

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

// Check-out = check-in date + N nights, at the chosen check-out time.
function bkUpdateCheckout() {
  var dateIn = $('#bk-date-in').val();
  var days   = parseInt($('#bk-days').val()) || 1;
  var outT   = $('#bk-out-time').val() || '11:00';
  if (!dateIn) { $('#bk-checkout-text').text('—'); return; }
  var d = new Date(dateIn + 'T00:00:00');
  d.setDate(d.getDate() + days);
  $('#bk-checkout-text').text(
    d.toLocaleDateString('en-GB', {weekday:'short', day:'numeric', month:'short', year:'numeric'}) + ' at ' + outT
  );
}

// Backdating guard: snap the field back to today if an older date is typed in
// (typing bypasses the picker's `min`).
$('#bk-date-in').on('change', function() {
  if (this.value && this.value < BK_TODAY) {
    alert_toast('Check-in date cannot be in the past', 'warning');
    this.value = BK_TODAY;
  }
});

$('#bk-cid, #bk-days').on('change input', bkUpdateCost);
$('#bk-date-in, #bk-days, #bk-out-time').on('change input', bkUpdateCheckout);
bkUpdateCheckout();

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
        html += '<a href="#" class="list-group-item list-group-item-action py-1 bk-guest-pick" data-id="' + g.id + '" data-name="' + g.full_name + '" data-phone="' + (g.phone || '') + '" data-idno="' + (g.id_number || '') + '">' +
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
  if ($(this).data('idno'))  $('[name="id_number"]').val($(this).data('idno'));
  $('#bk-guest-results').hide();
});
$(document).on('click', function(e) {
  if (!$(e.target).closest('#bk-guest-search, #bk-guest-results').length) $('#bk-guest-results').hide();
});
function bkClearGuest() { $('#bk-guest-id').val(''); $('#bk-guest-search').val(''); }

$('#manage-booking').submit(function(e) {
  e.preventDefault();
  var name  = ($('[name="name"]').val() || '').trim();
  var cid   = $('#bk-cid').val();
  var phone = ($('[name="contact"]').val() || '').trim();
  var idno  = ($('[name="id_number"]').val() || '').trim();
  var dIn   = $('#bk-date-in').val();
  if (!name)  { alert_toast('Enter the guest name', 'warning'); return; }
  if (!phone) { alert_toast('Phone number is required', 'warning'); $('[name="contact"]').focus(); return; }
  if (phone.replace(/\D/g, '').length < 7) { alert_toast('Enter a valid phone number', 'warning'); $('[name="contact"]').focus(); return; }
  if (!idno)  { alert_toast('ID / passport number is required', 'warning'); $('[name="id_number"]').focus(); return; }
  if (!cid)   { alert_toast('Select a room category', 'warning'); return; }
  if (!dIn || dIn < BK_TODAY) { alert_toast('Check-in date cannot be in the past', 'warning'); $('#bk-date-in').focus(); return; }
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
