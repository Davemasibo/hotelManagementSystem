<?php
include 'db_connect.php';

// Pending signups
$pending_q = $conn->query("SELECT * FROM users WHERE status = 'pending' ORDER BY id DESC");
$pending   = $pending_q ? $pending_q->fetch_all(MYSQLI_ASSOC) : [];

// Active/rejected users
$users_q = $conn->query("SELECT * FROM users WHERE status != 'pending' ORDER BY name ASC");
?>

<div class="container-fluid">

  <!-- Pending Approvals Banner -->
  <?php if (!empty($pending)): ?>
  <div class="row mb-3">
    <div class="col-12">
      <div class="card border-0" style="background:linear-gradient(135deg,#1a1208,#3a2808);border-left:4px solid #c4913a !important;">
        <div class="card-body py-3">
          <h6 class="text-warning mb-0"><i class="fas fa-user-clock mr-2"></i>
            <?php echo count($pending); ?> Pending Access Request<?php echo count($pending) > 1 ? 's' : ''; ?> — awaiting your approval
          </h6>
        </div>
      </div>
    </div>
  </div>

  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-header bg-dark text-white">
          <i class="fas fa-user-clock mr-2"></i> Pending Approvals
        </div>
        <div class="card-body p-0">
          <table class="table table-dark table-hover mb-0">
            <thead>
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Requested</th>
                <th class="text-center">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pending as $i => $row): ?>
              <tr>
                <td><?php echo $i + 1; ?></td>
                <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                <td><code><?php echo htmlspecialchars($row['username']); ?></code></td>
                <td><?php echo htmlspecialchars($row['email'] ?? '—'); ?></td>
                <td><?php echo htmlspecialchars($row['phone'] ?? '—'); ?></td>
                <td><small class="text-muted"><?php echo date('M j, Y', strtotime($row['last_login'] ?? 'now')); ?></small></td>
                <td class="text-center">
                  <button class="btn btn-success btn-sm approve_user" data-id="<?php echo $row['id']; ?>"
                          title="Approve">
                    <i class="fas fa-check mr-1"></i> Approve
                  </button>
                  <button class="btn btn-danger btn-sm reject_user ml-1" data-id="<?php echo $row['id']; ?>"
                          title="Reject">
                    <i class="fas fa-times mr-1"></i> Reject
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- All Users -->
  <div class="row">
    <div class="col-12">
      <button class="btn btn-primary float-right btn-sm mb-2" id="new_user">
        <i class="fa fa-plus mr-1"></i> New User
      </button>
    </div>
  </div>
  <div class="row">
    <div class="card col-lg-12">
      <div class="card-body">
        <table class="table table-striped table-bordered col-md-12" id="users-table">
          <thead>
            <tr>
              <th class="text-center">#</th>
              <th>Name</th>
              <th>Username</th>
              <th>Email</th>
              <th class="text-center">Type</th>
              <th class="text-center">Status</th>
              <th class="text-center">Last Login</th>
              <th class="text-center">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php $i = 1; while ($row = $users_q->fetch_assoc()): ?>
            <tr>
              <td class="text-center"><?php echo $i++; ?></td>
              <td><?php echo htmlspecialchars($row['name']); ?></td>
              <td><code><?php echo htmlspecialchars($row['username']); ?></code></td>
              <td><?php echo htmlspecialchars($row['email'] ?? '—'); ?></td>
              <td class="text-center">
                <span class="badge badge-<?php echo $row['type'] == 1 ? 'danger' : 'info'; ?>">
                  <?php echo $row['type'] == 1 ? 'Admin' : 'Staff'; ?>
                </span>
              </td>
              <td class="text-center">
                <span class="badge badge-<?php echo $row['status'] === 'active' ? 'success' : 'secondary'; ?>">
                  <?php echo ucfirst($row['status'] ?? 'active'); ?>
                </span>
              </td>
              <td class="text-center">
                <small><?php echo $row['last_login'] ? date('M j, Y g:i a', strtotime($row['last_login'])) : '—'; ?></small>
              </td>
              <td class="text-center">
                <div class="btn-group">
                  <button class="btn btn-primary btn-sm">Action</button>
                  <button class="btn btn-primary btn-sm dropdown-toggle dropdown-toggle-split" data-toggle="dropdown">
                    <span class="sr-only">Toggle</span>
                  </button>
                  <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item edit_user" href="javascript:void(0)" data-id="<?php echo $row['id']; ?>">
                      <i class="fas fa-edit fa-fw mr-1"></i> Edit
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger delete_user" href="javascript:void(0)" data-id="<?php echo $row['id']; ?>">
                      <i class="fas fa-trash fa-fw mr-1"></i> Delete
                    </a>
                  </div>
                </div>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
$('#new_user').click(function(){
  uni_modal('New User', 'manage_user.php');
});
$('.edit_user').click(function(){
  uni_modal('Edit User', 'manage_user.php?id=' + $(this).data('id'));
});

$(document).on('click', '.approve_user', function(){
  const id = $(this).data('id');
  const $row = $(this).closest('tr');
  if (!confirm('Approve this user and grant access?')) return;
  $.post('ajax.php?action=approve_user', {id: id}, function(resp){
    const r = typeof resp === 'string' ? JSON.parse(resp) : resp;
    if (r.status === 'ok') {
      alert_toast('User approved and activated.', 'success');
      setTimeout(() => location.reload(), 1200);
    } else {
      alert_toast(r.message || 'Failed to approve user.', 'danger');
    }
  });
});

$(document).on('click', '.reject_user', function(){
  const id = $(this).data('id');
  if (!confirm('Reject and remove this signup request?')) return;
  $.post('ajax.php?action=reject_user', {id: id}, function(resp){
    const r = typeof resp === 'string' ? JSON.parse(resp) : resp;
    if (r.status === 'ok') {
      alert_toast('Request rejected.', 'warning');
      setTimeout(() => location.reload(), 1200);
    } else {
      alert_toast(r.message || 'Failed to reject user.', 'danger');
    }
  });
});
</script>
