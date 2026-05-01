<?php
session_start();
if(isset($_SESSION['user_id'])){
    if($_SESSION['role'] === 'admin') header('Location: /quickfix/admin/dashboard.php');
    elseif($_SESSION['role'] === 'professional') header('Location: /quickfix/professional/dashboard.php');
    else header('Location: /quickfix/client/dashboard.php');
    exit;
}
if($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['auth'])){
    header('Location: /quickfix/browse.php');
    exit;
}
require_once 'includes/db.php';
$error = '';
$success = '';
$authMode = ($_GET['auth'] ?? 'login') === 'register' ? 'register' : 'login';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $action = $_POST['action'] ?? '';

    if($action === 'login'){
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $stmt  = $pdo->prepare("SELECT * FROM users WHERE email=?");
        $stmt->execute([$email]);
        $user  = $stmt->fetch();
        if($user && password_verify($pass, $user['password_hash'])){
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email']     = $user['email'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['location']  = $user['location'];
            if($user['role'] === 'admin') header('Location: /quickfix/admin/dashboard.php');
            elseif($user['role'] === 'professional') header('Location: /quickfix/professional/dashboard.php');
            else header('Location: /quickfix/client/dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }

    if($action === 'register'){
        $name  = htmlspecialchars(trim($_POST['full_name'] ?? ''));
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $phone = trim($_POST['phone'] ?? '');
        $role  = $_POST['role'] ?? 'client';
        $loc   = htmlspecialchars(trim($_POST['location'] ?? ''));
        $trade = $_POST['trade'] ?? '';
        $nid   = trim($_POST['national_id'] ?? '');
        if(strlen($pass) < 6){
            $error = 'Password must be at least 6 characters.';
        } else {
            $chk = $pdo->prepare("SELECT user_id FROM users WHERE email=?");
            $chk->execute([$email]);
            if($chk->fetch()){
                $error = 'Email already registered.';
            } else {
                $hash = password_hash($pass, PASSWORD_BCRYPT);
                $pdo->prepare("INSERT INTO users (full_name,email,password_hash,phone,role,location,national_id) VALUES (?,?,?,?,?,?,?)")->execute([$name,$email,$hash,$phone,$role,$loc,$nid]);
                $uid = $pdo->lastInsertId();
                if($role === 'professional' && $trade){
                    $pdo->prepare("INSERT INTO professional_profiles (user_id,trade,hourly_rate) VALUES (?,?,?)")->execute([$uid,$trade,5.00]);
                }
                $success = 'Account created. Please log in.';
            }
        }
    }
}
$TRADES = ['Plumbing','Electrical','Painting','Carpentry','Tiling','Roofing','Welding','Landscaping','General Handyman'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>QuickFix ZW - Zimbabwe's Home Services Marketplace</title>
<link rel="stylesheet" href="/quickfix/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
<div style="background:linear-gradient(135deg,#1a1a2e 0%,#c44d00 100%);min-height:100vh;display:flex;flex-direction:column">
  <nav style="display:flex;justify-content:space-between;align-items:center;padding:1.2rem 2rem;gap:1rem;flex-wrap:wrap">
    <div style="color:white;font-size:1.6rem;font-weight:800"><i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i> Quick<span style="color:#f96a15">Fix</span> ZW</div>
    <div style="display:flex;gap:0.5rem;flex-wrap:wrap">
      <a href="/quickfix/browse.php" class="btn btn-outline" style="color:white;border-color:rgba(255,255,255,0.5)">Browse Services</a>
      <a href="/quickfix/about.php" class="btn btn-outline" style="color:white;border-color:rgba(255,255,255,0.5)">About</a>
      <a href="/quickfix/support.php" class="btn btn-outline" style="color:white;border-color:rgba(255,255,255,0.5)">Support</a>
      <a href="/quickfix/index.php?auth=login#auth" class="btn btn-outline" style="color:white;border-color:rgba(255,255,255,0.5)">Login</a>
      <a href="/quickfix/index.php?auth=register#auth" class="btn btn-primary">Get Started</a>
    </div>
  </nav>

  <div style="flex:1;display:flex;align-items:center;justify-content:center;flex-wrap:wrap;gap:3rem;padding:3rem 2rem">
    <div style="color:white;max-width:520px">
      <h1 style="font-size:3rem;font-weight:800;line-height:1.2;margin-bottom:1.2rem">
        Zimbabwe's Home Services <span style="color:#f96a15">Marketplace</span>
      </h1>
      <p style="font-size:1.1rem;opacity:0.85;margin-bottom:2rem;line-height:1.7">
        Post a job and get bids from verified local plumbers, electricians, painters, carpenters and more, or browse professionals before creating an account.
      </p>
      <div style="display:flex;flex-wrap:wrap;gap:0.8rem">
        <span style="background:rgba(255,255,255,0.15);padding:0.5rem 1rem;border-radius:25px;font-size:0.85rem"><i class="fa-solid fa-wrench" aria-hidden="true"></i> Plumbing</span>
        <span style="background:rgba(255,255,255,0.15);padding:0.5rem 1rem;border-radius:25px;font-size:0.85rem"><i class="fa-solid fa-bolt" aria-hidden="true"></i> Electrical</span>
        <span style="background:rgba(255,255,255,0.15);padding:0.5rem 1rem;border-radius:25px;font-size:0.85rem"><i class="fa-solid fa-paint-roller" aria-hidden="true"></i> Painting</span>
        <span style="background:rgba(255,255,255,0.15);padding:0.5rem 1rem;border-radius:25px;font-size:0.85rem"><i class="fa-solid fa-hammer" aria-hidden="true"></i> Carpentry</span>
        <span style="background:rgba(255,255,255,0.15);padding:0.5rem 1rem;border-radius:25px;font-size:0.85rem"><i class="fa-solid fa-border-all" aria-hidden="true"></i> Tiling</span>
        <span style="background:rgba(255,255,255,0.15);padding:0.5rem 1rem;border-radius:25px;font-size:0.85rem"><i class="fa-solid fa-house" aria-hidden="true"></i> Roofing</span>
      </div>
      <div style="display:flex;gap:0.8rem;flex-wrap:wrap;margin-top:1.5rem">
        <a href="/quickfix/browse.php" class="btn btn-primary">Browse providers first</a>
        <a href="/quickfix/support.php" class="btn btn-outline" style="color:white;border-color:rgba(255,255,255,0.5)">Support contact</a>
      </div>
    </div>

    <div class="auth-card" style="flex-shrink:0" id="auth">
      <div class="auth-logo">
        <h2>Quick<span>Fix</span> ZW</h2>
        <p>Connect with trusted home service professionals</p>
      </div>

      <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
      <?php if($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

      <div class="auth-tabs">
        <div class="auth-tab <?= $authMode === 'login' ? 'active' : '' ?>" id="tab-login" onclick="showTab('login',this)">Login</div>
        <div class="auth-tab <?= $authMode === 'register' ? 'active' : '' ?>" id="tab-register" onclick="showTab('register',this)">Register</div>
      </div>

      <form id="login-form" method="POST" style="<?= $authMode === 'login' ? '' : 'display:none' ?>">
        <input type="hidden" name="action" value="login">
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input type="email" name="email" class="form-control" placeholder="your@email.com" required>
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Login</button>
      </form>

      <form id="register-form" method="POST" style="<?= $authMode === 'register' ? '' : 'display:none' ?>">
        <input type="hidden" name="action" value="register">
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" name="full_name" class="form-control" placeholder="Tatenda Moyo" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Email *</label>
            <input type="email" name="email" class="form-control" placeholder="you@email.com" required>
          </div>
          <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" placeholder="+263771234567">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">City / Town *</label>
            <input type="text" name="location" class="form-control" placeholder="Chinhoyi" required>
          </div>
          <div class="form-group">
            <label class="form-label">National ID</label>
            <input type="text" name="national_id" class="form-control" placeholder="63-123456A21">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">I am a *</label>
          <select name="role" class="form-select" id="role-select" onchange="toggleTrade(this.value)">
            <option value="client">Client - I need home services</option>
            <option value="professional">Professional - I offer home services</option>
          </select>
        </div>
        <div class="form-group" id="trade-group" style="display:none">
          <label class="form-label">My Trade *</label>
          <select name="trade" class="form-select">
            <option value="">Select your trade</option>
            <?php foreach($TRADES as $t): ?>
            <option value="<?= $t ?>"><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Password *</label>
          <input type="password" name="password" class="form-control" placeholder="Min. 6 characters" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Create Account</button>
      </form>
    </div>
  </div>
</div>

<script>
function showTab(t, el){
  document.querySelectorAll('.auth-tab').forEach(x => x.classList.remove('active'));
  if(el) el.classList.add('active');
  document.getElementById('login-form').style.display = t === 'login' ? 'block' : 'none';
  document.getElementById('register-form').style.display = t === 'register' ? 'block' : 'none';
}
function toggleTrade(role){
  document.getElementById('trade-group').style.display = role === 'professional' ? 'block' : 'none';
}
</script>
</body>
</html>
