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
<?php
$methodMeta = [
 'ecocash' => ['label' => 'EcoCash', 'color' => '#e30613', 'tint' => '#fde9eb', 'ussd' => '*151#'],
 'onemoney' => ['label' => 'OneMoney', 'color' => '#0a7eea', 'tint' => '#e3f0fd', 'ussd' => '*111#'],
 'innbucks' => ['label' => 'InnBucks', 'color' => '#7a3bff', 'tint' => '#f0e9ff', 'ussd' => '*569#'],
 'omari' => ['label' => 'Omari', 'color' => '#00a651', 'tint' => '#e0f5e9', 'ussd' => '*220#'],
];
$mm = $methodMeta[$method] ?? ['label' => ucfirst($method), 'color' => 'var(--primary)', 'tint' => '#fff7ee', 'ussd' => ''];
$phoneMasked = strlen($phone) >= 6 ? substr($phone,0,4).' '.substr($phone,4,3).' '.substr($phone,7) : $phone;
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Confirm payment - QuickFix ZW</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
.pay-shell { max-width:520px; margin:2rem auto 3rem; }
.pay-card { background:var(--surface); border:1px solid var(--border); border-radius:20px; padding:2.2rem 2rem; box-shadow:0 12px 40px rgba(15,15,18,0.06); position:relative; overflow:hidden; }
.pay-card::before { content:""; position:absolute; top:-90px; right:-90px; width:220px; height:220px; border-radius:50%; background:radial-gradient(circle at 30% 30%,<?=$mm['tint']?> 0%,transparent 70%); pointer-events:none; }
.pay-method-chip { display:inline-flex; align-items:center; gap:0.5rem; background:<?=$mm['tint']?>; color:<?=$mm['color']?>; border-radius:99px; padding:0.4rem 0.85rem; font-size:0.78rem; font-weight:700; letter-spacing:0.02em; position:relative; z-index:1; }
.pay-method-chip .dot { width:7px; height:7px; border-radius:50%; background:<?=$mm['color']?>; animation:pulse 1.6s infinite; }
@keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:0.5;transform:scale(1.3)} }
.pay-heading { font-size:1.9rem; font-weight:800; color:var(--dark); letter-spacing:-0.025em; margin:1rem 0 0.5rem; line-height:1.15; position:relative; z-index:1; }
.pay-sub { color:var(--gray); font-size:0.95rem; line-height:1.6; position:relative; z-index:1; }
.pay-sub strong { color:var(--dark); font-weight:700; }
.pay-stat { display:grid; grid-template-columns:auto 1fr; gap:0.9rem; align-items:center; background:var(--bg); border:1px solid var(--border); border-radius:14px; padding:1rem 1.1rem; margin-top:1.5rem; position:relative; z-index:1; }
.pay-stat-icon { width:42px; height:42px; border-radius:12px; background:<?=$mm['color']?>; color:#fff; display:flex; align-items:center; justify-content:center; font-size:1.05rem; }
.pay-stat-label { font-size:0.74rem; color:var(--gray); text-transform:uppercase; letter-spacing:0.06em; font-weight:600; }
.pay-stat-value { font-weight:700; color:var(--dark); font-size:1rem; margin-top:0.1rem; }
.pay-amount { font-size:2.2rem; font-weight:800; color:var(--dark); letter-spacing:-0.03em; }
.pay-steps { margin-top:1.5rem; position:relative; z-index:1; }
.pay-steps h3 { font-size:0.78rem; color:var(--gray); text-transform:uppercase; letter-spacing:0.08em; font-weight:700; margin-bottom:0.8rem; }
.pay-step { display:grid; grid-template-columns:28px 1fr; gap:0.8rem; align-items:flex-start; padding:0.55rem 0; }
.pay-step-num { width:24px; height:24px; border-radius:50%; background:var(--dark); color:#fff; font-size:0.75rem; font-weight:700; display:flex; align-items:center; justify-content:center; }
.pay-step-text { color:var(--dark); font-size:0.92rem; line-height:1.5; }
.pay-step-text code { background:<?=$mm['tint']?>; color:<?=$mm['color']?>; padding:0.1rem 0.4rem; border-radius:6px; font-weight:700; font-size:0.86rem; }
.pay-actions { display:flex; gap:0.6rem; flex-wrap:wrap; margin-top:1.7rem; position:relative; z-index:1; }
.pay-actions .btn { padding:0.8rem 1.3rem; font-size:0.92rem; }
.pay-help { text-align:center; margin-top:1.2rem; font-size:0.82rem; color:var(--gray); }
.pay-error-icon { width:64px; height:64px; border-radius:50%; background:#fee2e2; color:#dc2626; display:flex; align-items:center; justify-content:center; font-size:1.7rem; margin:0 auto 1rem; }
</style>
</head><body>
<?php include __DIR__.'/includes/navbar.php'; ?>
<div class="container">
 <div class="pay-shell">
 <div class="pay-card">
 <?php if($errMsg): ?>
 <div style="text-align:center;position:relative;z-index:1">
 <div class="pay-error-icon"><?=icon('triangle-exclamation')?></div>
 <h2 class="pay-heading" style="margin-top:0">Payment could not start</h2>
 <p class="pay-sub" style="margin-bottom:1.5rem"><?=htmlspecialchars($errMsg)?></p>
 <div class="pay-actions" style="justify-content:center">
 <a href="<?= BASE_URL ?>/client/bookings.php" class="btn btn-primary"><?=icon('arrow-left')?> Try a different method</a>
 </div>
 </div>
 <?php else: ?>
 <span class="pay-method-chip"><span class="dot"></span> Awaiting <?=htmlspecialchars($mm['label'])?> confirmation</span>
 <h1 class="pay-heading">Check your phone</h1>
 <p class="pay-sub">We've sent a payment prompt to <strong>+263 <?=htmlspecialchars($phoneMasked)?></strong>. Approve it on your handset to complete the booking.</p>

 <div class="pay-stat">
 <div class="pay-stat-icon"><?=icon('coins')?></div>
 <div>
 <div class="pay-stat-label">Amount due</div>
 <div class="pay-amount">$<?=number_format($amount,2)?></div>
 </div>
 </div>

 <div class="pay-steps">
 <h3>How to confirm on your phone</h3>
 <div class="pay-step"><div class="pay-step-num">1</div><div class="pay-step-text">Check for the <strong><?=htmlspecialchars($mm['label'])?></strong> prompt on your phone.</div></div>
 <?php if(!empty($mm['ussd'])): ?>
 <div class="pay-step"><div class="pay-step-num">2</div><div class="pay-step-text">No prompt? Dial <code><?=htmlspecialchars($mm['ussd'])?></code> &rarr; select "Approve payment".</div></div>
 <?php endif; ?>
 <div class="pay-step"><div class="pay-step-num"><?=!empty($mm['ussd'])?3:2?></div><div class="pay-step-text">Enter your PIN to authorize <strong>$<?=number_format($amount,2)?></strong>.</div></div>
 <div class="pay-step"><div class="pay-step-num"><?=!empty($mm['ussd'])?4:3?></div><div class="pay-step-text">Come back and refresh &mdash; we'll mark your booking as paid.</div></div>
 </div>

 <?php if($instructions): ?>
 <div style="margin-top:1.3rem;background:<?=$mm['tint']?>;border:1px solid rgba(0,0,0,0.04);border-radius:12px;padding:0.95rem 1.1rem;font-size:0.88rem;line-height:1.6;color:var(--dark);position:relative;z-index:1">
 <strong style="color:<?=$mm['color']?>"><?=icon('circle-info')?> From <?=htmlspecialchars($mm['label'])?>:</strong><br>
 <?=nl2br(htmlspecialchars($instructions))?>
 </div>
 <?php endif; ?>

 <div class="pay-actions">
 <a href="<?= BASE_URL ?>/paynow_return.php?ref=<?=urlencode($reference)?>" class="btn btn-primary" style="flex:1;justify-content:center"><?=icon('rotate')?> I've paid &mdash; refresh status</a>
 <a href="<?= BASE_URL ?>/client/bookings.php" class="btn btn-outline" style="justify-content:center"><?=icon('xmark')?> Cancel</a>
 </div>
 <div class="pay-help"><?=icon('shield-halved')?> Secured by Paynow &middot; reference <code style="background:var(--bg);padding:0.1rem 0.4rem;border-radius:4px"><?=htmlspecialchars($reference)?></code></div>
 <?php endif; ?>
 </div>
 </div>
</div>
<?php include __DIR__.'/includes/footer.php'; ?>
</body></html>
