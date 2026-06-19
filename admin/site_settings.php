<?php
include 'db_connect.php';
$meta = [];
$qry  = $conn->query("SELECT * FROM system_settings LIMIT 1");
if ($qry && $qry->num_rows > 0) {
    $meta = $qry->fetch_assoc();
}
?>

<div class="container-fluid py-3">

  <div class="page-header mb-4">
    <h5 class="page-title"><i class="fa fa-cog mr-2"></i>Site Settings</h5>
  </div>

  <form id="manage-settings" enctype="multipart/form-data">

    <!-- Hotel Identity -->
    <div class="card mb-4">
      <div class="card-header"><strong><i class="fa fa-hotel mr-2"></i>Hotel Identity</strong></div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label class="control-label">Hotel Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($meta['hotel_name'] ?? ''); ?>" required>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label class="control-label">Tagline / Slogan</label>
              <input type="text" class="form-control" name="tagline" value="<?php echo htmlspecialchars($meta['tagline'] ?? ''); ?>" placeholder="e.g. Your comfort, our priority">
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label class="control-label">Email Address <span class="text-danger">*</span></label>
              <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($meta['email'] ?? ''); ?>" required>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label class="control-label">Phone / Contact</label>
              <input type="text" class="form-control" name="contact" value="<?php echo htmlspecialchars($meta['contact'] ?? ''); ?>" placeholder="+254 700 000 000">
            </div>
          </div>
        </div>

        <div class="form-group">
          <label class="control-label">Physical Address</label>
          <input type="text" class="form-control" name="address" value="<?php echo htmlspecialchars($meta['address'] ?? ''); ?>" placeholder="e.g. Nairobi, Kenya">
        </div>

        <div class="form-group">
          <label class="control-label">About / Description</label>
          <textarea name="about" class="form-control text-jqte" rows="4"><?php echo htmlspecialchars($meta['about_content'] ?? ''); ?></textarea>
        </div>
      </div>
    </div>

    <!-- Branding -->
    <div class="card mb-4">
      <div class="card-header"><strong><i class="fa fa-image mr-2"></i>Branding</strong></div>
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-md-4">
            <div class="form-group mb-0">
              <label class="control-label">Hotel Logo / Cover Image</label>
              <input type="file" class="form-control" name="img" accept="image/*" onchange="previewImg(this)">
              <small class="text-muted">JPG, PNG, WEBP — recommended 800×400px</small>
            </div>
          </div>
          <div class="col-md-4 text-center mt-3 mt-md-0">
            <?php if (!empty($meta['cover_img'])): ?>
            <img src="../assets/img/<?php echo htmlspecialchars($meta['cover_img']); ?>" id="cimg"
                 style="max-height:100px;max-width:100%;border-radius:8px;filter:brightness(0.9)">
            <?php else: ?>
            <img src="" id="cimg" style="max-height:100px;max-width:100%;border-radius:8px;display:none">
            <div id="no-img" class="text-muted py-3"><i class="fa fa-image fa-2x d-block mb-1"></i>No image set</div>
            <?php endif; ?>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label class="control-label">Currency Symbol</label>
              <input type="text" class="form-control" name="currency" value="<?php echo htmlspecialchars($meta['currency'] ?? 'KES'); ?>" placeholder="KES">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- System Preferences -->
    <div class="card mb-4">
      <div class="card-header"><strong><i class="fa fa-sliders-h mr-2"></i>System Preferences</strong></div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-4">
            <div class="form-group">
              <label class="control-label">Default Check-In Time</label>
              <input type="time" class="form-control" name="checkin_time" value="<?php echo htmlspecialchars($meta['checkin_time'] ?? '14:00'); ?>">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label class="control-label">Default Check-Out Time</label>
              <input type="time" class="form-control" name="checkout_time" value="<?php echo htmlspecialchars($meta['checkout_time'] ?? '11:00'); ?>">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label class="control-label">Tax Rate (%)</label>
              <input type="number" class="form-control" name="default_tax" min="0" max="100" step="0.01"
                     value="<?php echo htmlspecialchars($meta['default_tax'] ?? '0'); ?>">
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label class="control-label">Max Rooms per Floor</label>
              <input type="number" class="form-control" name="max_rooms_per_floor" min="1"
                     value="<?php echo htmlspecialchars($meta['max_rooms_per_floor'] ?? '20'); ?>">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label class="control-label">Invoice Prefix</label>
              <input type="text" class="form-control" name="invoice_prefix" maxlength="10"
                     value="<?php echo htmlspecialchars($meta['invoice_prefix'] ?? 'INV'); ?>" placeholder="INV">
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-end">
      <button type="submit" class="btn btn-primary px-5">
        <i class="fa fa-save mr-2"></i>Save Settings
      </button>
    </div>

  </form>
</div>

<script>
function previewImg(input) {
  if (input.files && input.files[0]) {
    var reader = new FileReader();
    reader.onload = function(e) {
      $('#cimg').attr('src', e.target.result).show();
      $('#no-img').hide();
    };
    reader.readAsDataURL(input.files[0]);
  }
}

if (typeof $.fn.jqte === 'function') {
  $('.text-jqte').jqte();
}

$('#manage-settings').submit(function(e) {
  e.preventDefault();
  start_load();
  $.ajax({
    url: 'ajax.php?action=save_settings',
    data: new FormData(this),
    cache: false,
    contentType: false,
    processData: false,
    method: 'POST',
    success: function(resp) {
      end_load();
      if (resp == 1) {
        alert_toast('Settings saved successfully', 'success');
        setTimeout(function() { location.reload(); }, 1200);
      } else {
        alert_toast('Failed to save settings', 'danger');
      }
    },
    error: function() {
      end_load();
      alert_toast('Request failed', 'danger');
    }
  });
});
</script>
