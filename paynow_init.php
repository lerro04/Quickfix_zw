<?php
require_once __DIR__.'/includes/auth.php';
requireRole('client');
require_once __DIR__.'/includes/db.php';
require_once __DIR__.'/includes/functions.php';
require_once __DIR__.'/includes/paynow.php';
require_once __DIR__.'/includes/mailer.php';

$uid = $_SESSION['user_id'];
$bookingId = (int)($_POST['booking_id'] ?? $_GET['booking_id'] ?? 0);

if($bookingId <= 0){
 http_response_code(400);
 exit('Missing booking id.');
}

$bk = $pdo->prepare("SELECT * FROM bookings WHERE booking_id=? AND client_id=?");
$bk->execute([$bookingId, $uid]);
$booking = $bk->fetch();

if(!$booking){
 http_response_code(404);
 exit('Booking not found.');
}

if(!in_array($booking['status'], ['confirmed','in_progress','completed'], true)){
 exit('This booking cannot be paid for in its current state ('.$booking['status'].').');
}
if($booking['payment_status'] === 'released'){
 exit('This booking has already been paid out.');
}

$reference = 'QFX-'.$bookingId.'-'.time();
$breakdown = bookingPaymentBreakdown($pdo, $booking);
$amount = round((float)($_POST['payment_amount'] ?? $breakdown['remaining_online']), 2);
$minimumAmount = $breakdown['commission_due'] > 0 ? $breakdown['commission_due'] : 0.01;

if($amount < $minimumAmount || $amount > $breakdown['remaining_online']){
 http_response_code(400);
 exit('Enter a Paynow amount between $'.number_format($minimumAmount, 2).' and $'.number_format($breakdown['remaining_online'], 2).'. The platform commission must be paid before the receiver gets the remaining amount.');
}

$existing = $pdo->prepare("SELECT * FROM payments WHERE booking_id=? AND ABS(amount - ?) < 0.01 AND status IN ('created','sent','pending') ORDER BY payment_id DESC LIMIT 1");
$existing->execute([$bookingId, $amount]);
$pending = $existing->fetch();
if($pending && $pending['browser_url']){
 header('Location: '.$pending['browser_url']);
 exit;
}

$base = getSiteUrl().BASE_URL;
$returnUrl = $base.'/paynow_return.php?ref='.urlencode($reference);
$resultUrl = $base.'/paynow_result.php?ref='.urlencode($reference);

$pdo->prepare("INSERT INTO payments (booking_id,client_id,amount,reference,status) VALUES (?,?,?,?,?)")
 ->execute([$bookingId, $uid, $amount, $reference, 'created']);

try {
 $pn = paynowClient($returnUrl, $resultUrl);
 $resp = $pn->initiate($reference, $amount, 'QuickFix ZW booking #'.$bookingId, $_SESSION['email'] ?? '');

 if(($resp['status'] ?? '') === 'Ok' && !empty($resp['browserurl']) && !empty($resp['pollurl'])){
 $pdo->prepare("UPDATE payments SET browser_url=?, poll_url=?, status='sent', raw_response=? WHERE reference=?")
 ->execute([$resp['browserurl'], $resp['pollurl'], json_encode($resp), $reference]);
 header('Location: '.$resp['browserurl']);
 exit;
 }

 $errMsg = $resp['error'] ?? 'Unknown error from Paynow';
 $pdo->prepare("UPDATE payments SET status='failed', raw_response=? WHERE reference=?")
 ->execute([json_encode($resp), $reference]);
 ?>
 <!DOCTYPE html><html><head><meta charset="UTF-8"><title>Payment failed</title>
 <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css"></head>
 <body><div class="container"><br>
 <div class="alert alert-danger">Could not start the Paynow transaction: <?=htmlspecialchars($errMsg)?></div>
 <a href="<?= BASE_URL ?>/client/bookings.php" class="btn btn-primary">Back to my bookings</a>
 </div></body></html>
 <?php
 exit;
} catch(RuntimeException $e){
 $pdo->prepare("UPDATE payments SET status='failed', raw_response=? WHERE reference=?")
 ->execute([$e->getMessage(), $reference]);
 ?>
 <!DOCTYPE html><html><head><meta charset="UTF-8"><title>Payment error</title>
 <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css"></head>
 <body><div class="container"><br>
 <div class="alert alert-danger">Could not reach Paynow: <?=htmlspecialchars($e->getMessage())?></div>
 <a href="<?= BASE_URL ?>/client/bookings.php" class="btn btn-primary">Back to my bookings</a>
 </div></body></html>
 <?php
 exit;
}
