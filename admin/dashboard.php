<?php
require_once '../includes/auth.php';
requireRole('admin');
require_once '../includes/db.php';
require_once '../includes/functions.php';
$users    = $pdo->query("SELECT COUNT(*) as c FROM users")->fetch()['c'];
$pros     = $pdo->query("SELECT COUNT(*) as c FROM users WHERE role='professional'")->fetch()['c'];
$clients  = $pdo->query("SELECT COUNT(*) as c FROM users WHERE role='client'")->fetch()['c'];
$jobs     = $pdo->query("SELECT COUNT(*) as c FROM job_requests WHERE status='open'")->fetch()['c'];
$bookings = $pdo->query("SELECT COUNT(*) as c FROM bookings")->fetch()['c'];
$revenue  = $pdo->query("SELECT COALESCE(SUM(agreed_amount),0) as t FROM bookings WHERE payment_status='released'")->fetch()['t'];
$pending  = $pdo->query("SELECT COUNT(*) as c FROM users WHERE verified=0")->fetch()['c'];
$disputes = $pdo->query("SELECT COUNT(*) as c FROM bookings WHERE status='disputed'")->fetch()['c'];
$recent   = $pdo->query("SELECT u.*,pp.trade FROM users u LEFT JOIN professional_profiles pp ON u.user_id=pp.user_id ORDER BY u.created_at DESC LIMIT 10")->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Dashboard — QuickFix ZW</title>
<link rel="stylesheet" href="/quickfix/css/style.css">
</head><body>
<?php include '../includes/navbar.php'; ?>
<div class="container"><br>
<?php if($pending>0): ?><div class="alert alert-warning">⚠️ <?=$pending?> professional(s) awaiting verification. <a href="/quickfix/admin/users.php" style="color:var(--warning);font-weight:700">Verify now →</a></div><?php endif; ?>
<?php if($disputes>0): ?><div class="alert alert-danger">⚠️ <?=$disputes?> active dispute(s) need resolution. <a href="/quickfix/admin/disputes.php" style="color:var(--danger);font-weight:700">Resolve now →</a></div><?php endif; ?>

<div class="page-title">🛡 Admin Dashboard</div>
<div class="card-grid">
  <div class="stat-card"><div class="stat-icon">👥</div><div class="stat-info"><h3><?=$users?></h3><p>Total Users</p></div></div>
  <div class="stat-card"><div class="stat-icon">🔧</div><div class="stat-info"><h3><?=$pros?></h3><p>Professionals</p></div></div>
  <div class="stat-card"><div class="stat-icon">🏠</div><div class="stat-info"><h3><?=$clients?></h3><p>Clients</p></div></div>
  <div class="stat-card"><div class="stat-icon">📋</div><div class="stat-info"><h3><?=$jobs?></h3><p>Open Jobs</p></div></div>
  <div class="stat-card"><div class="stat-icon">📅</div><div class="stat-info"><h3><?=$bookings?></h3><p>Total Bookings</p></div></div>
  <div class="stat-card" style="border-left-color:var(--accent)"><div class="stat-icon" style="background:linear-gradient(135deg,var(--accent),#e67e22)">💰</div><div class="stat-info"><h3>$<?=number_format($revenue,2)?></h3><p>Total Revenue</p></div></div>
</div>

<div style="display:flex;gap:1rem;flex-wrap:wrap;margin:0.5rem 0 1.5rem">
  <a href="/quickfix/admin/users.php" class="btn btn-primary">👥 Manage Users</a>
  <a href="/quickfix/admin/jobs.php" class="btn btn-secondary">📋 All Jobs</a>
  <a href="/quickfix/admin/bookings.php" class="btn btn-secondary">📅 All Bookings</a>
  <a href="/quickfix/admin/disputes.php" class="btn btn-danger">⚖️ Disputes</a>
  <a href="/quickfix/admin/analytics.php" class="btn btn-success">📊 Analytics</a>
</div>

<div class="card">
  <div class="card-header">👥 Recent Registrations</div>
  <div class="card-body">
    <div class="table-wrap"><table>
      <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Trade</th><th>Location</th><th>Verified</th><th>Joined</th></tr></thead>
      <tbody>
      <?php foreach($recent as $u): ?>
      <tr>
        <td><strong><?=$u['full_name']?></strong></td>
        <td><?=$u['email']?></td>
        <td><span class="badge badge-<?=$u['role']==='admin'?'danger':($u['role']==='professional'?'primary':'success')?>"><?=ucfirst($u['role'])?></span></td>
        <td><?=$u['trade']??'—'?></td>
        <td><?=$u['location']??'—'?></td>
        <td><?=$u['verified']?'<span class="badge badge-success">✅ Yes</span>':'<span class="badge badge-warning">⏳ Pending</span>'?></td>
        <td style="font-size:0.82rem;color:#999"><?=timeAgo($u['created_at'])?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>
</div>
</div>
</body></html>
