<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

$providerId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT u.*,pp.* FROM professional_profiles pp JOIN users u ON pp.user_id=u.user_id WHERE u.user_id=? AND u.role='professional'");
$stmt->execute([$providerId]);
$provider = $stmt->fetch();

if(!$provider){
    http_response_code(404);
}

$canBook = isset($_SESSION['role']) && $_SESSION['role'] === 'client';
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $provider ? htmlspecialchars($provider['full_name']).' - QuickFix ZW' : 'Provider not found - QuickFix ZW' ?></title>
<link rel="stylesheet" href="/quickfix/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head><body>
<?php include 'includes/navbar.php'; ?>
<div class="container"><br>
<?php if(!$provider): ?>
  <div class="alert alert-danger">This professional profile could not be found.</div>
<?php else: ?>
  <div class="page-title"><?=icon('id-card')?> Professional Details</div>
  <div class="detail-grid">
    <div class="section-card">
      <div class="detail-hero">
        <div class="pro-avatar" style="margin-bottom:0"><?=strtoupper(substr($provider['full_name'],0,1))?></div>
        <div>
          <h2 style="margin-bottom:0.35rem"><?=htmlspecialchars($provider['full_name'])?></h2>
          <div class="pro-trade"><?=tradeIcon($provider['trade'])?> <?=$provider['trade']?></div>
          <div class="pro-meta"><?=stars($provider['rating_avg'])?> <span style="margin-left:0.35rem">(<?=$provider['total_reviews']?> reviews)</span></div>
        </div>
      </div>
      <h3 style="margin-bottom:0.75rem">About this professional</h3>
      <p style="line-height:1.7;color:var(--dark)"><?=nl2br(htmlspecialchars($provider['bio'] ?: 'This professional has not added a full description yet.'))?></p>

      <h3 style="margin:1.5rem 0 0.75rem">Work gallery</h3>
      <div class="detail-gallery">
        <div class="gallery-placeholder"><?=icon('images')?> Portfolio image uploads are not implemented yet in this build.</div>
        <div class="gallery-placeholder"><?=icon('camera')?> Add gallery storage to professional profiles to show past work here.</div>
      </div>
    </div>

    <div class="section-card">
      <h3 style="margin-bottom:1rem">Profile summary</h3>
      <div class="info-list">
        <div><?=icon('location-dot')?> <strong>Location:</strong> <?=htmlspecialchars($provider['location'] ?: 'Not provided')?></div>
        <div><?=icon('map')?> <strong>Service area:</strong> <?=htmlspecialchars($provider['service_area'] ?: 'Not provided')?></div>
        <div><?=icon('briefcase')?> <strong>Experience:</strong> <?=$provider['years_experience']?> year(s)</div>
        <div><?=icon('circle-check')?> <strong>Completed jobs:</strong> <?=$provider['jobs_completed']?></div>
        <div><?=icon('money-bill-wave')?> <strong>Hourly rate:</strong> $<?=number_format($provider['hourly_rate'],2)?></div>
        <div><?=icon('shield-halved')?> <strong>Verification:</strong> <?=$provider['verified'] ? 'Verified professional' : 'Pending verification'?></div>
      </div>
      <div style="display:grid;gap:0.75rem;margin-top:1.25rem">
        <?php if($canBook): ?>
          <a href="/quickfix/browse.php?book=<?=$provider['user_id']?>" class="btn btn-primary"><?=icon('calendar-plus')?> Book this professional</a>
          <a href="/quickfix/messages.php?with=<?=$provider['user_id']?>" class="btn btn-outline"><?=icon('comments')?> Message professional</a>
        <?php else: ?>
          <a href="/quickfix/index.php#auth" class="btn btn-primary"><?=icon('user-plus')?> Create an account to book</a>
          <a href="/quickfix/support.php" class="btn btn-outline"><?=icon('headset')?> Need help choosing?</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endif; ?>
</div>
</body></html>
