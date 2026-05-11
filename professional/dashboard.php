<?php
require_once '../includes/auth.php';
requireRole('professional');
require_once '../includes/db.php';
require_once '../includes/functions.php';
$uid = $_SESSION['user_id'];

$prof = $pdo->prepare("SELECT pp.*,u.location,u.phone,u.verified,u.national_id_file FROM professional_profiles pp JOIN users u ON pp.user_id=u.user_id WHERE pp.user_id=?"); $prof->execute([$uid]); $p=$prof->fetch();
$bids = $pdo->prepare("SELECT COUNT(*) as c FROM bids WHERE professional_id=?"); $bids->execute([$uid]); $bidCount=$bids->fetch()['c'];
$books= $pdo->prepare("SELECT COUNT(*) as c FROM bookings WHERE professional_id=?"); $books->execute([$uid]); $bookCount=$books->fetch()['c'];
$earned=$pdo->prepare("SELECT COALESCE(SUM(CASE WHEN professional_payout IS NULL OR professional_payout = 0 THEN agreed_amount - ROUND(agreed_amount * ? / 100, 2) ELSE professional_payout END),0) as t FROM bookings WHERE professional_id=? AND payment_status='released'"); $earned->execute([PLATFORM_COMMISSION_RATE * 100,$uid]); $totalEarned=$earned->fetch()['t'];
$openJobsStmt = $pdo->prepare("SELECT COUNT(*) as c FROM job_requests WHERE status='open' AND trade=?"); $openJobsStmt->execute([$p['trade']]); $openJobs = $openJobsStmt->fetch()['c'];
$recentBids=$pdo->prepare("SELECT b.*,j.title,j.location,j.client_budget,u.full_name as client FROM bids b JOIN job_requests j ON b.job_id=j.job_id JOIN users u ON j.client_id=u.user_id WHERE b.professional_id=? ORDER BY b.created_at DESC LIMIT 5"); $recentBids->execute([$uid]); $myBids=$recentBids->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard - QuickFix ZW</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head><body>
<?php include '../includes/navbar.php'; ?>
<div class="container"><br>
<?php if(empty($p['national_id_file'])): ?>
 <div class="alert alert-warning" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap"><div><?=icon('id-card')?> <strong>Upload your national ID</strong> on your profile to start bidding on jobs.</div><a href="<?= BASE_URL ?>/professional/profile.php" class="btn btn-warning btn-sm"><?=icon('upload')?> Upload ID</a></div>
<?php elseif(!$p['verified']): ?>
 <div class="alert alert-info"><?=icon('hourglass-half')?> Your ID is awaiting admin verification. You will be able to bid once it is approved.</div>
<?php endif; ?>

<div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap">
 <div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;font-size:1.8rem;color:white;font-weight:700"><?=strtoupper(substr($_SESSION['full_name'],0,1))?></div>
 <div>
 <h2 style="margin:0"><?=htmlspecialchars($_SESSION['full_name'])?></h2>
 <div style="color:var(--primary);font-weight:600"><?=tradeIcon($p['trade'])?> <?=htmlspecialchars($p['trade'])?></div>
 <div style="font-size:0.85rem;color:var(--gray)"><?=stars($p['rating_avg'])?> (<?=$p['total_reviews']?> reviews) &middot; <?=$p['jobs_completed']?> jobs done</div>
 </div>
 <div style="margin-left:auto">
 <span style="color:var(--<?=$p['is_available']?'success':'gray'?>);font-weight:600">
 <span class="avail-dot <?=$p['is_available']?'online':'offline'?>"></span>
 <?=$p['is_available']?'Available':'Unavailable'?>
 </span>
 </div>
</div>

<div class="card-grid">
 <div class="stat-card"><div class="stat-icon"><?=icon('briefcase')?></div><div class="stat-info"><h3><?=$openJobs?></h3><p>Open <?=htmlspecialchars($p['trade'])?> Jobs</p></div></div>
 <div class="stat-card"><div class="stat-icon"><?=icon('sack-dollar')?></div><div class="stat-info"><h3><?=$bidCount?></h3><p>Bids Placed</p></div></div>
 <div class="stat-card"><div class="stat-icon"><?=icon('calendar-days')?></div><div class="stat-info"><h3><?=$bookCount?></h3><p>Total Bookings</p></div></div>
 <div class="stat-card" style="border-left-color:var(--accent)"><div class="stat-icon" style="background:linear-gradient(135deg,var(--accent),#e67e22)"><?=icon('money-bill-wave')?></div><div class="stat-info"><h3>$<?=number_format($totalEarned,2)?></h3><p>Total Earned</p><div class="metric-note">After platform commission</div></div></div>
</div>

<?php if(empty($p['bio'])): ?>
 <div class="alert alert-info" style="margin:1rem 0">
 <?=icon('circle-info')?> Your profile has no description yet. Clients are far more likely to book a professional with a clear About-Me write-up.
 <a href="<?= BASE_URL ?>/professional/profile.php" style="color:var(--primary);font-weight:600;margin-left:0.4rem"><?=icon('pen-to-square')?> Add description</a>
 </div>
<?php endif; ?>

<div style="display:flex;gap:1rem;flex-wrap:wrap;margin:1rem 0">
 <a href="<?= BASE_URL ?>/professional/job_board.php" class="btn btn-primary"><?=icon('briefcase')?> View Job Board</a>
 <a href="<?= BASE_URL ?>/professional/profile.php" class="btn btn-secondary"><?=icon('user-pen')?> Edit Profile</a>
</div>

<div class="card">
 <div class="card-header"><?=icon('sack-dollar')?> Recent Bids</div>
 <div class="card-body">
 <?php if(empty($myBids)): ?><p style="color:#999;text-align:center;padding:1.5rem">No bids yet. <a href="<?= BASE_URL ?>/professional/job_board.php" style="color:var(--primary)">Browse the job board!</a></p>
 <?php else: ?>
 <div class="table-wrap"><table>
 <thead><tr><th>Job</th><th>Client</th><th>Location</th><th>My Bid</th><th>Status</th><th>Time</th></tr></thead>
 <tbody>
 <?php foreach($myBids as $b): ?>
 <tr>
 <td><strong><?=htmlspecialchars(substr($b['title'],0,35))?></strong></td>
 <td><?=htmlspecialchars($b['client'])?></td>
 <td><?=icon('location-dot')?> <?=htmlspecialchars($b['location'])?></td>
 <td style="font-weight:700;color:var(--primary)">$<?=number_format($b['bid_amount'],2)?></td>
 <td><span class="badge badge-<?=$b['status']==='accepted'?'success':($b['status']==='rejected'?'danger':'warning')?>"><?=ucfirst($b['status'])?></span></td>
 <td><?=timeAgo($b['created_at'])?></td>
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
