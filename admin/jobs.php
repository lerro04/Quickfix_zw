<?php
require_once '../includes/auth.php';
requireRole('admin');
require_once '../includes/db.php';
require_once '../includes/functions.php';

$msg = '';
if(isset($_POST['delete_job'])){
    $pdo->prepare("UPDATE job_requests SET status='cancelled' WHERE job_id=?")->execute([(int)$_POST['job_id']]);
    $msg = 'Job cancelled.';
}

$perPage = 25;
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$totalJobs = (int)$pdo->query("SELECT COUNT(*) FROM job_requests")->fetchColumn();
$pagination = paginationData($totalJobs, $perPage, $currentPage);
$jobs = $pdo->query("SELECT j.*,u.full_name as client,(SELECT COUNT(*) FROM bids WHERE job_id=j.job_id) as bid_count FROM job_requests j JOIN users u ON j.client_id=u.user_id ORDER BY j.created_at DESC LIMIT ".$perPage." OFFSET ".$pagination['offset'])->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>All Jobs - QuickFix ZW Admin</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head><body>
<?php include '../includes/navbar.php'; ?>
<div class="container"><br>
<?php if($msg): ?><div class="alert alert-warning"><?=$msg?></div><?php endif; ?>
<div class="page-title"><?=icon('briefcase')?> All Job Requests (<?=$totalJobs?>)</div>
<div class="card"><div class="card-body">
  <div class="table-wrap"><table>
    <thead><tr><th>Title</th><th>Client</th><th>Trade</th><th>Location</th><th>Budget</th><th>Urgency</th><th>Bids</th><th>Status</th><th>Posted</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach($jobs as $j): ?>
    <tr>
      <td><strong><?=htmlspecialchars(substr($j['title'],0,35))?></strong></td>
      <td><?=htmlspecialchars($j['client'])?></td>
      <td><?=tradeIcon($j['trade'])?> <?=$j['trade']?></td>
      <td><?=icon('location-dot')?> <?=$j['location']?></td>
      <td>$<?=number_format($j['client_budget'],2)?></td>
      <td><?=urgencyBadge($j['urgency'])?></td>
      <td><span class="badge badge-info"><?=$j['bid_count']?></span></td>
      <td><span class="badge badge-<?=$j['status']==='open'?'success':($j['status']==='in_progress'?'warning':'secondary')?>"><?=ucfirst($j['status'])?></span></td>
      <td style="font-size:0.8rem;color:#999"><?=timeAgo($j['created_at'])?></td>
      <td>
        <?php if($j['status'] === 'open'): ?>
        <form method="POST" onsubmit="return confirm('Cancel this job?')"><input type="hidden" name="job_id" value="<?=$j['job_id']?>"><button name="delete_job" class="btn btn-danger btn-sm">Cancel</button></form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?=renderPagination($pagination['page'], $pagination['total_pages'])?>
</div></div>
</div>
</body></html>
