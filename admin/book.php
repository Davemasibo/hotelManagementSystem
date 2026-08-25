<?php
include('db_connect.php');
require_once('reservation_helpers.php');

$today = date('Y-m-d');
$times = reservation_time_defaults($conn);

// Dates arrive from the availability search on list.php. Clamp the check-in to
// today so a stale/backdated link cannot seed a past reservation.
$in_raw  = !empty($_GET['in'])  ? date('Y-m-d', strtotime($_GET['in']))  : $today;
$out_raw = !empty($_GET['out']) ? date('Y-m-d', strtotime($_GET['out'])) : date('Y-m-d', strtotime($today . ' +1 day'));
if ($in_raw < $today) $in_raw = $today;
if ($out_raw <= $in_raw) $out_raw = date('Y-m-d', strtotime($in_raw . ' +1 day'));

$calc_days = max(1, (int)floor((strtotime($out_raw) - strtotime($in_raw)) / 86400));
?>
<div class="container-fluid">

	<form action="" id="manage-check">
		<input type="hidden" name="cid" value="<?php echo isset($_GET['cid']) ? (int)$_GET['cid'] : ''; ?>">
		<input type="hidden" name="rid" value="<?php echo isset($_GET['rid']) ? (int)$_GET['rid'] : ''; ?>">

		<div class="form-group">
			<label for="name">Name <span class="text-danger">*</span></label>
			<input type="text" name="name" id="name" class="form-control" required>
		</div>
		<div class="row">
			<div class="col-md-6">
				<div class="form-group">
					<label for="contact">Phone Number <span class="text-danger">*</span></label>
					<input type="tel" name="contact" id="contact" class="form-control" placeholder="e.g. 0712 345 678" required>
				</div>
			</div>
			<div class="col-md-6">
				<div class="form-group">
					<label for="id_number">ID / Passport Number <span class="text-danger">*</span></label>
					<input type="text" name="id_number" id="id_number" class="form-control" placeholder="e.g. 12345678" required>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6">
				<div class="form-group">
					<label for="date_in">Check-In Date <span class="text-danger">*</span></label>
					<input type="date" name="date_in" id="date_in" class="form-control"
					       value="<?php echo $in_raw; ?>" min="<?php echo $today; ?>" required readonly>
				</div>
			</div>
			<div class="col-md-6">
				<div class="form-group">
					<label for="date_in_time">Check-In Time <span class="text-danger">*</span></label>
					<input type="time" name="date_in_time" id="date_in_time" class="form-control"
					       value="<?php echo htmlspecialchars($times['in']); ?>" required>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6">
				<div class="form-group">
					<label for="days">Nights of Stay <span class="text-danger">*</span></label>
					<input type="number" min="1" name="days" id="days" class="form-control" value="<?php echo $calc_days; ?>" required readonly>
				</div>
			</div>
			<div class="col-md-6">
				<div class="form-group">
					<label for="date_out_time">Check-Out Time <span class="text-danger">*</span></label>
					<input type="time" name="date_out_time" id="date_out_time" class="form-control"
					       value="<?php echo htmlspecialchars($times['out']); ?>" required>
				</div>
			</div>
		</div>
		<div class="alert alert-secondary py-2">
			<small><i class="fa fa-sign-out-alt mr-1"></i>Check-out: <strong id="bk-out-text">—</strong></small>
		</div>
		<div class="form-group">
			<label for="special_requests">Additional Notes</label>
			<textarea name="special_requests" id="special_requests" class="form-control" rows="4"
			          placeholder="Special requests, and any other guests on this booking — e.g. &quot;Travelling with family: Jane Doe (spouse), Amos Doe (son, 8). Requires an extra bed.&quot;"></textarea>
			<small class="text-muted">Use this to list accompanying guests / family members, extra beds or any other request.</small>
		</div>
	</form>
</div>
<script>
	var BK_TODAY = '<?php echo $today; ?>';

	function bkOutPreview(){
		var dIn  = $('#date_in').val();
		var days = parseInt($('#days').val()) || 1;
		var outT = $('#date_out_time').val() || '11:00';
		if(!dIn){ $('#bk-out-text').text('—'); return; }
		var d = new Date(dIn + 'T00:00:00');
		d.setDate(d.getDate() + days);
		$('#bk-out-text').text(d.toLocaleDateString('en-GB',{weekday:'short',day:'numeric',month:'short',year:'numeric'}) + ' at ' + outT);
	}
	$('#date_in, #days, #date_out_time').on('change input', bkOutPreview);
	bkOutPreview();

	$('#manage-check').submit(function(e){
		e.preventDefault();

		var name  = ($('#name').val()      || '').trim();
		var phone = ($('#contact').val()   || '').trim();
		var idno  = ($('#id_number').val() || '').trim();
		var dIn   = $('#date_in').val();
		if(!name)  { alert_toast('Enter your name','warning'); $('#name').focus(); return; }
		if(!phone || phone.replace(/\D/g,'').length < 7){ alert_toast('A valid phone number is required','warning'); $('#contact').focus(); return; }
		if(!idno)  { alert_toast('ID / passport number is required','warning'); $('#id_number').focus(); return; }
		if(!dIn || dIn < BK_TODAY){ alert_toast('Check-in date cannot be in the past','warning'); return; }

		start_load()
		$.ajax({
			url:'admin/ajax.php?action=save_book',
			method:'POST',
			data:$(this).serialize(),
			success:function(resp){
				end_load()
				// save_book returns JSON; jQuery may already have parsed it.
				var r;
				if(resp && typeof resp === 'object'){ r = resp; }
				else { try { r = JSON.parse(resp); } catch(err){ r = {status: parseInt(resp) > 0 ? 'ok' : 'error'}; } }
				if(r.status === 'ok' || r.id > 0){
					alert_toast("Booking saved successfully",'success')
					setTimeout(function(){ $('.modal').modal('hide') },1500)
				} else {
					alert_toast(r.message || 'Could not save the booking','danger')
				}
			},
			error:function(){ end_load(); alert_toast('Request failed — please retry','danger') }
		})
	})
</script>
