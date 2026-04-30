<?php
require_once '../includes/auth.php';
requireRole('professional');
require_once '../includes/db.php';
require_once '../includes/functions.php';
$uid=$_SESSION['user_id'];
$bids=$pdo->prepare("SELECT b.*,j.title,j.location,j.client_budget,j.urgency,j.status as job_status,u.full_name as client FROM bids b JOIN job_requests j ON b.job_id=j.job_id JOIN users u ON j.client_id=u.user_id WHERE b.professional_id=? ORDER BY b.created_at DESC");
$bids->execute([$uid]); $myBids=$bids->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Bids — QuickFix ZW</title>
<link rel="stylesheet" href="/quickfix/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head><body>
<?php include '../includes/navbar.php'; ?>
<div class="container"><br>
<div class="page-title">💰 My Bids (<?=count($myBids)?>)</div>
<?php if(empty($myBids)): ?>
<div class="alert alert-info">No bids yet. <a href="/quickfix/professional/job_board.php" style="color:var(--primary)">Browse the job board!</a></div>
<?php else: ?>
<?php foreach($myBids as $b): ?>
<div class="job-card">
  <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:0.8rem">
    <div>
      <div class="job-title"><?=htmlspecialchars($b['title'])?></div>
      <div class="job-meta">
        <span>👤 <?=$b['client']?></span>
        <span>📍 <?=$b['location']?></span>
        <span>💵 Client budget: $<?=number_format($b['client_budget'],2)?></span>
        <span><?=urgencyBadge($b['urgency'])?></span>
      </div>
      <?php if($b['message']): ?>
      <p style="margin-top:0.5rem;font-size:0.88rem;color:var(--gray);line-height:1.5;font-style:italic">"<?=htmlspecialchars(substr($b['message'],0,150))?><?=strlen($b['message'])>150?'...':''?>"</p>
      <?php endif; ?>
    </div>
    <div style="text-align:right">
      <div style="font-size:1.4rem;font-weight:800;color:var(--primary)">$<?=number_format($b['bid_amount'],2)?></div>
      <div style="font-size:0.8rem;color:#999">⏱ <?=$b['estimated_days']?> day(s)</div>
      <div style="margin-top:0.4rem">
        <span class="badge badge-<?=$b['status']==='accepted'?'success':($b['status']==='rejected'?'danger':'warning')?>"><?=ucfirst($b['status'])?></span>
        <span class="badge badge-<?=$b['job_status']==='open'?'info':'success'?>" style="margin-left:0.3rem">Job: <?=ucfirst($b['job_status'])?></span>
      </div>
      <div style="margin-top:0.4rem;font-size:0.78rem;color:#999"><?=timeAgo($b['created_at'])?></div>
    </div>
  </div>
  <?php if($b['status']==='accepted'): ?>
  <div style="margin-top:0.8rem;padding:0.7rem;background:#d4edda;border-radius:8px;font-size:0.88rem;color:#155724">
    ✅ <strong>Your bid was accepted!</strong> Check <a href="/quickfix/professional/bookings.php" style="color:var(--success)">My Bookings</a> for details.
  </div>
  <?php endif; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
</body></html>
