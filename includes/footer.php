<?php
$footerYear = date('Y');
$footerRole = $_SESSION['role'] ?? '';
?>
<footer class="site-footer">
  <div class="site-footer-inner">
    <div class="site-footer-col site-footer-brand">
      <div class="site-footer-logo"><?=icon('screwdriver-wrench')?> Quick<span>Fix</span> ZW</div>
      <p>Zimbabwe's home services marketplace. Find verified plumbers, electricians, painters, carpenters and more &mdash; or offer your skills to clients across the country.</p>
    </div>

    <div class="site-footer-col">
      <h4>Quick Links</h4>
      <ul>
        <li><a href="<?= BASE_URL ?>/index.php"><?=icon('chevron-right')?> Home</a></li>
        <li><a href="<?= BASE_URL ?>/browse.php"><?=icon('chevron-right')?> Browse Providers</a></li>
        <li><a href="<?= BASE_URL ?>/about.php"><?=icon('chevron-right')?> About Us</a></li>
        <li><a href="<?= BASE_URL ?>/support.php"><?=icon('chevron-right')?> Contact Us</a></li>
        <?php if($footerRole === ''): ?>
          <li><a href="<?= BASE_URL ?>/login.php"><?=icon('chevron-right')?> Login</a></li>
          <li><a href="<?= BASE_URL ?>/login.php?mode=register"><?=icon('chevron-right')?> Create Account</a></li>
        <?php else: ?>
          <li><a href="<?= BASE_URL ?>/messages.php"><?=icon('chevron-right')?> Messages</a></li>
          <li><a href="<?= BASE_URL ?>/logout.php"><?=icon('chevron-right')?> Logout</a></li>
        <?php endif; ?>
      </ul>
    </div>

    <div class="site-footer-col">
      <h4>Popular Services</h4>
      <ul>
        <li><a href="<?= BASE_URL ?>/browse.php?trade=Plumbing"><?=icon('wrench')?> Plumbing</a></li>
        <li><a href="<?= BASE_URL ?>/browse.php?trade=Electrical"><?=icon('bolt')?> Electrical</a></li>
        <li><a href="<?= BASE_URL ?>/browse.php?trade=Painting"><?=icon('paint-roller')?> Painting</a></li>
        <li><a href="<?= BASE_URL ?>/browse.php?trade=Carpentry"><?=icon('hammer')?> Carpentry</a></li>
        <li><a href="<?= BASE_URL ?>/browse.php?trade=Roofing"><?=icon('house')?> Roofing</a></li>
        <li><a href="<?= BASE_URL ?>/browse.php?trade=General+Handyman"><?=icon('screwdriver-wrench')?> General Handyman</a></li>
      </ul>
    </div>

    <div class="site-footer-col">
      <h4>Contact Us</h4>
      <ul class="site-footer-contact">
        <li><?=icon('envelope')?> <a href="mailto:support@quickfixzw.co.zw">support@quickfixzw.co.zw</a></li>
        <li><?=icon('phone')?> <a href="tel:+263771000000">+263 77 100 0000</a></li>
        <li><?=icon('location-dot')?> Harare, Zimbabwe</li>
        <li><?=icon('clock')?> Mon &ndash; Sat &middot; 8:00 AM &ndash; 6:00 PM</li>
      </ul>
      <div class="site-footer-cta">
        <a href="<?= BASE_URL ?>/support.php" class="btn btn-primary btn-sm"><?=icon('paper-plane')?> Send a message</a>
      </div>
    </div>
  </div>

  <div class="site-footer-bottom">
    <span>&copy; <?=$footerYear?> QuickFix ZW. All rights reserved.</span>
    <span class="site-footer-meta">Built for Zimbabwean homes &middot; <a href="<?= BASE_URL ?>/about.php">About</a> &middot; <a href="<?= BASE_URL ?>/support.php">Contact</a></span>
  </div>
</footer>
