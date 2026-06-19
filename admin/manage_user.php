<?php
include('db_connect.php');
$meta = [];
if (!empty($_GET['id'])) {
    $uid  = (int)$_GET['id'];
    $row  = $conn->query("SELECT * FROM users WHERE id=$uid");
    if ($row && $row->num_rows > 0) {
        $meta = $row->fetch_assoc();
    }
}
$is_edit = !empty($meta['id']);
?>
<div class="container-fluid">
  <form id="manage-user">
    <input type="hidden" name="id" value="<?php echo $meta['id'] ?? ''; ?>">

    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <label>Full Name <span class="text-danger">*</span></label>
          <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($meta['name'] ?? ''); ?>" required>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <label>Username <span class="text-danger">*</span></label>
          <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($meta['username'] ?? ''); ?>" required>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($meta['email'] ?? ''); ?>" placeholder="user@hotel.com">
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <label>Phone</label>
          <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($meta['phone'] ?? ''); ?>" placeholder="+254...">
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-4">
        <div class="form-group">
          <label>User Type <span class="text-danger">*</span></label>
          <select name="type" class="custom-select">
            <option value="1" <?php echo ($meta['type'] ?? '') == 1 ? 'selected' : ''; ?>>Admin</option>
            <option value="2" <?php echo ($meta['type'] ?? '') == 2 ? 'selected' : ''; ?>>Staff</option>
          </select>
        </div>
      </div>
      <div class="col-md-4">
        <div class="form-group">
          <label>Account Status</label>
          <select name="status" class="custom-select">
            <option value="active"   <?php echo ($meta['status'] ?? '') === 'active'   ? 'selected' : ''; ?>>Active</option>
            <option value="pending"  <?php echo ($meta['status'] ?? '') === 'pending'  ? 'selected' : ''; ?>>Pending</option>
            <option value="rejected" <?php echo ($meta['status'] ?? '') === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
          </select>
        </div>
      </div>
      <div class="col-md-4">
        <div class="form-group">
          <label>Active</label>
          <select name="is_active" class="custom-select">
            <option value="1" <?php echo ($meta['is_active'] ?? 1) == 1 ? 'selected' : ''; ?>>Yes</option>
            <option value="0" <?php echo ($meta['is_active'] ?? 1) == 0 ? 'selected' : ''; ?>>No</option>
          </select>
        </div>
      </div>
    </div>

    <div class="form-group">
      <label>Password <?php echo $is_edit ? '<small class="text-muted">(leave blank to keep current)</small>' : '<span class="text-danger">*</span>'; ?></label>
      <div class="input-group">
        <input type="password" name="password" id="user-pwd" class="form-control"
               <?php echo !$is_edit ? 'required' : ''; ?> placeholder="<?php echo $is_edit ? 'Leave blank to keep current' : 'Enter password'; ?>">
        <div class="input-group-append">
          <button type="button" class="btn btn-outline-secondary" onclick="togglePwd()">
            <i class="fa fa-eye" id="pwd-eye"></i>
          </button>
        </div>
      </div>
      <?php if (!$is_edit): ?>
      <div class="mt-1" style="height:4px;border-radius:2px;background:var(--bg-elevated);overflow:hidden">
        <div id="pwd-strength-bar" style="height:100%;width:0;transition:width .3s,background .3s"></div>
      </div>
      <?php endif; ?>
    </div>

    <?php if ($is_edit): ?>
    <div class="alert border py-2 mb-3" style="background:var(--bg-elevated);border-color:var(--border-subtle)!important;font-size:.85rem">
      <i class="fa fa-info-circle mr-1 text-info"></i>
      Last login: <strong><?php echo !empty($meta['last_login']) ? $meta['last_login'] : 'Never'; ?></strong>
      &nbsp;&bull;&nbsp; Member since: <strong><?php echo !empty($meta['date_added']) ? date('M d, Y', strtotime($meta['date_added'])) : '—'; ?></strong>
    </div>
    <?php endif; ?>

    <div class="d-flex justify-content-end" style="gap:8px">
      <button type="button" class="btn btn-sm btn-secondary" onclick="$('#uni_modal').modal('hide')">
        <i class="fa fa-times mr-1"></i>Cancel
      </button>
      <button type="submit" class="btn btn-sm btn-primary">
        <i class="fa fa-save mr-1"></i><?php echo $is_edit ? 'Update User' : 'Create User'; ?>
      </button>
    </div>
  </form>
</div>

<script>
function togglePwd() {
  var f = $('#user-pwd');
  var isText = f.attr('type') === 'text';
  f.attr('type', isText ? 'password' : 'text');
  $('#pwd-eye').toggleClass('fa-eye fa-eye-slash');
}

<?php if (!$is_edit): ?>
$('#user-pwd').on('input', function() {
  var v = $(this).val(), s = 0, color = '#ef4444';
  if (v.length >= 8) s++;
  if (/[A-Z]/.test(v)) s++;
  if (/[0-9]/.test(v)) s++;
  if (/[^A-Za-z0-9]/.test(v)) s++;
  var w = ['0%','25%','50%','75%','100%'][s];
  var colors = ['#ef4444','#ef4444','#f97316','#eab308','#22c55e'];
  $('#pwd-strength-bar').css({width: w, background: colors[s]});
});
<?php endif; ?>

$('#manage-user').submit(function(e) {
  e.preventDefault();
  start_load();
  $.ajax({
    url: 'ajax.php?action=save_user',
    method: 'POST',
    data: $(this).serialize(),
    success: function(resp) {
      end_load();
      if (resp == 1) {
        alert_toast('User saved successfully', 'success');
        setTimeout(function() { location.reload(); }, 1500);
      } else {
        alert_toast('Failed to save user', 'danger');
      }
    }
  });
});
</script>
