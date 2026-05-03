<?php include('db_connect.php'); ?>
<style>
.badge-vip { background:#ffc107; color:#000; }
.guest-avatar { width:36px; height:36px; border-radius:50%; background:#6c757d; color:#fff;
  display:inline-flex; align-items:center; justify-content:center; font-weight:700; font-size:.85rem; }
</style>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="fa fa-users mr-2 text-primary"></i>Guest Profiles</h5>
    <button class="btn btn-primary btn-sm" onclick="uni_modal('New Guest','manage_guest.php')">
      <i class="fa fa-plus mr-1"></i>Add Guest
    </button>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <table class="table table-hover mb-0" id="guests-table">
        <thead class="thead-dark">
          <tr>
            <th>#</th><th>Guest</th><th>Contact</th><th>ID</th>
            <th>Nationality</th><th>Total Stays</th><th>Status</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $guests = $conn->query("SELECT * FROM guests ORDER BY full_name ASC");
        $i = 1;
        while ($g = $guests->fetch_assoc()):
          $initials = strtoupper(substr($g['full_name'], 0, 1));
        ?>
          <tr>
            <td><?php echo $i++; ?></td>
            <td>
              <div class="d-flex align-items-center">
                <div class="guest-avatar mr-2"><?php echo htmlspecialchars($initials); ?></div>
                <div>
                  <div class="font-weight-bold"><?php echo htmlspecialchars($g['full_name']); ?>
                    <?php if ($g['is_vip']): ?><span class="badge badge-vip ml-1">VIP</span><?php endif; ?>
                  </div>
                  <small class="text-muted"><?php echo htmlspecialchars($g['email'] ?? '—'); ?></small>
                </div>
              </div>
            </td>
            <td><?php echo htmlspecialchars($g['phone'] ?? '—'); ?></td>
            <td>
              <span class="badge badge-light"><?php echo strtoupper($g['id_type'] ?? ''); ?></span>
              <small><?php echo htmlspecialchars($g['id_number'] ?? '—'); ?></small>
            </td>
            <td><?php echo htmlspecialchars($g['nationality'] ?? '—'); ?></td>
            <td><span class="badge badge-info"><?php echo (int)$g['total_stays']; ?> stays</span></td>
            <td>
              <?php if ($g['is_vip']): ?>
                <span class="badge badge-warning"><i class="fa fa-crown mr-1"></i>VIP</span>
              <?php else: ?>
                <span class="badge badge-secondary">Standard</span>
              <?php endif; ?>
            </td>
            <td>
              <button class="btn btn-sm btn-info" onclick="uni_modal('Guest Profile','guest_view.php?id=<?php echo $g['id']; ?>')">
                <i class="fa fa-eye"></i>
              </button>
              <button class="btn btn-sm btn-primary" onclick="uni_modal('Edit Guest','manage_guest.php?id=<?php echo $g['id']; ?>')">
                <i class="fa fa-edit"></i>
              </button>
              <button class="btn btn-sm btn-danger" onclick="_conf('Delete this guest profile?','deleteGuest',[<?php echo $g['id']; ?>])">
                <i class="fa fa-trash"></i>
              </button>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
$(document).ready(function() { $('#guests-table').DataTable(); });
function deleteGuest(id) {
  start_load();
  $.post('ajax.php?action=delete_guest', {id:id}, function() {
    alert_toast('Guest deleted','danger');
    setTimeout(function(){ location.reload(); }, 1200);
  });
}
</script>
