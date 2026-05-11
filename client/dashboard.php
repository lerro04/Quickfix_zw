<?php
require_once '../includes/auth.php';
requireRole('client');
require_once '../includes/db.php';
require_once '../includes/functions.php';
$uid = $_SESSION['user_id'];

$me = $pdo->prepare("SELECT latitude, longitude, location_label FROM users WHERE user_id=?");
$me->execute([$uid]);
$myLoc = $me->fetch() ?: ['latitude'=>null,'longitude'=>null,'location_label'=>null];
$hasLoc = $myLoc['latitude'] !== null && $myLoc['longitude'] !== null;

$jobs = $pdo->prepare("SELECT COUNT(*) as c FROM job_requests WHERE client_id=?"); $jobs->execute([$uid]); $jobCount=$jobs->fetch()['c'];
$books = $pdo->prepare("SELECT COUNT(*) as c FROM bookings WHERE client_id=?"); $books->execute([$uid]); $bookCount=$books->fetch()['c'];
$done = $pdo->prepare("SELECT COUNT(*) as c FROM bookings WHERE client_id=? AND status='completed'"); $done->execute([$uid]); $doneCount=$done->fetch()['c'];
$openJobs = $pdo->prepare("SELECT j.*,(SELECT COUNT(*) FROM bids WHERE job_id=j.job_id) as bid_count FROM job_requests j WHERE j.client_id=? AND j.status='open' ORDER BY j.created_at DESC LIMIT 5"); $openJobs->execute([$uid]); $myOpen=$openJobs->fetchAll();
$recentBooks = $pdo->prepare("SELECT b.*,u.full_name as pro_name,pp.trade FROM bookings b JOIN users u ON b.professional_id=u.user_id JOIN professional_profiles pp ON u.user_id=pp.user_id WHERE b.client_id=? ORDER BY b.created_at DESC LIMIT 5"); $recentBooks->execute([$uid]); $recent=$recentBooks->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard - QuickFix ZW</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head><body>
<?php include '../includes/navbar.php'; ?>
<div class="container">
 <br>

 <div id="loc-card" style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:1.1rem 1.3rem;margin-bottom:1.2rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;<?= $hasLoc ? '' : 'border-left:3px solid var(--primary);' ?>">
 <div style="width:42px;height:42px;border-radius:12px;background:#fff7ee;color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0"><?=icon('location-crosshairs')?></div>
 <div style="flex:1;min-width:220px">
 <div style="font-weight:700;color:var(--dark);font-size:0.98rem"><?= $hasLoc ? 'Location saved' : 'Set your location' ?></div>
 <div id="loc-status" style="font-size:0.84rem;color:var(--gray);margin-top:0.15rem">
 <?php if($hasLoc): ?>
 <?= htmlspecialchars($myLoc['location_label'] ?: 'GPS pin saved') ?> &middot; we use this to show pros near you
 <?php else: ?>
 Share your location so we can sort pros by how close they are
 <?php endif; ?>
 </div>
 </div>
 <button id="loc-btn" type="button" class="btn <?= $hasLoc ? 'btn-outline' : 'btn-primary' ?>">
 <?=icon('location-crosshairs')?> <?= $hasLoc ? 'Update location' : 'Use my location' ?>
 </button>
 </div>

 <div class="card-grid">
 <div class="stat-card"><div class="stat-icon"><?=icon('briefcase')?></div><div class="stat-info"><h3><?=$jobCount?></h3><p>Jobs Posted</p></div></div>
 <div class="stat-card"><div class="stat-icon"><?=icon('calendar-days')?></div><div class="stat-info"><h3><?=$bookCount?></h3><p>Total Bookings</p></div></div>
 <div class="stat-card"><div class="stat-icon"><?=icon('circle-check')?></div><div class="stat-info"><h3><?=$doneCount?></h3><p>Completed</p></div></div>
 </div>

 <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem">
 <a href="<?= BASE_URL ?>/browse.php" class="btn btn-primary btn-lg"><?=icon('magnifying-glass')?> Browse Professionals</a>
 <a href="<?= BASE_URL ?>/client/post_job.php" class="btn btn-secondary btn-lg"><?=icon('plus')?> Post a Job</a>
 </div>

 <?php if(!empty($myOpen)): ?>
 <div class="card" style="margin-bottom:1.5rem">
 <div class="card-header"><?=icon('briefcase')?> Your Open Jobs</div>
 <div class="card-body" style="padding:0">
 <?php foreach($myOpen as $j): ?>
 <div style="padding:1rem 1.5rem;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.5rem">
 <div>
 <strong><?= htmlspecialchars($j['title']) ?></strong>
 <div style="font-size:0.83rem;color:var(--gray);margin-top:0.2rem">
 <?= tradeIcon($j['trade']) ?> <?=htmlspecialchars($j['trade'])?> &middot; <?=icon('location-dot')?> <?=htmlspecialchars($j['location'])?> &middot; Budget: $<?=number_format($j['client_budget'],2)?>
 </div>
 </div>
 <div style="display:flex;align-items:center;gap:0.8rem">
 <?= urgencyBadge($j['urgency']) ?>
 <?php if($j['bid_count']>0): ?>
 <a href="<?= BASE_URL ?>/client/my_jobs.php?job=<?=$j['job_id']?>" class="btn btn-primary btn-sm"><?=icon('sack-dollar')?> <?=$j['bid_count']?> Bid(s)</a>
 <?php else: ?>
 <span class="badge badge-warning">No bids yet</span>
 <?php endif; ?>
 </div>
 </div>
 <?php endforeach; ?>
 </div>
 </div>
 <?php endif; ?>

 <div class="card">
 <div class="card-header"><?=icon('calendar-days')?> Recent Bookings</div>
 <div class="card-body">
 <?php if(empty($recent)): ?>
 <p style="color:#999;text-align:center;padding:1.5rem">No bookings yet. <a href="<?= BASE_URL ?>/browse.php" style="color:var(--primary)">Find a professional!</a></p>
 <?php else: ?>
 <div class="table-wrap"><table>
 <thead><tr><th>Professional</th><th>Trade</th><th>Amount</th><th>Date</th><th>Status</th></tr></thead>
 <tbody>
 <?php foreach($recent as $b): ?>
 <tr>
 <td><strong><?=htmlspecialchars($b['pro_name'])?></strong></td>
 <td><?=tradeIcon($b['trade'])?> <?=htmlspecialchars($b['trade'])?></td>
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
<script>
(function(){
 const btn = document.getElementById('loc-btn');
 const status = document.getElementById('loc-status');
 if(!btn) return;
 btn.addEventListener('click', function(){
 if(!navigator.geolocation){
 status.textContent = 'Your browser does not support GPS.';
 return;
 }
 btn.disabled = true;
 const original = btn.innerHTML;
 btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Locating...';
 navigator.geolocation.getCurrentPosition(function(pos){
 const lat = pos.coords.latitude;
 const lng = pos.coords.longitude;
 const fd = new FormData();
 fd.append('latitude', lat);
 fd.append('longitude', lng);
 fd.append('location_label', '');
 fetch('<?= BASE_URL ?>/api/save_location.php', { method:'POST', body: fd, credentials:'same-origin' })
 .then(r => r.json())
 .then(j => {
 if(j.ok){
 status.textContent = 'Location saved (' + lat.toFixed(4) + ', ' + lng.toFixed(4) + ') — pros will now be sorted by distance.';
 btn.innerHTML = '<i class="fa-solid fa-check"></i> Saved';
 btn.classList.remove('btn-primary');
 btn.classList.add('btn-outline');
 setTimeout(() => { btn.innerHTML = original.replace('Use my location','Update location'); btn.disabled = false; }, 1800);
 } else {
 status.textContent = 'Could not save location: ' + (j.error||'error');
 btn.innerHTML = original; btn.disabled = false;
 }
 })
 .catch(() => { status.textContent='Network error saving location.'; btn.innerHTML=original; btn.disabled=false; });
 }, function(err){
 status.textContent = 'Permission denied or unable to read GPS: ' + err.message;
 btn.innerHTML = original; btn.disabled = false;
 }, { enableHighAccuracy:true, timeout:10000, maximumAge:60000 });
 });
})();
</script>
</body></html>
