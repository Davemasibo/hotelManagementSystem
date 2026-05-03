<style>
.stat-card { border:none; border-radius:12px; transition:.2s; }
.stat-card:hover { transform:translateY(-2px); box-shadow:0 8px 25px rgba(0,0,0,.15); }
.stat-icon { font-size:2.2rem; opacity:.25; position:absolute; right:15px; bottom:10px; }
.kpi-value { font-size:2rem; font-weight:700; }
.kpi-label { font-size:.78rem; text-transform:uppercase; letter-spacing:.06em; opacity:.85; }
.activity-time { font-size:.72rem; color:#999; }
.guest-row { border-left:3px solid transparent; }
.guest-row.vip { border-color:#ffc107; background:#fffbf0; }
.badge-vip { background:#ffc107; color:#000; font-size:.65rem; }
.section-header { font-size:.8rem; text-transform:uppercase; letter-spacing:.08em; color:#6c757d; font-weight:700; margin-bottom:.5rem; }
</style>

<div class="container-fluid py-4">

  <!-- KPI Row -->
  <div class="row mb-4" id="kpi-row">
    <div class="col-6 col-md-3 mb-3">
      <div class="card stat-card bg-primary text-white">
        <div class="card-body position-relative">
          <div class="kpi-label">Occupancy Rate</div>
          <div class="kpi-value" id="kpi-occ">—</div>
          <i class="fa fa-bed stat-icon"></i>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
      <div class="card stat-card bg-success text-white">
        <div class="card-body position-relative">
          <div class="kpi-label">Today's Revenue</div>
          <div class="kpi-value" id="kpi-rev">—</div>
          <i class="fa fa-dollar-sign stat-icon"></i>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
      <div class="card stat-card bg-info text-white">
        <div class="card-body position-relative">
          <div class="kpi-label">Today's Check-Ins</div>
          <div class="kpi-value" id="kpi-ci">—</div>
          <i class="fa fa-sign-in-alt stat-icon"></i>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
      <div class="card stat-card bg-warning text-dark">
        <div class="card-body position-relative">
          <div class="kpi-label">Pending Bookings</div>
          <div class="kpi-value" id="kpi-book">—</div>
          <i class="fa fa-calendar-check stat-icon"></i>
        </div>
      </div>
    </div>
  </div>

  <!-- Second KPI row -->
  <div class="row mb-4">
    <div class="col-6 col-md-3 mb-3">
      <div class="card stat-card border-left border-primary" style="border-left-width:4px!important">
        <div class="card-body py-2">
          <div class="kpi-label text-primary">Available Rooms</div>
          <div class="font-weight-bold" style="font-size:1.5rem" id="kpi-avail">—</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
      <div class="card stat-card border-left border-danger" style="border-left-width:4px!important">
        <div class="card-body py-2">
          <div class="kpi-label text-danger">Occupied Rooms</div>
          <div class="font-weight-bold" style="font-size:1.5rem" id="kpi-occ-rooms">—</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
      <div class="card stat-card border-left border-warning" style="border-left-width:4px!important">
        <div class="card-body py-2">
          <div class="kpi-label text-warning">HK Tasks Today</div>
          <div class="font-weight-bold" style="font-size:1.5rem" id="kpi-hk">—</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
      <div class="card stat-card border-left border-secondary" style="border-left-width:4px!important">
        <div class="card-body py-2">
          <div class="kpi-label text-secondary">Unpaid Invoices</div>
          <div class="font-weight-bold" style="font-size:1.5rem" id="kpi-unpaid">—</div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">

    <!-- Current Guests -->
    <div class="col-lg-7 mb-4">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <strong><i class="fa fa-users mr-2 text-primary"></i>Current Guests</strong>
          <a href="index.php?page=check_out" class="btn btn-sm btn-outline-secondary">Check-Out</a>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm mb-0" id="current-guests-table">
              <thead class="thead-light">
                <tr><th>Room</th><th>Guest</th><th>Check-Out</th><th>Balance</th><th></th></tr>
              </thead>
              <tbody id="current-guests-body">
                <tr><td colspan="5" class="text-center py-4 text-muted">Loading...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Upcoming Check-Ins -->
    <div class="col-lg-5 mb-4">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <strong><i class="fa fa-calendar-alt mr-2 text-info"></i>Arriving Soon</strong>
          <a href="index.php?page=booked" class="btn btn-sm btn-outline-secondary">All Bookings</a>
        </div>
        <div class="card-body p-0" id="upcoming-list">
          <div class="text-center py-4 text-muted">Loading...</div>
        </div>
      </div>
    </div>

  </div>

  <!-- Quick Actions -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-header"><strong><i class="fa fa-bolt mr-2 text-warning"></i>Quick Actions</strong></div>
        <div class="card-body">
          <a href="index.php?page=check_in"    class="btn btn-primary mr-2 mb-2"><i class="fa fa-sign-in-alt mr-1"></i>New Check-In</a>
          <a href="index.php?page=check_out"   class="btn btn-danger mr-2 mb-2"><i class="fa fa-sign-out-alt mr-1"></i>Check-Out</a>
          <a href="index.php?page=guests"      class="btn btn-info mr-2 mb-2"><i class="fa fa-user-plus mr-1"></i>Add Guest</a>
          <a href="index.php?page=housekeeping" class="btn btn-warning mr-2 mb-2 text-dark"><i class="fa fa-broom mr-1"></i>Housekeeping</a>
          <a href="index.php?page=billing"     class="btn btn-success mr-2 mb-2"><i class="fa fa-file-invoice mr-1"></i>Billing</a>
          <a href="index.php?page=reports"     class="btn btn-secondary mr-2 mb-2"><i class="fa fa-chart-bar mr-1"></i>Reports</a>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
function loadDashboard() {
  $.getJSON('ajax.php?action=get_dashboard_stats', function(d) {
    $('#kpi-occ').text(d.occupancy_rate + '%');
    $('#kpi-rev').text('$' + parseFloat(d.todays_revenue).toFixed(2));
    $('#kpi-ci').text(d.todays_checkins);
    $('#kpi-book').text(d.pending_bookings);
    $('#kpi-avail').text((d.rooms.available || 0) + ' / ' + (d.rooms.total || 0));
    $('#kpi-occ-rooms').text(d.rooms.occupied || 0);
    $('#kpi-hk').text((d.hk_pending || 0) + ' pending');
    $('#kpi-unpaid').text(d.pending_payments || 0);

    // Current guests table
    var rows = '';
    if (d.current_guests && d.current_guests.length > 0) {
      d.current_guests.forEach(function(g) {
        var vip = g.is_vip == 1 ? '<span class="badge badge-vip mr-1">VIP</span>' : '';
        var gname = vip + (g.guest_name || g.name);
        var dout = g.date_out ? g.date_out.substring(0,10) : '—';
        rows += '<tr class="guest-row' + (g.is_vip==1?' vip':'') + '">' +
          '<td><strong>' + g.room + '</strong><br><small class="text-muted">' + (g.category||'') + '</small></td>' +
          '<td>' + gname + '<br><small class="text-muted">' + (g.contact_no||'') + '</small></td>' +
          '<td><span class="text-danger">' + dout + '</span></td>' +
          '<td>—</td>' +
          '<td><a href="index.php?page=check_out" class="btn btn-xs btn-outline-danger" style="font-size:.75rem;padding:2px 6px">Out</a></td></tr>';
      });
    } else {
      rows = '<tr><td colspan="5" class="text-center py-3 text-muted">No guests currently checked in</td></tr>';
    }
    $('#current-guests-body').html(rows);

    // Upcoming check-ins
    var upcoming = '';
    if (d.upcoming_checkins && d.upcoming_checkins.length > 0) {
      d.upcoming_checkins.forEach(function(u) {
        var vip = u.is_vip == 1 ? '<span class="badge badge-vip mr-1">VIP</span>' : '';
        var din = u.date_in ? u.date_in.substring(0,16) : '—';
        upcoming += '<div class="d-flex align-items-center border-bottom px-3 py-2' + (u.is_vip==1?' bg-warning-light':'') + '">' +
          '<div class="mr-3"><i class="fa fa-calendar-check text-info fa-lg"></i></div>' +
          '<div class="flex-grow-1">' +
          '<div>' + vip + (u.guest_name || u.name) + '</div>' +
          '<small class="text-muted">' + din + ' &bull; ' + (u.room||'No room') + '</small></div>' +
          '<a href="index.php?page=check_in" class="btn btn-xs btn-primary ml-2" style="font-size:.75rem;padding:2px 8px">Check In</a></div>';
      });
    } else {
      upcoming = '<div class="text-center py-4 text-muted"><i class="fa fa-calendar-times fa-2x mb-2 d-block"></i>No arrivals in next 24 hours</div>';
    }
    $('#upcoming-list').html(upcoming);
  }).fail(function() {
    $('#kpi-occ').text('Error');
  });
}
$(document).ready(function() {
  loadDashboard();
  setInterval(loadDashboard, 60000);
});
</script>
