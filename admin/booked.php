<?php include('db_connect.php');
$cat = $conn->query("SELECT * FROM room_categories");
$cat_arr = array();
while ($row = $cat->fetch_assoc()) {
    $cat_arr[$row['id']] = $row;
}
$room = $conn->query("SELECT * FROM rooms");
$room_arr = array();
while ($row = $room->fetch_assoc()) {
    $room_arr[$row['id']] = $row;
}
?>
<div class="container-fluid py-3">

  <div class="page-header mb-4">
    <h5 class="page-title"><i class="fa fa-calendar-check mr-2"></i>Bookings</h5>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <table class="table table-hover mb-0" id="booked-table">
        <thead>
          <tr>
            <th class="text-center">#</th>
            <th>Category</th>
            <th>Reference</th>
            <th class="text-center">Status</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $i = 1;
          $checked = $conn->query("SELECT * FROM checked WHERE status = 0 ORDER BY status DESC, id ASC");
          while ($row = $checked->fetch_assoc()):
          ?>
          <tr>
            <td class="text-center"><?php echo $i++; ?></td>
            <td><?php echo htmlspecialchars($cat_arr[$row['booked_cid']]['name'] ?? '—'); ?></td>
            <td><?php echo htmlspecialchars($row['ref_no']); ?></td>
            <td class="text-center"><span class="badge badge-warning">Booked</span></td>
            <td class="text-center">
              <button class="btn btn-sm btn-primary check_out" type="button" data-id="<?php echo $row['id']; ?>">
                <i class="fa fa-eye mr-1"></i>View
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
  $('.check_out').click(function() {
    uni_modal("Check Out", "manage_check_out.php?checkout=1&id=" + $(this).attr("data-id"));
  });
  $('#filter').submit(function(e) {
    e.preventDefault();
    location.replace('index.php?page=check_in&category_id=' + $(this).find('[name="category_id"]').val() + '&status=' + $(this).find('[name="status"]').val());
  });
</script>
