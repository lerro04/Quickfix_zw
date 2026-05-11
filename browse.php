<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/mailer.php';

$canBook = isset($_SESSION['role']) && $_SESSION['role'] === 'client';
$uid = $_SESSION['user_id'] ?? null;
$msg = '';
try { $TRADES = $pdo->query("SELECT name FROM trades WHERE is_active=1 ORDER BY name")->fetchAll(PDO::FETCH_COLUMN); }
catch(PDOException $e){ /* keep $TRADES from functions.php */ }

if($_SERVER['REQUEST_METHOD'] === 'POST' && $canBook && isset($_POST['direct_book'])){
 $proId = (int)$_POST['pro_id'];
 $amount = (float)$_POST['amount'];
 $sched = $_POST['scheduled_date'] ?? null;
 $note = htmlspecialchars(trim($_POST['note'] ?? ''));
 if($proId > 0 && $amount > 0 && $sched){
 $pdo->prepare("INSERT INTO bookings (client_id,professional_id,agreed_amount,scheduled_date,status,payment_status) VALUES (?,?,?,?,?,?)")->execute([$uid,$proId,$amount,$sched,'confirmed','pending']);
 $bookingId = (int)$pdo->lastInsertId();
 notifyProNewBooking($pdo, $bookingId);
 $msg = 'Booking confirmed. The professional has been notified.';
 }
}

$q = trim($_GET['q'] ?? '');
$trade = $_GET['trade'] ?? '';
$loc = trim($_GET['loc'] ?? '');
$bookProviderId = (int)($_GET['book'] ?? 0);
$perPage = 12;
$currentPage = max(1, (int)($_GET['page'] ?? 1));

$userLat = is_numeric($_GET['lat'] ?? '') ? (float)$_GET['lat'] : null;
$userLng = is_numeric($_GET['lng'] ?? '') ? (float)$_GET['lng'] : null;
if($userLat === null && $userLng === null && isset($_SESSION['user_id'])){
 try {
 $sl = $pdo->prepare("SELECT latitude, longitude FROM users WHERE user_id=?");
 $sl->execute([(int)$_SESSION['user_id']]);
 $row = $sl->fetch();
 if($row && $row['latitude'] !== null && $row['longitude'] !== null){
 $userLat = (float)$row['latitude'];
 $userLng = (float)$row['longitude'];
 }
 } catch(PDOException $e){}
}
$useGps = $userLat !== null && $userLng !== null;

$where = " WHERE u.verified=1 AND pp.is_available=1";
$params = [];
if($q !== ''){
 $where .= " AND (u.full_name LIKE ? OR pp.trade LIKE ? OR pp.bio LIKE ? OR pp.service_area LIKE ?)";
 $params[] = "%$q%";
 $params[] = "%$q%";
 $params[] = "%$q%";
 $params[] = "%$q%";
}
if($trade !== ''){
 $where .= " AND pp.trade=?";
 $params[] = $trade;
}
if($loc !== ''){
 $where .= " AND (u.location LIKE ? OR pp.service_area LIKE ?)";
 $params[] = "%$loc%";
 $params[] = "%$loc%";
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM professional_profiles pp JOIN users u ON pp.user_id=u.user_id".$where);
$countStmt->execute($params);
$totalPros = (int)$countStmt->fetchColumn();
$pagination = paginationData($totalPros, $perPage, $currentPage);

$select = "SELECT u.*,pp.*";
$queryParams = $params;
if($useGps){
 $select .= ", (6371 * acos(LEAST(1, cos(radians(?)) * cos(radians(pp.latitude)) * cos(radians(pp.longitude) - radians(?)) + sin(radians(?)) * sin(radians(pp.latitude))))) AS distance_km";
 array_unshift($queryParams, $userLat, $userLng, $userLat);
}
$order = $useGps ? " ORDER BY (pp.latitude IS NULL) ASC, distance_km ASC, pp.rating_avg DESC" : " ORDER BY pp.rating_avg DESC, pp.jobs_completed DESC";
$query = $select." FROM professional_profiles pp JOIN users u ON pp.user_id=u.user_id".$where.$order." LIMIT ".$perPage." OFFSET ".$pagination['offset'];
$stmt = $pdo->prepare($query);
$stmt->execute($queryParams);
$pros = $stmt->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Browse Professionals - QuickFix ZW</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head><body>
<?php include 'includes/navbar.php'; ?>
<div class="container"><br>
<div class="page-title"><?=icon('magnifying-glass')?> Browse Professionals</div>
<?php if($msg): ?><div class="alert alert-success"><?=$msg?></div><?php endif; ?>
<div class="search-bar">
 <form method="GET" style="display:flex;gap:0.8rem;flex-wrap:wrap;width:100%;align-items:flex-end">
 <div class="form-group" style="margin:0;flex:2;min-width:220px">
 <label class="form-label">Search service or provider</label>
 <input type="text" name="q" class="form-control" placeholder="e.g. plumber, painting, Tafadzwa" value="<?=htmlspecialchars($q)?>">
 </div>
 <div class="form-group" style="margin:0;flex:1;min-width:160px">
 <label class="form-label">Filter by location</label>
 <input type="text" name="loc" class="form-control" placeholder="e.g. Chinhoyi, Harare" value="<?=htmlspecialchars($loc)?>">
 </div>
 <div class="form-group" style="margin:0;flex:1;min-width:160px">
 <label class="form-label">Filter by trade</label>
 <select name="trade" class="form-select">
 <option value="">All trades</option>
 <?php foreach($TRADES as $t): ?>
 <option value="<?=$t?>" <?=$trade === $t ? 'selected' : ''?>><?=$t?></option>
 <?php endforeach; ?>
 </select>
 </div>
 <button type="submit" class="btn btn-primary" style="margin-bottom:0"><?=icon('magnifying-glass')?> Search</button>
 <a href="<?= BASE_URL ?>/browse.php" class="btn btn-outline" style="margin-bottom:0">Clear</a>
 </form>
</div>

<div class="section-card" style="padding-bottom:1rem;display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
 <div>
 <p style="color:var(--gray)">
 <?=$totalPros?> professional(s) found
 <?php if($q !== ''): ?> matching "<strong><?=htmlspecialchars($q)?></strong>"<?php endif; ?>
 <?php if($loc !== ''): ?> in <strong><?=htmlspecialchars($loc)?></strong><?php endif; ?>
 <?php if($useGps): ?> &middot; <span style="color:var(--primary);font-weight:600"><?=icon('location-crosshairs')?> sorted by distance from you</span><?php endif; ?>
 </p>
 <p class="metric-note">Search by service (e.g. plumbing) or by a provider's name. Use the location field to filter to your area.</p>
 </div>
 <?php if(!$useGps): ?>
 <button id="use-loc-btn" type="button" class="btn btn-outline btn-sm"><?=icon('location-crosshairs')?> Use my location</button>
 <?php endif; ?>
</div>

<div class="card-grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr))">
<?php foreach($pros as $p): ?>
 <div class="pro-card">
 <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:0.5rem">
 <div class="pro-avatar"><?=strtoupper(substr($p['full_name'],0,1))?></div>
 <?php if($useGps && isset($p['distance_km']) && $p['distance_km'] !== null): ?>
 <span style="background:#fff7ee;color:var(--primary);border:1px solid #ffd6a8;border-radius:99px;padding:0.25rem 0.6rem;font-size:0.74rem;font-weight:700"><?=icon('location-crosshairs')?> <?=number_format((float)$p['distance_km'],1)?> km</span>
 <?php endif; ?>
 </div>
 <div class="pro-name"><?=htmlspecialchars($p['full_name'])?></div>
 <div class="pro-trade"><?=tradeIcon($p['trade'])?> <?=$p['trade']?></div>
 <div class="pro-meta"><?=icon('location-dot')?> <?=htmlspecialchars($p['location'] ?: 'Zimbabwe')?></div>
 <div class="pro-meta" style="color:#999;font-size:0.78rem"><?=icon('lock')?> Phone shared after a booking is confirmed</div>
 <div class="pro-meta"><?=icon('circle-check')?> <?=$p['jobs_completed']?> jobs completed</div>
 <div style="margin:0.3rem 0;font-size:0.9rem"><?=stars($p['rating_avg'])?> <span style="color:#999;font-size:0.8rem">(<?=$p['total_reviews']?> reviews)</span></div>
 <?php if($p['service_area']): ?><div class="pro-meta"><?=icon('map')?> <?=htmlspecialchars($p['service_area'])?></div><?php endif; ?>
 <div class="pro-rate">$<?=number_format($p['hourly_rate'],2)?>/hr</div>
 <div style="margin-top:0.3rem;font-size:0.82rem;color:var(--gray);line-height:1.5"><?=htmlspecialchars(substr((string)$p['bio'],0,120))?><?=strlen((string)$p['bio']) > 120 ? '...' : ''?></div>
 <div style="display:flex;gap:0.5rem;margin-top:1rem">
 <?php if($canBook): ?>
 <button type="button" onclick="openBook(<?=$p['user_id']?>,'<?=htmlspecialchars($p['full_name'], ENT_QUOTES)?>',<?=$p['hourly_rate']?>)" class="btn btn-primary btn-sm" style="flex:1"><?=icon('calendar-plus')?> Book Now</button>
 <?php else: ?>
 <a href="<?= BASE_URL ?>/provider.php?id=<?=$p['user_id']?>" class="btn btn-primary btn-sm" style="flex:1"><?=icon('id-card')?> View Details</a>
 <?php endif; ?>
 <a href="<?= BASE_URL ?>/provider.php?id=<?=$p['user_id']?>" class="btn btn-outline btn-sm"><?=icon('circle-info')?></a>
 <?php if($canBook): ?>
 <a href="<?= BASE_URL ?>/messages.php?with=<?=$p['user_id']?>" class="btn btn-outline btn-sm"><?=icon('comments')?></a>
 <?php else: ?>
 <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline btn-sm"><?=icon('right-to-bracket')?></a>
 <?php endif; ?>
 </div>
 </div>
<?php endforeach; ?>
</div>

<?php if(empty($pros)): ?><div class="alert alert-info">No professionals found for that search. Try a different trade or location.</div><?php endif; ?>
<?=renderPagination($pagination['page'], $pagination['total_pages'])?>
</div>
<?php if($canBook): ?>
<div class="modal-overlay" id="book-modal">
 <div class="modal-box">
 <div class="modal-header">
 <?=icon('calendar-plus')?> Book Professional
 <span class="modal-close" onclick="document.getElementById('book-modal').classList.remove('show')">&times;</span>
 </div>
 <div class="modal-body">
 <p id="book-pro-name" style="font-weight:700;font-size:1.05rem;margin-bottom:1rem;color:var(--primary)"></p>
 <form method="POST">
 <input type="hidden" name="direct_book" value="1">
 <input type="hidden" name="pro_id" id="book-pro-id">
 <div class="form-group">
 <label class="form-label">Describe the job *</label>
 <textarea name="note" class="form-control" rows="3" placeholder="e.g. Fix leaking bathroom pipe under sink..." required style="resize:vertical"></textarea>
 </div>
 <div class="form-row">
 <div class="form-group">
 <label class="form-label">Agreed Amount (USD) *</label>
 <input type="number" name="amount" id="book-amount" class="form-control" step="0.50" min="1" required>
 </div>
 <div class="form-group">
 <label class="form-label">Preferred Date *</label>
 <input type="date" name="scheduled_date" class="form-control" min="<?=date('Y-m-d')?>" required>
 </div>
 </div>
 <button type="submit" class="btn btn-primary btn-block"><?=icon('check')?> Confirm Booking</button>
 </form>
 </div>
 </div>
</div>
<script>
function openBook(id, name, rate){
 document.getElementById('book-pro-id').value = id;
 document.getElementById('book-pro-name').textContent = 'Booking: ' + name;
 document.getElementById('book-amount').value = rate;
 document.getElementById('book-modal').classList.add('show');
}
<?php if($bookProviderId > 0): ?>
openBook(<?=$bookProviderId?>, 'Selected Professional', 5);
<?php endif; ?>
</script>
<?php endif; ?>
<script>
(function(){
 const btn = document.getElementById('use-loc-btn');
 if(!btn) return;
 btn.addEventListener('click', function(){
 if(!navigator.geolocation){ alert('Your browser does not support GPS.'); return; }
 btn.disabled = true;
 btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Locating...';
 navigator.geolocation.getCurrentPosition(function(pos){
 const url = new URL(window.location.href);
 url.searchParams.set('lat', pos.coords.latitude.toFixed(6));
 url.searchParams.set('lng', pos.coords.longitude.toFixed(6));
 <?php if(isset($_SESSION['user_id'])): ?>
 const fd = new FormData();
 fd.append('latitude', pos.coords.latitude);
 fd.append('longitude', pos.coords.longitude);
 fd.append('location_label', '');
 fetch('<?= BASE_URL ?>/api/save_location.php', { method:'POST', body: fd, credentials:'same-origin' })
 .finally(() => { window.location.href = url.toString(); });
 <?php else: ?>
 window.location.href = url.toString();
 <?php endif; ?>
 }, function(err){
 alert('Could not read your location: ' + err.message);
 btn.disabled = false;
 btn.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i> Use my location';
 }, { enableHighAccuracy:true, timeout:10000 });
 });
})();
</script>
<?php include 'includes/footer.php'; ?>
</body></html>
