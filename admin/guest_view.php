<?php
include('db_connect.php');
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { echo '<div class="alert alert-danger">Invalid guest ID.</div>'; exit; }

$stmt = $conn->prepare("SELECT * FROM guests WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$g = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$g) { echo '<div class="alert alert-warning">Guest not found.</div>'; exit; }

$prefs_res = $conn->prepare("SELECT * FROM guest_preferences WHERE guest_id=?");
$prefs_res->bind_param("i", $id);
$prefs_res->execute();
$prefs = $prefs_res->get_result()->fetch_all(MYSQLI_ASSOC);
$prefs_res->close();

// Stay history
$hist = $conn->prepare(
    "SELECT c.*, r.room, rc.name AS category, i.total, i.status AS inv_status, i.balance
     FROM checked c
     LEFT JOIN rooms r ON c.room_id = r.id
     LEFT JOIN room_categories rc ON r.category_id = rc.id
     LEFT JOIN invoices i ON i.checked_id = c.id
     WHERE c.guest_id = ? ORDER BY c.date_in DESC LIMIT 10"
);
$hist->bind_param("i", $id);
$hist->execute();
$history = $hist->get_result()->fetch_all(MYSQLI_ASSOC);
$hist->close();

// Active requests
$req = $conn->prepare(
    "SELECT gr.* FROM guest_requests gr
     JOIN checked c ON gr.checked_id = c.id
     WHERE c.guest_id = ? AND gr.status NOT IN ('completed','cancelled')
     ORDER BY gr.created_at DESC"
);
$req->bind_param("i", $id);
$req->execute();
$requests = $req->get_result()->fetch_all(MYSQLI_ASSOC);
$req->close();

$status_labels = [0=>'Pending',1=>'Checked In',2=>'Checked Out'];
$status_colors = [0=>'warning',1=>'success',2=>'secondary'];
$inv_colors = ['draft'=>'secondary','issued'=>'warning','partial'=>'info','paid'=>'success','cancelled'=>'danger'];
?>
<div class="container-fluid">

  <div class="d-flex justify-content-between align-items-start mb-3">
    <div class="d-flex align-items-center">
      <div style="width:54px;height:54px;border-radius:50%;background:#6c757d;color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:700;margin-right:12px">
        <?php echo strtoupper(substr($g['full_name'],0,1)); ?>
      </div>
      <div>
        <h5 class="mb-0"><?php echo htmlspecialchars($g['full_name']); ?>
          <?php if ($g['is_vip']): ?><span class="badge" style="background:#ffc107;color:#000">VIP</span><?php endif; ?>
        </h5>
        <small class="text-muted">Guest since <?php echo date('M Y', strtotime($g['created_at'])); ?> &bull; <?php echo (int)$g['total_stays']; ?> stay(s)</small>
      </div>
    </div>
    <button class="btn btn-sm btn-primary" onclick="uni_modal('Edit Guest','manage_guest.php?id=<?php echo $id; ?>')">
      <i class="fa fa-edit mr-1"></i>Edit
    </button>
  </div>

  <ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#gv-info">Profile</a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#gv-history">Stay History</a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#gv-requests">Active Requests <span class="badge badge-warning"><?php echo count($requests); ?></span></a></li>
  </ul>

  <div class="tab-content">
    <!-- Profile -->
    <div class="tab-pane fade show active" id="gv-info">
      <div class="row">
        <div class="col-md-6">
          <table class="table table-sm table-borderless">
            <tr><th class="text-muted" style="width:40%">Email</th><td><?php echo htmlspecialchars($g['email']??'—'); ?></td></tr>
            <tr><th class="text-muted">Phone</th><td><?php echo htmlspecialchars($g['phone']??'—'); ?></td></tr>
            <tr><th class="text-muted">Nationality</th><td><?php echo htmlspecialchars($g['nationality']??'—'); ?></td></tr>
            <tr><th class="text-muted">Date of Birth</th><td><?php echo $g['date_of_birth']??'—'; ?></td></tr>
            <tr><th class="text-muted">Address</th><td><?php echo htmlspecialchars(($g['address']??'').' '.($g['city']??'').' '.($g['country']??'')); ?></td></tr>
            <tr><th class="text-muted">ID Type</th><td><span class="badge badge-light"><?php echo strtoupper($g['id_type']??''); ?></span> <?php echo htmlspecialchars($g['id_number']??''); ?></td></tr>
          </table>
        </div>
        <div class="col-md-6">
          <?php if ($g['is_vip'] && $g['vip_note']): ?>
          <div class="alert alert-warning"><i class="fa fa-crown mr-1"></i><strong>VIP Note:</strong> <?php echo htmlspecialchars($g['vip_note']); ?></div>
          <?php endif; ?>
          <?php if ($g['notes']): ?>
          <div class="alert alert-light border"><strong>Notes:</strong><br><?php echo nl2br(htmlspecialchars($g['notes'])); ?></div>
          <?php endif; ?>
          <?php if (!empty($prefs)): ?>
          <div class="card"><div class="card-header py-2"><strong>Preferences</strong></div>
            <div class="card-body p-0">
              <table class="table table-sm mb-0">
                <?php foreach ($prefs as $p): ?>
                <tr><td class="text-muted"><?php echo htmlspecialchars($p['pref_key']); ?></td>
                    <td><?php echo htmlspecialchars($p['pref_value']); ?></td></tr>
                <?php endforeach; ?>
              </table>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Stay History -->
    <div class="tab-pane fade" id="gv-history">
      <table class="table table-sm table-hover">
        <thead class="thead-light"><tr><th>Ref</th><th>Room</th><th>Check-In</th><th>Check-Out</th><th>Status</th><th>Invoice</th><th></th></tr></thead>
        <tbody>
          <?php if (empty($history)): ?>
          <tr><td colspan="7" class="text-center text-muted py-3">No stay history found</td></tr>
          <?php else: ?>
          <?php foreach ($history as $h): ?>
          <tr>
            <td><code><?php echo htmlspecialchars($h['ref_no']); ?></code></td>
            <td><?php echo htmlspecialchars($h['room']??'—'); ?><br><small class="text-muted"><?php echo htmlspecialchars($h['category']??''); ?></small></td>
            <td><?php echo substr($h['date_in'],0,10); ?></td>
            <td><?php echo substr($h['date_out'],0,10); ?></td>
            <td><span class="badge badge-<?php echo $status_colors[$h['status']]??'secondary'; ?>"><?php echo $status_labels[$h['status']]??'Unknown'; ?></span></td>
            <td>
              <?php if (!empty($h['total'])): ?>
              <span class="badge badge-<?php echo $inv_colors[$h['inv_status']]??'secondary'; ?>">$<?php echo number_format($h['total'],2); ?></span>
              <?php if ($h['balance'] > 0): ?><small class="text-danger ml-1">$<?php echo number_format($h['balance'],2); ?> due</small><?php endif; ?>
              <?php else: ?>—<?php endif; ?>
            </td>
            <td><?php if (!empty($h['total'])): ?><a href="invoice_view.php?checked_id=<?php echo $h['id']; ?>" target="_blank" class="btn btn-xs btn-outline-secondary" style="font-size:.72rem">Invoice</a><?php endif; ?></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Active Requests -->
    <div class="tab-pane fade" id="gv-requests">
      <?php if (empty($requests)): ?>
      <div class="text-center text-muted py-4"><i class="fa fa-check-circle fa-2x mb-2 d-block text-success"></i>No active requests</div>
      <?php else: ?>
      <?php $pcolors=['low'=>'secondary','normal'=>'info','high'=>'warning','urgent'=>'danger'];
            $scolors=['pending'=>'warning','in_progress'=>'info','completed'=>'success','cancelled'=>'secondary'];
      ?>
      <?php foreach ($requests as $r): ?>
      <div class="alert alert-light border mb-2">
        <div class="d-flex justify-content-between">
          <div>
            <span class="badge badge-<?php echo $pcolors[$r['priority']]??'secondary'; ?> mr-1"><?php echo strtoupper($r['priority']); ?></span>
            <span class="badge badge-light mr-2"><?php echo ucwords(str_replace('_',' ',$r['request_type'])); ?></span>
            <?php echo htmlspecialchars($r['description']); ?>
          </div>
          <span class="badge badge-<?php echo $scolors[$r['status']]??'secondary'; ?>"><?php echo ucwords(str_replace('_',' ',$r['status'])); ?></span>
        </div>
        <small class="text-muted"><?php echo $r['created_at']; ?></small>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

</div>
