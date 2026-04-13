<?php
require_once '../includes/auth.php';
requireRole('admin');
require_once '../includes/db.php';
require_once '../includes/functions.php';
$msg='';
if(isset($_POST['delete_job'])) { $pdo->prepare("UPDATE job_requests SET status='cancelled' WHERE job_id=?")->execute([(int)$_POST['job_id']]); $msg="Job cancelled."; }
$jobs=$pdo->query("SELECT j.*,u.full_name as client,(SELECT COUNT(*) FROM bids WHERE job_id=j.job_id) as bid_count FROM job_requests j JOIN users u ON j.client_id=u.user_id ORDER BY j.created_at DESC")->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>All Jobs — QuickFix ZW Admin</title>
<link rel="stylesheet" href="/quickfix/css/style.css">
</head><body>
<?php include '../includes/navbar.php'; ?>
<div class="container"><br>
<?php if($msg): ?><div class="alert alert-warning"><?=$msg?></div><?php endif; ?>
<div class="page-title">📋 All Job Requests (<?=count($jobs)?>)</div>
<div class="card"><div class="card-body">
  <div class="table-wrap"><table>
    <thead><tr><th>Title</th><th>Client</th><th>Trade</th><th>Location</th><th>Budget</th><th>Urgency</th><th>Bids</th><th>Status</th><th>Posted</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach($jobs as $j): ?>
    <tr>
      <td><strong><?=htmlspecialchars(substr($j['title'],0,35))?></strong></td>
      <td><?=$j['client']?></td>
      <td><?=tradeIcon($j['trade'])?> <?=$j['trade']?></td>
      <td>📍 <?=$j['location']?></td>
      <td>$<?=number_format($j['client_budget'],2)?></td>
      <td><?=urgencyBadge($j['urgency'])?></td>
      <td><span class="badge badge-info"><?=$j['bid_count']?></span></td>
      <td><span class="badge badge-<?=$j['status']==='open'?'success':($j['status']==='in_progress'?'warning':'secondary')?>"><?=ucfirst($j['status'])?></span></td>
      <td style="font-size:0.8rem;color:#999"><?=timeAgo($j['created_at'])?></td>
      <td>
        <?php if($j['status']==='open'): ?>
        <form method="POST" onsubmit="return confirm('Cancel this job?')"><input type="hidden" name="job_id" value="<?=$j['job_id']?>"><button name="delete_job" class="btn btn-danger btn-sm">Cancel</button></form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div></div>
</div>
</body></html>
