<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

if(isset($_SESSION['user_id'])){
 if($_SESSION['role'] === 'admin') header('Location: '.BASE_URL.'/admin/dashboard.php');
 elseif($_SESSION['role'] === 'professional') header('Location: '.BASE_URL.'/professional/dashboard.php');
 else header('Location: '.BASE_URL.'/client/dashboard.php');
 exit;
}

$nearLoc = trim($_GET['near'] ?? '');
$userLat = is_numeric($_GET['lat'] ?? '') ? (float)$_GET['lat'] : null;
$userLng = is_numeric($_GET['lng'] ?? '') ? (float)$_GET['lng'] : null;
$useGps  = $userLat !== null && $userLng !== null;

$params = [];
$select = "SELECT u.*,pp.*";
if($useGps){
 $select .= ", (6371 * acos(LEAST(1, cos(radians(?)) * cos(radians(pp.latitude)) * cos(radians(pp.longitude) - radians(?)) + sin(radians(?)) * sin(radians(pp.latitude))))) AS distance_km";
 $params[] = $userLat;
 $params[] = $userLng;
 $params[] = $userLat;
}
$where = " FROM professional_profiles pp JOIN users u ON pp.user_id=u.user_id WHERE u.verified=1 AND pp.is_available=1";
if($useGps){
 $where .= " AND pp.latitude IS NOT NULL AND pp.longitude IS NOT NULL";
}
if($nearLoc !== ''){
 $where .= " AND (u.location LIKE ? OR pp.service_area LIKE ?)";
 $params[] = "%$nearLoc%";
 $params[] = "%$nearLoc%";
}
$order = $useGps ? " ORDER BY distance_km ASC" : " ORDER BY pp.rating_avg DESC, pp.jobs_completed DESC";
$nearby = $pdo->prepare($select.$where.$order." LIMIT 10");
$nearby->execute($params);
$nearbyPros = $nearby->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>QuickFix ZW - Find Trusted Home Service Professionals in Zimbabwe</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
<nav style="display:flex;justify-content:space-between;align-items:center;padding:1rem 1.5rem;gap:1rem;flex-wrap:wrap;background:#ffffff;border-bottom:1px solid var(--border);box-shadow:0 1px 3px rgba(15,15,18,0.04);position:sticky;top:0;z-index:1000">
 <div style="font-size:1.15rem;font-weight:800;color:var(--dark);letter-spacing:-0.01em"><i class="fa-solid fa-screwdriver-wrench" style="color:var(--primary)" aria-hidden="true"></i> Quick<span style="color:var(--primary)">Fix</span> ZW</div>
 <div style="display:flex;gap:0.3rem;flex-wrap:wrap;align-items:center">
 <a href="<?= BASE_URL ?>/browse.php" class="nav-link">Find pros</a>
 <a href="<?= BASE_URL ?>/about.php" class="nav-link">About</a>
 <a href="<?= BASE_URL ?>/support.php" class="nav-link">Contact</a>
 <a href="<?= BASE_URL ?>/login.php" class="nav-link">Sign in</a>
 <a href="<?= BASE_URL ?>/login.php?mode=register" class="btn btn-secondary btn-sm" style="margin-left:0.3rem">Get started</a>
 </div>
</nav>

<section style="background:#ffffff;padding:5rem 1.5rem 3rem;border-bottom:1px solid var(--border);position:relative;overflow:hidden">
 <!-- Decorative corner blobs (TaskRabbit-style) -->
 <div aria-hidden="true" style="position:absolute;top:-160px;left:-180px;width:420px;height:420px;border-radius:50%;background:radial-gradient(circle at 30% 30%,#ffe2c2 0%,#ffd29b 60%,transparent 75%);pointer-events:none"></div>
 <div aria-hidden="true" style="position:absolute;top:-80px;right:-140px;width:380px;height:380px;border-radius:50%;background:radial-gradient(circle at 70% 30%,#ffe2c2 0%,#ffc890 70%,transparent 80%);pointer-events:none"></div>
 <div aria-hidden="true" style="position:absolute;top:60px;right:60px;width:140px;height:140px;border-radius:50%;border:6px solid #f5a623;opacity:0.85;pointer-events:none"></div>
 <div aria-hidden="true" style="position:absolute;top:140px;right:30px;width:80px;height:6px;background:repeating-linear-gradient(90deg,var(--primary) 0,var(--primary) 6px,transparent 6px,transparent 14px);pointer-events:none"></div>
 <div aria-hidden="true" style="position:absolute;bottom:30px;left:40px;display:grid;grid-template-columns:repeat(5,6px);gap:8px;opacity:0.4;pointer-events:none">
 <?php for($i=0;$i<25;$i++): ?><span style="width:6px;height:6px;border-radius:50%;background:#1a1a2e"></span><?php endfor; ?>
 </div>

 <div style="max-width:840px;margin:0 auto;text-align:center;position:relative;z-index:2">
 <div style="display:inline-flex;align-items:center;gap:0.4rem;background:#fff7ee;border:1px solid #ffd6a8;border-radius:99px;padding:0.4rem 0.9rem;font-size:0.78rem;color:#9a3d00;font-weight:600;margin-bottom:1.4rem">
 <span style="width:6px;height:6px;background:var(--primary);border-radius:50%;display:inline-block"></span>
 Trusted handymen across Zimbabwe
 </div>

 <h1 style="font-size:3.4rem;font-weight:800;line-height:1.08;letter-spacing:-0.035em;margin-bottom:0.8rem;color:var(--dark)">
 Book trusted help<br>for home jobs
 </h1>
 <p style="font-size:1.05rem;color:var(--gray);max-width:520px;margin:0 auto 2rem;line-height:1.6">
 Plumb, wire, paint or fix &mdash; pick a verified local pro and book in minutes.
 </p>

 <!-- TaskRabbit-style rounded search pill -->
 <form method="GET" action="<?= BASE_URL ?>/browse.php" style="max-width:640px;margin:0 auto">
 <div style="background:#ffffff;border:2px solid var(--dark);border-radius:99px;padding:0.35rem 0.35rem 0.35rem 1.5rem;display:flex;gap:0.4rem;align-items:center;box-shadow:0 10px 30px rgba(15,15,18,0.08)">
 <i class="fa-solid fa-magnifying-glass" style="color:var(--gray);font-size:0.95rem"></i>
 <input type="text" name="q" placeholder="What do you need help with?" style="flex:1;min-width:0;border:0;outline:none;padding:0.85rem 0.4rem;font-size:1rem;background:transparent;color:var(--dark);font-family:inherit">
 <button type="submit" class="btn btn-primary" style="padding:0.7rem 1.6rem;border-radius:99px;font-size:0.95rem">Search</button>
 </div>
 <div style="margin-top:0.6rem;font-size:0.82rem;color:var(--gray)">Tip: try "plumber Harare" or "electrician Chinhoyi"</div>
 </form>

 <!-- Trade icons row (TaskRabbit-style category tray) -->
 <div style="margin-top:3rem;border-top:1px solid var(--border);padding-top:2rem">
 <div style="display:flex;justify-content:center;gap:2.5rem;flex-wrap:wrap">
 <?php $heroTrades = ['Plumbing'=>'wrench','Electrical'=>'bolt','Painting'=>'paint-roller','Carpentry'=>'hammer','Roofing'=>'house','Tiling'=>'border-all','Welding'=>'fire-flame-curved','Landscaping'=>'leaf']; ?>
 <?php foreach($heroTrades as $tname => $ic): ?>
 <a href="<?= BASE_URL ?>/browse.php?trade=<?=urlencode($tname)?>" style="display:flex;flex-direction:column;align-items:center;gap:0.4rem;text-decoration:none;color:var(--gray);font-size:0.8rem;font-weight:600;transition:color 0.15s" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--gray)'">
 <div style="width:48px;height:48px;border-radius:14px;background:#fff7ee;border:1px solid #ffd6a8;display:flex;align-items:center;justify-content:center;color:var(--primary);font-size:1.15rem"><?=icon($ic)?></div>
 <span><?=$tname?></span>
 </a>
 <?php endforeach; ?>
 </div>
 </div>

 <!-- Subcategory pills (popular searches) -->
 <div style="margin-top:1.6rem;display:flex;flex-wrap:wrap;justify-content:center;gap:0.5rem">
 <?php $popular = ['Burst pipe repair','Geyser installation','Light fitting','Door repair','Wall painting','Roof leak']; ?>
 <?php foreach($popular as $term): ?>
 <a href="<?= BASE_URL ?>/browse.php?q=<?=urlencode($term)?>" style="background:#ffffff;border:1px solid var(--border-strong);border-radius:99px;padding:0.55rem 1.1rem;font-size:0.85rem;font-weight:600;color:var(--dark);text-decoration:none;transition:background 0.15s,border-color 0.15s" onmouseover="this.style.background='var(--bg)';this.style.borderColor='var(--dark)'" onmouseout="this.style.background='#ffffff';this.style.borderColor='var(--border-strong)'">
 <?=htmlspecialchars($term)?>
 </a>
 <?php endforeach; ?>
 </div>
 </div>
</section>

<style>
@media (max-width: 720px){
 section h1[style*="font-size:3.4rem"] { font-size:2.2rem !important; }
}
</style>

<!-- How it works -->
<div class="container" style="margin-top:3.5rem">
 <div style="text-align:center;margin-bottom:2rem">
 <h2 style="font-size:1.8rem;margin-bottom:0.4rem;letter-spacing:-0.02em;font-weight:700">How it works</h2>
 <p style="color:var(--gray);font-size:0.95rem">Two ways to use QuickFix ZW &mdash; whichever fits.</p>
 </div>
 <div class="card-grid" style="grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem">
 <div class="section-card" style="padding:1.6rem">
 <div style="width:40px;height:40px;background:var(--bg);border:1px solid var(--border);border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--primary);margin-bottom:0.9rem;font-size:1rem"><?=icon('user')?></div>
 <h3 style="margin-bottom:0.6rem;color:var(--dark);font-size:1.05rem;font-weight:700">If you need a service</h3>
 <ol style="line-height:1.8;padding-left:1.2rem;margin:0;color:var(--gray);font-size:0.92rem">
 <li>Search a service or browse pros near you</li>
 <li>Compare profiles, ratings, rates</li>
 <li>Message and confirm a booking</li>
 <li>Pay only when the job is done right</li>
 </ol>
 </div>
 <div class="section-card" style="padding:1.6rem">
 <div style="width:40px;height:40px;background:var(--bg);border:1px solid var(--border);border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--primary);margin-bottom:0.9rem;font-size:1rem"><?=icon('briefcase')?></div>
 <h3 style="margin-bottom:0.6rem;color:var(--dark);font-size:1.05rem;font-weight:700">If you offer a service</h3>
 <ol style="line-height:1.8;padding-left:1.2rem;margin:0;color:var(--gray);font-size:0.92rem">
 <li>Create a free pro account and get verified</li>
 <li>List your trade, rate and service area</li>
 <li>Bid on open jobs or take direct bookings</li>
 <li>Get paid when the client confirms completion</li>
 </ol>
 </div>
 </div>
</div>

<!-- Service providers near you -->
<div class="container" style="margin-top:3.5rem">
 <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:1rem;margin-bottom:1.4rem">
 <div>
 <h2 style="font-size:1.5rem;margin-bottom:0.3rem;letter-spacing:-0.02em;font-weight:700">Service providers near you</h2>
 <p style="color:var(--gray);font-size:0.92rem;margin:0">
 <?php if($nearLoc !== ''): ?>
 Top providers in <strong style="color:var(--dark)"><?=htmlspecialchars($nearLoc)?></strong>.
 <?php else: ?>
 Verified pros on QuickFix ZW. Enter a city to filter.
 <?php endif; ?>
 </p>
 </div>
 <form method="GET" id="nearby-form" style="display:flex;gap:0.4rem;flex-wrap:wrap;align-items:center">
 <input type="text" name="near" value="<?=htmlspecialchars($nearLoc)?>" placeholder="City or area" class="form-control" style="min-width:180px">
 <input type="hidden" name="lat" id="nearby-lat" value="<?=htmlspecialchars((string)($_GET['lat'] ?? ''))?>">
 <input type="hidden" name="lng" id="nearby-lng" value="<?=htmlspecialchars((string)($_GET['lng'] ?? ''))?>">
 <button type="submit" class="btn btn-secondary"><?=icon('magnifying-glass')?> Show</button>
 <button type="button" id="use-gps-btn" class="btn btn-outline" title="Use my current location"><?=icon('location-crosshairs')?> Use my location</button>
 </form>
 </div>
 <?php if($useGps): ?>
 <div style="margin-bottom:1rem;padding:0.6rem 0.9rem;background:var(--surface);border:1px solid var(--border);border-radius:10px;font-size:0.85rem;color:var(--gray);display:flex;align-items:center;gap:0.5rem">
 <?=icon('location-crosshairs')?> Sorted by distance from your location.
 <a href="<?= BASE_URL ?>/index.php<?= $nearLoc !== '' ? '?near='.urlencode($nearLoc) : '' ?>" style="margin-left:auto;color:var(--primary);font-weight:500"><?=icon('xmark')?> Clear</a>
 </div>
 <?php endif; ?>
 <script>
 (function(){
 var btn = document.getElementById('use-gps-btn');
 if(!btn) return;
 btn.addEventListener('click', function(){
 if(!navigator.geolocation){ alert('Geolocation not supported by this browser.'); return; }
 btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Locating...';
 navigator.geolocation.getCurrentPosition(function(pos){
 document.getElementById('nearby-lat').value = pos.coords.latitude.toFixed(8);
 document.getElementById('nearby-lng').value = pos.coords.longitude.toFixed(8);
 document.getElementById('nearby-form').submit();
 }, function(err){
 btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i> Use my location';
 alert('Could not get location: ' + (err.message || 'permission denied'));
 }, { enableHighAccuracy: true, timeout: 10000 });
 });
 })();
 </script>

 <?php if(empty($nearbyPros)): ?>
 <div class="alert alert-info">No verified professionals match that location yet. <a href="<?= BASE_URL ?>/browse.php" style="color:var(--primary);font-weight:700">Browse all providers</a>.</div>
 <?php else: ?>
 <div class="card-grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr))">
 <?php foreach($nearbyPros as $p): ?>
 <div class="pro-card">
 <div class="pro-avatar"><?=strtoupper(substr($p['full_name'],0,1))?></div>
 <div class="pro-name"><?=htmlspecialchars($p['full_name'])?></div>
 <div class="pro-trade"><?=tradeIcon($p['trade'])?> <?=$p['trade']?></div>
 <div class="pro-meta"><?=icon('location-dot')?> <?=htmlspecialchars($p['location'] ?: 'Zimbabwe')?><?php if(!empty($p['distance_km'])): ?> &middot; <strong style="color:var(--primary)"><?=number_format((float)$p['distance_km'], 1)?> km away</strong><?php endif; ?></div>
 <div class="pro-meta"><?=icon('circle-check')?> <?=$p['jobs_completed']?> jobs completed</div>
 <div style="margin:0.3rem 0;font-size:0.9rem"><?=stars($p['rating_avg'])?> <span style="color:#999;font-size:0.8rem">(<?=$p['total_reviews']?>)</span></div>
 <div class="pro-rate">$<?=number_format($p['hourly_rate'],2)?>/hr</div>
 <div style="display:flex;gap:0.5rem;margin-top:0.9rem">
 <a href="<?= BASE_URL ?>/provider.php?id=<?=$p['user_id']?>" class="btn btn-primary btn-sm" style="flex:1"><?=icon('id-card')?> View</a>
 <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline btn-sm"><?=icon('comments')?></a>
 </div>
 </div>
 <?php endforeach; ?>
 </div>
 <div style="text-align:center;margin-top:1.5rem">
 <a href="<?= BASE_URL ?>/browse.php<?= $nearLoc !== '' ? '?loc='.urlencode($nearLoc) : '' ?>" class="btn btn-outline">See all providers <?=icon('arrow-right')?></a>
 </div>
 <?php endif; ?>
</div>

<!-- Trade categories -->
<div class="container" style="margin-top:3.5rem;margin-bottom:4rem">
 <h2 style="text-align:center;font-size:1.5rem;margin-bottom:1.2rem;letter-spacing:-0.02em;font-weight:700">Browse by trade</h2>
 <div style="display:flex;flex-wrap:wrap;gap:0.5rem;justify-content:center">
 <?php foreach($TRADES as $t): ?>
 <a href="<?= BASE_URL ?>/browse.php?trade=<?=urlencode($t)?>" style="background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:0.55rem 1rem;font-size:0.88rem;color:var(--dark);text-decoration:none;display:inline-flex;align-items:center;gap:0.4rem;transition:border-color 0.15s,background 0.15s" onmouseover="this.style.borderColor='var(--border-strong)';this.style.background='var(--bg)'" onmouseout="this.style.borderColor='var(--border)';this.style.background='var(--surface)'">
 <?=tradeIcon($t)?> <?=$t?>
 </a>
 <?php endforeach; ?>
 </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
