<?php
// Paynow webhook (resulturl).
// Validates inbound hash, updates payment + booking, sends notifications.

require_once __DIR__.'/includes/db.php';
require_once __DIR__.'/includes/functions.php';
require_once __DIR__.'/includes/paynow.php';
require_once __DIR__.'/includes/mailer.php';

$raw = file_get_contents('php://input');
if($raw === false || $raw === ''){
 http_response_code(400);
 exit('No payload');
}

$pn = paynowClient('', '');
$values = $pn->parsePostBody($raw);

if(!$pn->validateInboundHash($values)){
 http_response_code(400);
 error_log('Paynow webhook: invalid hash for ref='.($values['reference'] ?? 'unknown'));
 exit('Invalid hash');
}

$ref = $values['reference'] ?? '';
$payRef = $values['paynowreference'] ?? '';
$amount = $values['amount'] ?? '0';
$status = strtolower($values['status'] ?? '');
$channel = $values['paymentchannel'] ?? '';

if($ref === ''){
 http_response_code(400);
 exit('Missing reference');
}

$lookup = $pdo->prepare("SELECT * FROM payments WHERE reference=?");
$lookup->execute([$ref]);
$payment = $lookup->fetch();

if(!$payment){
 http_response_code(404);
 exit('Unknown reference');
}

$pdo->prepare("UPDATE payments SET status=?, paynow_reference=?, paid_amount=?, payment_method=?, raw_response=? WHERE payment_id=?")
 ->execute([$status, $payRef, $amount, $channel, $raw, $payment['payment_id']]);

if(in_array($status, ['paid','awaiting delivery','delivered'], true)){
 $pdo->prepare("UPDATE bookings SET payment_status='held' WHERE booking_id=? AND payment_status='pending'")
 ->execute([$payment['booking_id']]);

 $bk = $pdo->prepare("SELECT b.*, p.full_name AS pro_name, p.email AS pro_email, c.full_name AS client_name FROM bookings b JOIN users p ON b.professional_id=p.user_id JOIN users c ON b.client_id=c.user_id WHERE b.booking_id=?");
 $bk->execute([$payment['booking_id']]);
 $booking = $bk->fetch();
 if($booking){
 $body = emailLayout(
 'Client paid for your booking',
 '<p>Hi '.htmlspecialchars($booking['pro_name']).',</p>'
 .'<p><strong>'.htmlspecialchars($booking['client_name']).'</strong> has paid <strong>$'.number_format((float)$amount,2).'</strong> via Paynow ('.htmlspecialchars($channel ?: 'card').') for your booking.</p>'
 .'<p>The funds are held by the platform. After the client marks the job complete, you must confirm payment received before the net payout is released after commission.</p>',
 '',
 ''
 );
 sendEmail($booking['pro_email'], 'Payment received for your QuickFix booking', $body);
 }
} elseif(in_array($status, ['cancelled','disputed','refunded'], true)){
 $pdo->prepare("UPDATE bookings SET payment_status=? WHERE booking_id=?")
 ->execute([$status === 'refunded' ? 'refunded' : 'pending', $payment['booking_id']]);
}

http_response_code(200);
echo 'OK';
