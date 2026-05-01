<?php
require_once '../includes/auth.php';
requireRole('client');
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/mailer.php';
$uid = $_SESSION['user_id'];
$TRADES = $pdo->query("SELECT name FROM trades WHERE is_active=1 ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
$msg = '';
$errors = [];
if($_SERVER['REQUEST_METHOD']==='POST'){
 $title = htmlspecialchars(trim($_POST['title'] ?? ''));
 $desc = htmlspecialchars(trim($_POST['description'] ?? ''));
 $trade = trim($_POST['trade'] ?? '');
 $custom = trim($_POST['trade_other'] ?? '');
 if($trade === '__other__' && $custom !== ''){
 $trade = htmlspecialchars($custom);
 $pdo->prepare("INSERT IGNORE INTO trades (name) VALUES (?)")->execute([$trade]);
 }
 $loc = htmlspecialchars(trim($_POST['location'] ?? ''));
 $budget = (float)($_POST['client_budget'] ?? 0);
 $urg = $_POST['urgency'] ?? 'flexible';
 if($trade === ''){
 $errors[] = 'Please pick a trade.';
 } else {
 $pdo->prepare("INSERT INTO job_requests (client_id,title,description,trade,location,client_budget,urgency) VALUES (?,?,?,?,?,?,?)")->execute([$uid,$title,$desc,$trade,$loc,$budget,$urg]);
 $jobId = (int)$pdo->lastInsertId();

 if(!empty($_FILES['job_images']['name'][0] ?? '')){
 $result = processImageUploads($_FILES['job_images'], 'job_images');
 foreach($result['uploads'] as $img){
 $pdo->prepare("INSERT INTO job_request_images (job_id,image_path,original_name,mime_type,file_size) VALUES (?,?,?,?,?)")
 ->execute([$jobId, $img['path'], $img['original_name'], $img['mime_type'], $img['file_size']]);
 }
 $errors = array_merge($errors, $result['errors']);
 }

 notifyProsOfNewJob($pdo, $jobId);
 $msg = 'Job posted! Professionals in your area have been notified.';
 }
}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Post a Job - QuickFix ZW</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head><body>
<?php include '../includes/navbar.php'; ?>
<div class="container"><br>
<?php if($msg): ?><div class="alert alert-success"><?=icon('circle-check')?> <?=$msg?></div><?php endif; ?>
<?php foreach($errors as $error): ?><div class="alert alert-warning"><?=icon('triangle-exclamation')?> <?=htmlspecialchars($error)?></div><?php endforeach; ?>
<div class="card" style="max-width:680px;margin:0 auto">
 <div class="card-header"><?=icon('plus')?> Post a Job Request</div>
 <div class="card-body">
 <p style="color:var(--gray);margin-bottom:1.5rem;font-size:0.9rem">
 <?=icon('bullhorn')?> Post your job and let professionals bid on it. Add photos so providers can estimate more accurately.
 </p>
 <form method="POST" enctype="multipart/form-data">
 <div class="form-group">
 <label class="form-label">Job Title *</label>
 <input type="text" name="title" class="form-control" placeholder="e.g. Fix leaking kitchen sink pipe" required>
 </div>
 <div class="form-group">
 <label class="form-label">Description *</label>
 <textarea name="description" class="form-control" rows="5" placeholder="Describe the problem in detail - what needs fixing, size of the job, any relevant info..." required style="resize:vertical"></textarea>
 </div>
 <div class="form-group">
 <label class="form-label">Job Images</label>
 <input type="file" name="job_images[]" class="form-control" accept=".jpg,.jpeg,.png,.webp" multiple>
 <div class="upload-note" style="font-size:0.8rem;color:#999;margin-top:0.3rem">Upload clear photos of the damage or work area. Up to <?=MAX_IMAGE_UPLOADS?> images, 5MB each.</div>
 </div>
 <div class="form-row">
 <div class="form-group">
 <label class="form-label">Trade Needed *</label>
 <select name="trade" class="form-select" required onchange="document.getElementById('trade_other_wrap').style.display=this.value==='__other__'?'block':'none'">
 <option value="">- Select trade -</option>
 <?php foreach($TRADES as $t): ?><option value="<?=htmlspecialchars($t)?>"><?=htmlspecialchars($t)?></option><?php endforeach; ?>
 <option value="__other__">+ Other (specify)</option>
 </select>
 <div id="trade_other_wrap" style="display:none;margin-top:0.4rem">
 <input type="text" name="trade_other" class="form-control" placeholder="Type the trade you need (e.g. Solar Installation)">
 </div>
 </div>
 <div class="form-group">
 <label class="form-label">Your Location *</label>
 <input type="text" name="location" class="form-control" placeholder="e.g. Chinhoyi, Kuwadzana Harare" value="<?=htmlspecialchars($_SESSION['location'] ?? '')?>" required>
 </div>
 </div>
 <div class="form-row">
 <div class="form-group">
 <label class="form-label">Your Budget (USD)</label>
 <input type="number" name="client_budget" class="form-control" step="0.50" min="1" placeholder="20.00">
 <small style="color:#999">Professionals will see this and bid accordingly</small>
 </div>
 <div class="form-group">
 <label class="form-label">Urgency *</label>
 <select name="urgency" class="form-select" required>
 <option value="flexible">Flexible - No rush</option>
 <option value="within_week">Within this week</option>
 <option value="urgent">Urgent - ASAP</option>
 </select>
 </div>
 </div>
 <button type="submit" class="btn btn-primary btn-block btn-lg"><?=icon('rocket')?> Post Job Request</button>
 </form>
 </div>
</div>
</div>
<?php include '../includes/footer.php'; ?>
</body></html>
