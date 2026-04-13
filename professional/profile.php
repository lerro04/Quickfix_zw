<?php
require_once '../includes/auth.php';
requireRole('professional');
require_once '../includes/db.php';
require_once '../includes/functions.php';
$uid=$_SESSION['user_id'];
$TRADES=['Plumbing','Electrical','Painting','Carpentry','Tiling','Roofing','Welding','Landscaping','General Handyman'];
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $bio=htmlspecialchars(trim($_POST['bio']));
    $rate=(float)$_POST['hourly_rate'];
    $exp=(int)$_POST['years_experience'];
    $area=htmlspecialchars(trim($_POST['service_area']));
    $avail=(int)($_POST['is_available']??0);
    $phone=trim($_POST['phone']);
    $loc=htmlspecialchars(trim($_POST['location']));
    $pdo->prepare("UPDATE professional_profiles SET bio=?,hourly_rate=?,years_experience=?,service_area=?,is_available=? WHERE user_id=?")->execute([$bio,$rate,$exp,$area,$avail,$uid]);
    $pdo->prepare("UPDATE users SET phone=?,location=? WHERE user_id=?")->execute([$phone,$loc,$uid]);
    $msg="✅ Profile updated successfully!";
}
$prof=$pdo->prepare("SELECT pp.*,u.email,u.phone,u.location,u.national_id FROM professional_profiles pp JOIN users u ON pp.user_id=u.user_id WHERE pp.user_id=?"); $prof->execute([$uid]); $p=$prof->fetch();
$reviews=$pdo->prepare("SELECT r.*,u.full_name as reviewer FROM reviews r JOIN users u ON r.reviewer_id=u.user_id WHERE r.reviewee_id=? ORDER BY r.created_at DESC"); $reviews->execute([$uid]); $myRevs=$reviews->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Profile — QuickFix ZW</title>
<link rel="stylesheet" href="/quickfix/css/style.css">
</head><body>
<?php include '../includes/navbar.php'; ?>
<div class="container"><br>
<?php if($msg): ?><div class="alert alert-success"><?=$msg?></div><?php endif; ?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;flex-wrap:wrap">
  <div class="card">
    <div class="card-header">✏️ Edit Profile</div>
    <div class="card-body">
      <form method="POST">
        <div class="form-group">
          <label class="form-label">Trade</label>
          <input type="text" class="form-control" value="<?=tradeIcon($p['trade'])?> <?=$p['trade']?>" disabled style="background:#f0f0f0">
        </div>
        <div class="form-group">
          <label class="form-label">Bio / About Me</label>
          <textarea name="bio" class="form-control" rows="4" style="resize:vertical" placeholder="Describe your experience, skills, specialisations..."><?=htmlspecialchars($p['bio']??'')?></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Hourly Rate (USD)</label>
            <input type="number" name="hourly_rate" class="form-control" value="<?=$p['hourly_rate']?>" step="0.50" min="1">
          </div>
          <div class="form-group">
            <label class="form-label">Years Experience</label>
            <input type="number" name="years_experience" class="form-control" value="<?=$p['years_experience']?>" min="0">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Service Area (cities/towns you cover)</label>
          <input type="text" name="service_area" class="form-control" value="<?=htmlspecialchars($p['service_area']??'')?>" placeholder="Chinhoyi, Karoi, Harare">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" value="<?=htmlspecialchars($p['phone']??'')?>">
          </div>
          <div class="form-group">
            <label class="form-label">Location (City)</label>
            <input type="text" name="location" class="form-control" value="<?=htmlspecialchars($p['location']??'')?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Availability</label>
          <select name="is_available" class="form-select">
            <option value="1" <?=$p['is_available']?'selected':''?>>🟢 Available for jobs</option>
            <option value="0" <?=!$p['is_available']?'selected':''?>>🔴 Not available right now</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary">💾 Save Profile</button>
      </form>
    </div>
  </div>

  <div>
    <div class="card" style="margin-bottom:1.5rem">
      <div class="card-header">📊 My Stats</div>
      <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
          <div style="text-align:center;padding:1rem;background:var(--light);border-radius:10px">
            <div style="font-size:1.8rem;font-weight:700;color:var(--primary)"><?=$p['jobs_completed']?></div>
            <div style="font-size:0.8rem;color:#999">Jobs Completed</div>
          </div>
          <div style="text-align:center;padding:1rem;background:var(--light);border-radius:10px">
            <div style="font-size:1.8rem;font-weight:700;color:var(--warning)"><?=number_format($p['rating_avg'],1)?></div>
            <div style="font-size:0.8rem;color:#999">Average Rating</div>
          </div>
        </div>
        <p style="margin-top:0.8rem;font-size:0.85rem;color:#999">National ID: <?=$p['national_id']??'Not provided'?></p>
      </div>
    </div>

    <div class="card">
      <div class="card-header">⭐ Client Reviews (<?=count($myRevs)?>)</div>
      <div class="card-body" style="max-height:400px;overflow-y:auto">
        <?php if(empty($myRevs)): ?><p style="color:#999;text-align:center;padding:1.5rem">No reviews yet. Complete jobs to earn reviews!</p>
        <?php else: ?>
        <?php foreach($myRevs as $r): ?>
        <div style="border-bottom:1px solid var(--border);padding:0.8rem 0">
          <div style="display:flex;justify-content:space-between">
            <strong style="font-size:0.9rem"><?=$r['reviewer']?></strong>
            <span><?=stars($r['rating'])?></span>
          </div>
          <p style="color:var(--gray);font-size:0.85rem;margin-top:0.3rem;line-height:1.5"><?=htmlspecialchars($r['comment']??'')?></p>
          <small style="color:#999"><?=timeAgo($r['created_at'])?></small>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
</div>
</body></html>
