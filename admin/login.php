<?php
session_start();
include './db_connect.php';

if (isset($_SESSION['login_id'])) {
    header('Location: index.php?page=home');
    exit;
}

$q = $conn->query("SELECT * FROM system_settings LIMIT 1");
$settings = $q ? $q->fetch_assoc() : [];
foreach ($settings as $k => $v) {
    if (!is_numeric($k)) $_SESSION['setting_' . $k] = $v;
}

$hotel_name = htmlspecialchars($settings['hotel_name'] ?? 'Hotel Management');
$cover_img  = htmlspecialchars($settings['cover_img']  ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — <?php echo $hotel_name; ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/font-awesome/css/all.min.css">
  <link rel="stylesheet" href="assets/css/auth.css">
  <script src="assets/vendor/jquery/jquery.min.js"></script>
</head>
<body class="auth-page">

<div class="auth-container">
  <div class="auth-header">
    <div class="auth-icon-wrap">
      <i class="fas fa-hotel"></i>
    </div>
    <h1><?php echo $hotel_name; ?></h1>
    <p>Management Portal</p>
  </div>

  <div class="auth-body">
    <div class="auth-subtitle">
      <h2>Welcome Back</h2>
      <p>Sign in to your account to continue</p>
    </div>

    <div id="auth-alert-wrap"></div>

    <form id="login-form" autocomplete="on">
      <div class="form-group">
        <label for="username">Username <span class="req">*</span></label>
        <input type="text" id="username" name="username" class="form-control-auth"
               placeholder="Enter your username" autocomplete="username" autofocus>
      </div>

      <div class="form-group">
        <label for="password">Password <span class="req">*</span></label>
        <div class="input-wrapper">
          <input type="password" id="password" name="password" class="form-control-auth"
                 placeholder="Enter your password" autocomplete="current-password"
                 style="padding-right:44px;">
          <i class="fas fa-eye password-toggle" onclick="togglePw('password',this)"></i>
        </div>
      </div>

      <div class="forgot-row">
        <a href="forgot_password.php">Forgot Password?</a>
      </div>

      <button type="submit" class="btn-auth" id="login-btn">
        <span class="btn-text"><span>Sign In</span> <i class="fas fa-arrow-right"></i></span>
        <span class="spinner"></span>
      </button>
    </form>

    <div class="auth-link">
      Need an account? <a href="signup.php">Request Access</a>
    </div>
  </div>
</div>

<script>
function togglePw(id, icon) {
  const inp = document.getElementById(id);
  const show = inp.type === 'password';
  inp.type = show ? 'text' : 'password';
  icon.classList.toggle('fa-eye',      !show);
  icon.classList.toggle('fa-eye-slash', show);
}

$('#login-form').submit(function(e) {
  e.preventDefault();
  const $btn = $('#login-btn');
  $btn.addClass('loading').prop('disabled', true);
  $('#auth-alert-wrap').html('');

  $.ajax({
    url: 'ajax.php?action=login',
    method: 'POST',
    data: $(this).serialize(),
    success: function(resp) {
      if (resp == 1 || resp == 2) {
        location.href = 'index.php?page=home';
      } else if (resp == 4) {
        showAlert('info', 'Your account is pending administrator approval. Please check back later.');
        $btn.removeClass('loading').prop('disabled', false);
      } else if (resp == 3) {
        showAlert('danger', 'Please enter both username and password.');
        $btn.removeClass('loading').prop('disabled', false);
      } else {
        showAlert('danger', 'Incorrect username or password. Please try again.');
        $btn.removeClass('loading').prop('disabled', false);
      }
    },
    error: function() {
      showAlert('danger', 'Server error — please try again.');
      $btn.removeClass('loading').prop('disabled', false);
    }
  });
});

function showAlert(type, msg) {
  var icons = {danger: 'fa-exclamation-circle', success: 'fa-check-circle', info: 'fa-info-circle'};
  $('#auth-alert-wrap').html(
    '<div class="auth-alert auth-alert-' + type + '">' +
    '<i class="fas ' + (icons[type] || 'fa-info-circle') + '"></i><span>' + msg + '</span></div>'
  );
}
</script>
</body>
</html>
