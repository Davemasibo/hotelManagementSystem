<?php include('db_connect.php'); ?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="fa fa-comment-dots mr-2 text-info"></i>Guest Requests</h5>
    <div>
      <select id="status-f" class="custom-select custom-select-sm mr-1" style="width:auto">
        <option value="">All</option>
        <?php foreach(['pending','in_progress','completed','cancelled'] as $s): ?>
        <option value="<?php echo $s; ?>"><?php echo ucwords(str_replace('_',' ',$s)); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <table class="table table-hover mb-0" id="req-table">
        <thead class="thead-dark">
          <tr><th>#</th><th>Guest / Stay</th><th>Type</th><th>Description</th><th>Priority</th><th>Status</th><th>Created</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php
        $reqs = $conn->query(
            "SELECT gr.*, c.ref_no, c.name AS guest_raw, g.full_name AS guest_name, r.room,
                    u.name AS resolved_by_name
             FROM guest_requests gr
             JOIN checked c ON gr.checked_id = c.id
             LEFT JOIN guests g ON c.guest_id = g.id
             LEFT JOIN rooms r ON c.room_id = r.id
             LEFT JOIN users u ON gr.resolved_by = u.id
             ORDER BY
               CASE gr.priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END,
               gr.created_at DESC"
        );
        $pcolors=['urgent'=>'danger','high'=>'warning','normal'=>'info','low'=>'secondary'];
        $scolors=['pending'=>'warning','in_progress'=>'info','completed'=>'success','cancelled'=>'secondary'];
        $n = 1;
        while ($r = $reqs->fetch_assoc()):
            $gname = $r['guest_name'] ?: $r['guest_raw'];
        ?>
        <tr>
          <td><?php echo $n++; ?></td>
          <td><?php echo htmlspecialchars($gname); ?><br>
              <small class="text-muted"><?php echo htmlspecialchars($r['room']??'—'); ?> &bull; <?php echo htmlspecialchars($r['ref_no']); ?></small></td>
          <td><span class="badge badge-light"><?php echo ucwords(str_replace('_',' ',$r['request_type'])); ?></span></td>
          <td><?php echo htmlspecialchars(mb_substr($r['description'],0,80)); ?><?php if(strlen($r['description'])>80) echo '…'; ?></td>
          <td><span class="badge badge-<?php echo $pcolors[$r['priority']]??'secondary'; ?>"><?php echo strtoupper($r['priority']); ?></span></td>
          <td><span class="badge badge-<?php echo $scolors[$r['status']]??'secondary'; ?>"><?php echo ucwords(str_replace('_',' ',$r['status'])); ?></span></td>
          <td><small><?php echo substr($r['created_at'],0,16); ?></small></td>
          <td>
            <button class="btn btn-sm btn-primary" onclick="uni_modal('Update Request','manage_request.php?id=<?php echo $r['id']; ?>')">
              <i class="fa fa-edit"></i>
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
$(document).ready(function() {
  var table = $('#req-table').DataTable({order:[[5,'asc']]});
  $('#status-f').change(function(){ table.column(5).search($(this).val()).draw(); });
});
</script>
