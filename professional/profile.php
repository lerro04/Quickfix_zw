<?php
require_once '../includes/auth.php';
requireRole('professional');
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/id_upload.php';
$uid=$_SESSION['user_id'];
$TRADES = $pdo->query("SELECT name FROM trades WHERE is_active=1 ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
$msg='';
$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $bio=htmlspecialchars(trim($_POST['bio']));
 $rate=(float)$_POST['hourly_rate'];
 $exp=(int)$_POST['years_experience'];
 $area=htmlspecialchars(trim($_POST['service_area']));
 $avail=(int)($_POST['is_available']??0);
 $phone=normalizePhone($_POST['phone'] ?? '');
 $loc=htmlspecialchars(trim($_POST['location']));
 $latIn = $_POST['latitude'] ?? '';
 $lngIn = $_POST['longitude'] ?? '';
 $latVal = ($latIn !== '' && is_numeric($latIn)) ? round((float)$latIn, 8) : null;
 $lngVal = ($lngIn !== '' && is_numeric($lngIn)) ? round((float)$lngIn, 8) : null;
 if($latVal !== null && ($latVal < -90 || $latVal > 90)) $latVal = null;
 if($lngVal !== null && ($lngVal < -180 || $lngVal > 180)) $lngVal = null;
 $locLabelIn = htmlspecialchars(trim($_POST['location_label'] ?? ''));
 $nidIn = strtoupper(trim($_POST['national_id'] ?? ''));
 $tradeIn = trim($_POST['trade'] ?? '');
 $tradeOther = trim($_POST['trade_other'] ?? '');
 if($tradeIn === '__other__' && $tradeOther !== ''){
 $tradeIn = htmlspecialchars($tradeOther);
 $pdo->prepare("INSERT IGNORE INTO trades (name) VALUES (?)")->execute([$tradeIn]);
 }

 if($phone !== '' && !isValidPhone($phone)){
 $err = 'Phone must be 10 digits starting with 0 (e.g. 0779913031).';
 } elseif($nidIn !== '' && !isValidNationalIdNumber($nidIn)){
 $err = 'National ID must be in the format 12-345678X90 (e.g. 70-345234P09).';
 }

 if($err === ''){
 try {
 $newIdFile = handleIdUpload($_FILES['id_file'] ?? []);
 } catch(RuntimeException $e){
 $err = $e->getMessage();
 $newIdFile = '__error__';
 }

 if($err === ''){
 if($tradeIn !== ''){
 $pdo->prepare("UPDATE professional_profiles SET trade=?,bio=?,hourly_rate=?,years_experience=?,service_area=?,is_available=?,latitude=?,longitude=?,location_label=? WHERE user_id=?")->execute([$tradeIn,$bio,$rate,$exp,$area,$avail,$latVal,$lngVal,$locLabelIn,$uid]);
 } else {
 $pdo->prepare("UPDATE professional_profiles SET bio=?,hourly_rate=?,years_experience=?,service_area=?,is_available=?,latitude=?,longitude=?,location_label=? WHERE user_id=?")->execute([$bio,$rate,$exp,$area,$avail,$latVal,$lngVal,$locLabelIn,$uid]);
 }
 $pdo->prepare("UPDATE users SET phone=?,location=?,national_id=? WHERE user_id=?")->execute([$phone,$loc,$nidIn,$uid]);

 if($newIdFile !== '' && $newIdFile !== '__error__'){
 $oldFileQ = $pdo->prepare("SELECT national_id_file FROM users WHERE user_id=?");
 $oldFileQ->execute([$uid]);
 $oldFile = $oldFileQ->fetchColumn();
 $pdo->prepare("UPDATE users SET national_id_file=? WHERE user_id=?")->execute([$newIdFile, $uid]);
 if($oldFile) deleteIdFile($oldFile);
 }
 $msg=" Profile updated successfully!";
 }
 }
}
$prof=$pdo->prepare("SELECT pp.*,u.email,u.phone,u.location,u.national_id,u.national_id_file FROM professional_profiles pp JOIN users u ON pp.user_id=u.user_id WHERE pp.user_id=?"); $prof->execute([$uid]); $p=$prof->fetch();
$reviews=$pdo->prepare("SELECT r.*,u.full_name as reviewer FROM reviews r JOIN users u ON r.reviewer_id=u.user_id WHERE r.reviewee_id=? ORDER BY r.created_at DESC"); $reviews->execute([$uid]); $myRevs=$reviews->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Profile - QuickFix ZW</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head><body>
<?php include '../includes/navbar.php'; ?>
<div class="container"><br>
<?php if($msg): ?><div class="alert alert-success"><?=$msg?></div><?php endif; ?>
<?php if($err): ?><div class="alert alert-danger"><?=htmlspecialchars($err)?></div><?php endif; ?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;flex-wrap:wrap">
 <div class="card">
 <div class="card-header"><?=icon('pen-to-square')?> Edit Profile</div>
 <div class="card-body">
 <form method="POST" enctype="multipart/form-data">
 <div class="form-group">
 <label class="form-label">Trade *</label>
 <select name="trade" class="form-select" required onchange="document.getElementById('trade_other_wrap').style.display=this.value==='__other__'?'block':'none'">
 <?php $tradeFound = in_array($p['trade'], $TRADES, true); ?>
 <?php foreach($TRADES as $t): ?>
 <option value="<?=htmlspecialchars($t)?>" <?=$p['trade']===$t?'selected':''?>><?=$t?></option>
 <?php endforeach; ?>
 <?php if(!$tradeFound && $p['trade']): ?>
 <option value="<?=htmlspecialchars($p['trade'])?>" selected><?=htmlspecialchars($p['trade'])?></option>
 <?php endif; ?>
 <option value="__other__">+ Other (specify)</option>
 </select>
 <div id="trade_other_wrap" style="display:none;margin-top:0.4rem">
 <input type="text" name="trade_other" class="form-control" placeholder="Type your trade (e.g. Solar Installation)">
 </div>
 </div>
 <div class="form-group">
 <label class="form-label">Description / About Me <span style="color:var(--primary)">*</span></label>
 <textarea name="bio" class="form-control" rows="6" style="resize:vertical" placeholder="Describe your experience, the kinds of jobs you handle, what makes you different, and the areas you cover. Clients use this to decide who to book."><?=htmlspecialchars($p['bio'] ?? '')?></textarea>
 <small style="color:#999;font-size:0.78rem">A good description gets you more bookings. Aim for at least 2-3 sentences.</small>
 </div>
 <div class="form-row">
 <div class="form-group">
 <label class="form-label">Hourly Rate (USD)</label>
 <input type="number" name="hourly_rate" class="form-control" value="<?=$p['hourly_rate']?>" step="0.50" min="1">
 </div>
 <div class="form-group">
 <label class="form-label">Years Experience</label>
 <input type="number" name="years_experience" class="form-control" value="<?=$p['years_experience']?>" min="0">
 </div>
 </div>
 <div class="form-group">
 <label class="form-label">Service Area (cities/towns you cover)</label>
 <input type="text" name="service_area" class="form-control" value="<?=htmlspecialchars($p['service_area'] ?? '')?>" placeholder="Chinhoyi, Karoi, Harare">
 </div>
 <div class="form-row">
 <div class="form-group">
 <label class="form-label">Phone</label>
 <input type="tel" name="phone" class="form-control" value="<?=htmlspecialchars($p['phone'] ?? '')?>" placeholder="0779913031" pattern="0\d{9}" title="10 digits starting with 0 (e.g. 0779913031)">
 </div>
 <div class="form-group">
 <label class="form-label">Location (City)</label>
 <input type="text" name="location" class="form-control" value="<?=htmlspecialchars($p['location'] ?? '')?>">
 </div>
 </div>
 <div class="form-group">
 <label class="form-label">Pin your map location <span style="color:var(--gray);font-weight:400">(so clients see distance to you)</span></label>
 <div style="display:flex;gap:0.5rem;flex-wrap:wrap;align-items:center">
 <button type="button" id="gps-btn" class="btn btn-outline btn-sm"><?=icon('location-crosshairs')?> Use my current location</button>
 <span id="gps-label" style="font-size:0.85rem;color:var(--gray)">
 <?php if(!empty($p['latitude']) && !empty($p['longitude'])): ?>
 <?=icon('circle-check')?> Pinned at <?=number_format((float)$p['latitude'], 4)?>, <?=number_format((float)$p['longitude'], 4)?><?php if(!empty($p['location_label'])): ?> &middot; <?=htmlspecialchars($p['location_label'])?><?php endif; ?>
 <?php else: ?>
 No location pinned yet
 <?php endif; ?>
 </span>
 <?php if(!empty($p['latitude'])): ?>
 <button type="button" id="gps-clear" class="btn btn-outline btn-sm" style="color:var(--danger)"><?=icon('xmark')?> Clear</button>
 <?php endif; ?>
 </div>
 <input type="hidden" name="latitude" id="lat-input" value="<?=htmlspecialchars((string)($p['latitude'] ?? ''))?>">
 <input type="hidden" name="longitude" id="lng-input" value="<?=htmlspecialchars((string)($p['longitude'] ?? ''))?>">
 <input type="hidden" name="location_label" id="locLabel-input" value="<?=htmlspecialchars($p['location_label'] ?? '')?>">
 <small style="color:var(--gray);font-size:0.78rem;display:block;margin-top:0.4rem">We only store the coordinates &mdash; not your exact address. Clients see <em>"X km away"</em>, not your spot on a map.</small>
 </div>
 <script>
 (function(){
 var btn = document.getElementById('gps-btn');
 var clear = document.getElementById('gps-clear');
 var label = document.getElementById('gps-label');
 var lat = document.getElementById('lat-input');
 var lng = document.getElementById('lng-input');
 var locLabel = document.getElementById('locLabel-input');
 if(btn){
 btn.addEventListener('click', function(){
 if(!navigator.geolocation){
 label.textContent = 'Geolocation not supported by this browser.';
 return;
 }
 label.textContent = 'Locating...';
 navigator.geolocation.getCurrentPosition(function(pos){
 lat.value = pos.coords.latitude.toFixed(8);
 lng.value = pos.coords.longitude.toFixed(8);
 var locInput = document.querySelector('input[name="location"]');
 if(locInput && locInput.value) locLabel.value = locInput.value;
 label.innerHTML = '<i class="fa-solid fa-circle-check" style="color:var(--success)"></i> Pinned at ' + (+lat.value).toFixed(4) + ', ' + (+lng.value).toFixed(4) + '. Save profile to keep it.';
 }, function(err){
 label.textContent = 'Could not get location: ' + (err.message || 'permission denied');
 }, { enableHighAccuracy: true, timeout: 10000 });
 });
 }
 if(clear){
 clear.addEventListener('click', function(){
 lat.value = '';
 lng.value = '';
 locLabel.value = '';
 label.textContent = 'Location cleared. Save profile to remove.';
 clear.style.display = 'none';
 });
 }
 })();
 </script>
 <div class="form-group">
 <label class="form-label">Availability</label>
 <select name="is_available" class="form-select">
 <option value="1" <?=$p['is_available'] ? 'selected' : ''?>>Available for jobs</option>
 <option value="0" <?=!$p['is_available'] ? 'selected' : ''?>>Not available right now</option>
 </select>
 </div>
 <hr style="margin:1.2rem 0;border:none;border-top:1px solid var(--border)">
 <h4 style="margin-bottom:0.6rem;font-size:0.95rem;color:var(--gray)">Identity verification</h4>
 <div class="form-group">
 <label class="form-label">National ID Number</label>
 <input type="text" name="national_id" class="form-control" value="<?=htmlspecialchars($p['national_id']??'')?>" placeholder="70-345234P09" pattern="\d{2}-\d{6,7}[A-Z]\d{2}" title="Format: 12-345678X90 (e.g. 70-345234P09)">
 <small style="color:#999;font-size:0.78rem">Format: <code>12-345678X90</code></small>
 </div>
 <div class="form-group">
 <label class="form-label">ID Document</label>
 <?php if(!empty($p['national_id_file'])): ?>
 <div style="background:#eaf6ee;border:1px solid #b8e0c0;color:#155724;padding:0.5rem 0.7rem;border-radius:6px;font-size:0.85rem;margin-bottom:0.4rem">
 <?=icon('circle-check')?> ID document on file. Upload a new one below to replace it.
 </div>
 <?php endif; ?>
 <input type="file" name="id_file" class="form-control" accept="image/jpeg,image/png,image/webp,application/pdf">
 <small style="color:#999;font-size:0.78rem">JPG, PNG, WEBP or PDF, max 5 MB. Only admins can view this file.</small>
 </div>
 <button type="submit" class="btn btn-primary"> Save Profile</button>
 </form>
 </div>
 </div>

 <div>
 <div class="card" style="margin-bottom:1.5rem">
 <div class="card-header"><?=icon('images')?> My Portfolio</div>
 <div class="card-body">
 <?php if(empty($portfolioImages)): ?>
 <div class="gallery-placeholder"><?=icon('camera')?> Upload photos of your work to build trust with clients.</div>
 <?php else: ?>
 <div class="image-grid">
 <?php foreach($portfolioImages as $image): ?>
 <div class="image-tile">
 <img src="<?=$image['image_path']?>" alt="Portfolio image">
 <div class="image-tile-body">
 <div><?=htmlspecialchars($image['original_name'] ?: 'Portfolio image')?></div>
 <form method="POST" style="margin-top:0.6rem">
 <input type="hidden" name="image_id" value="<?=$image['image_id']?>">
 <button type="submit" name="delete_portfolio_image" class="btn btn-danger btn-sm"><?=icon('trash')?> Remove</button>
 </form>
 </div>
 </div>
 <?php endforeach; ?>
 </div>
 <?php endif; ?>
 </div>
 </div>

 <div class="card" style="margin-bottom:1.5rem">
 <div class="card-header"><?=icon('chart-line')?> My Stats</div>
 <div class="card-body">
 <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
 <div style="text-align:center;padding:1rem;background:var(--light);border-radius:10px">
 <div style="font-size:1.8rem;font-weight:700;color:var(--primary)"><?=$p['jobs_completed']?></div>
 <div style="font-size:0.8rem;color:#999">Jobs Completed</div>
 </div>
 <div style="text-align:center;padding:1rem;background:var(--light);border-radius:10px">
 <div style="font-size:1.8rem;font-weight:700;color:var(--warning)"><?=number_format($p['rating_avg'],1)?></div>
 <div style="font-size:0.8rem;color:#999">Average Rating</div>
 </div>
 </div>
 <p style="margin-top:0.8rem;font-size:0.85rem;color:#999">National ID: <?=$p['national_id'] ?? 'Not provided'?></p>
 </div>
 </div>

 <div class="card">
 <div class="card-header"><?=icon('star')?> Client Reviews (<?=count($myRevs)?>)</div>
 <div class="card-body" style="max-height:400px;overflow-y:auto">
 <?php if(empty($myRevs)): ?><p style="color:#999;text-align:center;padding:1.5rem">No reviews yet. Complete jobs to earn reviews.</p>
 <?php else: ?>
 <?php foreach($myRevs as $r): ?>
 <div style="border-bottom:1px solid var(--border);padding:0.8rem 0">
 <div style="display:flex;justify-content:space-between">
 <strong style="font-size:0.9rem"><?=htmlspecialchars($r['reviewer'])?></strong>
 <span><?=stars($r['rating'])?></span>
 </div>
 <p style="color:var(--gray);font-size:0.85rem;margin-top:0.3rem;line-height:1.5"><?=htmlspecialchars($r['comment'] ?? '')?></p>
 <small style="color:#999"><?=timeAgo($r['created_at'])?></small>
 </div>
 <?php endforeach; ?>
 <?php endif; ?>
 </div>
 </div>
 </div>
</div>
</div>
<?php include '../includes/footer.php'; ?>
</body></html>
