<?php include('db_connect.php'); ?>

<div class="container-fluid py-3">

  <div class="page-header mb-4">
    <h5 class="page-title"><i class="fa fa-door-open mr-2"></i>Rooms</h5>
  </div>

  <div class="row">
    <!-- FORM Panel -->
    <div class="col-md-4">
      <form action="" id="manage-room">
        <div class="card">
          <div class="card-header">Room Form</div>
          <div class="card-body">
            <input type="hidden" name="id">
            <div class="form-group">
              <label class="control-label">Room Name / Number</label>
              <input type="text" class="form-control" name="room" placeholder="e.g. 101">
            </div>
            <div class="form-group">
              <label class="control-label">Category</label>
              <select class="custom-select" name="category_id">
                <?php
                $cat = $conn->query("SELECT * FROM room_categories ORDER BY name ASC");
                while ($row = $cat->fetch_assoc()) {
                    $cat_name[$row['id']] = $row['name'];
                ?>
                <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                <?php } ?>
              </select>
            </div>
            <div class="form-group">
              <label class="control-label">Availability</label>
              <select class="custom-select" name="status">
                <option value="0">Available</option>
                <option value="1">Unavailable</option>
              </select>
            </div>
            <div class="row">
              <div class="col-6">
                <div class="form-group">
                  <label class="control-label">Floor</label>
                  <input type="number" class="form-control" name="floor" min="1" value="1">
                </div>
              </div>
              <div class="col-6">
                <div class="form-group">
                  <label class="control-label">Max Guests</label>
                  <input type="number" class="form-control" name="max_occupancy" min="1" value="2">
                </div>
              </div>
            </div>
            <div class="form-group">
              <label class="control-label">Description</label>
              <input type="text" class="form-control" name="description" placeholder="e.g. Sea view, corner room">
            </div>
          </div>
          <div class="card-footer">
            <div class="d-flex" style="gap:8px">
              <button class="btn btn-sm btn-primary flex-fill"><i class="fa fa-save mr-1"></i>Save</button>
              <button class="btn btn-sm btn-secondary" type="button" onclick="$('#manage-room').get(0).reset()">
                <i class="fa fa-times mr-1"></i>Cancel
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>
    <!-- /FORM Panel -->

    <!-- Table Panel -->
    <div class="col-md-8">
      <div class="card">
        <div class="card-body p-0">
          <table class="table table-hover mb-0" id="rooms-table">
            <thead>
              <tr>
                <th class="text-center" style="width:40px">#</th>
                <th>Category</th>
                <th>Room</th>
                <th class="text-center">Status</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $i     = 1;
              $rooms = $conn->query("SELECT * FROM rooms ORDER BY id ASC");
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
                  <button class="btn btn-sm btn-primary edit_cat" type="button"
                    data-id="<?php echo $row['id']; ?>"
                    data-room="<?php echo htmlspecialchars($row['room']); ?>"
                    data-category_id="<?php echo $row['category_id']; ?>"
                    data-status="<?php echo $row['status']; ?>"
                    data-floor="<?php echo $row['floor'] ?? 1; ?>"
                    data-max_occupancy="<?php echo $row['max_occupancy'] ?? 2; ?>"
                    data-description="<?php echo htmlspecialchars($row['description'] ?? ''); ?>">
                    <i class="fa fa-edit"></i>
                  </button>
                  <button class="btn btn-sm btn-danger delete_cat" type="button" data-id="<?php echo $row['id']; ?>">
                    <i class="fa fa-trash"></i>
                  </button>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <!-- /Table Panel -->
  </div>

</div>

<script>
$('#manage-room').submit(function(e) {
  e.preventDefault();
  start_load();
  $.ajax({
    url: 'ajax.php?action=save_room',
    method: "POST",
    data: $(this).serialize(),
    success: function(resp) {
      if (resp == 1) {
        alert_toast("Data successfully added", 'success');
        setTimeout(function() { location.reload(); }, 1500);
      } else if (resp == 2) {
        alert_toast("Data successfully updated", 'success');
        setTimeout(function() { location.reload(); }, 1500);
      }
    }
  });
});

$('.edit_cat').click(function() {
  start_load();
  var cat = $('#manage-room');
  cat.get(0).reset();
  cat.find("[name='id']").val($(this).data('id'));
  cat.find("[name='room']").val($(this).data('room'));
  cat.find("[name='category_id']").val($(this).data('category_id'));
  cat.find("[name='status']").val($(this).data('status'));
  cat.find("[name='floor']").val($(this).data('floor') || 1);
  cat.find("[name='max_occupancy']").val($(this).data('max_occupancy') || 2);
  cat.find("[name='description']").val($(this).data('description') || '');
  end_load();
});

$('.delete_cat').click(function() {
  _conf("Are you sure to delete this room?", "delete_cat", [$(this).attr('data-id')]);
});

function delete_cat($id) {
  start_load();
  $.ajax({
    url: 'ajax.php?action=delete_room',
    method: 'POST',
    data: {id: $id},
    success: function(resp) {
      if (resp == 1) {
        alert_toast("Data successfully deleted", 'success');
        setTimeout(function() { location.reload(); }, 1500);
      }
    }
  });
}
</script>
