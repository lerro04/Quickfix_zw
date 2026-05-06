<?php
session_start();
require_once 'includes/functions.php';
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>About - QuickFix ZW</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head><body>
<?php include 'includes/navbar.php'; ?>
<div class="container"><br>
 <div class="page-title"><?=icon('circle-info')?> About QuickFix ZW</div>

 <div class="section-card" style="margin-bottom:1.5rem">
 <p style="line-height:1.8;font-size:1.02rem">
 QuickFix ZW is Zimbabwe's marketplace for home services. We connect homeowners with verified local professionals &mdash; plumbers, electricians, painters, carpenters, tilers, roofers, welders, landscapers and general handymen &mdash; so you can get the right person for the job, fast.
 </p>
 </div>

 <h2 style="margin:2rem 0 1rem;font-size:1.4rem"><?=icon('user')?> How to get a service done</h2>
 <div class="card-grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">
 <div class="section-card">
 <div style="font-size:2rem;color:var(--primary);font-weight:800;margin-bottom:0.4rem">1</div>
 <h3 style="margin-bottom:0.5rem">Search or browse</h3>
 <p style="line-height:1.6">Search by service (e.g. <em>plumbing</em>) or provider name on the home page, or browse the full list of verified professionals. Filter by your city to see who's nearby.</p>
 </div>
 <div class="section-card">
 <div style="font-size:2rem;color:var(--primary);font-weight:800;margin-bottom:0.4rem">2</div>
 <h3 style="margin-bottom:0.5rem">Pick a professional</h3>
 <p style="line-height:1.6">View their profile, ratings, completed jobs, hourly rate and service area. Compare a few before deciding.</p>
 </div>
 <div class="section-card">
 <div style="font-size:2rem;color:var(--primary);font-weight:800;margin-bottom:0.4rem">3</div>
 <h3 style="margin-bottom:0.5rem">Message and book</h3>
 <p style="line-height:1.6">Sign in, message the provider to discuss the job, agree on a price and date, and confirm the booking right inside the platform.</p>
 </div>
 <div class="section-card">
 <div style="font-size:2rem;color:var(--primary);font-weight:800;margin-bottom:0.4rem">4</div>
 <h3 style="margin-bottom:0.5rem">Pay after the job</h3>
 <p style="line-height:1.6">When the work is complete and you're happy, mark the job done and release the agreed amount. If something goes wrong, raise a dispute and an admin will step in.</p>
 </div>
 </div>

 <div class="section-card" style="margin-top:1.5rem;background:#fff8f3;border-left:4px solid var(--primary)">
 <strong><?=icon('lightbulb')?> Not sure which provider to pick?</strong>
 <p style="margin-top:0.4rem;line-height:1.7">You can also <strong>post your job</strong> with a budget and let multiple professionals send you bids &mdash; then choose the one whose price and profile suits you best.</p>
 </div>

 <h2 style="margin:2.5rem 0 1rem;font-size:1.4rem"><?=icon('briefcase')?> How to get clients as a professional</h2>
 <div class="card-grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">
 <div class="section-card">
 <div style="font-size:2rem;color:var(--primary);font-weight:800;margin-bottom:0.4rem">1</div>
 <h3 style="margin-bottom:0.5rem">Create your profile</h3>
 <p style="line-height:1.6">Sign up as a professional &mdash; it's free. Add your trade, hourly rate, service area and a short bio so clients know what you do.</p>
 </div>
 <div class="section-card">
 <div style="font-size:2rem;color:var(--primary);font-weight:800;margin-bottom:0.4rem">2</div>
 <h3 style="margin-bottom:0.5rem">Get verified</h3>
 <p style="line-height:1.6">Submit your details (including your national ID) so our team can verify you. Verified professionals show a badge that builds client trust.</p>
 </div>
 <div class="section-card">
 <div style="font-size:2rem;color:var(--primary);font-weight:800;margin-bottom:0.4rem">3</div>
 <h3 style="margin-bottom:0.5rem">Bid on jobs or get booked</h3>
 <p style="line-height:1.6">Watch the job board for new requests in your trade and place a bid &mdash; or wait for clients to find your profile and book you directly.</p>
 </div>
 <div class="section-card">
 <div style="font-size:2rem;color:var(--primary);font-weight:800;margin-bottom:0.4rem">4</div>
 <h3 style="margin-bottom:0.5rem">Do the work, get paid</h3>
 <p style="line-height:1.6">Complete the job and message the client through the platform. Once they confirm it's done, payment is released and the booking is added to your completed jobs &mdash; growing your rating and visibility.</p>
 </div>
 </div>

 <div class="section-card" style="margin:2rem 0;text-align:center">
 <h3 style="margin-bottom:0.6rem">Ready to get started?</h3>
 <p style="color:var(--gray);margin-bottom:1.2rem">Whether you need a service or want to offer one, it only takes a minute.</p>
 <div style="display:flex;gap:0.8rem;justify-content:center;flex-wrap:wrap">
 <a href="<?= BASE_URL ?>/login.php?mode=register" class="btn btn-primary"><?=icon('user-plus')?> Create an account</a>
 <a href="<?= BASE_URL ?>/browse.php" class="btn btn-outline"><?=icon('magnifying-glass')?> Browse providers</a>
 </div>
 </div>
</div>
<?php include 'includes/footer.php'; ?>
</body></html>
