<?php
require_once '../includes/auth.php';
requireRole('client');
require_once '../includes/db.php';
require_once '../includes/functions.php';
$uid = $_SESSION['user_id'];

$jobs   = $pdo->prepare("SELECT COUNT(*) as c FROM job_requests WHERE client_id=?"); $jobs->execute([$uid]); $jobCount=$jobs->fetch()['c'];
$books  = $pdo->prepare("SELECT COUNT(*) as c FROM bookings WHERE client_id=?"); $books->execute([$uid]); $bookCount=$books->fetch()['c'];
$done   = $pdo->prepare("SELECT COUNT(*) as c FROM bookings WHERE client_id=? AND status='completed'"); $done->execute([$uid]); $doneCount=$done->fetch()['c'];
$openJobs = $pdo->prepare("SELECT j.*,(SELECT COUNT(*) FROM bids WHERE job_id=j.job_id) as bid_count FROM job_requests j WHERE j.client_id=? AND j.status='open' ORDER BY j.created_at DESC LIMIT 5"); $openJobs->execute([$uid]); $myOpen=$openJobs->fetchAll();
$recentBooks = $pdo->prepare("SELECT b.*,u.full_name as pro_name,pp.trade FROM bookings b JOIN users u ON b.professional_id=u.user_id JOIN professional_profiles pp ON u.user_id=pp.user_id WHERE b.client_id=? ORDER BY b.created_at DESC LIMIT 5"); $recentBooks->execute([$uid]); $recent=$recentBooks->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard â€” QuickFix ZW</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head><body>
<?php include '../includes/navbar.php'; ?>
<div class="container">
  <br>
  <div class="card-grid">
    <div class="stat-card"><div class="stat-icon">ðŸ“‹</div><div class="stat-info"><h3><?=$jobCount?></h3><p>Jobs Posted</p></div></div>
    <div class="stat-card"><div class="stat-icon">ðŸ“…</div><div class="stat-info"><h3><?=$bookCount?></h3><p>Total Bookings</p></div></div>
    <div class="stat-card"><div class="stat-icon">âœ…</div><div class="stat-info"><h3><?=$doneCount?></h3><p>Completed</p></div></div>
  </div>

  <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem">
    <a href="<?= BASE_URL ?>/client/browse.php" class="btn btn-primary btn-lg">ðŸ” Browse Professionals</a>
    <a href="<?= BASE_URL ?>/client/post_job.php" class="btn btn-secondary btn-lg">âž• Post a Job</a>
  </div>

  <!-- Open jobs with bids -->
  <?php if(!empty($myOpen)): ?>
  <div class="card" style="margin-bottom:1.5rem">
    <div class="card-header">ðŸ“‹ Your Open Jobs</div>
    <div class="card-body" style="padding:0">
      <?php foreach($myOpen as $j): ?>
      <div style="padding:1rem 1.5rem;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.5rem">
        <div>
          <strong><?= htmlspecialchars($j['title']) ?></strong>
          <div style="font-size:0.83rem;color:var(--gray);margin-top:0.2rem">
            <?= tradeIcon($j['trade']) ?> <?=$j['trade']?> Â· ðŸ“ <?=$j['location']?> Â· Budget: $<?=number_format($j['client_budget'],2)?>
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:0.8rem">
          <?= urgencyBadge($j['urgency']) ?>
          <?php if($j['bid_count']>0): ?>
            <a href="<?= BASE_URL ?>/client/view_bids.php?job=<?=$j['job_id']?>" class="btn btn-primary btn-sm">ðŸ’° <?=$j['bid_count']?> Bid(s)</a>
          <?php else: ?>
            <span class="badge badge-warning">No bids yet</span>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Recent bookings -->
  <div class="card">
    <div class="card-header">ðŸ“… Recent Bookings</div>
    <div class="card-body">
      <?php if(empty($recent)): ?>
        <p style="color:#999;text-align:center;padding:1.5rem">No bookings yet. <a href="<?= BASE_URL ?>/client/browse.php" style="color:var(--primary)">Find a professional!</a></p>
      <?php else: ?>
      <div class="table-wrap"><table>
        <thead><tr><th>Professional</th><th>Trade</th><th>Amount</th><th>Date</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach($recent as $b): ?>
        <tr>
          <td><strong><?=$b['pro_name']?></strong></td>
          <td><?=tradeIcon($b['trade'])?> <?=$b['trade']?></td>
          <td style="font-weight:700;color:var(--success)">$<?=number_format($b['agreed_amount'],2)?></td>
          <td><?=$b['scheduled_date']??'TBD'?></td>
          <td><span class="badge badge-<?=$b['status']==='completed'?'success':($b['status']==='confirmed'?'info':'warning')?>"><?=ucfirst($b['status'])?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
</body></html>
