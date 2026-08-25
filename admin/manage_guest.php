<?php
include('db_connect.php');
$meta = [];
if (isset($_GET['id']) && (int)$_GET['id'] > 0) {
    $id   = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM guests WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $meta = $stmt->get_result()->fetch_assoc() ?? [];
    $stmt->close();

    // Preferences
    $prefs_res = $conn->prepare("SELECT * FROM guest_preferences WHERE guest_id=?");
    $prefs_res->bind_param("i", $id);
    $prefs_res->execute();
    $prefs = $prefs_res->get_result()->fetch_all(MYSQLI_ASSOC);
    $prefs_res->close();
}
function v($meta, $key, $default = '') {
    return htmlspecialchars($meta[$key] ?? $default, ENT_QUOTES);
}
?>
<div class="container-fluid">
<form id="guest-form">
  <input type="hidden" name="id" value="<?php echo v($meta,'id'); ?>">

  <ul class="nav nav-tabs mb-3" id="guestTab">
    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tab-basic">Basic Info</a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-id">ID &amp; Travel <span class="text-danger">*</span></a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-prefs">Preferences</a></li>
  </ul>

  <div class="tab-content">
    <!-- Basic Info -->
    <div class="tab-pane fade show active" id="tab-basic">
      <div class="row">
        <div class="col-md-8">
          <div class="form-group">
            <label>Full Name <span class="text-danger">*</span></label>
            <input type="text" name="full_name" class="form-control" value="<?php echo v($meta,'full_name'); ?>" required>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label>VIP Status</label>
            <select name="is_vip" class="custom-select">
              <option value="0" <?php echo ($meta['is_vip']??0)==0?'selected':''; ?>>Standard</option>
              <option value="1" <?php echo ($meta['is_vip']??0)==1?'selected':''; ?>>VIP</option>
            </select>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?php echo v($meta,'email'); ?>">
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label>Phone <span class="text-danger">*</span></label>
            <input type="tel" name="phone" class="form-control" placeholder="e.g. 0712 345 678" value="<?php echo v($meta,'phone'); ?>" required>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label>Date of Birth</label>
            <input type="date" name="date_of_birth" class="form-control" value="<?php echo v($meta,'date_of_birth'); ?>">
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label>Nationality</label>
            <input type="text" name="nationality" class="form-control" value="<?php echo v($meta,'nationality'); ?>">
          </div>
        </div>
      </div>
      <div class="form-group">
        <label>Address</label>
        <input type="text" name="address" class="form-control" value="<?php echo v($meta,'address'); ?>">
      </div>
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label>City</label>
            <input type="text" name="city" class="form-control" value="<?php echo v($meta,'city'); ?>">
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label>Country</label>
            <input type="text" name="country" class="form-control" value="<?php echo v($meta,'country'); ?>">
          </div>
        </div>
      </div>
      <div id="vip-note-field" style="<?php echo ($meta['is_vip']??0)==0?'display:none':''; ?>">
        <div class="form-group">
          <label>VIP Note</label>
          <input type="text" name="vip_note" class="form-control" placeholder="Special VIP instructions..." value="<?php echo v($meta,'vip_note'); ?>">
        </div>
      </div>
    </div>

    <!-- ID & Travel -->
    <div class="tab-pane fade" id="tab-id">
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label>ID Type</label>
            <select name="id_type" class="custom-select">
              <?php foreach (['passport','national_id','drivers_license','other'] as $t): ?>
              <option value="<?php echo $t; ?>" <?php echo v($meta,'id_type')===$t?'selected':''; ?>><?php echo ucwords(str_replace('_',' ',$t)); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label>ID Number <span class="text-danger">*</span></label>
            <input type="text" name="id_number" class="form-control" placeholder="e.g. 12345678" value="<?php echo v($meta,'id_number'); ?>" required>
          </div>
        </div>
      </div>
      <div class="form-group">
        <label>Internal Notes</label>
        <textarea name="notes" class="form-control" rows="4" placeholder="Allergies, special needs, internal notes..."><?php echo v($meta,'notes'); ?></textarea>
      </div>
    </div>

    <!-- Preferences -->
    <div class="tab-pane fade" id="tab-prefs">
      <p class="text-muted small">Guest preferences are saved here and auto-populated on check-in.</p>
      <div id="prefs-list">
        <?php foreach (($prefs ?? []) as $p): ?>
        <div class="pref-row d-flex mb-2">
          <input type="text" class="form-control form-control-sm mr-2" name="pref_key[]"   value="<?php echo htmlspecialchars($p['pref_key']); ?>"   placeholder="Preference (e.g. Pillow type)">
          <input type="text" class="form-control form-control-sm mr-2" name="pref_value[]" value="<?php echo htmlspecialchars($p['pref_value']); ?>" placeholder="Value (e.g. Firm)">
          <button type="button" class="btn btn-sm btn-outline-danger" onclick="$(this).closest('.pref-row').remove()"><i class="fa fa-times"></i></button>
        </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add-pref">
        <i class="fa fa-plus mr-1"></i>Add Preference
      </button>
      <div class="mt-3">
        <label class="text-muted small">Common preferences:</label><br>
        <?php foreach (['Pillow type','Floor preference','Room location','Dietary restrictions','Wake-up call','Newspaper','Extra towels'] as $s): ?>
        <button type="button" class="btn btn-xs btn-outline-secondary mr-1 mb-1 suggestion-btn" style="font-size:.75rem"><?php echo $s; ?></button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</form>
</div>

<script>
$('[name="is_vip"]').change(function() {
  $(this).val()==='1' ? $('#vip-note-field').show() : $('#vip-note-field').hide();
});

$('#add-pref').click(function() {
  $('#prefs-list').append(
    '<div class="pref-row d-flex mb-2">' +
    '<input type="text" class="form-control form-control-sm mr-2" name="pref_key[]" placeholder="Preference">' +
    '<input type="text" class="form-control form-control-sm mr-2" name="pref_value[]" placeholder="Value">' +
    '<button type="button" class="btn btn-sm btn-outline-danger" onclick="$(this).closest(\'.pref-row\').remove()"><i class="fa fa-times"></i></button></div>'
  );
});

$('.suggestion-btn').click(function() {
  $('#prefs-list').append(
    '<div class="pref-row d-flex mb-2">' +
    '<input type="text" class="form-control form-control-sm mr-2" name="pref_key[]" value="' + $(this).text() + '">' +
    '<input type="text" class="form-control form-control-sm mr-2" name="pref_value[]" placeholder="Value">' +
    '<button type="button" class="btn btn-sm btn-outline-danger" onclick="$(this).closest(\'.pref-row\').remove()"><i class="fa fa-times"></i></button></div>'
  );
});

$('#guest-form').submit(function(e) {
  e.preventDefault();

  var name  = ($('[name="full_name"]').val() || '').trim();
  var phone = ($('[name="phone"]').val()     || '').trim();
  var idno  = ($('[name="id_number"]').val() || '').trim();
  if (!name) {
    $('#guestTab a[href="#tab-basic"]').tab('show');
    alert_toast('Full name is required', 'warning'); $('[name="full_name"]').focus(); return;
  }
  if (!phone || phone.replace(/\D/g, '').length < 7) {
    $('#guestTab a[href="#tab-basic"]').tab('show');
    alert_toast('A valid phone number is required', 'warning'); $('[name="phone"]').focus(); return;
  }
  // ID Number lives on the second tab — switch to it so the user sees what is missing.
  if (!idno) {
    $('#guestTab a[href="#tab-id"]').tab('show');
    alert_toast('ID / passport number is required', 'warning'); $('[name="id_number"]').focus(); return;
  }

  start_load();
  var data = $(this).serialize();
  $.post('ajax.php?action=save_guest', data, function(resp) {
    end_load();
    try { var r = JSON.parse(resp); } catch(e) { var r = resp; }
    if (r.status === 'ok' || r === 1) {
      alert_toast('Guest saved successfully', 'success');
      setTimeout(function() {
        $('#uni_modal').modal('hide');
        location.reload();
      }, 1200);
    } else {
      alert_toast(r.message || 'Error saving guest', 'danger');
    }
  });
});
</script>
