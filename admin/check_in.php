<?php include('db_connect.php'); ?>
<div class="container-fluid py-3">

  <div class="page-header mb-4">
    <h5 class="page-title"><i class="fa fa-sign-in-alt mr-2"></i>Check-In</h5>
  </div>

  <!-- Filter -->
  <div class="card mb-3">
    <div class="card-body">
      <form id="filter">
        <div class="row align-items-end">
          <div class="col-md-4">
            <label class="control-label">Category</label>
            <select class="custom-select" name="category_id">
              <option value="all" <?php echo isset($_GET['category_id']) && $_GET['category_id'] == 'all' ? 'selected' : ''; ?>>All</option>
              <?php
              $cat = $conn->query("SELECT * FROM room_categories ORDER BY name ASC");
              while ($row = $cat->fetch_assoc()) {
                  $cat_name[$row['id']] = $row['name'];
              ?>
                <option value="<?php echo $row['id']; ?>" <?php echo isset($_GET['category_id']) && $_GET['category_id'] == $row['id'] ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($row['name']); ?>
                </option>
              <?php } ?>
            </select>
          </div>
          <div class="col-md-2">
            <button class="btn btn-primary btn-block mt-2">
              <i class="fa fa-filter mr-1"></i>Filter
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Rooms Table -->
  <div class="card">
    <div class="card-body p-0">
      <table class="table table-hover mb-0" id="checkin-table">
        <thead>
          <tr>
            <th class="text-center">#</th>
            <th>Category</th>
            <th>Room</th>
            <th class="text-center">Status</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $i = 1;
          $where = '';
          if (isset($_GET['category_id']) && !empty($_GET['category_id']) && $_GET['category_id'] != 'all') {
              $where .= " WHERE category_id = '" . $conn->real_escape_string($_GET['category_id']) . "' ";
          }
          if (empty($where)) {
              $where .= " WHERE status = '0' ";
          } else {
              $where .= " AND status = '0' ";
          }
          $rooms = $conn->query("SELECT * FROM rooms " . $where . " ORDER BY id ASC");
          while ($row = $rooms->fetch_assoc()):
          ?>
          <tr>
            <td class="text-center"><?php echo $i++; ?></td>
            <td><?php echo htmlspecialchars($cat_name[$row['category_id']] ?? '—'); ?></td>
            <td><?php echo htmlspecialchars($row['room']); ?></td>
            <?php if ($row['status'] == 0): ?>
              <td class="text-center"><span class="badge badge-success">Available</span></td>
            <?php else: ?>
              <td class="text-center"><span class="badge badge-secondary">Unavailable</span></td>
            <?php endif; ?>
            <td class="text-center">
              <button class="btn btn-sm btn-primary check_in" type="button" data-id="<?php echo $row['id']; ?>">
                <i class="fa fa-sign-in-alt mr-1"></i>Check-in
              </button>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<script>
  $('table').dataTable();
  $('.check_in').click(function() {
    uni_modal("Check In", "manage_check_in.php?rid=" + $(this).attr("data-id"));
  });
  $('#filter').submit(function(e) {
    e.preventDefault();
    location.replace('index.php?page=check_in&category_id=' + $(this).find('[name="category_id"]').val() + '&status=' + $(this).find('[name="status"]').val());
  });
</script>
