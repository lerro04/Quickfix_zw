<?php
require_once '../includes/auth.php';
requireRole('professional');
require_once '../includes/db.php';
require_once '../includes/functions.php';
$uid=$_SESSION['user_id'];
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['start_job'])){
    $pdo->prepare("UPDATE bookings SET status='in_progress' WHERE booking_id=? AND professional_id=?")->execute([(int)$_POST['booking_id'],$uid]);
    $msg="✅ Job started! Complete the work and the client will mark it as done.";
}
$books=$pdo->prepare("SELECT b.*,u.full_name as client_name,u.phone as client_phone,j.title as job_title,j.description as job_desc FROM bookings b JOIN users u ON b.client_id=u.user_id LEFT JOIN job_requests j ON b.job_id=j.job_id WHERE b.professional_id=? ORDER BY b.created_at DESC");
$books->execute([$uid]); $bookings=$books->fetchAll();
$totalEarned=$pdo->prepare("SELECT COALESCE(SUM(agreed_amount),0) as t FROM bookings WHERE professional_id=? AND payment_status='released'"); $totalEarned->execute([$uid]); $earned=$totalEarned->fetch()['t'];
$pending=$pdo->prepare("SELECT COALESCE(SUM(agreed_amount),0) as t FROM bookings WHERE professional_id=? AND status IN ('confirmed','in_progress')"); $pending->execute([$uid]); $pendingAmt=$pending->fetch()['t'];
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Bookings — QuickFix ZW</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head><body>
<?php include '../includes/navbar.php'; ?>
<div class="container"><br>
<?php if($msg): ?><div class="alert alert-success"><?=$msg?></div><?php endif; ?>
<div class="card-grid" style="margin-bottom:1.5rem">
  <div class="stat-card" style="border-left-color:var(--success)"><div class="stat-icon" style="background:var(--success)">💵</div><div class="stat-info"><h3>$<?=number_format($earned,2)?></h3><p>Total Earned</p></div></div>
  <div class="stat-card" style="border-left-color:var(--warning)"><div class="stat-icon" style="background:var(--warning)">⏳</div><div class="stat-info"><h3>$<?=number_format($pendingAmt,2)?></h3><p>Pending Payment</p></div></div>
</div>
<div class="page-title">📅 My Bookings (<?=count($bookings)?>)</div>
<?php if(empty($bookings)): ?>
<div class="alert alert-info">No bookings yet. Bid on jobs to get hired!</div>
<?php else: ?>
<?php foreach($bookings as $b): ?>
<div class="job-card">
  <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:0.8rem;margin-bottom:0.8rem">
    <div>
      <div class="job-title"><?=htmlspecialchars($b['job_title']??'Direct Booking')?></div>
      <div class="job-meta">
        <span>👤 <?=$b['client_name']?></span>
        <span>📞 <?=$b['client_phone']?></span>
        <span>📅 <?=$b['scheduled_date']??'Date TBD'?></span>
      </div>
      <?php if($b['job_desc']): ?><p style="font-size:0.85rem;color:var(--gray);margin-top:0.4rem;line-height:1.5"><?=htmlspecialchars(substr($b['job_desc'],0,150))?></p><?php endif; ?>
    </div>
    <div style="text-align:right">
      <div style="font-size:1.4rem;font-weight:800;color:var(--success)">$<?=number_format($b['agreed_amount'],2)?></div>
      <span class="badge badge-<?=$b['status']==='completed'?'success':($b['status']==='in_progress'?'warning':($b['status']==='confirmed'?'info':'danger'))?>"><?=ucfirst(str_replace('_',' ',$b['status']))?></span>
      <span class="badge badge-<?=$b['payment_status']==='released'?'success':'warning'?>" style="margin-left:0.3rem"><?=ucfirst($b['payment_status'])?></span>
    </div>
  </div>
  <?php if($b['status']==='confirmed'): ?>
  <div style="display:flex;gap:0.5rem;flex-wrap:wrap">
    <form method="POST"><input type="hidden" name="booking_id" value="<?=$b['booking_id']?>"><button name="start_job" class="btn btn-primary btn-sm">🚀 Start Job</button></form>
    <a href="<?= BASE_URL ?>/messages.php?with=<?=$b['client_id']?>" class="btn btn-outline btn-sm">💬 Message Client</a>
  </div>
  <?php elseif($b['status']==='in_progress'): ?>
  <div style="padding:0.6rem;background:#fff3cd;border-radius:8px;font-size:0.85rem;color:#856404">
    🔧 Job in progress. The client will mark it complete once you're done. <a href="<?= BASE_URL ?>/messages.php?with=<?=$b['client_id']?>" style="color:var(--primary)">Message client →</a>
  </div>
  <?php elseif($b['status']==='completed'): ?>
  <div style="padding:0.6rem;background:#d4edda;border-radius:8px;font-size:0.85rem;color:#155724">
    ✅ Job completed. Payment <?=$b['payment_status']==='released'?'has been released to you':'is being processed'?>.
  </div>
  <?php endif; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
</body></html>
