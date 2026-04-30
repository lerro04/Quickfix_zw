<?php
require_once '../includes/auth.php';
requireRole('client');
require_once '../includes/db.php';
require_once '../includes/functions.php';
$uid = $_SESSION['user_id'];
$msg='';

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(isset($_POST['complete'])){
        $bid = (int)$_POST['booking_id'];
        $pdo->prepare("UPDATE bookings SET status='completed', payment_status='released', completed_at=NOW() WHERE booking_id=? AND client_id=?")->execute([$bid,$uid]);
        $pdo->prepare("UPDATE professional_profiles SET jobs_completed=jobs_completed+1 WHERE user_id=(SELECT professional_id FROM bookings WHERE booking_id=?)")->execute([$bid]);
        $msg="✅ Job marked as complete. Payment released!";
    }
    if(isset($_POST['dispute'])){
        $pdo->prepare("UPDATE bookings SET status='disputed' WHERE booking_id=? AND client_id=?")->execute([(int)$_POST['booking_id'],$uid]);
        $msg="⚠️ Dispute raised. Admin will review.";
    }
    if(isset($_POST['leave_review'])){
        $bid=(int)$_POST['booking_id']; $fid=(int)$_POST['pro_id'];
        $rat=(int)$_POST['rating']; $com=htmlspecialchars(trim($_POST['comment']));
        $ex=$pdo->prepare("SELECT review_id FROM reviews WHERE booking_id=? AND reviewer_id=?"); $ex->execute([$bid,$uid]);
        if(!$ex->fetch()){
            $pdo->prepare("INSERT INTO reviews (booking_id,reviewer_id,reviewee_id,rating,comment) VALUES (?,?,?,?,?)")->execute([$bid,$uid,$fid,$rat,$com]);
            $avg=$pdo->prepare("SELECT AVG(rating) as a, COUNT(*) as c FROM reviews WHERE reviewee_id=?"); $avg->execute([$fid]); $r=$avg->fetch();
            $pdo->prepare("UPDATE professional_profiles SET rating_avg=?,total_reviews=? WHERE user_id=?")->execute([round($r['a'],2),$r['c'],$fid]);
            $msg="✅ Review submitted! Thank you.";
        } else { $msg="You already reviewed this booking."; }
    }
}

$books=$pdo->prepare("SELECT b.*,u.full_name as pro_name,u.phone as pro_phone,pp.trade,j.title as job_title FROM bookings b JOIN users u ON b.professional_id=u.user_id JOIN professional_profiles pp ON u.user_id=pp.user_id LEFT JOIN job_requests j ON b.job_id=j.job_id WHERE b.client_id=? ORDER BY b.created_at DESC");
$books->execute([$uid]); $bookings=$books->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Bookings — QuickFix ZW</title>
<link rel="stylesheet" href="/quickfix/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head><body>
<?php include '../includes/navbar.php'; ?>
<div class="container"><br>
<?php if($msg): ?><div class="alert alert-<?=str_starts_with($msg,'✅')?'success':'warning'?>"><?=$msg?></div><?php endif; ?>
<div class="page-title">📅 My Bookings</div>
<?php if(empty($bookings)): ?>
<div class="alert alert-info">No bookings yet. <a href="/quickfix/client/browse.php" style="color:var(--primary)">Browse professionals</a> or <a href="/quickfix/client/post_job.php" style="color:var(--primary)">post a job!</a></div>
<?php else: ?>
<?php foreach($bookings as $b): ?>
<div class="job-card">
  <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;margin-bottom:0.8rem">
    <div>
      <div class="job-title"><?=$b['job_title']??'Direct Booking'?></div>
      <div class="job-meta">
        <span><?=tradeIcon($b['trade'])?> <?=$b['trade']?></span>
        <span>👤 <?=$b['pro_name']?></span>
        <span>📞 <?=$b['pro_phone']?></span>
        <span>📅 <?=$b['scheduled_date']??'TBD'?></span>
      </div>
    </div>
    <div style="text-align:right">
      <div style="font-size:1.3rem;font-weight:800;color:var(--success)">$<?=number_format($b['agreed_amount'],2)?></div>
      <span class="badge badge-<?=$b['status']==='completed'?'success':($b['status']==='confirmed'?'info':($b['status']==='in_progress'?'warning':'danger'))?>"><?=ucfirst(str_replace('_',' ',$b['status']))?></span>
      <span class="badge badge-<?=$b['payment_status']==='released'?'success':'warning'?>" style="margin-left:0.3rem"><?=ucfirst($b['payment_status'])?></span>
    </div>
  </div>

  <?php if($b['status']==='in_progress' || $b['status']==='confirmed'): ?>
  <div style="display:flex;gap:0.5rem;flex-wrap:wrap">
    <form method="POST" onsubmit="return confirm('Mark as complete and release payment?')">
      <input type="hidden" name="booking_id" value="<?=$b['booking_id']?>">
      <button name="complete" class="btn btn-success btn-sm">✅ Mark Complete & Pay</button>
    </form>
    <form method="POST">
      <input type="hidden" name="booking_id" value="<?=$b['booking_id']?>">
      <button name="dispute" class="btn btn-danger btn-sm">⚠️ Raise Dispute</button>
    </form>
    <a href="/quickfix/messages.php?with=<?=$b['professional_id']?>" class="btn btn-outline btn-sm">💬 Message Professional</a>
  </div>
  <?php endif; ?>

  <?php if($b['status']==='completed'): ?>
  <?php $rev=$pdo->prepare("SELECT review_id FROM reviews WHERE booking_id=? AND reviewer_id=?"); $rev->execute([$b['booking_id'],$uid]); $hasRev=$rev->fetch(); ?>
  <?php if(!$hasRev): ?>
  <div style="margin-top:0.8rem;border-top:1px solid var(--border);padding-top:0.8rem">
    <strong style="font-size:0.88rem">⭐ Leave a Review for <?=$b['pro_name']?></strong>
    <form method="POST" style="margin-top:0.5rem;display:flex;gap:0.5rem;flex-wrap:wrap;align-items:flex-end">
      <input type="hidden" name="booking_id" value="<?=$b['booking_id']?>">
      <input type="hidden" name="pro_id" value="<?=$b['professional_id']?>">
      <select name="rating" class="form-select" style="width:180px">
        <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
        <option value="4">⭐⭐⭐⭐ Good</option>
        <option value="3">⭐⭐⭐ Average</option>
        <option value="2">⭐⭐ Poor</option>
        <option value="1">⭐ Very Poor</option>
      </select>
      <input type="text" name="comment" class="form-control" placeholder="Add a comment..." style="flex:1;min-width:200px">
      <button name="leave_review" class="btn btn-primary btn-sm">Submit Review</button>
    </form>
  </div>
  <?php else: ?><p style="font-size:0.82rem;color:var(--success);margin-top:0.5rem">✅ You reviewed this job</p><?php endif; ?>
  <?php endif; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
</body></html>
