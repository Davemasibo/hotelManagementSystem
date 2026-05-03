<?php include('db_connect.php'); ?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="fa fa-bell mr-2 text-warning"></i>Notifications</h5>
    <button class="btn btn-sm btn-outline-secondary" id="mark-all-btn">
      <i class="fa fa-check-double mr-1"></i>Mark All Read
    </button>
  </div>

  <?php
  $uid  = (int)($_SESSION['login_id'] ?? 0);
  $notifs = $conn->query(
      "SELECT * FROM notifications WHERE user_id IS NULL OR user_id=$uid
       ORDER BY created_at DESC LIMIT 200"
  );
  $type_icons  = ['checkin'=>'sign-in-alt','checkout'=>'sign-out-alt','booking'=>'calendar-check',
                  'housekeeping'=>'broom','payment'=>'dollar-sign','alert'=>'exclamation-circle',
                  'vip'=>'crown','overbooking'=>'exclamation-triangle','request'=>'comment-alt','maintenance'=>'tools'];
  $priority_colors = ['critical'=>'danger','high'=>'warning','normal'=>'info','low'=>'secondary'];
  ?>

  <?php if ($notifs->num_rows === 0): ?>
  <div class="text-center py-5 text-muted">
    <i class="fa fa-bell-slash fa-3x mb-3 d-block"></i>
    <h6>No notifications</h6>
  </div>
  <?php else: ?>

  <div class="card">
    <div class="card-body p-0">
      <?php while ($n = $notifs->fetch_assoc()):
        $icon  = $type_icons[$n['type']] ?? 'bell';
        $color = $priority_colors[$n['priority']] ?? 'info';
        $unread = !$n['is_read'];
      ?>
      <div class="d-flex align-items-start border-bottom px-3 py-2 notif-row <?php echo $unread?'bg-light':''; ?>"
           id="notif-<?php echo $n['id']; ?>">
        <div class="mr-3 mt-1">
          <div style="width:36px;height:36px;border-radius:50%;background:<?php echo ['danger'=>'#dc3545','warning'=>'#ffc107','info'=>'#17a2b8','secondary'=>'#6c757d'][$color]??'#17a2b8';?>;display:flex;align-items:center;justify-content:center;color:<?php echo $color==='warning'?'#000':'#fff';?>">
            <i class="fa fa-<?php echo $icon; ?>" style="font-size:.85rem"></i>
          </div>
        </div>
        <div class="flex-grow-1">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <?php if ($unread): ?><span class="badge badge-primary mr-1" style="font-size:.6rem">NEW</span><?php endif; ?>
              <span class="badge badge-<?php echo $color; ?> mr-1" style="font-size:.65rem"><?php echo strtoupper($n['type']); ?></span>
              <strong><?php echo htmlspecialchars($n['title']); ?></strong>
            </div>
            <small class="text-muted ml-2"><?php echo $n['created_at']; ?></small>
          </div>
          <div class="text-muted mt-1"><?php echo htmlspecialchars($n['message']); ?></div>
        </div>
        <?php if ($unread): ?>
        <div class="ml-2">
          <button class="btn btn-xs btn-outline-secondary mark-read-btn" data-id="<?php echo $n['id']; ?>"
                  style="font-size:.7rem;padding:2px 6px">✓</button>
        </div>
        <?php endif; ?>
      </div>
      <?php endwhile; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
$('.mark-read-btn').click(function() {
  var id = $(this).data('id');
  $.post('ajax.php?action=mark_notification_read', {id:id}, function() {
    $('#notif-'+id).removeClass('bg-light').find('.mark-read-btn').remove();
    $('#notif-'+id).find('.badge-primary').remove();
    loadNotifications && loadNotifications();
  });
});

$('#mark-all-btn').click(function() {
  $.post('ajax.php?action=mark_notification_read', {all:1}, function() {
    $('.notif-row').removeClass('bg-light');
    $('.mark-read-btn').remove();
    $('.badge-primary').remove();
    loadNotifications && loadNotifications();
    alert_toast('All notifications marked as read','success');
  });
});
</script>
