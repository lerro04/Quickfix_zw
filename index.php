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
$params = [];
$where = " WHERE u.verified=1 AND pp.is_available=1";
if($nearLoc !== ''){
    $where .= " AND (u.location LIKE ? OR pp.service_area LIKE ?)";
    $params[] = "%$nearLoc%";
    $params[] = "%$nearLoc%";
}
$nearby = $pdo->prepare("SELECT u.*,pp.* FROM professional_profiles pp JOIN users u ON pp.user_id=u.user_id".$where." ORDER BY pp.rating_avg DESC, pp.jobs_completed DESC LIMIT 10");
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
<div style="background:linear-gradient(135deg,#1a1a2e 0%,#c44d00 100%);padding-bottom:3rem">
  <nav style="display:flex;justify-content:space-between;align-items:center;padding:1.2rem 2rem;gap:1rem;flex-wrap:wrap">
    <div style="color:white;font-size:1.6rem;font-weight:800"><i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i> Quick<span style="color:#f96a15">Fix</span> ZW</div>
    <div style="display:flex;gap:0.5rem;flex-wrap:wrap">
      <a href="<?= BASE_URL ?>/browse.php" class="btn btn-outline" style="color:white;border-color:rgba(255,255,255,0.5)">Browse Services</a>
      <a href="<?= BASE_URL ?>/about.php" class="btn btn-outline" style="color:white;border-color:rgba(255,255,255,0.5)">About</a>
      <a href="<?= BASE_URL ?>/support.php" class="btn btn-outline" style="color:white;border-color:rgba(255,255,255,0.5)">Contact</a>
      <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline" style="color:white;border-color:rgba(255,255,255,0.5)">Login</a>
      <a href="<?= BASE_URL ?>/login.php?mode=register" class="btn btn-primary">Get Started</a>
    </div>
  </nav>

  <div style="max-width:1100px;margin:2rem auto 0;padding:1rem 2rem;color:white;text-align:center">
    <h1 style="font-size:2.6rem;font-weight:800;line-height:1.2;margin-bottom:1rem">
      Find a trusted local <span style="color:#f96a15">handyman, plumber or electrician</span> in minutes.
    </h1>
    <p style="font-size:1.05rem;opacity:0.9;max-width:760px;margin:0 auto 1.5rem;line-height:1.7">
      QuickFix ZW connects Zimbabweans with verified local professionals. Search a service or pick from providers near you, message them directly, and book the job &mdash; or post a job and let pros bid for it.
    </p>
  </div>

  <form method="GET" action="<?= BASE_URL ?>/browse.php" style="max-width:780px;margin:0 auto;padding:0 1.2rem">
    <div style="background:white;border-radius:14px;padding:0.8rem;display:flex;gap:0.6rem;flex-wrap:wrap;box-shadow:0 14px 40px rgba(0,0,0,0.25)">
      <input type="text" name="q" placeholder="Search a service or a provider name (e.g. plumber, Tafadzwa)" style="flex:2;min-width:220px;border:none;outline:none;padding:0.85rem 1rem;font-size:0.95rem;border-radius:10px;background:#f6f6fa">
      <input type="text" name="loc" placeholder="Location (optional)" style="flex:1;min-width:160px;border:none;outline:none;padding:0.85rem 1rem;font-size:0.95rem;border-radius:10px;background:#f6f6fa">
      <button type="submit" class="btn btn-primary" style="padding:0.85rem 1.6rem;font-weight:700"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
    </div>
    <div style="text-align:center;color:rgba(255,255,255,0.85);font-size:0.85rem;margin-top:0.7rem">
      Tip: leave location blank to search everywhere, or type a city like <em>Harare</em> or <em>Chinhoyi</em> to narrow down.
    </div>
  </form>
</div>

<!-- How it works -->
<div class="container" style="margin-top:2.5rem">
  <h2 style="text-align:center;font-size:1.6rem;margin-bottom:0.5rem">How QuickFix ZW works</h2>
  <p style="text-align:center;color:var(--gray);margin-bottom:1.5rem">Two ways to use the platform &mdash; whichever suits you</p>
  <div class="card-grid" style="grid-template-columns:repeat(auto-fit,minmax(260px,1fr))">
    <div class="section-card">
      <h3 style="margin-bottom:0.6rem;color:var(--primary)"><?=icon('user')?> If you need a service</h3>
      <ol style="line-height:1.9;padding-left:1.2rem;margin:0">
        <li>Search a service or browse providers near you</li>
        <li>Compare profiles, ratings and rates</li>
        <li>Message the provider and confirm a booking</li>
        <li>Pay only after the job is done to your satisfaction</li>
      </ol>
    </div>
    <div class="section-card">
      <h3 style="margin-bottom:0.6rem;color:var(--primary)"><?=icon('briefcase')?> If you offer a service</h3>
      <ol style="line-height:1.9;padding-left:1.2rem;margin:0">
        <li>Create a free professional account and get verified</li>
        <li>List your trade, rate and service area</li>
        <li>Bid on open jobs or accept direct bookings</li>
        <li>Get paid once the client confirms the work is complete</li>
      </ol>
    </div>
  </div>
</div>

<!-- Service providers near you -->
<div class="container" style="margin-top:2.5rem">
  <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:1rem;margin-bottom:1rem">
    <div>
      <h2 style="font-size:1.5rem;margin-bottom:0.2rem"><?=icon('location-dot')?> Service providers near you</h2>
      <p style="color:var(--gray);font-size:0.92rem;margin:0">
        <?php if($nearLoc !== ''): ?>
          Showing top providers in <strong><?=htmlspecialchars($nearLoc)?></strong>
        <?php else: ?>
          Top-rated verified professionals on QuickFix ZW. Enter a location to see who's closest to you.
        <?php endif; ?>
      </p>
    </div>
    <form method="GET" style="display:flex;gap:0.5rem;flex-wrap:wrap">
      <input type="text" name="near" value="<?=htmlspecialchars($nearLoc)?>" placeholder="Your city or area" class="form-control" style="min-width:200px">
      <button type="submit" class="btn btn-primary"><?=icon('location-crosshairs')?> Show nearby</button>
    </form>
  </div>

  <?php if(empty($nearbyPros)): ?>
    <div class="alert alert-info">No verified professionals match that location yet. <a href="<?= BASE_URL ?>/browse.php" style="color:var(--primary);font-weight:700">Browse all providers</a>.</div>
  <?php else: ?>
  <div class="card-grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr))">
    <?php foreach($nearbyPros as $p): ?>
    <div class="pro-card">
      <div class="pro-avatar"><?=strtoupper(substr($p['full_name'],0,1))?></div>
      <div class="pro-name"><?=htmlspecialchars($p['full_name'])?></div>
      <div class="pro-trade"><?=tradeIcon($p['trade'])?> <?=$p['trade']?></div>
      <div class="pro-meta"><?=icon('location-dot')?> <?=htmlspecialchars($p['location'] ?: 'Zimbabwe')?></div>
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
<div class="container" style="margin-top:2.5rem;margin-bottom:3rem">
  <h2 style="text-align:center;font-size:1.5rem;margin-bottom:1.2rem">Browse by trade</h2>
  <div style="display:flex;flex-wrap:wrap;gap:0.7rem;justify-content:center">
    <?php foreach($TRADES as $t): ?>
      <a href="<?= BASE_URL ?>/browse.php?trade=<?=urlencode($t)?>" style="background:#fff;border:1px solid var(--border);border-radius:25px;padding:0.6rem 1.2rem;font-size:0.9rem;color:var(--dark);text-decoration:none;display:inline-flex;align-items:center;gap:0.4rem">
        <?=tradeIcon($t)?> <?=$t?>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
