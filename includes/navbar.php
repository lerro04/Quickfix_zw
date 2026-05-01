<?php
$role = $_SESSION['role'] ?? '';
$name = $_SESSION['full_name'] ?? '';
$navUnread = isset($_SESSION['user_id']) && isset($pdo) ? countUnreadMessages($pdo, $_SESSION['user_id']) : 0;
?>
<nav class="navbar">
  <div class="navbar-brand">
    <?=icon('screwdriver-wrench')?> <div class="brand-name">Quick<span>Fix</span> ZW</div>
  </div>
  <div class="navbar-nav">
    <?php if($role === 'client'): ?>
      <a href="<?= BASE_URL ?>/client/dashboard.php" class="nav-link"><?=icon('house')?> Home</a>
      <a href="<?= BASE_URL ?>/browse.php" class="nav-link"><?=icon('magnifying-glass')?> Find Professionals</a>
      <a href="<?= BASE_URL ?>/client/post_job.php" class="nav-link"><?=icon('plus')?> Post Job</a>
      <a href="<?= BASE_URL ?>/client/my_jobs.php" class="nav-link"><?=icon('briefcase')?> My Jobs</a>
      <a href="<?= BASE_URL ?>/client/bookings.php" class="nav-link"><?=icon('calendar-days')?> Bookings</a>
      <a href="<?= BASE_URL ?>/messages.php" class="nav-link"><?=icon('comments')?> Messages<?php if($navUnread > 0): ?> <span class="nav-badge"><?=$navUnread > 99 ? '99+' : $navUnread?></span><?php endif; ?></a>
    <?php elseif($role === 'professional'): ?>
      <a href="<?= BASE_URL ?>/professional/dashboard.php" class="nav-link"><?=icon('house')?> Home</a>
      <a href="<?= BASE_URL ?>/professional/job_board.php" class="nav-link"><?=icon('briefcase')?> Job Board</a>
      <a href="<?= BASE_URL ?>/professional/my_bids.php" class="nav-link"><?=icon('sack-dollar')?> My Bids</a>
      <a href="<?= BASE_URL ?>/professional/bookings.php" class="nav-link"><?=icon('calendar-days')?> Bookings</a>
      <a href="<?= BASE_URL ?>/professional/profile.php" class="nav-link"><?=icon('user')?> Profile</a>
      <a href="<?= BASE_URL ?>/messages.php" class="nav-link"><?=icon('comments')?> Messages<?php if($navUnread > 0): ?> <span class="nav-badge"><?=$navUnread > 99 ? '99+' : $navUnread?></span><?php endif; ?></a>
    <?php elseif($role === 'admin'): ?>
      <a href="<?= BASE_URL ?>/admin/dashboard.php" class="nav-link"><?=icon('gauge-high')?> Dashboard</a>
      <a href="<?= BASE_URL ?>/admin/users.php" class="nav-link"><?=icon('users')?> Users</a>
      <a href="<?= BASE_URL ?>/admin/jobs.php" class="nav-link"><?=icon('briefcase')?> Jobs</a>
      <a href="<?= BASE_URL ?>/admin/bookings.php" class="nav-link"><?=icon('calendar-days')?> Bookings</a>
      <a href="<?= BASE_URL ?>/admin/disputes.php" class="nav-link"><?=icon('scale-balanced')?> Disputes</a>
      <a href="<?= BASE_URL ?>/admin/trades.php" class="nav-link"><?=icon('list')?> Trades</a>
      <a href="<?= BASE_URL ?>/admin/analytics.php" class="nav-link"><?=icon('chart-line')?> Analytics</a>
      <a href="<?= BASE_URL ?>/messages.php" class="nav-link"><?=icon('comments')?> Messages<?php if($navUnread > 0): ?> <span class="nav-badge"><?=$navUnread > 99 ? '99+' : $navUnread?></span><?php endif; ?></a>
    <?php else: ?>
      <a href="<?= BASE_URL ?>/index.php" class="nav-link"><?=icon('house')?> Home</a>
      <a href="<?= BASE_URL ?>/browse.php" class="nav-link"><?=icon('magnifying-glass')?> Browse</a>
      <a href="<?= BASE_URL ?>/about.php" class="nav-link"><?=icon('circle-info')?> About</a>
      <a href="<?= BASE_URL ?>/support.php" class="nav-link"><?=icon('headset')?> Contact</a>
      <a href="<?= BASE_URL ?>/login.php" class="nav-link"><?=icon('right-to-bracket')?> Login</a>
    <?php endif; ?>
    <?php if($role): ?>
      <span style="color:rgba(255,255,255,0.55);padding:0 0.4rem;font-size:0.82rem"><?=icon('user')?> <?= htmlspecialchars($name) ?></span>
      <a href="<?= BASE_URL ?>/logout.php" class="nav-link btn-logout"><?=icon('right-from-bracket')?> Logout</a>
    <?php else: ?>
      <a href="<?= BASE_URL ?>/login.php?mode=register" class="nav-link btn-logout"><?=icon('user-plus')?> Create Account</a>
    <?php endif; ?>
  </div>
</nav>
