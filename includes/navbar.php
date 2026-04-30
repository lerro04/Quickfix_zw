<?php
$role = $_SESSION['role'] ?? '';
$name = $_SESSION['full_name'] ?? '';
?>
<nav class="navbar">
  <div class="navbar-brand">
    <?=icon('screwdriver-wrench')?> <div class="brand-name">Quick<span>Fix</span> ZW</div>
  </div>
  <div class="navbar-nav">
    <?php if($role === 'client'): ?>
      <a href="/quickfix/client/dashboard.php" class="nav-link"><?=icon('house')?> Home</a>
      <a href="/quickfix/browse.php" class="nav-link"><?=icon('magnifying-glass')?> Find Professionals</a>
      <a href="/quickfix/client/post_job.php" class="nav-link"><?=icon('plus')?> Post Job</a>
      <a href="/quickfix/client/my_jobs.php" class="nav-link"><?=icon('briefcase')?> My Jobs</a>
      <a href="/quickfix/client/bookings.php" class="nav-link"><?=icon('calendar-days')?> Bookings</a>
      <a href="/quickfix/messages.php" class="nav-link"><?=icon('comments')?> Messages</a>
    <?php elseif($role === 'professional'): ?>
      <a href="/quickfix/professional/dashboard.php" class="nav-link"><?=icon('house')?> Home</a>
      <a href="/quickfix/professional/job_board.php" class="nav-link"><?=icon('briefcase')?> Job Board</a>
      <a href="/quickfix/professional/my_bids.php" class="nav-link"><?=icon('sack-dollar')?> My Bids</a>
      <a href="/quickfix/professional/bookings.php" class="nav-link"><?=icon('calendar-days')?> Bookings</a>
      <a href="/quickfix/professional/profile.php" class="nav-link"><?=icon('user')?> Profile</a>
      <a href="/quickfix/messages.php" class="nav-link"><?=icon('comments')?> Messages</a>
    <?php elseif($role === 'admin'): ?>
      <a href="/quickfix/admin/dashboard.php" class="nav-link"><?=icon('gauge-high')?> Dashboard</a>
      <a href="/quickfix/admin/users.php" class="nav-link"><?=icon('users')?> Users</a>
      <a href="/quickfix/admin/jobs.php" class="nav-link"><?=icon('briefcase')?> Jobs</a>
      <a href="/quickfix/admin/bookings.php" class="nav-link"><?=icon('calendar-days')?> Bookings</a>
      <a href="/quickfix/admin/disputes.php" class="nav-link"><?=icon('scale-balanced')?> Disputes</a>
      <a href="/quickfix/admin/analytics.php" class="nav-link"><?=icon('chart-line')?> Analytics</a>
    <?php else: ?>
      <a href="/quickfix/index.php" class="nav-link"><?=icon('house')?> Home</a>
      <a href="/quickfix/browse.php" class="nav-link"><?=icon('magnifying-glass')?> Browse</a>
      <a href="/quickfix/about.php" class="nav-link"><?=icon('circle-info')?> About</a>
      <a href="/quickfix/support.php" class="nav-link"><?=icon('headset')?> Support</a>
      <a href="/quickfix/index.php#auth" class="nav-link"><?=icon('right-to-bracket')?> Login</a>
    <?php endif; ?>
    <?php if($role): ?>
      <span style="color:rgba(255,255,255,0.55);padding:0 0.4rem;font-size:0.82rem"><?=icon('user')?> <?= htmlspecialchars($name) ?></span>
      <a href="/quickfix/logout.php" class="nav-link btn-logout"><?=icon('right-from-bracket')?> Logout</a>
    <?php else: ?>
      <a href="/quickfix/index.php#auth" class="nav-link btn-logout"><?=icon('user-plus')?> Create Account</a>
    <?php endif; ?>
  </div>
</nav>
