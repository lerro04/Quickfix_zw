<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/mailer.php';
require_once 'includes/id_upload.php';

if(isset($_SESSION['user_id'])){
    if($_SESSION['role'] === 'admin') header('Location: '.BASE_URL.'/admin/dashboard.php');
    elseif($_SESSION['role'] === 'professional') header('Location: '.BASE_URL.'/professional/dashboard.php');
    else header('Location: '.BASE_URL.'/client/dashboard.php');
    exit;
}

$error = '';
$success = '';
$mode = $_GET['mode'] ?? 'login';
if(!in_array($mode, ['login','register'], true)) $mode = 'login';

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
            if($user['role'] === 'admin') header('Location: '.BASE_URL.'/admin/dashboard.php');
            elseif($user['role'] === 'professional') header('Location: '.BASE_URL.'/professional/dashboard.php');
            else header('Location: '.BASE_URL.'/client/dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
            $mode = 'login';
        }
    }

    if($action === 'register'){
        $name  = htmlspecialchars(trim($_POST['full_name'] ?? ''));
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $phone = trim($_POST['phone'] ?? '');
        $role  = $_POST['role'] ?? 'client';
        $loc   = htmlspecialchars(trim($_POST['location'] ?? ''));
        $trade = trim($_POST['trade'] ?? '');
        $tradeOther = trim($_POST['trade_other'] ?? '');
        if($trade === '__other__' && $tradeOther !== ''){
            $trade = htmlspecialchars($tradeOther);
        }
        $nid   = strtoupper(trim($_POST['national_id'] ?? ''));
        if(strlen($pass) < 6){
            $error = 'Password must be at least 6 characters.';
            $mode = 'register';
        } elseif($nid !== '' && !isValidNationalIdNumber($nid)){
            $error = 'National ID must be in the format 12-345678X90 (e.g. 70-345234P09).';
            $mode = 'register';
        } else {
            $chk = $pdo->prepare("SELECT user_id FROM users WHERE email=?");
            $chk->execute([$email]);
            if($chk->fetch()){
                $error = 'Email already registered.';
                $mode = 'register';
            } else {
                try {
                    $idFile = handleIdUpload($_FILES['id_file'] ?? []);
                } catch(RuntimeException $e){
                    $error = $e->getMessage();
                    $mode = 'register';
                    $idFile = '__error__';
                }
                if($idFile !== '__error__'){
                    $hash = password_hash($pass, PASSWORD_BCRYPT);
                    $pdo->prepare("INSERT INTO users (full_name,email,password_hash,phone,role,location,national_id,national_id_file) VALUES (?,?,?,?,?,?,?,?)")->execute([$name,$email,$hash,$phone,$role,$loc,$nid,$idFile ?: null]);
                    $uid = $pdo->lastInsertId();
                    if($role === 'professional' && $trade){
                        $pdo->prepare("INSERT INTO professional_profiles (user_id,trade,hourly_rate) VALUES (?,?,?)")->execute([$uid,$trade,5.00]);
                        $pdo->prepare("INSERT IGNORE INTO trades (name) VALUES (?)")->execute([$trade]);
                        notifyAdminNewProfessional($pdo, (int)$uid);
                    }
                    $success = 'Account created. Please log in.';
                    $mode = 'login';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $mode === 'register' ? 'Create Account' : 'Login' ?> - QuickFix ZW</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
<div style="background:linear-gradient(135deg,#1a1a2e 0%,#c44d00 100%);min-height:100vh;display:flex;flex-direction:column">
  <nav style="display:flex;justify-content:space-between;align-items:center;padding:1.2rem 2rem;gap:1rem;flex-wrap:wrap">
    <a href="<?= BASE_URL ?>/index.php" style="color:white;font-size:1.6rem;font-weight:800;text-decoration:none"><i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i> Quick<span style="color:#f96a15">Fix</span> ZW</a>
    <div style="display:flex;gap:0.5rem;flex-wrap:wrap">
      <a href="<?= BASE_URL ?>/index.php" class="btn btn-outline" style="color:white;border-color:rgba(255,255,255,0.5)"><i class="fa-solid fa-house"></i> Home</a>
      <a href="<?= BASE_URL ?>/browse.php" class="btn btn-outline" style="color:white;border-color:rgba(255,255,255,0.5)">Browse Services</a>
      <a href="<?= BASE_URL ?>/about.php" class="btn btn-outline" style="color:white;border-color:rgba(255,255,255,0.5)">About</a>
      <a href="<?= BASE_URL ?>/support.php" class="btn btn-outline" style="color:white;border-color:rgba(255,255,255,0.5)">Contact</a>
    </div>
  </nav>

  <div style="flex:1;display:flex;align-items:center;justify-content:center;padding:2rem">
    <div class="auth-card" style="flex-shrink:0">
      <div class="auth-logo">
        <h2>Quick<span>Fix</span> ZW</h2>
        <p>Connect with trusted home service professionals</p>
      </div>

      <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
      <?php if($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

      <div class="auth-tabs">
        <div class="auth-tab <?= $mode === 'login' ? 'active' : '' ?>" id="tab-login" onclick="showTab('login',this)">Login</div>
        <div class="auth-tab <?= $mode === 'register' ? 'active' : '' ?>" id="tab-register" onclick="showTab('register',this)">Register</div>
      </div>

      <form id="login-form" method="POST" style="display:<?= $mode === 'login' ? 'block' : 'none' ?>">
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

      <form id="register-form" method="POST" enctype="multipart/form-data" style="display:<?= $mode === 'register' ? 'block' : 'none' ?>">
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
            <label class="form-label">National ID Number</label>
            <input type="text" name="national_id" class="form-control" placeholder="70-345234P09" pattern="\d{2}-\d{6,7}[A-Z]\d{2}" title="Format: 12-345678X90 (e.g. 70-345234P09)">
            <small style="color:#999;font-size:0.78rem">Format: <code>12-345678X90</code></small>
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
          <select name="trade" class="form-select" onchange="document.getElementById('trade_other_wrap').style.display=this.value==='__other__'?'block':'none'">
            <option value="">Select your trade</option>
            <?php
              $dynamicTrades = [];
              try { $dynamicTrades = $pdo->query("SELECT name FROM trades WHERE is_active=1 ORDER BY name")->fetchAll(PDO::FETCH_COLUMN); }
              catch(PDOException $e){ $dynamicTrades = $TRADES; }
              foreach($dynamicTrades as $t):
            ?>
            <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
            <?php endforeach; ?>
            <option value="__other__">+ Other (specify)</option>
          </select>
          <div id="trade_other_wrap" style="display:none;margin-top:0.4rem">
            <input type="text" name="trade_other" class="form-control" placeholder="Type your trade (e.g. Solar Installation)">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Upload ID Document (optional but speeds up verification)</label>
          <input type="file" name="id_file" class="form-control" accept="image/jpeg,image/png,image/webp,application/pdf">
          <small style="color:#999;font-size:0.78rem">JPG, PNG, WEBP or PDF, max 5 MB. Only admins can view this file.</small>
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
  history.replaceState(null,'','?mode='+t);
}
function toggleTrade(role){
  document.getElementById('trade-group').style.display = role === 'professional' ? 'block' : 'none';
}
</script>
</body>
</html>
