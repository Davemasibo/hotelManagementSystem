<?php
include('db_connect.php');
$rid = (int)($_GET['rid'] ?? 0);
$id  = (int)($_GET['id']  ?? 0);
$meta = [];

if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM checked WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $meta = $stmt->get_result()->fetch_assoc() ?? [];
    $stmt->close();
    $calc_days = max(1, (int)round(abs(strtotime($meta['date_out']) - strtotime($meta['date_in'])) / 86400));
    $rooms = $conn->query("SELECT r.id, r.room, rc.name FROM rooms r LEFT JOIN room_categories rc ON r.category_id=rc.id WHERE r.status=0 OR r.id=$rid ORDER BY r.room");
}

// Load preferences if guest_id exists
$guest_prefs = [];
if (!empty($meta['guest_id'])) {
    $gp = $conn->prepare("SELECT * FROM guest_preferences WHERE guest_id=?");
    $gp->bind_param("i", $meta['guest_id']);
    $gp->execute();
    $guest_prefs = $gp->get_result()->fetch_all(MYSQLI_ASSOC);
    $gp->close();
}
?>
<div class="container-fluid">
<form id="manage-check">
  <input type="hidden" name="id" value="<?php echo $meta['id'] ?? ''; ?>">

  <?php if ($id > 0): ?>
  <div class="form-group">
    <label>Room</label>
    <select name="rid" class="custom-select">
      <?php while ($row = $rooms->fetch_assoc()): ?>
      <option value="<?php echo $row['id']; ?>" <?php echo $row['id']==$rid?'selected':''; ?>>
        <?php echo htmlspecialchars($row['room'] . ' — ' . $row['name']); ?>
      </option>
      <?php endwhile; ?>
    </select>
  </div>
  <?php else: ?>
  <input type="hidden" name="rid" value="<?php echo $rid; ?>">
  <?php if ($rid > 0):
    $rm = $conn->query("SELECT r.room, rc.name, rc.price FROM rooms r LEFT JOIN room_categories rc ON r.category_id=rc.id WHERE r.id=$rid")->fetch_assoc();
  ?>
  <div class="alert alert-info py-2 mb-3">
    <i class="fa fa-bed mr-1"></i>
    <strong><?php echo htmlspecialchars($rm['room']??''); ?></strong> — <?php echo htmlspecialchars($rm['name']??''); ?>
    &bull; <strong>KES <?php echo number_format($rm['price']??0,2); ?>/night</strong>
  </div>
  <?php endif; ?>
  <?php endif; ?>

  <!-- Guest Search -->
  <div class="form-group">
    <label>Link Guest Profile <small class="text-muted">(optional — search by name, email, or phone)</small></label>
    <div class="input-group">
      <input type="text" id="guest-search" class="form-control" placeholder="Search existing guest..." autocomplete="off"
             value="<?php echo htmlspecialchars(!empty($meta['guest_id']) ? ($meta['name']??'') : ''); ?>">
      <div class="input-group-append">
        <button type="button" class="btn btn-outline-secondary" onclick="clearGuest()"><i class="fa fa-times"></i></button>
        <button type="button" class="btn btn-outline-primary" onclick="uni_modal('New Guest','manage_guest.php')"><i class="fa fa-plus"></i></button>
      </div>
    </div>
    <input type="hidden" name="guest_id" id="guest-id" value="<?php echo $meta['guest_id']??''; ?>">
    <div id="guest-results" class="list-group mt-1" style="position:absolute;z-index:1000;width:94%;display:none"></div>
    <?php if (!empty($guest_prefs)): ?>
    <div class="mt-1 p-2 rounded" id="guest-prefs-display" style="background:var(--bg-elevated);border:1px solid var(--border-subtle)">
      <small class="text-muted">Preferences: </small>
      <?php foreach($guest_prefs as $gp): ?>
      <span class="badge badge-secondary mr-1"><?php echo htmlspecialchars($gp['pref_key']); ?>: <?php echo htmlspecialchars($gp['pref_value']); ?></span>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <div class="form-group">
    <label>Guest Name <span class="text-danger">*</span></label>
    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($meta['name']??''); ?>" required>
  </div>

  <div class="form-group">
    <label>Contact #</label>
    <input type="text" name="contact" class="form-control" value="<?php echo htmlspecialchars($meta['contact_no']??''); ?>">
  </div>

  <div class="row">
    <div class="col-md-6">
      <div class="form-group">
        <label>Check-In Date</label>
        <input type="date" name="date_in" class="form-control"
               value="<?php echo isset($meta['date_in']) ? date('Y-m-d',strtotime($meta['date_in'])) : date('Y-m-d'); ?>" required>
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-group">
        <label>Check-In Time</label>
        <input type="time" name="date_in_time" class="form-control"
               value="<?php echo isset($meta['date_in']) ? date('H:i',strtotime($meta['date_in'])) : '14:00'; ?>" required>
      </div>
    </div>
  </div>

  <div class="form-group">
    <label>Days of Stay <span id="checkout-preview" class="text-muted small"></span></label>
    <input type="number" name="days" id="days-input" min="1" class="form-control"
           value="<?php echo isset($meta['date_in']) ? $calc_days : 1; ?>" required>
  </div>

  <div class="form-group">
    <label>Special Requests</label>
    <textarea name="special_requests" class="form-control" rows="2" placeholder="Any special requests or notes..."><?php echo htmlspecialchars($meta['special_requests']??''); ?></textarea>
  </div>

  <!-- Estimated cost preview -->
  <div id="cost-preview" class="alert border" style="display:none;background:var(--bg-elevated);border-color:var(--border-subtle)!important;color:var(--text-primary)">
    <i class="fa fa-calculator mr-1"></i>
    Estimated room charge: <strong id="cost-amount">—</strong>
  </div>
</form>
</div>

<script>
<?php if (!empty($rm)): ?>
var pricePerNight = <?php echo (float)($rm['price']??0); ?>;
<?php else: ?>
var pricePerNight = 0;
<?php endif; ?>

function updateCostPreview() {
  var days = parseInt($('#days-input').val()) || 1;
  var dateIn = $('[name="date_in"]').val();
  var timeIn = $('[name="date_in_time"]').val();
  if (dateIn && timeIn) {
    var checkoutDate = new Date(dateIn + 'T' + timeIn);
    checkoutDate.setDate(checkoutDate.getDate() + days);
    $('#checkout-preview').text('— checkout: ' + checkoutDate.toLocaleDateString('en-US',{weekday:'short',month:'short',day:'numeric'}));
  }
  if (pricePerNight > 0) {
    var total = (pricePerNight * days).toFixed(2);
    $('#cost-amount').text('KES ' + total + ' (' + days + ' night' + (days>1?'s':'') + ' × KES ' + pricePerNight.toFixed(2) + ')');
    $('#cost-preview').show();
  }
}

$('#days-input, [name="date_in"], [name="date_in_time"]').on('change input', updateCostPreview);
updateCostPreview();

// Guest search autocomplete
var searchTimer;
$('#guest-search').on('input', function() {
  clearTimeout(searchTimer);
  var q = $(this).val();
  if (q.length < 2) { $('#guest-results').hide(); return; }
  searchTimer = setTimeout(function() {
    $.getJSON('ajax.php?action=search_guests&q=' + encodeURIComponent(q), function(guests) {
      var html = '';
      guests.forEach(function(g) {
        var vip = g.is_vip ? '<span class="badge" style="background:#ffc107;color:#000;font-size:.6rem">VIP</span> ' : '';
        html += '<a href="#" class="list-group-item list-group-item-action py-1 guest-pick" data-id="'+g.id+'" data-name="'+g.full_name+'">' +
                vip + g.full_name + '<small class="text-muted ml-2">' + (g.phone||g.email||'') + '</small></a>';
      });
      if (!html) html = '<div class="list-group-item text-muted">No guests found</div>';
      $('#guest-results').html(html).show();
    });
  }, 300);
});

$(document).on('click', '.guest-pick', function(e) {
  e.preventDefault();
  var gid  = $(this).data('id');
  var name = $(this).data('name');
  $('#guest-id').val(gid);
  $('#guest-search').val(name);
  $('[name="name"]').val(name);
  $('#guest-results').hide();
});

$(document).click(function(e) {
  if (!$(e.target).closest('#guest-search, #guest-results').length) $('#guest-results').hide();
});

function clearGuest() {
  $('#guest-id').val('');
  $('#guest-search').val('');
  $('#guest-prefs-display').remove();
}

$('#manage-check').submit(function(e) {
  e.preventDefault();
  start_load();
  $.ajax({
    url: 'ajax.php?action=save_check-in',
    method: 'POST',
    data: $(this).serialize(),
    success: function(resp) {
      end_load();
      // jQuery auto-parses application/json responses into an object, so `resp`
      // may already be an object here. Only JSON.parse when it's still a string,
      // and keep the numeric-string fallback for legacy plain-number responses.
      var r;
      if (resp && typeof resp === 'object') {
        r = resp;
      } else {
        try { r = JSON.parse(resp); }
        catch(e) { var n = parseInt(resp); r = {status: n > 0 ? 'ok' : 'error', id: n}; }
      }
      if (r.status === 'ok' || r.id > 0) {
        alert_toast('Check-in successful!', 'success');
        if (r.invoice_id) {
          setTimeout(function() {
            uni_modal('Invoice', 'manage_billing.php?id=' + r.invoice_id);
          }, 1300);
        } else {
          setTimeout(function() { location.reload(); }, 1500);
        }
      } else {
        alert_toast(r.message || 'Check-in failed', 'danger');
      }
    }
  });
});
</script>
