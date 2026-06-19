<?php
session_start();
include './db_connect.php';

if (isset($_SESSION['login_id'])) { header('Location: index.php?page=home'); exit; }

$q = $conn->query("SELECT hotel_name FROM system_settings LIMIT 1");
$settings = $q ? $q->fetch_assoc() : [];
$hotel_name = htmlspecialchars($settings['hotel_name'] ?? 'Hotel Management');

$token = trim($_GET['token'] ?? '');
$user  = null;
$error = '';
$success = '';

// Validate token
if ($token) {
    $stmt = $conn->prepare("SELECT id, name, username FROM users WHERE reset_token = ? AND reset_token_expiry > NOW() AND is_active = 1 AND status = 'active' LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $res  = $stmt->get_result();
    $stmt->close();
    if ($res->num_rows > 0) {
        $user = $res->fetch_assoc();
    } else {
        $error = 'This reset link is invalid or has expired. Please <a href="forgot_password.php">request a new one</a>.';
    }
} else {
    $error = 'No reset token provided. <a href="forgot_password.php">Go back</a>.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $pass  = $_POST['password']  ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if (strlen($pass) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($pass !== $pass2) {
        $error = 'Passwords do not match.';
    } else {
        $hashed = password_hash($pass, PASSWORD_BCRYPT);
        $upd = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
        $upd->bind_param("si", $hashed, $user['id']);
        $upd->execute();
        $upd->close();
        $success = 'Password reset successfully! <a href="login.php">Sign in now →</a>';
        $user = null;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password — <?php echo $hotel_name; ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/font-awesome/css/all.min.css">
  <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body class="auth-page">

<div class="auth-container">
  <div class="auth-header">
    <div class="auth-icon-wrap">
      <i class="fas fa-lock-open"></i>
    </div>
    <h1><?php echo $hotel_name; ?></h1>
    <p>Set New Password</p>
  </div>

  <div class="auth-body">
    <div class="auth-subtitle">
      <h2>Reset Password</h2>
      <?php if ($user): ?>
        <p>Hello, <strong style="color:#c4913a;"><?php echo htmlspecialchars($user['name']); ?></strong> — choose a new password</p>
      <?php else: ?>
        <p>Enter your new password below</p>
      <?php endif; ?>
    </div>

    <?php if ($error): ?>
      <div class="auth-alert auth-alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <span><?php echo $error; ?></span>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="auth-alert auth-alert-success">
        <i class="fas fa-check-circle"></i>
        <span><?php echo $success; ?></span>
      </div>
    <?php endif; ?>

    <?php if ($user): ?>
    <form method="POST" id="reset-form" autocomplete="off">
      <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

      <div class="form-group">
        <label for="password">New Password <span class="req">*</span></label>
        <div class="input-wrapper">
          <input type="password" id="password" name="password" class="form-control-auth"
                 placeholder="Minimum 8 characters" required minlength="8"
                 style="padding-right:44px;" oninput="checkStrength(this.value)">
          <i class="fas fa-eye password-toggle" onclick="togglePw('password',this)"></i>
        </div>
        <div class="strength-bar" id="strength-bar">
          <span></span><span></span><span></span><span></span>
        </div>
        <div class="strength-hint" id="strength-hint">Enter a password</div>
      </div>

      <div class="form-group">
        <label for="password2">Confirm Password <span class="req">*</span></label>
        <div class="input-wrapper">
          <input type="password" id="password2" name="password2" class="form-control-auth"
                 placeholder="Repeat your password" required
                 style="padding-right:44px;">
          <i class="fas fa-eye password-toggle" onclick="togglePw('password2',this)"></i>
        </div>
      </div>

      <button type="submit" class="btn-auth">
        <span>Save New Password</span>
        <i class="fas fa-check" style="margin-left:6px;"></i>
      </button>
    </form>
    <?php endif; ?>

    <?php if (!$user && !$success): ?>
    <div class="auth-link" style="margin-top:0;">
      <a href="forgot_password.php"><i class="fas fa-arrow-left" style="font-size:11px;margin-right:4px;"></i> Request New Link</a>
    </div>
    <?php endif; ?>

    <div class="auth-link" style="margin-top:14px;">
      <a href="login.php"><i class="fas fa-arrow-left" style="font-size:11px;margin-right:4px;"></i> Back to Sign In</a>
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

function checkStrength(val) {
  const bar  = document.getElementById('strength-bar');
  const hint = document.getElementById('strength-hint');
  let score = 0;
  if (val.length >= 8)                   score++;
  if (/[A-Z]/.test(val))                 score++;
  if (/[0-9]/.test(val))                 score++;
  if (/[^A-Za-z0-9]/.test(val))          score++;
  bar.className = 'strength-bar' + (score ? ' s' + score : '');
  const labels = ['','Weak','Fair','Good','Strong'];
  hint.textContent = val.length ? labels[score] || 'Weak' : 'Enter a password';
}
</script>
</body>
</html>
