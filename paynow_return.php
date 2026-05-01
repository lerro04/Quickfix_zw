<?php
require_once __DIR__.'/includes/auth.php';
requireLogin();
require_once __DIR__.'/includes/db.php';
require_once __DIR__.'/includes/functions.php';
require_once __DIR__.'/includes/paynow.php';
require_once __DIR__.'/includes/mailer.php';

$ref = trim($_GET['ref'] ?? '');
if($ref === ''){
    header('Location: '.BASE_URL.'/client/bookings.php');
    exit;
}

$stmt = $pdo->prepare("SELECT p.*, b.client_id AS booking_client FROM payments p JOIN bookings b ON p.booking_id=b.booking_id WHERE p.reference=?");
$stmt->execute([$ref]);
$payment = $stmt->fetch();

if(!$payment || (int)$payment['booking_client'] !== (int)$_SESSION['user_id']){
    header('Location: '.BASE_URL.'/client/bookings.php');
    exit;
}

if($payment['poll_url'] && in_array($payment['status'], ['created','sent','pending'], true)){
    try {
        $pn = paynowClient('', '');
        $resp = $pn->poll($payment['poll_url']);
        $newStatus = strtolower($resp['status'] ?? $payment['status']);
        $pdo->prepare("UPDATE payments SET status=?, paynow_reference=?, paid_amount=?, payment_method=?, raw_response=? WHERE payment_id=?")
            ->execute([
                $newStatus,
                $resp['paynowreference'] ?? $payment['paynow_reference'],
                $resp['amount'] ?? $payment['paid_amount'],
                $resp['paymentchannel'] ?? $payment['payment_method'],
                json_encode($resp),
                $payment['payment_id'],
            ]);
        if(in_array($newStatus, ['paid','awaiting delivery','delivered'], true)){
            $pdo->prepare("UPDATE bookings SET payment_status='held' WHERE booking_id=? AND payment_status='pending'")
                ->execute([$payment['booking_id']]);
        }
        $payment['status'] = $newStatus;
    } catch(RuntimeException $e){
        // ignore — keep showing the previous status
    }
}

$success = in_array(strtolower($payment['status']), ['paid','awaiting delivery','delivered'], true);
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Payment <?= $success ? 'received' : 'pending' ?> - QuickFix ZW</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head><body>
<?php include __DIR__.'/includes/navbar.php'; ?>
<div class="container"><br>
  <div class="section-card" style="max-width:560px;margin:1.5rem auto;text-align:center">
    <?php if($success): ?>
      <div style="font-size:3rem;color:var(--success);margin-bottom:0.5rem"><?=icon('circle-check')?></div>
      <h2 style="margin-bottom:0.5rem">Payment received</h2>
      <p style="color:var(--gray);line-height:1.7">Your payment of <strong>$<?=number_format((float)$payment['amount'], 2)?></strong> for booking #<?=$payment['booking_id']?> has been processed by Paynow.</p>
      <p style="color:var(--gray);line-height:1.7">Funds are held until you confirm the work is complete. The professional has been notified.</p>
    <?php elseif(strtolower($payment['status']) === 'cancelled'): ?>
      <div style="font-size:3rem;color:var(--danger);margin-bottom:0.5rem"><?=icon('circle-xmark')?></div>
      <h2 style="margin-bottom:0.5rem">Payment cancelled</h2>
      <p style="color:var(--gray);line-height:1.7">The Paynow transaction was cancelled. You can try again from your bookings.</p>
    <?php else: ?>
      <div style="font-size:3rem;color:var(--warning);margin-bottom:0.5rem"><?=icon('hourglass-half')?></div>
      <h2 style="margin-bottom:0.5rem">Payment pending</h2>
      <p style="color:var(--gray);line-height:1.7">We are still waiting for confirmation from Paynow. Refresh in a moment, or check your bookings.</p>
    <?php endif; ?>
    <div style="margin-top:1.2rem;display:flex;gap:0.6rem;justify-content:center;flex-wrap:wrap">
      <a href="<?= BASE_URL ?>/client/bookings.php" class="btn btn-primary"><?=icon('calendar-days')?> My Bookings</a>
      <?php if(!$success): ?>
        <a href="<?= BASE_URL ?>/paynow_return.php?ref=<?=urlencode($ref)?>" class="btn btn-outline"><?=icon('rotate')?> Refresh status</a>
      <?php endif; ?>
    </div>
  </div>
</div>
</body></html>
