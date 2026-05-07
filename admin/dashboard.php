<?php
require_once '../includes/auth.php';
requireRole('admin');
require_once '../includes/db.php';
require_once '../includes/functions.php';

$users = (int)$pdo->query("SELECT COUNT(*) as c FROM users")->fetch()['c'];
$pros = (int)$pdo->query("SELECT COUNT(*) as c FROM users WHERE role='professional'")->fetch()['c'];
$clients = (int)$pdo->query("SELECT COUNT(*) as c FROM users WHERE role='client'")->fetch()['c'];
$jobs = (int)$pdo->query("SELECT COUNT(*) as c FROM job_requests WHERE status='open'")->fetch()['c'];
$bookings = (int)$pdo->query("SELECT COUNT(*) as c FROM bookings")->fetch()['c'];
$grossBookings = (float)$pdo->query("SELECT COALESCE(SUM(agreed_amount),0) as t FROM bookings WHERE payment_status='released'")->fetch()['t'];
$platformRevenue = (float)$pdo->query("SELECT COALESCE(SUM(CASE WHEN platform_fee_amount IS NULL OR platform_fee_amount = 0 THEN ROUND(agreed_amount * ".(PLATFORM_COMMISSION_RATE * 100)." / 100, 2) ELSE platform_fee_amount END),0) as t FROM bookings WHERE payment_status='released'")->fetch()['t'];
$providerPayouts = (float)$pdo->query("SELECT COALESCE(SUM(CASE WHEN professional_payout IS NULL OR professional_payout = 0 THEN agreed_amount - ROUND(agreed_amount * ".(PLATFORM_COMMISSION_RATE * 100)." / 100, 2) ELSE professional_payout END),0) as t FROM bookings WHERE payment_status='released'")->fetch()['t'];
$pending = (int)$pdo->query("SELECT COUNT(*) as c FROM users WHERE verified=0 AND role='professional'")->fetch()['c'];
$disputes = (int)$pdo->query("SELECT COUNT(*) as c FROM bookings WHERE status='disputed'")->fetch()['c'];
$recent = $pdo->query("SELECT u.*,pp.trade FROM users u LEFT JOIN professional_profiles pp ON u.user_id=pp.user_id ORDER BY u.created_at DESC LIMIT 10")->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Dashboard - QuickFix ZW</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head><body>
<?php include '../includes/navbar.php'; ?>
<div class="container"><br>
<?php if($pending > 0): ?><div class="alert alert-warning"><?=icon('triangle-exclamation')?> <?=$pending?> professional(s) awaiting verification. <a href="<?= BASE_URL ?>/admin/users.php" style="color:var(--warning);font-weight:700">Verify now</a></div><?php endif; ?>
<?php if($disputes > 0): ?><div class="alert alert-danger"><?=icon('triangle-exclamation')?> <?=$disputes?> active dispute(s) need resolution. <a href="<?= BASE_URL ?>/admin/disputes.php" style="color:var(--danger);font-weight:700">Resolve now</a></div><?php endif; ?>

<div class="page-title"><?=icon('gauge-high')?> Admin Dashboard</div>
<div class="card-grid">
 <div class="stat-card"><div class="stat-icon"><?=icon('users')?></div><div class="stat-info"><h3><?=$users?></h3><p>Total Users</p></div></div>
 <div class="stat-card"><div class="stat-icon"><?=icon('user-gear')?></div><div class="stat-info"><h3><?=$pros?></h3><p>Professionals</p></div></div>
 <div class="stat-card"><div class="stat-icon"><?=icon('house-user')?></div><div class="stat-info"><h3><?=$clients?></h3><p>Clients</p></div></div>
 <div class="stat-card"><div class="stat-icon"><?=icon('briefcase')?></div><div class="stat-info"><h3><?=$jobs?></h3><p>Open Jobs</p></div></div>
 <div class="stat-card"><div class="stat-icon"><?=icon('calendar-days')?></div><div class="stat-info"><h3><?=$bookings?></h3><p>Total Bookings</p></div></div>
 <div class="stat-card" style="border-left-color:var(--accent)"><div class="stat-icon" style="background:linear-gradient(135deg,var(--accent),#e67e22)"><?=icon('coins')?></div><div class="stat-info"><h3>$<?=number_format($platformRevenue,2)?></h3><p>Platform Revenue</p><div class="metric-note">Gross booking value: $<?=number_format($grossBookings,2)?></div><div class="metric-note">Provider payouts: $<?=number_format($providerPayouts,2)?></div></div></div>
</div>

<div style="display:flex;gap:1rem;flex-wrap:wrap;margin:0.5rem 0 1.5rem">
 <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-primary"><?=icon('users')?> Manage Users</a>
 <a href="<?= BASE_URL ?>/admin/jobs.php" class="btn btn-secondary"><?=icon('briefcase')?> All Jobs</a>
 <a href="<?= BASE_URL ?>/admin/bookings.php" class="btn btn-secondary"><?=icon('calendar-days')?> All Bookings</a>
 <a href="<?= BASE_URL ?>/admin/disputes.php" class="btn btn-danger"><?=icon('scale-balanced')?> Disputes</a>
 <a href="<?= BASE_URL ?>/admin/analytics.php" class="btn btn-success"><?=icon('chart-line')?> Analytics</a>
</div>

<div class="card">
 <div class="card-header"><?=icon('user-plus')?> Recent Registrations</div>
 <div class="card-body">
 <div class="table-wrap"><table>
 <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Trade</th><th>Location</th><th>Verified</th><th>Joined</th></tr></thead>
 <tbody>
 <?php foreach($recent as $u): ?>
 <tr>
 <td><strong><?=htmlspecialchars($u['full_name'])?></strong></td>
 <td><?=htmlspecialchars($u['email'])?></td>
 <td><span class="badge badge-<?=$u['role']==='admin'?'danger':($u['role']==='professional'?'primary':'success')?>"><?=ucfirst($u['role'])?></span></td>
 <td><?=$u['trade'] ?? '-'?></td>
 <td><?=$u['location'] ?? '-'?></td>
 <td><?=$u['verified'] ? '<span class="badge badge-success">Verified</span>' : '<span class="badge badge-warning">Pending</span>'?></td>
 <td style="font-size:0.82rem;color:#999"><?=timeAgo($u['created_at'])?></td>
 </tr>
 <?php endforeach; ?>
 </tbody>
 </table></div>
 </div>
</div>
</div>
<?php include '../includes/footer.php'; ?>
</body></html>
