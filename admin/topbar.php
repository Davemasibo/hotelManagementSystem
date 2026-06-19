<nav class="navbar navbar-dark bg-dark fixed-top" style="padding:0 0;">
  <div class="container-fluid" style="padding:0 20px;height:56px;display:flex;align-items:center;justify-content:space-between;">

    <!-- Brand -->
    <div class="navbar-hotel-brand">
      <div class="brand-icon"><i class="fas fa-hotel"></i></div>
      <div>
        <div class="brand-name"><?php echo htmlspecialchars($_SESSION['setting_hotel_name'] ?? 'Hotel Management'); ?></div>
        <div class="brand-sub">Management Portal</div>
      </div>
    </div>

    <!-- Right actions -->
    <div style="display:flex;align-items:center;gap:10px;">

      <!-- Notification Bell -->
      <div class="dropdown">
        <a class="topbar-btn" id="notifBtn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
           href="#" onclick="return false;">
          <i class="fas fa-bell" style="font-size:14px;"></i>
          <span id="notif-badge" class="badge-dot" style="display:none;"></span>
        </a>
        <div class="dropdown-menu dropdown-menu-right notif-dropdown p-0" aria-labelledby="notifBtn">
          <div class="notif-header">
            <strong><i class="fas fa-bell mr-2" style="color:var(--brand);font-size:12px;"></i>Notifications</strong>
            <a href="#" id="mark-all-read">Mark all read</a>
          </div>
          <div id="notif-list">
            <div style="padding:20px;text-align:center;color:var(--text-muted);font-size:13px;">Loading...</div>
          </div>
          <div class="notif-footer">
            <a href="index.php?page=notifications_page"><i class="fas fa-list mr-1"></i>View all notifications</a>
          </div>
        </div>
      </div>

      <!-- User menu -->
      <div class="dropdown">
        <a class="user-menu-btn dropdown-toggle" href="#" id="userMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <div class="user-avatar">
            <i class="fas fa-user" style="font-size:11px;"></i>
          </div>
          <span style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
            <?php echo htmlspecialchars($_SESSION['login_name'] ?? 'User'); ?>
          </span>
        </a>
        <div class="dropdown-menu dropdown-menu-right" style="min-width:180px;">
          <div style="padding:10px 14px 8px;border-bottom:1px solid var(--border-subtle);">
            <div style="font-size:13px;font-weight:700;color:var(--text-primary);">
              <?php echo htmlspecialchars($_SESSION['login_name'] ?? ''); ?>
            </div>
            <div style="font-size:11px;color:var(--text-muted);">
              <?php echo ($_SESSION['login_type'] ?? 2) == 1 ? 'Administrator' : 'Staff'; ?>
            </div>
          </div>
          <a class="dropdown-item" href="ajax.php?action=logout" style="margin-top:4px;">
            <i class="fas fa-power-off mr-2" style="color:#ef4444;"></i>Sign Out
          </a>
        </div>
      </div>

    </div>
  </div>
</nav>

<script>
var notifIcons = {
  checkin:'sign-in-alt', checkout:'sign-out-alt', booking:'calendar-check',
  housekeeping:'broom', payment:'dollar-sign', alert:'exclamation-circle',
  vip:'crown', overbooking:'exclamation-triangle', request:'comment-alt', maintenance:'tools'
};
var notifColors = {critical:'#ef4444', high:'#f97316', normal:'var(--brand)', low:'rgba(255,255,255,0.3)'};
var notifBg    = {critical:'rgba(239,68,68,0.14)', high:'rgba(249,115,22,0.14)', normal:'rgba(196,145,58,0.14)', low:'rgba(255,255,255,0.06)'};

function loadNotifications() {
  $.getJSON('ajax.php?action=get_notifications&limit=8', function(data) {
    var count = data.unread_count || 0;
    var $badge = $('#notif-badge');
    count > 0 ? $badge.show() : $badge.hide();

    var html = '';
    if (data.notifications && data.notifications.length > 0) {
      data.notifications.forEach(function(n) {
        var icon  = notifIcons[n.type] || 'bell';
        var col   = notifColors[n.priority] || notifColors.normal;
        var bg    = notifBg[n.priority]    || notifBg.normal;
        var time  = n.created_at ? n.created_at.substring(5,16) : '';
        html += '<a href="#" class="notif-item priority-' + n.priority + '" data-id="' + n.id + '" onclick="markRead(' + n.id + ',this);return false;">' +
          '<div class="notif-icon" style="background:' + bg + ';color:' + col + '"><i class="fas fa-' + icon + '"></i></div>' +
          '<div style="flex:1;min-width:0">' +
          '<div class="notif-title">' + n.title + '</div>' +
          '<div class="notif-msg">' + (n.message||'').substring(0,80) + '</div>' +
          '<div class="notif-time">' + time + '</div></div></a>';
      });
    } else {
      html = '<div style="padding:24px 16px;text-align:center;color:var(--text-muted);font-size:13px;">' +
             '<i class="fas fa-check-circle mr-2" style="color:#22c55e;"></i>All caught up!</div>';
    }
    $('#notif-list').html(html);
  });
}

function markRead(id, el) {
  $.post('ajax.php?action=mark_notification_read', {id: id}, function() {
    $(el).fadeOut(200, function(){ $(this).remove(); });
    loadNotifications();
  });
}

$('#mark-all-read').click(function(e) {
  e.preventDefault();
  $.post('ajax.php?action=mark_notification_read', {all: 1}, loadNotifications);
});

loadNotifications();
setInterval(loadNotifications, 30000);
</script>
