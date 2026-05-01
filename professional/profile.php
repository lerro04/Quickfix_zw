<?php
require_once '../includes/auth.php';
requireRole('professional');
require_once '../includes/db.php';
require_once '../includes/functions.php';

$uid = $_SESSION['user_id'];
$msg = '';
$errors = [];

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if(isset($_POST['delete_portfolio_image'])){
        $imageId = (int)$_POST['image_id'];
        $imgStmt = $pdo->prepare("SELECT image_path FROM professional_portfolio_images WHERE image_id=? AND user_id=?");
        $imgStmt->execute([$imageId, $uid]);
        $image = $imgStmt->fetch();
        if($image){
            $pdo->prepare("DELETE FROM professional_portfolio_images WHERE image_id=? AND user_id=?")->execute([$imageId, $uid]);
            deleteUploadedFile($image['image_path']);
            $msg = 'Portfolio image removed.';
        }
    } else {
        $bio = htmlspecialchars(trim($_POST['bio'] ?? ''));
        $rate = (float)($_POST['hourly_rate'] ?? 0);
        $exp = (int)($_POST['years_experience'] ?? 0);
        $area = htmlspecialchars(trim($_POST['service_area'] ?? ''));
        $avail = (int)($_POST['is_available'] ?? 0);
        $phone = trim($_POST['phone'] ?? '');
        $loc = htmlspecialchars(trim($_POST['location'] ?? ''));

        $pdo->prepare("UPDATE professional_profiles SET bio=?,hourly_rate=?,years_experience=?,service_area=?,is_available=? WHERE user_id=?")->execute([$bio,$rate,$exp,$area,$avail,$uid]);
        $pdo->prepare("UPDATE users SET phone=?,location=? WHERE user_id=?")->execute([$phone,$loc,$uid]);

        if(isset($_FILES['portfolio_images'])){
            $upload = processImageUploads($_FILES['portfolio_images'], 'portfolio');
            foreach($upload['uploads'] as $file){
                $pdo->prepare("INSERT INTO professional_portfolio_images (user_id,image_path,original_name,mime_type,file_size) VALUES (?,?,?,?,?)")->execute([$uid,$file['path'],$file['original_name'],$file['mime_type'],$file['file_size']]);
            }
            $errors = array_merge($errors, $upload['errors']);
        }
        $msg = 'Profile updated successfully.';
    }
}

$prof = $pdo->prepare("SELECT pp.*,u.email,u.phone,u.location,u.national_id FROM professional_profiles pp JOIN users u ON pp.user_id=u.user_id WHERE pp.user_id=?");
$prof->execute([$uid]);
$p = $prof->fetch();

$reviews = $pdo->prepare("SELECT r.*,u.full_name as reviewer FROM reviews r JOIN users u ON r.reviewer_id=u.user_id WHERE r.reviewee_id=? ORDER BY r.created_at DESC");
$reviews->execute([$uid]);
$myRevs = $reviews->fetchAll();

$imagesStmt = $pdo->prepare("SELECT * FROM professional_portfolio_images WHERE user_id=? ORDER BY created_at DESC");
$imagesStmt->execute([$uid]);
$portfolioImages = $imagesStmt->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Profile - QuickFix ZW</title>
<link rel="stylesheet" href="/quickfix/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head><body>
<?php include '../includes/navbar.php'; ?>
<div class="container"><br>
<?php if($msg): ?><div class="alert alert-success"><?=$msg?></div><?php endif; ?>
<?php foreach($errors as $error): ?><div class="alert alert-warning"><?=$error?></div><?php endforeach; ?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;flex-wrap:wrap">
  <div class="card">
    <div class="card-header"><?=icon('pen-to-square')?> Edit Profile</div>
    <div class="card-body">
      <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
          <label class="form-label">Trade</label>
          <input type="text" class="form-control" value="<?=$p ? strip_tags(tradeIcon($p['trade'])).' '.$p['trade'] : ''?>" disabled style="background:#f0f0f0">
        </div>
        <div class="form-group">
          <label class="form-label">Bio / About Me</label>
          <textarea name="bio" class="form-control" rows="4" style="resize:vertical" placeholder="Describe your experience, skills, specialisations..."><?=htmlspecialchars($p['bio'] ?? '')?></textarea>
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
            <input type="text" name="phone" class="form-control" value="<?=htmlspecialchars($p['phone'] ?? '')?>">
          </div>
          <div class="form-group">
            <label class="form-label">Location (City)</label>
            <input type="text" name="location" class="form-control" value="<?=htmlspecialchars($p['location'] ?? '')?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Availability</label>
          <select name="is_available" class="form-select">
            <option value="1" <?=$p['is_available'] ? 'selected' : ''?>>Available for jobs</option>
            <option value="0" <?=!$p['is_available'] ? 'selected' : ''?>>Not available right now</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Portfolio Images</label>
          <input type="file" name="portfolio_images[]" class="form-control" accept=".jpg,.jpeg,.png,.webp" multiple>
          <div class="upload-note">Upload up to <?=MAX_IMAGE_UPLOADS?> JPG, PNG, or WEBP images. Max 5MB each.</div>
        </div>
        <button type="submit" class="btn btn-primary"><?=icon('floppy-disk')?> Save Profile</button>
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
</body></html>
