<?php include('db_connect.php'); ?>
<style>
.room-card { border-radius:10px; transition:.2s; cursor:pointer; }
.room-card:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(0,0,0,.12); }
.room-card .status-strip { height:5px; border-radius:10px 10px 0 0; }
.status-clean       .status-strip { background:#28a745; }
.status-dirty       .status-strip { background:#dc3545; }
.status-in_progress .status-strip { background:#ffc107; }
.status-inspection  .status-strip { background:#17a2b8; }
.status-maintenance .status-strip { background:#6c757d; }
.status-out_of_order .status-strip { background:#343a40; }
.room-badge { font-size:.7rem; }
.legend-dot { width:12px;height:12px;border-radius:50%;display:inline-block;margin-right:4px; }
.filter-btn.active { font-weight:700; }
</style>

<div class="container-fluid py-3">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="fa fa-broom mr-2 text-warning"></i>Housekeeping Board</h5>
    <div>
      <button class="btn btn-sm btn-primary" onclick="uni_modal('New Housekeeping Task','manage_housekeeping.php')">
        <i class="fa fa-plus mr-1"></i>Add Task
      </button>
      <button class="btn btn-sm btn-outline-secondary ml-1" onclick="location.reload()">
        <i class="fa fa-sync-alt"></i>
      </button>
    </div>
  </div>

  <!-- Stats Bar -->
  <div class="row mb-3" id="hk-stats">
    <?php
    $stats_q = $conn->query("SELECT housekeeping_status, COUNT(*) AS cnt FROM rooms GROUP BY housekeeping_status");
    $hk_counts = ['clean'=>0,'dirty'=>0,'in_progress'=>0,'inspection'=>0,'maintenance'=>0,'out_of_order'=>0];
    while ($sr = $stats_q->fetch_assoc()) $hk_counts[$sr['housekeeping_status']] = (int)$sr['cnt'];
    $total_rooms = array_sum($hk_counts);
    ?>
    <div class="col-6 col-md-2 mb-2">
      <div class="card border-success text-center py-2">
        <div class="text-success font-weight-bold" style="font-size:1.5rem"><?php echo $hk_counts['clean']; ?></div>
        <small class="text-muted">Clean</small>
      </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
      <div class="card border-danger text-center py-2">
        <div class="text-danger font-weight-bold" style="font-size:1.5rem"><?php echo $hk_counts['dirty']; ?></div>
        <small class="text-muted">Dirty</small>
      </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
      <div class="card border-warning text-center py-2">
        <div class="text-warning font-weight-bold" style="font-size:1.5rem"><?php echo $hk_counts['in_progress']; ?></div>
        <small class="text-muted">In Progress</small>
      </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
      <div class="card border-info text-center py-2">
        <div class="text-info font-weight-bold" style="font-size:1.5rem"><?php echo $hk_counts['inspection']; ?></div>
        <small class="text-muted">Inspection</small>
      </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
      <div class="card border-secondary text-center py-2">
        <div class="text-secondary font-weight-bold" style="font-size:1.5rem"><?php echo $hk_counts['maintenance']; ?></div>
        <small class="text-muted">Maintenance</small>
      </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
      <div class="card border-dark text-center py-2">
        <div class="text-dark font-weight-bold" style="font-size:1.5rem"><?php echo $total_rooms; ?></div>
        <small class="text-muted">Total Rooms</small>
      </div>
    </div>
  </div>

  <!-- Filter -->
  <div class="mb-3">
    <button class="btn btn-sm filter-btn active mr-1" data-filter="all">All</button>
    <button class="btn btn-sm btn-outline-danger filter-btn mr-1" data-filter="dirty">Dirty</button>
    <button class="btn btn-sm btn-outline-warning filter-btn mr-1" data-filter="in_progress">In Progress</button>
    <button class="btn btn-sm btn-outline-info filter-btn mr-1" data-filter="inspection">Inspection</button>
    <button class="btn btn-sm btn-outline-success filter-btn mr-1" data-filter="clean">Clean</button>
    <button class="btn btn-sm btn-outline-secondary filter-btn" data-filter="maintenance">Maintenance</button>
  </div>

  <!-- Room Cards Grid -->
  <div class="row" id="room-grid">
    <?php
    $rooms = $conn->query(
        "SELECT r.*, rc.name AS category, rc.price,
                ht.id AS task_id, ht.status AS task_status, ht.task_type, ht.assigned_to,
                u.name AS assigned_name,
                c.name AS guest_name, c.date_out
         FROM rooms r
         LEFT JOIN room_categories rc ON r.category_id = rc.id
         LEFT JOIN (
             SELECT * FROM housekeeping_tasks
             WHERE scheduled_date = CURDATE() AND status NOT IN ('completed','verified','skipped')
             ORDER BY id DESC LIMIT 1
         ) ht ON ht.room_id = r.id
         LEFT JOIN users u ON ht.assigned_to = u.id
         LEFT JOIN checked c ON c.room_id = r.id AND c.status = 1
         ORDER BY r.floor ASC, r.room ASC"
    );
    $hs_labels = ['clean'=>'Clean','dirty'=>'Needs Cleaning','in_progress'=>'Cleaning','inspection'=>'Inspection','maintenance'=>'Maintenance','out_of_order'=>'Out of Order'];
    $hs_icons  = ['clean'=>'check-circle','dirty'=>'exclamation-circle','in_progress'=>'spinner fa-spin','inspection'=>'search','maintenance'=>'tools','out_of_order'=>'ban'];
    $hs_colors = ['clean'=>'success','dirty'=>'danger','in_progress'=>'warning','inspection'=>'info','maintenance'=>'secondary','out_of_order'=>'dark'];
    while ($room = $rooms->fetch_assoc()):
        $hs = $room['housekeeping_status'] ?? 'clean';
    ?>
    <div class="col-6 col-md-3 col-lg-2 mb-3 room-card-wrap" data-status="<?php echo $hs; ?>">
      <div class="card room-card status-<?php echo $hs; ?>">
        <div class="status-strip"></div>
        <div class="card-body p-2">
          <div class="d-flex justify-content-between align-items-start">
            <strong><?php echo htmlspecialchars($room['room']); ?></strong>
            <span class="badge badge-<?php echo $hs_colors[$hs]; ?> room-badge">
              <i class="fa fa-<?php echo $hs_icons[$hs]; ?> mr-1"></i><?php echo $hs_labels[$hs]; ?>
            </span>
          </div>
          <small class="text-muted d-block"><?php echo htmlspecialchars($room['category']??''); ?> &bull; Floor <?php echo $room['floor']??1; ?></small>

          <?php if ($room['guest_name']): ?>
          <div class="mt-1 p-1 bg-light rounded" style="font-size:.75rem">
            <i class="fa fa-user text-primary mr-1"></i><?php echo htmlspecialchars($room['guest_name']); ?>
            <?php if ($room['date_out']): ?><br><i class="fa fa-clock text-muted mr-1"></i>Out: <?php echo substr($room['date_out'],0,10); ?><?php endif; ?>
          </div>
          <?php endif; ?>

          <?php if ($room['task_id']): ?>
          <div class="mt-1" style="font-size:.74rem">
            <i class="fa fa-tasks text-info mr-1"></i><?php echo ucwords(str_replace('_',' ',$room['task_type'])); ?>
            <?php if ($room['assigned_name']): ?>&bull; <?php echo htmlspecialchars($room['assigned_name']); ?><?php endif; ?>
          </div>
          <?php endif; ?>

          <div class="mt-2 d-flex flex-wrap" style="gap:4px">
            <?php if ($hs === 'dirty' || $hs === 'maintenance'): ?>
            <button class="btn btn-xs btn-warning btn-sm" style="font-size:.72rem;padding:2px 6px"
              onclick="updateRoomStatus(<?php echo $room['id']; ?>,'in_progress')">
              <i class="fa fa-play mr-1"></i>Start
            </button>
            <?php endif; ?>
            <?php if ($hs === 'in_progress'): ?>
            <button class="btn btn-xs btn-info btn-sm" style="font-size:.72rem;padding:2px 6px"
              onclick="updateRoomStatus(<?php echo $room['id']; ?>,'inspection')">
              <i class="fa fa-search mr-1"></i>Inspect
            </button>
            <?php endif; ?>
            <?php if ($hs === 'inspection'): ?>
            <button class="btn btn-xs btn-success btn-sm" style="font-size:.72rem;padding:2px 6px"
              onclick="updateRoomStatus(<?php echo $room['id']; ?>,'clean')">
              <i class="fa fa-check mr-1"></i>Approve
            </button>
            <?php endif; ?>
            <button class="btn btn-xs btn-outline-secondary btn-sm" style="font-size:.72rem;padding:2px 6px"
              onclick="uni_modal('Task for <?php echo htmlspecialchars($room['room']); ?>','manage_housekeeping.php?room_id=<?php echo $room['id']; ?>')">
              <i class="fa fa-plus"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
    <?php endwhile; ?>
  </div>

  <!-- Today's Tasks Table -->
  <div class="card mt-2">
    <div class="card-header"><strong><i class="fa fa-list mr-2"></i>Today's Tasks</strong></div>
    <div class="card-body p-0">
      <table class="table table-sm table-hover mb-0">
        <thead class="thead-light">
          <tr><th>Room</th><th>Task</th><th>Priority</th><th>Assigned</th><th>Status</th><th>Time</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php
          $today = date('Y-m-d');
          $tasks = $conn->query(
              "SELECT ht.*, r.room, u.name AS staff_name
               FROM housekeeping_tasks ht
               JOIN rooms r ON ht.room_id = r.id
               LEFT JOIN users u ON ht.assigned_to = u.id
               WHERE ht.scheduled_date = '$today'
               ORDER BY ht.priority DESC, ht.created_at ASC"
          );
          $pcolors=['low'=>'secondary','normal'=>'info','high'=>'warning'];
          $scolors=['pending'=>'warning','in_progress'=>'info','completed'=>'success','verified'=>'success','skipped'=>'secondary'];
          if ($tasks->num_rows === 0):
          ?>
          <tr><td colspan="7" class="text-center text-muted py-3">No tasks scheduled for today</td></tr>
          <?php else: while ($t = $tasks->fetch_assoc()): ?>
          <tr>
            <td><strong><?php echo htmlspecialchars($t['room']); ?></strong></td>
            <td><?php echo ucwords(str_replace('_',' ',$t['task_type'])); ?></td>
            <td><span class="badge badge-<?php echo $pcolors[$t['priority']]??'secondary'; ?>"><?php echo strtoupper($t['priority']); ?></span></td>
            <td><?php echo htmlspecialchars($t['staff_name']??'Unassigned'); ?></td>
            <td><span class="badge badge-<?php echo $scolors[$t['status']]??'secondary'; ?>"><?php echo ucwords(str_replace('_',' ',$t['status'])); ?></span></td>
            <td>
              <?php if ($t['started_at']): ?><small>Started: <?php echo substr($t['started_at'],11,5); ?></small><?php endif; ?>
              <?php if ($t['completed_at']): ?><br><small>Done: <?php echo substr($t['completed_at'],11,5); ?></small><?php endif; ?>
            </td>
            <td>
              <?php if ($t['status']==='pending'): ?>
              <button class="btn btn-xs btn-warning" style="font-size:.72rem" onclick="updateTaskStatus(<?php echo $t['id']; ?>,'in_progress')">Start</button>
              <?php elseif ($t['status']==='in_progress'): ?>
              <button class="btn btn-xs btn-success" style="font-size:.72rem" onclick="updateTaskStatus(<?php echo $t['id']; ?>,'completed')">Complete</button>
              <?php elseif ($t['status']==='completed'): ?>
              <button class="btn btn-xs btn-info" style="font-size:.72rem" onclick="updateTaskStatus(<?php echo $t['id']; ?>,'verified')">Verify</button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<script>
// Filter room cards
$('.filter-btn').click(function() {
  $('.filter-btn').removeClass('active');
  $(this).addClass('active');
  var f = $(this).data('filter');
  if (f === 'all') {
    $('.room-card-wrap').show();
  } else {
    $('.room-card-wrap').hide();
    $('.room-card-wrap[data-status="'+f+'"]').show();
  }
});

function updateRoomStatus(roomId, newStatus) {
  start_load();
  // Update housekeeping_status on rooms directly
  $.post('ajax.php?action=update_room_hk_status', {room_id: roomId, hk_status: newStatus}, function(resp) {
    end_load();
    alert_toast('Status updated', 'success');
    setTimeout(function() { location.reload(); }, 800);
  });
}

function updateTaskStatus(taskId, newStatus) {
  start_load();
  $.post('ajax.php?action=update_task_status', {id: taskId, status: newStatus}, function(resp) {
    end_load();
    var r = JSON.parse(resp);
    if (r.status === 'ok') {
      alert_toast('Task updated', 'success');
      setTimeout(function() { location.reload(); }, 800);
    }
  });
}
</script>
