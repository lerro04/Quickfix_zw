<?php
session_start();
require_once 'includes/functions.php';
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Support - QuickFix ZW</title>
<link rel="stylesheet" href="/quickfix/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head><body>
<?php include 'includes/navbar.php'; ?>
<div class="container"><br>
  <div class="page-title"><?=icon('headset')?> Support Contact</div>
  <div class="support-grid">
    <div class="section-card">
      <h3 style="margin-bottom:0.8rem"><?=icon('envelope')?> Email</h3>
      <p>support@quickfixzw.co.zw</p>
    </div>
    <div class="section-card">
      <h3 style="margin-bottom:0.8rem"><?=icon('phone')?> Phone</h3>
      <p>+263 77 100 0000</p>
    </div>
    <div class="section-card">
      <h3 style="margin-bottom:0.8rem"><?=icon('clock')?> Hours</h3>
      <p>Monday to Saturday, 8:00 AM to 6:00 PM</p>
    </div>
  </div>
  <div class="section-card">
    <h3 style="margin-bottom:0.8rem">Need account or booking help?</h3>
    <p style="line-height:1.7">Browse providers first, then sign up only when you are ready to message a professional, post a job, or confirm a booking.</p>
  </div>
</div>
</body></html>
