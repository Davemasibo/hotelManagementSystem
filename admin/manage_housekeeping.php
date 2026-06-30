<?php
include('db_connect.php');
$room_id = (int)($_GET['room_id'] ?? 0);
$id      = (int)($_GET['id']      ?? 0);
$meta    = [];
if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM housekeeping_tasks WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $meta = $stmt->get_result()->fetch_assoc() ?? [];
    $stmt->close();
}
$rooms = $conn->query("SELECT r.id, r.room, r.floor, rc.name AS category FROM rooms r LEFT JOIN room_categories rc ON r.category_id=rc.id ORDER BY r.floor, r.room");
$staff = $conn->query("SELECT id, name FROM users WHERE is_active=1 ORDER BY name");
?>
<div class="container-fluid">
<form id="hk-form">
  <input type="hidden" name="id" value="<?php echo $meta['id']??''; ?>">

  <div class="row">
    <div class="col-md-6">
      <div class="form-group">
        <label>Room <span class="text-danger">*</span></label>
        <select name="room_id" class="custom-select" required>
          <option value="">— Select Room —</option>
          <?php while ($r = $rooms->fetch_assoc()): ?>
          <option value="<?php echo $r['id']; ?>"
            <?php echo ($meta['room_id']??$room_id)==$r['id']?'selected':''; ?>>
            <?php echo htmlspecialchars("Floor {$r['floor']} - {$r['room']} ({$r['category']})"); ?>
          </option>
          <?php endwhile; ?>
        </select>
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-group">
        <label>Scheduled Date <span class="text-danger">*</span></label>
        <input type="date" name="scheduled_date" class="form-control"
               value="<?php echo $meta['scheduled_date']??date('Y-m-d'); ?>" required>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-6">
      <div class="form-group">
        <label>Task Type</label>
        <select name="task_type" class="custom-select">
          <?php foreach (['standard_clean','deep_clean','turndown','inspection','maintenance','prepare','linen_change'] as $t): ?>
          <option value="<?php echo $t; ?>" <?php echo ($meta['task_type']??'standard_clean')===$t?'selected':''; ?>>
            <?php echo ucwords(str_replace('_',' ',$t)); ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-group">
        <label>Priority</label>
        <select name="priority" class="custom-select">
          <?php foreach (['low','normal','high'] as $p): ?>
          <option value="<?php echo $p; ?>" <?php echo ($meta['priority']??'normal')===$p?'selected':''; ?>>
            <?php echo ucfirst($p); ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>

  <div class="form-group">
    <label>Assign To</label>
    <select name="assigned_to" class="custom-select">
      <option value="">— Unassigned —</option>
      <?php while ($s = $staff->fetch_assoc()): ?>
      <option value="<?php echo $s['id']; ?>" <?php echo ($meta['assigned_to']??0)==$s['id']?'selected':''; ?>>
        <?php echo htmlspecialchars($s['name']); ?>
      </option>
      <?php endwhile; ?>
    </select>
  </div>

  <div class="form-group">
    <label>Notes</label>
    <textarea name="notes" class="form-control" rows="3" placeholder="Special instructions..."><?php echo htmlspecialchars($meta['notes']??''); ?></textarea>
  </div>
</form>
</div>

<script>
$('#hk-form').submit(function(e) {
  e.preventDefault();
  start_load();
  $.post('ajax.php?action=save_housekeeping_task', $(this).serialize(), function(resp) {
    end_load();
    var r = (typeof resp === 'string') ? JSON.parse(resp) : resp;
    if (r.status === 'ok') {
      alert_toast('Task saved', 'success');
      setTimeout(function() { $('#uni_modal').modal('hide'); location.reload(); }, 1200);
    } else {
      alert_toast(r.message || 'Error saving task', 'danger');
    }
  });
});
</script>
