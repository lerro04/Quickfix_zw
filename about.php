<?php
session_start();
require_once 'includes/functions.php';
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>About - QuickFix ZW</title>
<link rel="stylesheet" href="/quickfix/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head><body>
<?php include 'includes/navbar.php'; ?>
<div class="container"><br>
  <div class="page-title"><?=icon('circle-info')?> About QuickFix ZW</div>
  <div class="section-card">
    <p style="line-height:1.8">QuickFix ZW connects clients with verified local professionals for plumbing, electrical, painting, carpentry, tiling, roofing, welding, landscaping, and general handyman work.</p>
  </div>
  <div class="card-grid">
    <div class="section-card">
      <h3 style="margin-bottom:0.8rem">How it works</h3>
      <p style="line-height:1.7">Clients can post a job for bids or browse providers by trade and location, then book or message the right professional.</p>
    </div>
    <div class="section-card">
      <h3 style="margin-bottom:0.8rem">How the platform makes money</h3>
      <p style="line-height:1.7">This build uses a <?=number_format(PLATFORM_COMMISSION_RATE * 100, 0)?>% commission model on completed bookings. Admin analytics now separate gross booking value, estimated platform commission, and professional payouts.</p>
    </div>
    <div class="section-card">
      <h3 style="margin-bottom:0.8rem">Payments status</h3>
      <p style="line-height:1.7">The current code tracks payment status only. A live payment gateway is still pending integration, so this environment should be treated as manual or sandbox payment flow.</p>
    </div>
  </div>
</div>
</body></html>
