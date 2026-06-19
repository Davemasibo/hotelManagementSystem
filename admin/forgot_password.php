<?php
session_start();
include './db_connect.php';

if (isset($_SESSION['login_id'])) { header('Location: index.php?page=home'); exit; }

$q = $conn->query("SELECT hotel_name FROM system_settings LIMIT 1");
$settings = $q ? $q->fetch_assoc() : [];
$hotel_name = htmlspecialchars($settings['hotel_name'] ?? 'Hotel Management');

$message = '';
$message_type = '';
$reset_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    if (empty($username)) {
        $message = 'Please enter your username.';
        $message_type = 'danger';
    } else {
        $stmt = $conn->prepare("SELECT id, name, username, email FROM users WHERE username = ? AND is_active = 1 AND status = 'active' LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        // Always show the same message (prevents username enumeration)
        if ($result->num_rows > 0) {
            $user  = $result->fetch_assoc();
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $upd = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
            $upd->bind_param("ssi", $token, $expiry, $user['id']);
            $upd->execute();
            $upd->close();

            $reset_link = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/reset_password.php?token=' . $token;
        }

        $message = 'If that username exists, a reset link has been generated below.';
        $message_type = 'info';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password — <?php echo $hotel_name; ?></title>
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
      <i class="fas fa-key"></i>
    </div>
    <h1><?php echo $hotel_name; ?></h1>
    <p>Password Recovery</p>
  </div>

  <div class="auth-body">
    <div class="auth-subtitle">
      <h2>Forgot Password?</h2>
      <p>Enter your username and we'll generate a reset link</p>
    </div>

    <?php if ($message): ?>
      <div class="auth-alert auth-alert-<?php echo $message_type; ?>">
        <i class="fas <?php echo $message_type === 'danger' ? 'fa-exclamation-circle' : 'fa-info-circle'; ?>"></i>
        <span><?php echo htmlspecialchars($message); ?></span>
      </div>
    <?php endif; ?>

    <?php if ($reset_link): ?>
      <div class="auth-alert auth-alert-info" style="flex-direction:column;gap:8px;">
        <div style="display:flex;align-items:center;gap:8px;">
          <i class="fas fa-link"></i>
          <strong>Your reset link (valid 1 hour):</strong>
        </div>
        <a href="<?php echo htmlspecialchars($reset_link); ?>" style="word-break:break-all;font-size:12px;">
          <?php echo htmlspecialchars($reset_link); ?>
        </a>
        <small style="opacity:0.7;font-size:11px;">In production this would be sent to the registered email address.</small>
      </div>
    <?php endif; ?>

    <?php if (!$reset_link): ?>
    <form method="POST" autocomplete="off">
      <div class="form-group">
        <label for="username">Username <span class="req">*</span></label>
        <input type="text" id="username" name="username" class="form-control-auth"
               placeholder="Enter your username" required autofocus
               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
      </div>

      <button type="submit" class="btn-auth">
        <span>Send Reset Link</span>
        <i class="fas fa-paper-plane" style="margin-left:6px;"></i>
      </button>
    </form>
    <?php endif; ?>

    <div class="auth-link" style="margin-top:18px;">
      <a href="login.php"><i class="fas fa-arrow-left" style="font-size:11px;margin-right:4px;"></i> Back to Sign In</a>
    </div>
  </div>
</div>

</body>
</html>
