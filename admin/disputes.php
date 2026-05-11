<?php
require_once '../includes/auth.php';
requireRole('admin');
require_once '../includes/db.php';
require_once '../includes/functions.php';

$msg = '';
$msgType = 'success';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
 $bookingId = (int)($_POST['booking_id'] ?? 0);
 if($bookingId > 0){
 if(isset($_POST['side_with_client'])){
 $pdo->prepare("UPDATE bookings SET status='cancelled' WHERE booking_id=?")->execute([$bookingId]);
 $msg = 'Dispute resolved in favour of the client. Booking cancelled.';
 } elseif(isset($_POST['side_with_pro'])){
 $stmt = $pdo->prepare("SELECT * FROM bookings WHERE booking_id=?");
 $stmt->execute([$bookingId]);
 $booking = $stmt->fetch();
 if($booking){
 $fee = platformCommissionAmount((float)$booking['agreed_amount']);
 $payout = max(0, round((float)$booking['agreed_amount'] - $fee, 2));
 $pdo->prepare("UPDATE bookings SET status='completed', payment_status='released', completed_at=NOW(), platform_fee_pct=?, platform_fee_amount=?, professional_payout=? WHERE booking_id=?")
 ->execute([PLATFORM_COMMISSION_RATE * 100, $fee, $payout, $bookingId]);
 $msg = 'Dispute resolved in favour of the professional. Payout released.';
 }
 } elseif(isset($_POST['close_no_action'])){
 $pdo->prepare("UPDATE bookings SET status='completed' WHERE booking_id=?")->execute([$bookingId]);
 $msg = 'Dispute closed without payout change.';
 }
 }
}

$disputes = $pdo->query("SELECT b.*, c.full_name AS client_name, c.email AS client_email, c.phone AS client_phone,
 p.full_name AS pro_name, p.email AS pro_email, p.phone AS pro_phone,
 j.title AS job_title, pp.trade
 FROM bookings b
 JOIN users c ON b.client_id=c.user_id
 JOIN users p ON b.professional_id=p.user_id
 LEFT JOIN job_requests j ON b.job_id=j.job_id
 LEFT JOIN professional_profiles pp ON b.professional_id=pp.user_id
 WHERE b.status='disputed'
 ORDER BY b.created_at DESC")->fetchAll();

$resolvedCount = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status IN ('completed','cancelled') AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Disputes - QuickFix ZW Admin</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head><body>
<?php include '../includes/navbar.php'; ?>
<div class="container"><br>
<?php if($msg): ?><div class="alert alert-<?=$msgType?>"><?=htmlspecialchars($msg)?></div><?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1.2rem">
 <div>
 <div class="page-title" style="margin:0"><?=icon('scale-balanced')?> Disputes</div>
 <p style="color:var(--gray);font-size:0.9rem;margin-top:0.3rem">Bookings flagged by either party for admin review.</p>
 </div>
 <div style="display:flex;gap:0.5rem;flex-wrap:wrap">
 <span class="badge badge-danger" style="padding:0.5rem 0.9rem;font-size:0.82rem"><?=count($disputes)?> open</span>
 <span class="badge badge-success" style="padding:0.5rem 0.9rem;font-size:0.82rem"><?=$resolvedCount?> resolved (30 days)</span>
 </div>
</div>

<?php if(empty($disputes)): ?>
 <div class="card"><div class="card-body" style="text-align:center;padding:3rem 1.5rem">
 <div style="font-size:2.6rem;color:var(--success);margin-bottom:0.6rem"><?=icon('circle-check')?></div>
 <h3 style="margin-bottom:0.3rem">No active disputes</h3>
 <p style="color:var(--gray);max-width:480px;margin:0 auto;line-height:1.6">Great — every booking is either in good standing or already resolved. Clients and professionals can raise a dispute from their bookings page if a problem arises, and it will appear here.</p>
 <div style="margin-top:1.4rem;display:flex;gap:0.5rem;justify-content:center;flex-wrap:wrap">
 <a href="<?= BASE_URL ?>/admin/bookings.php" class="btn btn-outline btn-sm"><?=icon('calendar-days')?> All bookings</a>
 <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-outline btn-sm"><?=icon('gauge-high')?> Back to dashboard</a>
 </div>
 </div></div>
<?php else: ?>
 <?php foreach($disputes as $d): ?>
 <div class="card" style="margin-bottom:1rem">
 <div class="card-body">
 <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:0.8rem;margin-bottom:0.8rem">
 <div>
 <div style="font-weight:700;font-size:1.05rem;color:var(--dark)"><?=htmlspecialchars($d['job_title'] ?? 'Direct Booking')?></div>
 <div style="color:var(--gray);font-size:0.85rem;margin-top:0.2rem">
 <?=tradeIcon($d['trade'] ?? '')?> <?=htmlspecialchars($d['trade'] ?? 'N/A')?>
 &middot; Booking #<?=$d['booking_id']?>
 &middot; <?=htmlspecialchars($d['scheduled_date'] ?? 'No date')?>
 &middot; raised <?=timeAgo($d['created_at'])?>
 </div>
 </div>
 <div style="text-align:right">
 <div style="font-size:1.3rem;font-weight:800;color:var(--dark)">$<?=number_format((float)$d['agreed_amount'],2)?></div>
 <span class="badge badge-warning"><?=ucfirst($d['payment_status'])?></span>
 </div>
 </div>

 <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">
 <div style="padding:0.9rem;background:var(--bg);border-radius:10px;border:1px solid var(--border)">
 <div style="font-size:0.78rem;color:var(--gray);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.3rem">Client</div>
 <div style="font-weight:700"><?=htmlspecialchars($d['client_name'])?></div>
 <div style="font-size:0.85rem;color:var(--gray);margin-top:0.2rem">
 <?=icon('envelope')?> <?=htmlspecialchars($d['client_email'])?><br>
 <?=icon('phone')?> <?=htmlspecialchars($d['client_phone'] ?? '—')?>
 </div>
 </div>
 <div style="padding:0.9rem;background:var(--bg);border-radius:10px;border:1px solid var(--border)">
 <div style="font-size:0.78rem;color:var(--gray);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.3rem">Professional</div>
 <div style="font-weight:700"><?=htmlspecialchars($d['pro_name'])?></div>
 <div style="font-size:0.85rem;color:var(--gray);margin-top:0.2rem">
 <?=icon('envelope')?> <?=htmlspecialchars($d['pro_email'])?><br>
 <?=icon('phone')?> <?=htmlspecialchars($d['pro_phone'] ?? '—')?>
 </div>
 </div>
 </div>

 <div style="display:flex;gap:0.5rem;flex-wrap:wrap">
 <form method="POST" onsubmit="return confirm('Side with the client and cancel this booking?')" style="margin:0">
 <input type="hidden" name="booking_id" value="<?=$d['booking_id']?>">
 <button name="side_with_client" class="btn btn-danger btn-sm"><?=icon('user')?> Refund client</button>
 </form>
 <form method="POST" onsubmit="return confirm('Side with the professional and release payout?')" style="margin:0">
 <input type="hidden" name="booking_id" value="<?=$d['booking_id']?>">
 <button name="side_with_pro" class="btn btn-success btn-sm"><?=icon('user-tie')?> Release payout to pro</button>
 </form>
 <form method="POST" onsubmit="return confirm('Close this dispute without changing payouts?')" style="margin:0">
 <input type="hidden" name="booking_id" value="<?=$d['booking_id']?>">
 <button name="close_no_action" class="btn btn-outline btn-sm"><?=icon('xmark')?> Close (no action)</button>
 </form>
 <a href="<?= BASE_URL ?>/messages.php?with=<?=$d['client_id']?>" class="btn btn-outline btn-sm"><?=icon('comments')?> Message client</a>
 <a href="<?= BASE_URL ?>/messages.php?with=<?=$d['professional_id']?>" class="btn btn-outline btn-sm"><?=icon('comments')?> Message pro</a>
 </div>
 </div>
 </div>
 <?php endforeach; ?>
<?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
</body></html>
