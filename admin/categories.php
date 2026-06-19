<?php include('db_connect.php'); ?>

<div class="container-fluid py-3">

  <div class="page-header mb-4">
    <h5 class="page-title"><i class="fa fa-th-large mr-2"></i>Room Categories</h5>
  </div>

  <div class="row">
    <!-- FORM Panel -->
    <div class="col-md-4">
      <form action="" id="manage-category">
        <div class="card">
          <div class="card-header">Room Category Form</div>
          <div class="card-body">
            <input type="hidden" name="id">
            <div class="form-group">
              <label class="control-label">Category Name</label>
              <input type="text" class="form-control" name="name" placeholder="e.g. Deluxe Suite">
            </div>
            <div class="form-group">
              <label class="control-label">Price (KES)</label>
              <input type="number" class="form-control text-right" name="price" step="any" placeholder="0.00">
            </div>
            <div class="form-group">
              <label class="control-label">Image</label>
              <input type="file" class="form-control" name="img" onchange="displayImg(this,$(this))">
            </div>
            <div class="form-group">
              <img src="<?php echo isset($image_path) ? '../assets/img/' . $cover_img : ''; ?>"
                   alt="" id="cimg"
                   style="max-height:10vh;max-width:100%;border-radius:8px;filter:brightness(0.9);display:<?php echo isset($image_path) ? 'block' : 'none'; ?>">
            </div>
          </div>
          <div class="card-footer">
            <div class="row">
              <div class="col-md-12 d-flex" style="gap:8px">
                <button class="btn btn-sm btn-primary flex-fill"><i class="fa fa-save mr-1"></i>Save</button>
                <button class="btn btn-sm btn-secondary" type="button" onclick="$('#manage-category').get(0).reset();$('#cimg').hide()">
                  <i class="fa fa-times mr-1"></i>Cancel
                </button>
              </div>
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
          <table class="table table-hover mb-0" id="cat-table">
            <thead>
              <tr>
                <th class="text-center" style="width:40px">#</th>
                <th class="text-center" style="width:80px">Image</th>
                <th>Category</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $i    = 1;
              $cats = $conn->query("SELECT * FROM room_categories ORDER BY id ASC");
              while ($row = $cats->fetch_assoc()):
              ?>
              <tr>
                <td class="text-center"><?php echo $i++; ?></td>
                <td class="text-center">
                  <?php if (!empty($row['cover_img'])): ?>
                  <img src="../assets/img/<?php echo htmlspecialchars($row['cover_img']); ?>"
                       alt="" class="cimg"
                       style="max-height:40px;max-width:60px;border-radius:6px;filter:brightness(0.9)">
                  <?php else: ?>
                  <span class="text-muted"><i class="fa fa-image"></i></span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="font-weight-bold"><?php echo htmlspecialchars($row['name']); ?></div>
                  <small class="text-muted">KES <?php echo number_format($row['price'], 2); ?></small>
                </td>
                <td class="text-center">
                  <button class="btn btn-sm btn-primary edit_cat" type="button"
                    data-id="<?php echo $row['id']; ?>"
                    data-name="<?php echo htmlspecialchars($row['name']); ?>"
                    data-price="<?php echo $row['price']; ?>"
                    data-cover_img="<?php echo htmlspecialchars($row['cover_img'] ?? ''); ?>">
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
function displayImg(input, _this) {
  if (input.files && input.files[0]) {
    var reader = new FileReader();
    reader.onload = function(e) {
      $('#cimg').attr('src', e.target.result).show();
    };
    reader.readAsDataURL(input.files[0]);
  }
}

$('#manage-category').submit(function(e) {
  e.preventDefault();
  start_load();
  $.ajax({
    url: 'ajax.php?action=save_category',
    data: new FormData($(this)[0]),
    cache: false,
    contentType: false,
    processData: false,
    method: 'POST',
    type: 'POST',
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
  var cat = $('#manage-category');
  cat.get(0).reset();
  cat.find("[name='id']").val($(this).attr('data-id'));
  cat.find("[name='name']").val($(this).attr('data-name'));
  cat.find("[name='price']").val($(this).attr('data-price'));
  var img = $(this).attr('data-cover_img');
  if (img) {
    cat.find("#cimg").attr('src', '../assets/img/' + img).show();
  } else {
    cat.find("#cimg").hide();
  }
  end_load();
});

$('.delete_cat').click(function() {
  _conf("Are you sure to delete this category?", "delete_cat", [$(this).attr('data-id')]);
});

function delete_cat($id) {
  start_load();
  $.ajax({
    url: 'ajax.php?action=delete_category',
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
