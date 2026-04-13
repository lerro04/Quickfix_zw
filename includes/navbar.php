<?php
$role = $_SESSION['role'] ?? '';
$name = $_SESSION['full_name'] ?? '';
?>
<nav class="navbar">
  <div class="navbar-brand">
    🔧 <div class="brand-name">Quick<span>Fix</span> ZW</div>
  </div>
  <div class="navbar-nav">
    <?php if($role === 'client'): ?>
      <a href="/quickfix/client/dashboard.php" class="nav-link">🏠 Home</a>
      <a href="/quickfix/client/browse.php" class="nav-link">🔍 Find Professionals</a>
      <a href="/quickfix/client/post_job.php" class="nav-link">➕ Post Job</a>
      <a href="/quickfix/client/my_jobs.php" class="nav-link">📋 My Jobs</a>
      <a href="/quickfix/client/bookings.php" class="nav-link">📅 Bookings</a>
      <a href="/quickfix/messages.php" class="nav-link">💬 Messages</a>
    <?php elseif($role === 'professional'): ?>
      <a href="/quickfix/professional/dashboard.php" class="nav-link">🏠 Home</a>
      <a href="/quickfix/professional/job_board.php" class="nav-link">📋 Job Board</a>
      <a href="/quickfix/professional/my_bids.php" class="nav-link">💰 My Bids</a>
      <a href="/quickfix/professional/bookings.php" class="nav-link">📅 Bookings</a>
      <a href="/quickfix/professional/profile.php" class="nav-link">👤 Profile</a>
      <a href="/quickfix/messages.php" class="nav-link">💬 Messages</a>
    <?php elseif($role === 'admin'): ?>
      <a href="/quickfix/admin/dashboard.php" class="nav-link">🏠 Dashboard</a>
      <a href="/quickfix/admin/users.php" class="nav-link">👥 Users</a>
      <a href="/quickfix/admin/jobs.php" class="nav-link">📋 Jobs</a>
      <a href="/quickfix/admin/bookings.php" class="nav-link">📅 Bookings</a>
      <a href="/quickfix/admin/disputes.php" class="nav-link">⚖️ Disputes</a>
      <a href="/quickfix/admin/analytics.php" class="nav-link">📊 Analytics</a>
    <?php endif; ?>
    <span style="color:rgba(255,255,255,0.55);padding:0 0.4rem;font-size:0.82rem">👤 <?= htmlspecialchars($name) ?></span>
    <a href="/quickfix/logout.php" class="nav-link btn-logout">Logout</a>
  </div>
</nav>
