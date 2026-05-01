<?php
require_once '../includes/auth.php';
requireRole('admin');
require_once '../includes/db.php';
require_once '../includes/functions.php';
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $bid=(int)$_POST['booking_id'];
    $book=$pdo->prepare("SELECT * FROM bookings WHERE booking_id=?"); $book->execute([$bid]); $b=$book->fetch();
    if($b){
        if(isset($_POST['pay_pro'])){
            $pdo->prepare("UPDATE bookings SET status='completed',payment_status='released' WHERE booking_id=?")->execute([$bid]);
            $pdo->prepare("UPDATE professional_profiles SET jobs_completed=jobs_completed+1 WHERE user_id=?")->execute([$b['professional_id']]);
            $msg="âœ… Resolved in professional's favour. Payment released.";
        }
        if(isset($_POST['refund_client'])){
            $pdo->prepare("UPDATE bookings SET status='completed',payment_status='refunded' WHERE booking_id=?")->execute([$bid]);
            $msg="âœ… Resolved in client's favour. Refund issued.";
        }
    }
}
$disputes=$pdo->query("SELECT b.*,c.full_name as client,c.phone as client_phone,p.full_name as professional,p.phone as pro_phone,pp.trade,j.title as job_title FROM bookings b JOIN users c ON b.client_id=c.user_id JOIN users p ON b.professional_id=p.user_id JOIN professional_profiles pp ON p.user_id=pp.user_id LEFT JOIN job_requests j ON b.job_id=j.job_id WHERE b.status='disputed'")->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Disputes â€” QuickFix ZW Admin</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head><body>
<?php include '../includes/navbar.php'; ?>
<div class="container"><br>
<?php if($msg): ?><div class="alert alert-success"><?=$msg?></div><?php endif; ?>
<div class="page-title">âš–ï¸ Dispute Resolution</div>
<?php if(empty($disputes)): ?>
<div class="alert alert-success">âœ… No active disputes. All jobs are running smoothly!</div>
<?php else: ?>
<?php foreach($disputes as $d): ?>
<div class="card" style="margin-bottom:1.2rem">
  <div class="card-header">âš ï¸ Dispute â€” <?=htmlspecialchars($d['job_title']??'Direct Booking')?></div>
  <div class="card-body">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1rem">
      <div style="background:#f8f9fa;border-radius:10px;padding:1rem">
        <div style="font-weight:700;color:var(--primary);margin-bottom:0.5rem">ðŸ  Client</div>
        <p><strong><?=$d['client']?></strong></p>
        <p style="font-size:0.85rem;color:#666">ðŸ“ž <?=$d['client_phone']?></p>
      </div>
      <div style="background:#f8f9fa;border-radius:10px;padding:1rem">
        <div style="font-weight:700;color:var(--primary);margin-bottom:0.5rem">ðŸ”§ Professional</div>
        <p><strong><?=$d['professional']?></strong></p>
        <p style="font-size:0.85rem;color:#666">ðŸ“ž <?=$d['pro_phone']?> Â· <?=tradeIcon($d['trade'])?> <?=$d['trade']?></p>
      </div>
    </div>
    <p><strong>Amount in dispute:</strong> <span style="font-size:1.2rem;font-weight:700;color:var(--primary)">$<?=number_format($d['agreed_amount'],2)?></span></p>
    <p style="font-size:0.85rem;color:#999;margin-top:0.3rem">Booked: <?=$d['created_at']?></p>
    <div style="display:flex;gap:0.8rem;margin-top:1.2rem;flex-wrap:wrap">
      <form method="POST">
        <input type="hidden" name="booking_id" value="<?=$d['booking_id']?>">
        <button name="pay_pro" class="btn btn-success" onclick="return confirm('Release payment to professional?')">âœ… Pay Professional</button>
      </form>
      <form method="POST">
        <input type="hidden" name="booking_id" value="<?=$d['booking_id']?>">
        <button name="refund_client" class="btn btn-warning" onclick="return confirm('Refund the client?')">â†©ï¸ Refund Client</button>
      </form>
      <a href="<?= BASE_URL ?>/messages.php?with=<?=$d['client_id']?>" class="btn btn-outline btn-sm">ðŸ’¬ Message Client</a>
      <a href="<?= BASE_URL ?>/messages.php?with=<?=$d['professional_id']?>" class="btn btn-outline btn-sm">ðŸ’¬ Message Professional</a>
    </div>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
</body></html>
