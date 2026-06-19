<?php
session_start();
include './db_connect.php';

if (isset($_SESSION['login_id'])) { header('Location: index.php?page=home'); exit; }

$q = $conn->query("SELECT hotel_name FROM system_settings LIMIT 1");
$settings = $q ? $q->fetch_assoc() : [];
$hotel_name = htmlspecialchars($settings['hotel_name'] ?? 'Hotel Management');

$errors  = [];
$success = '';
$old     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'name'     => trim($_POST['name']     ?? ''),
        'username' => trim($_POST['username'] ?? ''),
        'email'    => trim($_POST['email']    ?? ''),
        'phone'    => trim($_POST['phone']    ?? ''),
    ];
    $pass  = $_POST['password']  ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if (empty($old['name']))                 $errors[] = 'Full name is required.';
    if (empty($old['username']))             $errors[] = 'Username is required.';
    if (strlen($old['username']) < 4)        $errors[] = 'Username must be at least 4 characters.';
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $old['username'])) $errors[] = 'Username may only contain letters, numbers, and underscores.';
    if (empty($old['email']))                $errors[] = 'Email address is required.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (strlen($pass) < 8)                   $errors[] = 'Password must be at least 8 characters.';
    if ($pass !== $pass2)                    $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        // Check username uniqueness
        $chk = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $chk->bind_param("s", $old['username']);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) $errors[] = 'That username is already taken.';
        $chk->close();

        // Check email uniqueness
        $chk2 = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $chk2->bind_param("s", $old['email']);
        $chk2->execute();
        if ($chk2->get_result()->num_rows > 0) $errors[] = 'An account with that email already exists.';
        $chk2->close();
    }

    if (empty($errors)) {
        $hashed = password_hash($pass, PASSWORD_BCRYPT);
        $type   = 2; // staff
        $status = 'pending';
        $stmt = $conn->prepare(
            "INSERT INTO users (name, email, phone, username, password, type, is_active, status)
             VALUES (?, ?, ?, ?, ?, ?, 0, 'pending')"
        );
        $stmt->bind_param("sssssi", $old['name'], $old['email'], $old['phone'], $old['username'], $hashed, $type);
        if ($stmt->execute()) {
            $success = true;
            $old = [];
        } else {
            $errors[] = 'Registration failed — please try again.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Request Access — <?php echo $hotel_name; ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/font-awesome/css/all.min.css">
  <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body class="auth-page">

<div class="auth-container" style="max-width:480px;">
  <div class="auth-header">
    <div class="auth-icon-wrap">
      <i class="fas fa-user-plus"></i>
    </div>
    <h1><?php echo $hotel_name; ?></h1>
    <p>Staff Account Request</p>
  </div>

  <div class="auth-body">
    <div class="auth-subtitle">
      <h2>Request Access</h2>
      <p>Your account will be reviewed and activated by an administrator</p>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="auth-alert auth-alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <div>
          <?php foreach ($errors as $e): ?>
            <div><?php echo htmlspecialchars($e); ?></div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="auth-alert auth-alert-success" style="flex-direction:column;gap:8px;">
        <div style="display:flex;align-items:center;gap:8px;">
          <i class="fas fa-check-circle"></i>
          <strong>Request submitted!</strong>
        </div>
        <span>Your account is pending approval. You'll be notified by your administrator once it's activated.</span>
      </div>
      <div class="auth-link" style="margin-top:18px;">
        <a href="login.php"><i class="fas fa-arrow-left" style="font-size:11px;margin-right:4px;"></i> Back to Sign In</a>
      </div>
    <?php else: ?>

    <form method="POST" id="signup-form" autocomplete="off" novalidate>
      <div class="form-row">
        <div class="form-group">
          <label for="name">Full Name <span class="req">*</span></label>
          <input type="text" id="name" name="name" class="form-control-auth"
                 placeholder="Your full name" value="<?php echo htmlspecialchars($old['name'] ?? ''); ?>" autofocus>
        </div>
        <div class="form-group">
          <label for="phone">Phone</label>
          <input type="tel" id="phone" name="phone" class="form-control-auth"
                 placeholder="Optional" value="<?php echo htmlspecialchars($old['phone'] ?? ''); ?>">
        </div>
      </div>

      <div class="form-group">
        <label for="email">Email Address <span class="req">*</span></label>
        <input type="email" id="email" name="email" class="form-control-auth"
               placeholder="you@example.com" value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>">
      </div>

      <div class="form-group">
        <label for="username">Username <span class="req">*</span></label>
        <input type="text" id="username" name="username" class="form-control-auth"
               placeholder="Letters, numbers, underscores" value="<?php echo htmlspecialchars($old['username'] ?? ''); ?>"
               pattern="[a-zA-Z0-9_]+" minlength="4">
      </div>

      <div class="auth-divider">Password</div>

      <div class="form-group">
        <label for="password">Password <span class="req">*</span></label>
        <div class="input-wrapper">
          <input type="password" id="password" name="password" class="form-control-auth"
                 placeholder="Minimum 8 characters" minlength="8"
                 style="padding-right:44px;" oninput="checkStrength(this.value)">
          <i class="fas fa-eye password-toggle" onclick="togglePw('password',this)"></i>
        </div>
        <div class="strength-bar" id="strength-bar">
          <span></span><span></span><span></span><span></span>
        </div>
        <div class="strength-hint" id="strength-hint"></div>
      </div>

      <div class="form-group">
        <label for="password2">Confirm Password <span class="req">*</span></label>
        <div class="input-wrapper">
          <input type="password" id="password2" name="password2" class="form-control-auth"
                 placeholder="Repeat your password"
                 style="padding-right:44px;">
          <i class="fas fa-eye password-toggle" onclick="togglePw('password2',this)"></i>
        </div>
      </div>

      <button type="submit" class="btn-auth" id="signup-btn">
        <span class="btn-text"><span>Submit Request</span> <i class="fas fa-paper-plane"></i></span>
        <span class="spinner"></span>
      </button>
    </form>

    <div class="auth-link">
      Already have an account? <a href="login.php">Sign in</a>
    </div>

    <?php endif; ?>
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
  if (!bar) return;
  let score = 0;
  if (val.length >= 8)          score++;
  if (/[A-Z]/.test(val))        score++;
  if (/[0-9]/.test(val))        score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;
  bar.className = 'strength-bar' + (score ? ' s' + score : '');
  const labels = ['','Weak','Fair','Good','Strong'];
  hint.textContent = val.length ? labels[score] || 'Weak' : '';
}

document.getElementById('signup-form')?.addEventListener('submit', function() {
  const btn = document.getElementById('signup-btn');
  btn.classList.add('loading');
  btn.disabled = true;
});
</script>
</body>
</html>
