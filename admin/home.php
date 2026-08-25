<div class="container-fluid py-4">

  <!-- ── KPI Row 1 ─────────────────────────────────────────── -->
  <div class="row mb-3" id="kpi-row">

    <div class="col-6 col-md-3 mb-3 anim-up">
      <div class="kpi-card kpi-gold card">
        <div class="kpi-accent"></div>
        <div class="card-body d-flex align-items-center gap-3" style="gap:14px;padding:18px;">
          <div class="kpi-icon-wrap"><i class="fas fa-bed"></i></div>
          <div style="min-width:0;">
            <div class="kpi-label">Occupancy Rate</div>
            <div class="kpi-value" id="kpi-occ">—</div>
            <div class="kpi-sub" id="kpi-occ-sub">&nbsp;</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-6 col-md-3 mb-3 anim-up">
      <div class="kpi-card kpi-green card">
        <div class="kpi-accent"></div>
        <div class="card-body d-flex align-items-center" style="gap:14px;padding:18px;">
          <div class="kpi-icon-wrap"><i class="fas fa-dollar-sign"></i></div>
          <div>
            <div class="kpi-label">Today's Revenue</div>
            <div class="kpi-value" id="kpi-rev">—</div>
            <div class="kpi-sub">collected today</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-6 col-md-3 mb-3 anim-up">
      <div class="kpi-card kpi-blue card">
        <div class="kpi-accent"></div>
        <div class="card-body d-flex align-items-center" style="gap:14px;padding:18px;">
          <div class="kpi-icon-wrap"><i class="fas fa-sign-in-alt"></i></div>
          <div>
            <div class="kpi-label">Today's Check-Ins</div>
            <div class="kpi-value" id="kpi-ci">—</div>
            <div class="kpi-sub">guests arrived</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-6 col-md-3 mb-3 anim-up">
      <div class="kpi-card kpi-amber card">
        <div class="kpi-accent"></div>
        <div class="card-body d-flex align-items-center" style="gap:14px;padding:18px;">
          <div class="kpi-icon-wrap"><i class="fas fa-calendar-check"></i></div>
          <div>
            <div class="kpi-label">Pending Bookings</div>
            <div class="kpi-value" id="kpi-book">—</div>
            <div class="kpi-sub">awaiting check-in</div>
          </div>
        </div>
      </div>
    </div>

  </div>

  <!-- ── KPI Row 2 (secondary metrics) ─────────────────────── -->
  <div class="row mb-3">

    <div class="col-6 col-md-3 mb-3 anim-up">
      <div class="kpi-mini card">
        <div class="card-body d-flex align-items-center" style="gap:12px;padding:14px 16px;">
          <div class="mini-bar" style="background:#3b82f6;"></div>
          <div>
            <div class="mini-label">Available Rooms</div>
            <div class="mini-value" id="kpi-avail">—</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-6 col-md-3 mb-3 anim-up">
      <div class="kpi-mini card">
        <div class="card-body d-flex align-items-center" style="gap:12px;padding:14px 16px;">
          <div class="mini-bar" style="background:#ef4444;"></div>
          <div>
            <div class="mini-label">Occupied Rooms</div>
            <div class="mini-value" id="kpi-occ-rooms">—</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-6 col-md-3 mb-3 anim-up">
      <div class="kpi-mini card">
        <div class="card-body d-flex align-items-center" style="gap:12px;padding:14px 16px;">
          <div class="mini-bar" style="background:#f97316;"></div>
          <div>
            <div class="mini-label">HK Tasks Today</div>
            <div class="mini-value" id="kpi-hk">—</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-6 col-md-3 mb-3 anim-up">
      <div class="kpi-mini card">
        <div class="card-body d-flex align-items-center" style="gap:12px;padding:14px 16px;">
          <div class="mini-bar" style="background:rgba(255,255,255,0.2);"></div>
          <div>
            <div class="mini-label">Unpaid Invoices</div>
            <div class="mini-value" id="kpi-unpaid">—</div>
          </div>
        </div>
      </div>
    </div>

  </div>

  <!-- ── Quick Actions (immediately after KPI cards) ────────── -->
  <div class="mb-4">
    <div class="section-chip">Quick Actions</div>
    <div class="quick-actions-grid">

      <a href="index.php?page=check_in" class="qa-btn qa-checkin anim-up">
        <div class="qa-icon"><i class="fas fa-sign-in-alt"></i></div>
        <span>New Check-In</span>
      </a>

      <a href="index.php?page=check_out" class="qa-btn qa-checkout anim-up">
        <div class="qa-icon"><i class="fas fa-sign-out-alt"></i></div>
        <span>Check-Out</span>
      </a>

      <a href="index.php?page=guests" class="qa-btn qa-guest anim-up">
        <div class="qa-icon"><i class="fas fa-user-plus"></i></div>
        <span>Add Guest</span>
      </a>

      <a href="index.php?page=housekeeping" class="qa-btn qa-hk anim-up">
        <div class="qa-icon"><i class="fas fa-broom"></i></div>
        <span>Housekeeping</span>
      </a>

      <a href="index.php?page=billing" class="qa-btn qa-billing anim-up">
        <div class="qa-icon"><i class="fas fa-file-invoice-dollar"></i></div>
        <span>Billing</span>
      </a>

      <a href="index.php?page=reports" class="qa-btn qa-reports anim-up">
        <div class="qa-icon"><i class="fas fa-chart-bar"></i></div>
        <span>Reports</span>
      </a>

    </div>
  </div>

  <!-- ── Current Guests + Arriving Soon ─────────────────────── -->
  <div class="row">

    <!-- Current Guests -->
    <div class="col-lg-7 mb-4">
      <div class="panel-card">
        <div class="panel-header">
          <div class="panel-title">
            <i class="fas fa-users" style="background:rgba(196,145,58,0.14);color:var(--brand);border-radius:7px;width:26px;height:26px;display:flex;align-items:center;justify-content:center;"></i>
            Current Guests
          </div>
          <a href="index.php?page=check_out" class="btn-ghost" style="font-size:12px;padding:5px 12px;border-radius:8px;background:transparent;border:1px solid var(--border-subtle);color:var(--text-secondary);text-decoration:none;transition:all .18s;"
             onmouseover="this.style.borderColor='var(--border-brand)';this.style.color='var(--brand)'"
             onmouseout="this.style.borderColor='var(--border-subtle)';this.style.color='var(--text-secondary)'">
            Check-Out →
          </a>
        </div>
        <div class="table-responsive">
          <table class="table table-sm mb-0" id="current-guests-table">
            <thead>
              <tr>
                <th>Room</th>
                <th>Guest</th>
                <th>Check-Out</th>
                <th>Balance</th>
                <th></th>
              </tr>
            </thead>
            <tbody id="current-guests-body">
              <tr>
                <td colspan="5" style="text-align:center;padding:28px;color:var(--text-muted);font-size:13px;">
                  <i class="fas fa-spinner fa-spin mr-2"></i>Loading...
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Arriving Soon -->
    <div class="col-lg-5 mb-4">
      <div class="panel-card" style="height:100%;">
        <div class="panel-header">
          <div class="panel-title">
            <i class="fas fa-calendar-alt" style="background:rgba(59,130,246,0.14);color:#60a5fa;border-radius:7px;width:26px;height:26px;display:flex;align-items:center;justify-content:center;"></i>
            Arriving Soon
          </div>
          <a href="index.php?page=booked" class="btn-ghost" style="font-size:12px;padding:5px 12px;border-radius:8px;background:transparent;border:1px solid var(--border-subtle);color:var(--text-secondary);text-decoration:none;transition:all .18s;"
             onmouseover="this.style.borderColor='var(--border-brand)';this.style.color='var(--brand)'"
             onmouseout="this.style.borderColor='var(--border-subtle)';this.style.color='var(--text-secondary)'">
            All Bookings →
          </a>
        </div>
        <div id="upcoming-list">
          <div style="padding:28px;text-align:center;color:var(--text-muted);font-size:13px;">
            <i class="fas fa-spinner fa-spin mr-2"></i>Loading...
          </div>
        </div>
      </div>
    </div>

  </div>

</div>

<script>
function countUp(el, target, prefix, suffix) {
  var start = 0, duration = 600, step = target / (duration / 16);
  var timer = setInterval(function() {
    start += step;
    if (start >= target) { start = target; clearInterval(timer); }
    el.textContent = prefix + (Number.isInteger(target) ? Math.floor(start) : start.toFixed(2)) + suffix;
  }, 16);
}

function loadDashboard() {
  $.getJSON('ajax.php?action=get_dashboard_stats', function(d) {
    var occ = parseFloat(d.occupancy_rate || 0);
    countUp(document.getElementById('kpi-occ'), occ, '', '%');
    $('#kpi-occ-sub').text((d.rooms.occupied||0) + ' of ' + (d.rooms.total||0) + ' rooms');

    var rev = parseFloat(d.todays_revenue || 0);
    countUp(document.getElementById('kpi-rev'), rev, '$', '');

    var ci = parseInt(d.todays_checkins || 0);
    countUp(document.getElementById('kpi-ci'), ci, '', '');

    var bk = parseInt(d.pending_bookings || 0);
    countUp(document.getElementById('kpi-book'), bk, '', '');

    $('#kpi-avail').text((d.rooms.available||0) + ' / ' + (d.rooms.total||0));
    $('#kpi-occ-rooms').text(d.rooms.occupied||0);
    $('#kpi-hk').text((d.hk_pending||0) + ' pending');
    $('#kpi-unpaid').text(d.pending_payments||0);

    // Current guests
    var rows = '';
    if (d.current_guests && d.current_guests.length > 0) {
      d.current_guests.forEach(function(g) {
        var vip   = g.is_vip == 1 ? '<span class="badge-vip mr-1">VIP</span>' : '';
        var gname = vip + (g.guest_name || g.name);
        // Show the check-out time alongside the date ("2026-08-25 11:00").
        var dout  = g.date_out ? g.date_out.substring(0,16).replace('T',' ') : '—';
        rows += '<tr class="guest-row' + (g.is_vip==1?' vip':'') + '">' +
          '<td style="padding:10px 14px;"><strong>' + g.room + '</strong><br>' +
          '<small style="color:var(--text-muted);font-size:11px;">' + (g.category||'') + '</small></td>' +
          '<td style="padding:10px 14px;">' + gname + '<br>' +
          '<small style="color:var(--text-muted);font-size:11px;">' + (g.contact_no||'') + '</small></td>' +
          '<td style="padding:10px 14px;color:#f87171;font-size:13px;">' + dout + '</td>' +
          '<td style="padding:10px 14px;color:var(--text-muted);">—</td>' +
          '<td style="padding:10px 14px;">' +
          '<a href="index.php?page=check_out" style="font-size:11px;padding:3px 10px;background:rgba(239,68,68,0.14);color:#f87171;border-radius:6px;text-decoration:none;border:1px solid rgba(239,68,68,0.25);">Out</a>' +
          '</td></tr>';
      });
    } else {
      rows = '<tr><td colspan="5" style="text-align:center;padding:32px;color:var(--text-muted);font-size:13px;">' +
             '<i class="fas fa-moon mr-2"></i>No guests currently checked in</td></tr>';
    }
    $('#current-guests-body').html(rows);

    // Upcoming check-ins
    var upcoming = '';
    if (d.upcoming_checkins && d.upcoming_checkins.length > 0) {
      d.upcoming_checkins.forEach(function(u) {
        var vip = u.is_vip == 1 ? '<span class="badge-vip mr-1">VIP</span>' : '';
        var din = u.date_in ? u.date_in.substring(0,16) : '—';
        upcoming +=
          '<div class="arrival-item">' +
          '<div class="arrival-icon"><i class="fas fa-calendar-check"></i></div>' +
          '<div style="flex:1;min-width:0;">' +
          '<div class="arrival-name">' + vip + (u.guest_name||u.name) + '</div>' +
          '<div class="arrival-meta">' + din + ' &bull; ' + (u.room||'No room') + '</div></div>' +
          '<a href="index.php?page=check_in" style="font-size:11px;padding:4px 11px;background:rgba(59,130,246,0.14);color:#60a5fa;border-radius:6px;text-decoration:none;border:1px solid rgba(59,130,246,0.25);white-space:nowrap;">Check In</a>' +
          '</div>';
      });
    } else {
      upcoming = '<div style="padding:36px 16px;text-align:center;color:var(--text-muted);">' +
                 '<i class="fas fa-calendar-times mb-2 d-block" style="font-size:28px;opacity:.35;"></i>' +
                 '<div style="font-size:13px;">No arrivals in the next 24 hours</div></div>';
    }
    $('#upcoming-list').html(upcoming);

  }).fail(function() {
    $('#kpi-occ').text('—');
  });
}

$(document).ready(function() {
  loadDashboard();
  setInterval(loadDashboard, 60000);
});
</script>
