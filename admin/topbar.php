<style>
.logo { margin:auto; font-size:20px; background:white; padding:5px 11px; border-radius:50%; color:#000000b3; }
.notif-dropdown { width:360px; max-height:400px; overflow-y:auto; }
.notif-item { border-left:3px solid transparent; transition:.2s; }
.notif-item:hover { background:#f8f9fa; }
.notif-item.priority-critical { border-color:#dc3545; }
.notif-item.priority-high     { border-color:#fd7e14; }
.notif-item.priority-normal   { border-color:#0d6efd; }
#notif-badge { font-size:.65rem; }
</style>

<nav class="navbar navbar-dark bg-dark fixed-top" style="padding:0;">
  <div class="container-fluid mt-2 mb-2">
    <div class="col-lg-12">
      <div class="col-md-1 float-left" style="display:flex;">
        <div class="logo"><i class="fa fa-building"></i></div>
      </div>
      <div class="col-md-4 float-left text-white">
        <large><b><?php echo $_SESSION['setting_hotel_name'] ?? 'Hotel Management'; ?></b></large>
      </div>

      <div class="col-md-5 float-right text-white d-flex justify-content-end align-items-center">

        <!-- Notification Bell -->
        <div class="dropdown mr-3">
          <button class="btn btn-dark position-relative" id="notifBtn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fa fa-bell"></i>
            <span class="badge badge-danger badge-pill position-absolute" id="notif-badge" style="top:0;right:0;display:none;"></span>
          </button>
          <div class="dropdown-menu dropdown-menu-right notif-dropdown p-0" aria-labelledby="notifBtn">
            <div class="d-flex justify-content-between align-items-center px-3 py-2 bg-dark text-white">
              <strong>Notifications</strong>
              <a href="#" class="text-warning small" id="mark-all-read">Mark all read</a>
            </div>
            <div id="notif-list">
              <div class="dropdown-item text-muted text-center py-3">Loading...</div>
            </div>
            <div class="border-top text-center py-2">
              <a href="index.php?page=notifications_page" class="small text-primary">View all notifications</a>
            </div>
          </div>
        </div>

        <!-- User menu -->
        <div class="dropdown">
          <a class="text-white dropdown-toggle" href="#" id="userMenu" data-toggle="dropdown">
            <i class="fa fa-user-circle mr-1"></i><?php echo htmlspecialchars($_SESSION['login_name'] ?? ''); ?>
          </a>
          <div class="dropdown-menu dropdown-menu-right">
            <a class="dropdown-item" href="ajax.php?action=logout"><i class="fa fa-power-off mr-2 text-danger"></i>Logout</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</nav>

<script>
function loadNotifications() {
  $.getJSON('ajax.php?action=get_notifications&limit=8', function(data) {
    var count = data.unread_count || 0;
    if (count > 0) {
      $('#notif-badge').text(count > 99 ? '99+' : count).show();
    } else {
      $('#notif-badge').hide();
    }
    var icons = {checkin:'sign-in-alt',checkout:'sign-out-alt',booking:'book',housekeeping:'broom',
                 payment:'dollar-sign',alert:'exclamation-circle',vip:'crown',overbooking:'exclamation-triangle',
                 request:'comment-alt',maintenance:'tools'};
    var colors = {critical:'danger',high:'warning',normal:'info',low:'secondary'};
    var html = '';
    if (data.notifications && data.notifications.length > 0) {
      data.notifications.forEach(function(n) {
        var icon  = icons[n.type]  || 'bell';
        var color = colors[n.priority] || 'info';
        var time  = n.created_at ? n.created_at.substring(5,16) : '';
        html += '<a href="#" class="dropdown-item notif-item priority-' + n.priority +
                ' py-2 border-bottom" data-id="' + n.id + '" onclick="markRead('+n.id+',this)">' +
                '<div class="d-flex"><div class="mr-2 text-' + color + '" style="width:20px">' +
                '<i class="fa fa-' + icon + '"></i></div>' +
                '<div><div class="font-weight-bold" style="font-size:.85rem">' + n.title + '</div>' +
                '<div class="text-muted" style="font-size:.78rem;white-space:normal">' + n.message.substring(0,80) + '</div>' +
                '<div class="text-muted" style="font-size:.72rem">' + time + '</div></div></div></a>';
      });
    } else {
      html = '<div class="dropdown-item text-muted text-center py-3"><i class="fa fa-check-circle text-success mr-1"></i>All caught up!</div>';
    }
    $('#notif-list').html(html);
  });
}

function markRead(id, el) {
  $.post('ajax.php?action=mark_notification_read', {id: id}, function() {
    $(el).remove();
    loadNotifications();
  });
}

$('#mark-all-read').click(function(e) {
  e.preventDefault();
  $.post('ajax.php?action=mark_notification_read', {all: 1}, function() { loadNotifications(); });
});

loadNotifications();
setInterval(loadNotifications, 30000);
</script>
