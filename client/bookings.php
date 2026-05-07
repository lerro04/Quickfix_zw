<?php
require_once '../includes/auth.php';
requireRole('client');
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/mailer.php';

$uid = $_SESSION['user_id'];
$msg = '';
$msgType = 'success';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
 if(isset($_POST['complete'])){
 $bid = (int)$_POST['booking_id'];
 $bk = $pdo->prepare("SELECT * FROM bookings WHERE booking_id=? AND client_id=?");
 $bk->execute([$bid, $uid]);
 $booking = $bk->fetch();
 if($booking){
 $breakdown = bookingPaymentBreakdown($pdo, $booking);
 if(!$breakdown['commission_covered']){
 $msg = 'Please pay the platform commission of $'.number_format($breakdown['commission_due'], 2).' via Paynow before marking this job complete.';
 $msgType = 'warning';
 } else {
 $fee = platformCommissionAmount((float)$booking['agreed_amount']);
 $payout = max(0, round((float)$booking['agreed_amount'] - $fee, 2));
 $pdo->prepare("UPDATE bookings SET status='completed', payment_status='held', completed_at=NOW(), platform_fee_pct=?, platform_fee_amount=?, professional_payout=? WHERE booking_id=? AND client_id=? AND status IN ('confirmed','in_progress')")
 ->execute([PLATFORM_COMMISSION_RATE * 100, $fee, $payout, $bid, $uid]);
 $msg = 'Job marked complete. The professional must now confirm payment received before payout is released.';
 }
 } else {
 $msg = 'Booking not found.';
 $msgType = 'warning';
 }
 }
 if(isset($_POST['dispute'])){
 $bid = (int)$_POST['booking_id'];
 $pdo->prepare("UPDATE bookings SET status='disputed' WHERE booking_id=? AND client_id=?")->execute([$bid,$uid]);
 notifyAdminDispute($pdo, $bid);
 $msg = 'Dispute raised. Admin will review.';
 $msgType = 'warning';
 }
 if(isset($_POST['leave_review'])){
 $bid = (int)$_POST['booking_id'];
 $fid = (int)$_POST['pro_id'];
 $rat = (int)$_POST['rating'];
 $com = htmlspecialchars(trim($_POST['comment']));
 $ex = $pdo->prepare("SELECT review_id FROM reviews WHERE booking_id=? AND reviewer_id=?");
 $ex->execute([$bid,$uid]);
 if(!$ex->fetch()){
 $pdo->prepare("INSERT INTO reviews (booking_id,reviewer_id,reviewee_id,rating,comment) VALUES (?,?,?,?,?)")->execute([$bid,$uid,$fid,$rat,$com]);
 $avg = $pdo->prepare("SELECT AVG(rating) as a, COUNT(*) as c FROM reviews WHERE reviewee_id=?");
 $avg->execute([$fid]);
 $r = $avg->fetch();
 $pdo->prepare("UPDATE professional_profiles SET rating_avg=?,total_reviews=? WHERE user_id=?")->execute([round($r['a'],2),$r['c'],$fid]);
 $msg = 'Review submitted! Thank you.';
 } else {
 $msg = 'You already reviewed this booking.';
 $msgType = 'warning';
 }
 }
}

$books = $pdo->prepare("SELECT b.*,u.full_name as pro_name,u.phone as pro_phone,pp.trade,j.title as job_title FROM bookings b JOIN users u ON b.professional_id=u.user_id JOIN professional_profiles pp ON u.user_id=pp.user_id LEFT JOIN job_requests j ON b.job_id=j.job_id WHERE b.client_id=? ORDER BY b.created_at DESC");
$books->execute([$uid]);
$bookings = $books->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Bookings - QuickFix ZW</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head><body>
<?php include '../includes/navbar.php'; ?>
<div class="container"><br>
<?php if($msg): ?><div class="alert alert-<?=$msgType?>"><?=htmlspecialchars($msg)?></div><?php endif; ?>
<div class="page-title"><?=icon('calendar-days')?> My Bookings</div>
<?php if(empty($bookings)): ?>
<div class="alert alert-info">No bookings yet. <a href="<?= BASE_URL ?>/browse.php" style="color:var(--primary)">Browse professionals</a> or <a href="<?= BASE_URL ?>/client/post_job.php" style="color:var(--primary)">post a job!</a></div>
<?php else: ?>
<?php foreach($bookings as $b): ?>
<?php $pay = bookingPaymentBreakdown($pdo, $b); ?>
<div class="job-card">
 <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;margin-bottom:0.8rem">
 <div>
 <div class="job-title"><?=htmlspecialchars($b['job_title'] ?? 'Direct Booking')?></div>
 <div class="job-meta">
 <span><?=tradeIcon($b['trade'])?> <?=htmlspecialchars($b['trade'])?></span>
 <span><?=icon('user')?> <?=htmlspecialchars($b['pro_name'])?></span>
 <span><?=icon('phone')?> <?=htmlspecialchars($b['pro_phone'] ?? '')?></span>
 <span><?=icon('calendar-day')?> <?=htmlspecialchars($b['scheduled_date'] ?? 'TBD')?></span>
 </div>
 </div>
 <div style="text-align:right">
 <div style="font-size:1.3rem;font-weight:800;color:var(--success)">$<?=number_format($pay['agreed'],2)?></div>
 <span class="badge badge-<?=$b['status']==='completed'?'success':($b['status']==='confirmed'?'info':($b['status']==='in_progress'?'warning':'danger'))?>"><?=ucfirst(str_replace('_',' ',$b['status']))?></span>
 <span class="badge badge-<?=$b['payment_status']==='released'?'success':'warning'?>" style="margin-left:0.3rem"><?=ucfirst($b['payment_status'])?></span>
 </div>
 </div>

 <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:0.8rem;font-size:0.85rem;color:var(--gray)">
 <span>Paynow paid: <strong>$<?=number_format($pay['online_paid'],2)?></strong></span>
 <span>Cash/direct: <strong>$<?=number_format($pay['cash_expected'],2)?></strong></span>
 <span>Commission: <strong>$<?=number_format($pay['commission'],2)?></strong></span>
 <span>Professional net: <strong>$<?=number_format($pay['net_payout'],2)?></strong></span>
 <?php if(!$pay['commission_covered']): ?><span style="color:var(--danger)">Commission due via Paynow: <strong>$<?=number_format($pay['commission_due'],2)?></strong></span><?php endif; ?>
 </div>

 <?php if(in_array($b['status'], ['confirmed','in_progress','completed'], true) && $b['payment_status'] !== 'released'): ?>
 <div style="display:flex;gap:0.5rem;flex-wrap:wrap;align-items:center">
 <?php if($pay['remaining_online'] > 0 && $b['payment_status'] !== 'released'): ?>
 <form method="POST" action="<?= BASE_URL ?>/paynow_init.php" style="display:flex;gap:0.4rem;flex-wrap:wrap;align-items:center">
 <input type="hidden" name="booking_id" value="<?=$b['booking_id']?>">
 <input type="number" name="payment_amount" class="form-control" value="<?=number_format(max($pay['commission_due'], min($pay['remaining_online'], $pay['remaining_online'])),2,'.','')?>" min="<?=number_format(max(0.01, $pay['commission_due']),2,'.','')?>" max="<?=number_format($pay['remaining_online'],2,'.','')?>" step="0.01" style="width:130px">
 <button type="submit" class="btn btn-primary btn-sm"><?=icon('credit-card')?> Pay via Paynow</button>
 </form>
 <?php endif; ?>
 <?php if($b['payment_status'] === 'held'): ?>
 <span class="badge badge-success">Paynow funds held</span>
 <?php endif; ?>
 <?php if($b['status'] !== 'completed' && $pay['commission_covered']): ?>
 <form method="POST" onsubmit="return confirm('Mark this job complete? The professional will still need to confirm payment received before payout is released.')">
 <input type="hidden" name="booking_id" value="<?=$b['booking_id']?>">
 <button name="complete" class="btn btn-success btn-sm"><?=icon('circle-check')?> Mark Complete</button>
 </form>
 <?php elseif($b['status'] !== 'completed'): ?>
 <span class="badge badge-warning">Pay commission first</span>
 <?php endif; ?>
 <form method="POST">
 <input type="hidden" name="booking_id" value="<?=$b['booking_id']?>">
 <button name="dispute" class="btn btn-danger btn-sm"><?=icon('scale-balanced')?> Raise Dispute</button>
 </form>
 <a href="<?= BASE_URL ?>/messages.php?with=<?=$b['professional_id']?>" class="btn btn-outline btn-sm"><?=icon('comments')?> Message Professional</a>
 </div>
 <?php endif; ?>

 <?php if($b['status']==='completed' && $b['payment_status']==='held'): ?>
 <div style="padding:0.6rem;background:#fff3cd;border-radius:8px;font-size:0.85rem;color:#856404;margin-top:0.5rem">
 Waiting for the professional to confirm payment received. Net payout after commission: $<?=number_format($pay['net_payout'],2)?>.
 </div>
 <?php endif; ?>

 <?php if($b['status']==='completed'): ?>
 <?php $rev=$pdo->prepare("SELECT review_id FROM reviews WHERE booking_id=? AND reviewer_id=?"); $rev->execute([$b['booking_id'],$uid]); $hasRev=$rev->fetch(); ?>
 <?php if(!$hasRev): ?>
 <div style="margin-top:0.8rem;border-top:1px solid var(--border);padding-top:0.8rem">
 <strong style="font-size:0.88rem"><?=icon('star')?> Leave a Review for <?=htmlspecialchars($b['pro_name'])?></strong>
 <form method="POST" style="margin-top:0.5rem;display:flex;gap:0.5rem;flex-wrap:wrap;align-items:flex-end">
 <input type="hidden" name="booking_id" value="<?=$b['booking_id']?>">
 <input type="hidden" name="pro_id" value="<?=$b['professional_id']?>">
 <select name="rating" class="form-select" style="width:180px">
 <option value="5">5 - Excellent</option>
 <option value="4">4 - Good</option>
 <option value="3">3 - Average</option>
 <option value="2">2 - Poor</option>
 <option value="1">1 - Very Poor</option>
 </select>
 <input type="text" name="comment" class="form-control" placeholder="Add a comment..." style="flex:1;min-width:200px">
 <button name="leave_review" class="btn btn-primary btn-sm">Submit Review</button>
 </form>
 </div>
 <?php else: ?><p style="font-size:0.82rem;color:var(--success);margin-top:0.5rem"><?=icon('circle-check')?> You reviewed this job</p><?php endif; ?>
 <?php endif; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
</body></html>
