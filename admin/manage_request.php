<?php
include('db_connect.php');
$id = (int)($_GET['id'] ?? 0);
$meta = [];
if ($id > 0) {
    $stmt = $conn->prepare(
        "SELECT gr.*, c.ref_no, c.name AS guest_raw, g.full_name AS guest_name, r.room
         FROM guest_requests gr
         JOIN checked c ON gr.checked_id = c.id
         LEFT JOIN guests g ON c.guest_id = g.id
         LEFT JOIN rooms r ON c.room_id = r.id
         WHERE gr.id=?"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $meta = $stmt->get_result()->fetch_assoc() ?? [];
    $stmt->close();
}
$gname = ($meta['guest_name'] ?? '') ?: ($meta['guest_raw'] ?? '');
?>
<div class="container-fluid">
  <div class="alert alert-light border mb-3">
    <strong><?php echo htmlspecialchars($gname); ?></strong> &bull;
    Room: <?php echo htmlspecialchars($meta['room']??'—'); ?> &bull;
    Ref: <code><?php echo htmlspecialchars($meta['ref_no']??'—'); ?></code>
  </div>
  <form id="req-form">
    <input type="hidden" name="id" value="<?php echo $meta['id']??''; ?>">
    <input type="hidden" name="checked_id" value="<?php echo $meta['checked_id']??''; ?>">
    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <label>Request Type</label>
          <select name="request_type" class="custom-select">
            <?php foreach(['maintenance','housekeeping','room_service','amenity','complaint','transport','other'] as $t): ?>
            <option value="<?php echo $t; ?>" <?php echo ($meta['request_type']??'')===$t?'selected':''; ?>>
              <?php echo ucwords(str_replace('_',' ',$t)); ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-group">
          <label>Priority</label>
          <select name="priority" class="custom-select">
            <?php foreach(['low','normal','high','urgent'] as $p): ?>
            <option value="<?php echo $p; ?>" <?php echo ($meta['priority']??'normal')===$p?'selected':''; ?>><?php echo ucfirst($p); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-group">
          <label>Status</label>
          <select name="status" class="custom-select">
            <?php foreach(['pending','in_progress','completed','cancelled'] as $s): ?>
            <option value="<?php echo $s; ?>" <?php echo ($meta['status']??'pending')===$s?'selected':''; ?>><?php echo ucwords(str_replace('_',' ',$s)); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>
    <div class="form-group">
      <label>Description</label>
      <textarea name="description" class="form-control" rows="3" required><?php echo htmlspecialchars($meta['description']??''); ?></textarea>
    </div>
    <div class="form-group">
      <label>Resolution Note</label>
      <textarea name="resolution_note" class="form-control" rows="2" placeholder="How was this resolved?"><?php echo htmlspecialchars($meta['resolution_note']??''); ?></textarea>
    </div>
  </form>
</div>
<script>
$('#req-form').submit(function(e) {
  e.preventDefault();
  start_load();
  $.post('ajax.php?action=save_guest_request', $(this).serialize(), function(resp) {
    end_load();
    var r = JSON.parse(resp);
    if (r.status === 'ok') {
      alert_toast('Request updated','success');
      setTimeout(function(){ $('#uni_modal').modal('hide'); location.reload(); }, 1200);
    } else {
      alert_toast(r.message||'Error','danger');
    }
  });
});
</script>
