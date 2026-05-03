<style>
.kpi-card { border-radius:10px; border:none; }
.kpi-card .kpi-val { font-size:2rem; font-weight:800; }
.kpi-card .kpi-lbl { font-size:.75rem; text-transform:uppercase; letter-spacing:.06em; color:#6c757d; }
</style>

<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="fa fa-chart-bar mr-2 text-secondary"></i>Reports & Analytics</h5>
    <div class="d-flex align-items-center">
      <input type="date" id="rep-from" class="form-control form-control-sm mr-1" style="width:140px" value="<?php echo date('Y-m-01'); ?>">
      <span class="mx-1 text-muted">to</span>
      <input type="date" id="rep-to"   class="form-control form-control-sm mr-1" style="width:140px" value="<?php echo date('Y-m-d'); ?>">
      <button class="btn btn-sm btn-primary" id="run-report"><i class="fa fa-play mr-1"></i>Run</button>
      <div class="dropdown ml-1">
        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-toggle="dropdown">Quick</button>
        <div class="dropdown-menu dropdown-menu-right">
          <a class="dropdown-item preset-btn" data-days="7">Last 7 days</a>
          <a class="dropdown-item preset-btn" data-days="30">Last 30 days</a>
          <a class="dropdown-item preset-btn" data-days="90">Last 90 days</a>
          <a class="dropdown-item preset-btn" data-month="current">This month</a>
          <a class="dropdown-item preset-btn" data-month="last">Last month</a>
        </div>
      </div>
    </div>
  </div>

  <!-- KPI Cards -->
  <div class="row mb-4" id="kpi-row">
    <div class="col-6 col-md-3 mb-3">
      <div class="card kpi-card shadow-sm">
        <div class="card-body text-center">
          <div class="kpi-lbl">Occupancy Rate</div>
          <div class="kpi-val text-primary" id="kpi-occ">—</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
      <div class="card kpi-card shadow-sm">
        <div class="card-body text-center">
          <div class="kpi-lbl">Revenue</div>
          <div class="kpi-val text-success" id="kpi-rev">—</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
      <div class="card kpi-card shadow-sm">
        <div class="card-body text-center">
          <div class="kpi-lbl">ADR</div>
          <div class="kpi-val text-info" id="kpi-adr" data-toggle="tooltip" title="Average Daily Rate = Revenue / Room Nights Sold">—</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
      <div class="card kpi-card shadow-sm">
        <div class="card-body text-center">
          <div class="kpi-lbl">RevPAR</div>
          <div class="kpi-val text-warning" id="kpi-revpar" data-toggle="tooltip" title="Revenue Per Available Room = Revenue / Total Available Room-Nights">—</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Secondary KPIs -->
  <div class="row mb-4">
    <div class="col-6 col-md-2 mb-2"><div class="card"><div class="card-body text-center py-2"><div class="text-muted small">Total Rooms</div><div class="font-weight-bold" id="s-rooms">—</div></div></div></div>
    <div class="col-6 col-md-2 mb-2"><div class="card"><div class="card-body text-center py-2"><div class="text-muted small">Days in Period</div><div class="font-weight-bold" id="s-days">—</div></div></div></div>
    <div class="col-6 col-md-2 mb-2"><div class="card"><div class="card-body text-center py-2"><div class="text-muted small">Room Nights Sold</div><div class="font-weight-bold" id="s-nights">—</div></div></div></div>
    <div class="col-6 col-md-2 mb-2"><div class="card"><div class="card-body text-center py-2"><div class="text-muted small">Bookings</div><div class="font-weight-bold" id="s-book">—</div></div></div></div>
    <div class="col-6 col-md-2 mb-2"><div class="card"><div class="card-body text-center py-2"><div class="text-muted small">Check-Ins</div><div class="font-weight-bold" id="s-ci">—</div></div></div></div>
    <div class="col-6 col-md-2 mb-2"><div class="card"><div class="card-body text-center py-2"><div class="text-muted small">Avail. Room-Nights</div><div class="font-weight-bold" id="s-arn">—</div></div></div></div>
  </div>

  <div class="row">
    <!-- Revenue Chart -->
    <div class="col-lg-8 mb-4">
      <div class="card">
        <div class="card-header"><strong>Daily Revenue & Occupancy</strong></div>
        <div class="card-body"><canvas id="revenueChart" height="120"></canvas></div>
      </div>
    </div>
    <!-- Category Breakdown -->
    <div class="col-lg-4 mb-4">
      <div class="card">
        <div class="card-header"><strong>Revenue by Category</strong></div>
        <div class="card-body"><canvas id="categoryChart" height="200"></canvas></div>
      </div>
    </div>
  </div>

  <!-- Detailed Table -->
  <div class="card">
    <div class="card-header"><strong>Revenue by Category — Detail</strong></div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0" id="cat-table">
        <thead class="thead-light"><tr><th>Category</th><th>Revenue</th><th>% Share</th></tr></thead>
        <tbody id="cat-tbody"><tr><td colspan="3" class="text-center text-muted py-3">Run a report to see data</td></tr></tbody>
      </table>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
var revenueChart, categoryChart;

function initCharts() {
  var rctx = document.getElementById('revenueChart').getContext('2d');
  var cctx = document.getElementById('categoryChart').getContext('2d');

  revenueChart = new Chart(rctx, {
    type: 'bar',
    data: { labels: [], datasets: [
      { label: 'Revenue ($)', data: [], backgroundColor: 'rgba(37,99,235,.7)', yAxisID: 'y' },
      { label: 'Check-Ins', data: [], type: 'line', borderColor: '#f59e0b', backgroundColor: 'transparent', yAxisID: 'y1', tension: .4 }
    ]},
    options: { responsive: true, interaction: { mode: 'index', intersect: false },
      scales: {
        y:  { type:'linear', position:'left',  title:{display:true,text:'Revenue ($)'} },
        y1: { type:'linear', position:'right', title:{display:true,text:'Check-Ins'}, grid:{drawOnChartArea:false} }
      }
    }
  });

  categoryChart = new Chart(cctx, {
    type: 'doughnut',
    data: { labels: [], datasets: [{ data: [], backgroundColor: ['#2563eb','#7c3aed','#db2777','#ea580c','#16a34a','#0891b2'] }] },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
  });
}

function runReport() {
  var from = $('#rep-from').val();
  var to   = $('#rep-to').val();
  if (!from || !to) { alert('Select date range'); return; }
  start_load();
  $.getJSON('ajax.php?action=get_reports&from='+from+'&to='+to, function(d) {
    end_load();
    // KPIs
    $('#kpi-occ').text(d.occupancy_rate + '%');
    $('#kpi-rev').text('$' + parseFloat(d.revenue).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}));
    $('#kpi-adr').text('$' + parseFloat(d.adr).toFixed(2));
    $('#kpi-revpar').text('$' + parseFloat(d.revpar).toFixed(2));
    $('#s-rooms').text(d.total_rooms);
    $('#s-days').text(d.days_in_period);
    $('#s-nights').text(d.nights_sold);
    $('#s-book').text(d.bookings_count);
    $('#s-ci').text(d.checkins_count);
    $('#s-arn').text(d.available_room_nights);

    // Revenue chart
    var labels = [], revData = [], ciData = [];
    var ciMap = {};
    (d.daily_checkins||[]).forEach(function(c){ ciMap[c.day] = parseInt(c.checkins); });
    (d.daily_revenue||[]).forEach(function(r){
      labels.push(r.day);
      revData.push(parseFloat(r.revenue));
      ciData.push(ciMap[r.day]||0);
    });
    revenueChart.data.labels = labels;
    revenueChart.data.datasets[0].data = revData;
    revenueChart.data.datasets[1].data = ciData;
    revenueChart.update();

    // Category chart
    var catLabels = [], catData = [];
    (d.by_category||[]).forEach(function(c){ catLabels.push(c.name); catData.push(parseFloat(c.revenue)); });
    categoryChart.data.labels = catLabels;
    categoryChart.data.datasets[0].data = catData;
    categoryChart.update();

    // Category table
    var total = catData.reduce(function(a,b){return a+b;},0);
    var rows = '';
    (d.by_category||[]).forEach(function(c){
      var pct = total>0 ? ((parseFloat(c.revenue)/total)*100).toFixed(1) : '0.0';
      rows += '<tr><td>' + c.name + '</td><td>$' + parseFloat(c.revenue).toFixed(2) + '</td><td>' + pct + '%</td></tr>';
    });
    $('#cat-tbody').html(rows || '<tr><td colspan="3" class="text-center text-muted">No data</td></tr>');
  }).fail(function(){
    end_load();
    alert_toast('Failed to load report data','danger');
  });
}

$('#run-report').click(runReport);

$('.preset-btn').click(function() {
  var days = $(this).data('days');
  var month = $(this).data('month');
  var now = new Date();
  if (days) {
    var from = new Date(); from.setDate(from.getDate() - parseInt(days));
    $('#rep-from').val(from.toISOString().slice(0,10));
    $('#rep-to').val(now.toISOString().slice(0,10));
  } else if (month === 'current') {
    $('#rep-from').val(now.getFullYear() + '-' + String(now.getMonth()+1).padStart(2,'0') + '-01');
    $('#rep-to').val(now.toISOString().slice(0,10));
  } else if (month === 'last') {
    var lm = new Date(now.getFullYear(), now.getMonth(), 0);
    var ls = new Date(now.getFullYear(), now.getMonth()-1, 1);
    $('#rep-from').val(ls.toISOString().slice(0,10));
    $('#rep-to').val(lm.toISOString().slice(0,10));
  }
  runReport();
});

$(document).ready(function() {
  initCharts();
  $('[data-toggle="tooltip"]').tooltip();
  runReport();
});
</script>
