<style>
nav#sidebar { background:url(../assets/img/<?php echo $_SESSION['setting_cover_img'] ?? '' ?>); background-repeat:no-repeat; background-size:cover; }
nav#sidebar .section-label { font-size:.68rem; text-transform:uppercase; letter-spacing:.08em; color:#ffffffa0; padding:10px 15px 2px; font-weight:700; }
</style>

<nav id="sidebar" class="mx-lt-5">
  <div class="sidebar-list">
    <a href="index.php?page=home"         class="nav-item nav-home"><span class="icon-field"><i class="fa fa-tachometer-alt"></i></span> Dashboard</a>

    <div class="section-label">Front Desk</div>
    <a href="index.php?page=booked"       class="nav-item nav-booked"><span class="icon-field"><i class="fa fa-calendar-check"></i></span> Bookings</a>
    <a href="index.php?page=check_in"     class="nav-item nav-check_in"><span class="icon-field"><i class="fa fa-sign-in-alt"></i></span> Check In</a>
    <a href="index.php?page=check_out"    class="nav-item nav-check_out"><span class="icon-field"><i class="fa fa-sign-out-alt"></i></span> Check Out</a>

    <div class="section-label">Guests</div>
    <a href="index.php?page=guests"       class="nav-item nav-guests"><span class="icon-field"><i class="fa fa-users"></i></span> Guest Profiles</a>
    <a href="index.php?page=guest_requests_list" class="nav-item nav-guest_requests_list"><span class="icon-field"><i class="fa fa-comment-dots"></i></span> Guest Requests</a>

    <div class="section-label">Operations</div>
    <a href="index.php?page=housekeeping" class="nav-item nav-housekeeping"><span class="icon-field"><i class="fa fa-broom"></i></span> Housekeeping</a>
    <a href="index.php?page=billing"      class="nav-item nav-billing"><span class="icon-field"><i class="fa fa-file-invoice-dollar"></i></span> Billing</a>

    <div class="section-label">Intelligence</div>
    <a href="index.php?page=reports"      class="nav-item nav-reports"><span class="icon-field"><i class="fa fa-chart-bar"></i></span> Reports</a>
    <a href="index.php?page=notifications_page" class="nav-item nav-notifications_page"><span class="icon-field"><i class="fa fa-bell"></i></span> Notifications</a>

    <div class="section-label">Inventory</div>
    <a href="index.php?page=categories"   class="nav-item nav-categories"><span class="icon-field"><i class="fa fa-layer-group"></i></span> Room Categories</a>
    <a href="index.php?page=rooms"        class="nav-item nav-rooms"><span class="icon-field"><i class="fa fa-bed"></i></span> Rooms</a>

    <?php if (($_SESSION['login_type'] ?? 0) == 1): ?>
    <div class="section-label">Admin</div>
    <a href="index.php?page=users"        class="nav-item nav-users"><span class="icon-field"><i class="fa fa-user-shield"></i></span> Users</a>
    <a href="index.php?page=site_settings" class="nav-item nav-site_settings"><span class="icon-field"><i class="fa fa-cogs"></i></span> Settings</a>
    <?php endif; ?>
  </div>
</nav>

<script>
$('.nav-<?php echo isset($_GET['page']) ? htmlspecialchars($_GET['page']) : '' ?>').addClass('active');
</script>
