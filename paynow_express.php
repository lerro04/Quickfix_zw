<?php
require_once __DIR__.'/includes/auth.php';
requireRole('client');
require_once __DIR__.'/includes/db.php';
require_once __DIR__.'/includes/functions.php';
require_once __DIR__.'/includes/paynow.php';

$uid = $_SESSION['user_id'];
$bookingId = (int)($_POST['booking_id'] ?? 0);
$method = strtolower(trim($_POST['method'] ?? ''));
$phone = preg_replace('/\D+/', '', $_POST['mobile_phone'] ?? '');

if($bookingId <= 0 || $method === ''){
 http_response_code(400);
 exit('Missing booking or method.');
}
if(!in_array($method, ['ecocash','onemoney','innbucks','omari'], true)){
 http_response_code(400);
 exit('Unsupported method.');
}
if(strlen($phone) < 9 || strlen($phone) > 13){
 http_response_code(400);
 exit('Enter a valid mobile number for '.$method.'.');
}

$bk = $pdo->prepare("SELECT * FROM bookings WHERE booking_id=? AND client_id=?");
$bk->execute([$bookingId, $uid]);
$booking = $bk->fetch();
if(!$booking){ http_response_code(404); exit('Booking not found.'); }
if(!in_array($booking['status'], ['confirmed','in_progress','completed'], true)){
 exit('This booking cannot be paid for in its current state ('.$booking['status'].').');
}
if($booking['payment_status'] === 'released'){
 exit('This booking has already been paid out.');
}

$breakdown = bookingPaymentBreakdown($pdo, $booking);
$amount = round((float)($_POST['payment_amount'] ?? $breakdown['remaining_online']), 2);
$minimumAmount = $breakdown['commission_due'] > 0 ? $breakdown['commission_due'] : 0.01;
if($amount < $minimumAmount || $amount > $breakdown['remaining_online']){
 http_response_code(400);
 exit('Amount must be between $'.number_format($minimumAmount,2).' and $'.number_format($breakdown['remaining_online'],2).'.');
}

$reference = 'QFX-'.$bookingId.'-'.time();
$base = getSiteUrl().BASE_URL;
$returnUrl = $base.'/paynow_return.php?ref='.urlencode($reference);
$resultUrl = $base.'/paynow_result.php?ref='.urlencode($reference);

$pdo->prepare("INSERT INTO payments (booking_id,client_id,amount,reference,status,payment_method) VALUES (?,?,?,?,?,?)")
 ->execute([$bookingId, $uid, $amount, $reference, 'created', $method]);

$instructions = '';
$pollUrl = '';
$paynowReference = '';
$errMsg = '';

try {
 $pn = paynowClient($returnUrl, $resultUrl);
 $resp = $pn->initiateMobile($reference, $amount, $phone, $method, $_SESSION['email'] ?? '', 'QuickFix ZW booking #'.$bookingId);
 if(strtolower($resp['status'] ?? '') === 'ok' && !empty($resp['pollurl'])){
 $instructions = $resp['instructions'] ?? '';
 $pollUrl = $resp['pollurl'];
 $paynowReference = $resp['paynowreference'] ?? '';
 $pdo->prepare("UPDATE payments SET poll_url=?, paynow_reference=?, status='sent', raw_response=? WHERE reference=?")
 ->execute([$pollUrl, $paynowReference, json_encode($resp), $reference]);
 } else {
 $errMsg = $resp['error'] ?? 'Could not start express checkout.';
 $pdo->prepare("UPDATE payments SET status='failed', raw_response=? WHERE reference=?")
 ->execute([json_encode($resp), $reference]);
 }
} catch(RuntimeException $e){
 $errMsg = $e->getMessage();
 $pdo->prepare("UPDATE payments SET status='failed', raw_response=? WHERE reference=?")
 ->execute([$e->getMessage(), $reference]);
}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Confirm payment - QuickFix ZW</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head><body>
<?php include __DIR__.'/includes/navbar.php'; ?>
<div class="container"><br>
 <div class="section-card" style="max-width:560px;margin:1.5rem auto">
 <?php if($errMsg): ?>
 <div style="text-align:center">
 <div style="font-size:3rem;color:var(--danger);margin-bottom:0.5rem"><?=icon('circle-xmark')?></div>
 <h2>Could not start payment</h2>
 <p style="color:var(--gray);line-height:1.7"><?=htmlspecialchars($errMsg)?></p>
 <a href="<?= BASE_URL ?>/client/bookings.php" class="btn btn-primary"><?=icon('arrow-left')?> Back to bookings</a>
 </div>
 <?php else: ?>
 <div style="text-align:center;margin-bottom:1.2rem">
 <div style="font-size:3rem;color:var(--accent)"><?=icon('mobile-screen-button')?></div>
 <h2>Check your phone</h2>
 <p style="color:var(--gray)">We sent a payment prompt to <strong><?=htmlspecialchars($phone)?></strong> via <strong><?=ucfirst($method)?></strong>.</p>
 </div>
 <?php if($instructions): ?>
 <div style="background:#fff7ee;border:1px solid #ffd6a8;border-radius:10px;padding:1rem;line-height:1.7;font-size:0.95rem">
 <strong><?=icon('circle-info')?> Instructions:</strong><br>
 <?=nl2br(htmlspecialchars($instructions))?>
 </div>
 <?php endif; ?>
 <p style="margin:1.2rem 0;color:var(--gray);font-size:0.9rem;line-height:1.6">Once you complete the prompt on your phone, this page will update. You can also refresh manually.</p>
 <div style="display:flex;gap:0.6rem;flex-wrap:wrap;justify-content:center">
 <a href="<?= BASE_URL ?>/paynow_return.php?ref=<?=urlencode($reference)?>" class="btn btn-primary"><?=icon('rotate')?> Refresh status</a>
 <a href="<?= BASE_URL ?>/client/bookings.php" class="btn btn-outline"><?=icon('calendar-days')?> My bookings</a>
 </div>
 <?php endif; ?>
 </div>
</div>
<?php include __DIR__.'/includes/footer.php'; ?>
</body></html>
