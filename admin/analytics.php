<?php
require_once '../includes/auth.php';
requireRole('admin');
require_once '../includes/db.php';
require_once '../includes/functions.php';

$grossRevenue = (float)$pdo->query("SELECT COALESCE(SUM(agreed_amount),0) as t FROM bookings WHERE payment_status='released'")->fetch()['t'];
$todayGrossRevenue = (float)$pdo->query("SELECT COALESCE(SUM(agreed_amount),0) as t FROM bookings WHERE payment_status='released' AND DATE(completed_at)=CURDATE()")->fetch()['t'];
$platformRevenue = $grossRevenue * PLATFORM_COMMISSION_RATE;
$todayPlatformRevenue = $todayGrossRevenue * PLATFORM_COMMISSION_RATE;
$topTrades = $pdo->query("SELECT pp.trade,COUNT(b.booking_id) as bookings,COALESCE(SUM(b.agreed_amount),0) as revenue FROM bookings b JOIN professional_profiles pp ON b.professional_id=pp.user_id GROUP BY pp.trade ORDER BY bookings DESC")->fetchAll();
$topPros = $pdo->query("SELECT u.full_name,pp.trade,pp.rating_avg,pp.jobs_completed,COALESCE(SUM(b.agreed_amount),0) as earned FROM users u JOIN professional_profiles pp ON u.user_id=pp.user_id LEFT JOIN bookings b ON u.user_id=b.professional_id AND b.payment_status='released' GROUP BY u.user_id ORDER BY earned DESC LIMIT 5")->fetchAll();
$topLocations = $pdo->query("SELECT location, COUNT(*) as count FROM job_requests GROUP BY location ORDER BY count DESC LIMIT 5")->fetchAll();
$monthlyStats = $pdo->query("SELECT DATE_FORMAT(created_at,'%b %Y') as month, COUNT(*) as bookings, SUM(agreed_amount) as revenue FROM bookings WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY month ORDER BY created_at ASC")->fetchAll();
$maxBook = max(array_column($topTrades, 'bookings')) ?: 1;
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Analytics - QuickFix ZW Admin</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head><body>
<?php include '../includes/navbar.php'; ?>
<div class="container"><br>
<div class="page-title"><?=icon('chart-line')?> Platform Analytics</div>
<div class="card-grid" style="margin-bottom:1.5rem">
  <div class="stat-card" style="border-left-color:var(--success)"><div class="stat-icon" style="background:var(--success)"><?=icon('coins')?></div><div class="stat-info"><h3>$<?=number_format($platformRevenue,2)?></h3><p>All-Time Platform Revenue</p><div class="metric-note">Gross booking value: $<?=number_format($grossRevenue,2)?></div></div></div>
  <div class="stat-card" style="border-left-color:var(--accent)"><div class="stat-icon" style="background:var(--accent)"><?=icon('calendar-day')?></div><div class="stat-info"><h3>$<?=number_format($todayPlatformRevenue,2)?></h3><p>Today's Platform Revenue</p><div class="metric-note">Today's gross bookings: $<?=number_format($todayGrossRevenue,2)?></div></div></div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem">
  <div class="card">
    <div class="card-header"><?=icon('wrench')?> Bookings by Trade</div>
    <div class="card-body">
      <?php foreach($topTrades as $t): ?>
      <div style="margin-bottom:0.8rem">
        <div style="display:flex;justify-content:space-between;margin-bottom:0.3rem;font-size:0.88rem">
          <span><?=tradeIcon($t['trade'])?> <strong><?=$t['trade']?></strong></span>
          <span><?=$t['bookings']?> bookings Â· <span style="color:var(--success)">$<?=number_format($t['revenue'],2)?></span></span>
        </div>
        <div style="background:var(--border);border-radius:20px;height:8px"><div style="background:linear-gradient(90deg,var(--primary),var(--accent));height:100%;border-radius:20px;width:<?=($t['bookings'] / $maxBook) * 100?>%"></div></div>
      </div>
      <?php endforeach; ?>
      <?php if(empty($topTrades)): ?><p style="color:#999;text-align:center">No bookings yet.</p><?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><?=icon('trophy')?> Top Professionals by Earnings</div>
    <div class="card-body">
      <table><thead><tr><th>Name</th><th>Trade</th><th>Rating</th><th>Jobs</th><th>Earned</th></tr></thead><tbody>
      <?php foreach($topPros as $p): ?>
      <tr>
        <td><strong><?=htmlspecialchars($p['full_name'])?></strong></td>
        <td><?=tradeIcon($p['trade'])?> <?=$p['trade']?></td>
        <td><?=number_format($p['rating_avg'],1)?> / 5</td>
        <td><?=$p['jobs_completed']?></td>
        <td style="color:var(--success);font-weight:700">$<?=number_format($p['earned'],2)?></td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($topPros)): ?><tr><td colspan="5" style="color:#999;text-align:center">No data yet.</td></tr><?php endif; ?>
      </tbody></table>
    </div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">
  <div class="card">
    <div class="card-header"><?=icon('location-dot')?> Top Job Locations</div>
    <div class="card-body">
      <table><thead><tr><th>Location</th><th>Jobs Posted</th></tr></thead><tbody>
      <?php foreach($topLocations as $l): ?>
      <tr><td><?=icon('location-dot')?> <?=$l['location']?></td><td><strong><?=$l['count']?></strong></td></tr>
      <?php endforeach; ?>
      <?php if(empty($topLocations)): ?><tr><td colspan="2" style="color:#999;text-align:center">No data yet.</td></tr><?php endif; ?>
      </tbody></table>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><?=icon('chart-column')?> Monthly Booking Trends</div>
    <div class="card-body">
      <table><thead><tr><th>Month</th><th>Bookings</th><th>Gross Value</th></tr></thead><tbody>
      <?php foreach($monthlyStats as $m): ?>
      <tr><td><?=$m['month']?></td><td><?=$m['bookings']?></td><td style="color:var(--success)">$<?=number_format($m['revenue'],2)?></td></tr>
      <?php endforeach; ?>
      <?php if(empty($monthlyStats)): ?><tr><td colspan="3" style="color:#999;text-align:center">No data yet.</td></tr><?php endif; ?>
      </tbody></table>
    </div>
  </div>
</div>
</div>
<?php include '../includes/footer.php'; ?>
</body></html>
