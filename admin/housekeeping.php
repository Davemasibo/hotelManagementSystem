<?php include('db_connect.php'); ?>
<style>
/* Room card styles */
.hk-card {
  background: var(--bg-raised);
  border: 1px solid var(--border-subtle);
  border-radius: 10px;
  overflow: hidden;
  transition: box-shadow .2s, transform .15s;
  height: 100%;
}
.hk-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.35); transform: translateY(-2px); }
.hk-strip { height: 4px; width: 100%; }
.hs-clean       .hk-strip { background: #22c55e; }
.hs-dirty       .hk-strip { background: #ef4444; }
.hs-in_progress .hk-strip { background: #f97316; }
.hs-inspection  .hk-strip { background: #06b6d4; }
.hs-maintenance .hk-strip { background: #8b5cf6; }
.hs-out_of_order .hk-strip { background: #6b7280; }
.hs-clean       { border-color: rgba(34,197,94,.25) !important; }
.hs-dirty       { border-color: rgba(239,68,68,.25) !important; }
.hs-in_progress { border-color: rgba(249,115,22,.25) !important; }
.hs-inspection  { border-color: rgba(6,182,212,.25) !important; }
.hs-maintenance { border-color: rgba(139,92,246,.25) !important; }
.hk-room-no { font-size: 1.15rem; font-weight: 700; letter-spacing: .02em; color: var(--text-primary); }
.hk-badge { display: inline-flex; align-items: center; gap: 4px; font-size: .68rem; font-weight: 600; letter-spacing: .04em; padding: 2px 7px; border-radius: 20px; text-transform: uppercase; }
.hk-badge-clean       { background: rgba(34,197,94,.15);  color: #4ade80; }
.hk-badge-dirty       { background: rgba(239,68,68,.15);  color: #fca5a5; }
.hk-badge-in_progress { background: rgba(249,115,22,.15); color: #fdba74; }
.hk-badge-inspection  { background: rgba(6,182,212,.15);  color: #67e8f9; }
.hk-badge-maintenance { background: rgba(139,92,246,.15); color: #c4b5fd; }
.hk-badge-out_of_order { background: rgba(107,114,128,.15); color: #9ca3af; }
.hk-guest-pill { background: var(--bg-elevated); border: 1px solid var(--border-subtle); border-radius: 6px; padding: 4px 8px; font-size: .76rem; }
.hk-task-pill  { background: rgba(6,182,212,.07); border: 1px solid rgba(6,182,212,.2); border-radius: 6px; padding: 3px 7px; font-size: .73rem; }
.hk-actions { border-top: 1px solid var(--border-subtle); padding: 6px 10px; background: rgba(0,0,0,.08); display: flex; gap: 5px; flex-wrap: wrap; }
.hk-btn { font-size: .72rem; padding: 3px 8px; border-radius: 5px; cursor: pointer; font-weight: 600; border: 1px solid transparent; }
.hk-btn-start   { background: rgba(249,115,22,.2); color: #fdba74; border-color: rgba(249,115,22,.3); }
.hk-btn-inspect { background: rgba(6,182,212,.2);  color: #67e8f9; border-color: rgba(6,182,212,.3); }
.hk-btn-approve { background: rgba(34,197,94,.2);  color: #4ade80; border-color: rgba(34,197,94,.3); }
.hk-btn-task    { background: rgba(255,255,255,.05); color: var(--text-secondary); border-color: var(--border-subtle); }
.hk-btn:hover   { filter: brightness(1.2); }
.filter-btn { background: var(--bg-elevated); border: 1px solid var(--border-subtle); color: var(--text-secondary); border-radius: 7px; }
.filter-btn.active,
.filter-btn:hover { background: rgba(196,145,58,0.15) !important; border-color: var(--brand) !important; color: var(--brand) !important; }
</style>

<div class="container-fluid py-3">

  <div class="page-header mb-4">
    <h5 class="page-title"><i class="fa fa-broom mr-2"></i>Housekeeping Board</h5>
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
    $hk_counts = ['clean' => 0, 'dirty' => 0, 'in_progress' => 0, 'inspection' => 0, 'maintenance' => 0, 'out_of_order' => 0];
    while ($sr = $stats_q->fetch_assoc()) $hk_counts[$sr['housekeeping_status']] = (int)$sr['cnt'];
    $total_rooms = array_sum($hk_counts);
    ?>
    <div class="col-6 col-md-2 mb-2">
      <div class="card text-center py-2" style="border-left: 3px solid #22c55e !important">
        <div class="font-weight-bold" style="font-size:1.5rem;color:#4ade80"><?php echo $hk_counts['clean']; ?></div>
        <small class="text-muted">Clean</small>
      </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
      <div class="card text-center py-2" style="border-left: 3px solid #ef4444 !important">
        <div class="font-weight-bold" style="font-size:1.5rem;color:#fca5a5"><?php echo $hk_counts['dirty']; ?></div>
        <small class="text-muted">Dirty</small>
      </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
      <div class="card text-center py-2" style="border-left: 3px solid #f97316 !important">
        <div class="font-weight-bold" style="font-size:1.5rem;color:#fdba74"><?php echo $hk_counts['in_progress']; ?></div>
        <small class="text-muted">In Progress</small>
      </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
      <div class="card text-center py-2" style="border-left: 3px solid #06b6d4 !important">
        <div class="font-weight-bold" style="font-size:1.5rem;color:#67e8f9"><?php echo $hk_counts['inspection']; ?></div>
        <small class="text-muted">Inspection</small>
      </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
      <div class="card text-center py-2" style="border-left: 3px solid #6c757d !important">
        <div class="font-weight-bold" style="font-size:1.5rem;color:#adb5bd"><?php echo $hk_counts['maintenance']; ?></div>
        <small class="text-muted">Maintenance</small>
      </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
      <div class="card text-center py-2" style="border-left: 3px solid var(--brand) !important">
        <div class="font-weight-bold" style="font-size:1.5rem;color:var(--brand)"><?php echo $total_rooms; ?></div>
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
    $hs_labels = ['clean' => 'Clean', 'dirty' => 'Needs Cleaning', 'in_progress' => 'Cleaning', 'inspection' => 'Inspection', 'maintenance' => 'Maintenance', 'out_of_order' => 'Out of Order'];
    $hs_icons  = ['clean' => 'check-circle', 'dirty' => 'exclamation-circle', 'in_progress' => 'spinner fa-spin', 'inspection' => 'search', 'maintenance' => 'tools', 'out_of_order' => 'ban'];
    $hs_colors = ['clean' => 'success', 'dirty' => 'danger', 'in_progress' => 'warning', 'inspection' => 'info', 'maintenance' => 'secondary', 'out_of_order' => 'secondary'];
    while ($room = $rooms->fetch_assoc()):
        $hs = $room['housekeeping_status'] ?? 'clean';
    ?>
    <div class="col-6 col-md-4 col-lg-3 col-xl-2 mb-3 room-card-wrap d-flex" data-status="<?php echo $hs; ?>" style="min-height:0">
      <div class="hk-card w-100 d-flex flex-column hs-<?php echo $hs; ?>">
        <div class="hk-strip"></div>
        <div class="p-2 flex-grow-1">

          <!-- Room # + Status Badge -->
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="hk-room-no"><?php echo htmlspecialchars($room['room']); ?></span>
            <span class="hk-badge hk-badge-<?php echo $hs; ?>">
              <i class="fa fa-<?php echo $hs_icons[$hs]; ?>"></i><?php echo $hs_labels[$hs]; ?>
            </span>
          </div>

          <!-- Category & Floor -->
          <div class="text-muted mb-2" style="font-size:.73rem">
            <i class="fa fa-th-large mr-1" style="opacity:.5"></i><?php echo htmlspecialchars($room['category'] ?? '—'); ?>
            <span class="mx-1" style="opacity:.4">&bull;</span>
            <i class="fa fa-layer-group mr-1" style="opacity:.5"></i>Floor <?php echo $room['floor'] ?? 1; ?>
          </div>

          <!-- Occupied guest -->
          <?php if ($room['guest_name']): ?>
          <div class="hk-guest-pill mb-2">
            <div><i class="fa fa-user-circle mr-1" style="color:var(--brand)"></i><strong><?php echo htmlspecialchars($room['guest_name']); ?></strong></div>
            <?php if ($room['date_out']): ?>
            <div class="text-muted mt-1"><i class="fa fa-sign-out-alt mr-1"></i>Out <?php echo date('M d', strtotime($room['date_out'])); ?></div>
            <?php endif; ?>
          </div>
          <?php else: ?>
          <div class="text-muted mb-2" style="font-size:.73rem"><i class="fa fa-door-open mr-1" style="opacity:.5"></i>Vacant</div>
          <?php endif; ?>

          <!-- Active task -->
          <?php if ($room['task_id']): ?>
          <div class="hk-task-pill mb-1">
            <i class="fa fa-tasks mr-1 text-info"></i><?php echo ucwords(str_replace('_', ' ', $room['task_type'])); ?>
            <?php if ($room['assigned_name']): ?>
            <div class="text-muted mt-1" style="font-size:.69rem"><i class="fa fa-user-hard-hat mr-1"></i><?php echo htmlspecialchars($room['assigned_name']); ?></div>
            <?php endif; ?>
          </div>
          <?php endif; ?>

        </div>

        <!-- Action row -->
        <div class="hk-actions">
          <?php if ($hs === 'dirty' || $hs === 'maintenance'): ?>
          <button class="hk-btn hk-btn-start" onclick="updateRoomStatus(<?php echo $room['id']; ?>,'in_progress')">
            <i class="fa fa-play mr-1"></i>Start
          </button>
          <?php endif; ?>
          <?php if ($hs === 'in_progress'): ?>
          <button class="hk-btn hk-btn-inspect" onclick="updateRoomStatus(<?php echo $room['id']; ?>,'inspection')">
            <i class="fa fa-search mr-1"></i>Inspect
          </button>
          <?php endif; ?>
          <?php if ($hs === 'inspection'): ?>
          <button class="hk-btn hk-btn-approve" onclick="updateRoomStatus(<?php echo $room['id']; ?>,'clean')">
            <i class="fa fa-check mr-1"></i>Approve
          </button>
          <?php endif; ?>
          <button class="hk-btn hk-btn-task ml-auto" title="Add Task"
            onclick="uni_modal('Task for <?php echo htmlspecialchars($room['room']); ?>','manage_housekeeping.php?room_id=<?php echo $room['id']; ?>')">
            <i class="fa fa-plus mr-1"></i>Task
          </button>
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
        <thead>
          <tr>
            <th>Room</th>
            <th>Task</th>
            <th>Priority</th>
            <th>Assigned</th>
            <th>Status</th>
            <th>Time</th>
            <th>Actions</th>
          </tr>
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
          $pcolors = ['low' => 'secondary', 'normal' => 'info', 'high' => 'warning'];
          $scolors = ['pending' => 'warning', 'in_progress' => 'info', 'completed' => 'success', 'verified' => 'success', 'skipped' => 'secondary'];
          if ($tasks->num_rows === 0):
          ?>
          <tr><td colspan="7" class="text-center text-muted py-3">No tasks scheduled for today</td></tr>
          <?php else: while ($t = $tasks->fetch_assoc()): ?>
          <tr>
            <td><strong><?php echo htmlspecialchars($t['room']); ?></strong></td>
            <td><?php echo ucwords(str_replace('_', ' ', $t['task_type'])); ?></td>
            <td><span class="badge badge-<?php echo $pcolors[$t['priority']] ?? 'secondary'; ?>"><?php echo strtoupper($t['priority']); ?></span></td>
            <td><?php echo htmlspecialchars($t['staff_name'] ?? 'Unassigned'); ?></td>
            <td><span class="badge badge-<?php echo $scolors[$t['status']] ?? 'secondary'; ?>"><?php echo ucwords(str_replace('_', ' ', $t['status'])); ?></span></td>
            <td>
              <?php if ($t['started_at']): ?><small>Started: <?php echo substr($t['started_at'], 11, 5); ?></small><?php endif; ?>
              <?php if ($t['completed_at']): ?><br><small>Done: <?php echo substr($t['completed_at'], 11, 5); ?></small><?php endif; ?>
            </td>
            <td>
              <?php if ($t['status'] === 'pending'): ?>
              <button class="btn btn-xs btn-warning" style="font-size:.72rem" onclick="updateTaskStatus(<?php echo $t['id']; ?>,'in_progress')">Start</button>
              <?php elseif ($t['status'] === 'in_progress'): ?>
              <button class="btn btn-xs btn-success" style="font-size:.72rem" onclick="updateTaskStatus(<?php echo $t['id']; ?>,'completed')">Complete</button>
              <?php elseif ($t['status'] === 'completed'): ?>
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
    $('.room-card-wrap[data-status="' + f + '"]').show();
  }
});

function updateRoomStatus(roomId, newStatus) {
  start_load();
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
