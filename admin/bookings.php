<?php
require_once '../includes/auth.php';
requireRole('admin');
require_once '../includes/db.php';
require_once '../includes/functions.php';
$books=$pdo->query("SELECT b.*,c.full_name as client,p.full_name as professional,pp.trade FROM bookings b JOIN users c ON b.client_id=c.user_id JOIN users p ON b.professional_id=p.user_id JOIN professional_profiles pp ON p.user_id=pp.user_id ORDER BY b.created_at DESC")->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>All Bookings — QuickFix ZW Admin</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head><body>
<?php include '../includes/navbar.php'; ?>
<div class="container"><br>
<div class="page-title">📅 All Bookings (<?=count($books)?>)</div>
<div class="card"><div class="card-body">
  <div class="table-wrap"><table>
    <thead><tr><th>Client</th><th>Professional</th><th>Trade</th><th>Amount</th><th>Date</th><th>Status</th><th>Payment</th></tr></thead>
    <tbody>
    <?php foreach($books as $b): ?>
    <tr>
      <td><?=$b['client']?></td>
      <td><?=$b['professional']?></td>
      <td><?=tradeIcon($b['trade'])?> <?=$b['trade']?></td>
      <td style="font-weight:700;color:var(--success)">$<?=number_format($b['agreed_amount'],2)?></td>
      <td><?=$b['scheduled_date']??'TBD'?></td>
      <td><span class="badge badge-<?=$b['status']==='completed'?'success':($b['status']==='in_progress'?'warning':($b['status']==='disputed'?'danger':'info'))?>"><?=ucfirst(str_replace('_',' ',$b['status']))?></span></td>
      <td><span class="badge badge-<?=$b['payment_status']==='released'?'success':'warning'?>"><?=ucfirst($b['payment_status'])?></span></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div></div>
</div>
</body></html>
