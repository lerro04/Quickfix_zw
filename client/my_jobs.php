<?php
require_once '../includes/auth.php';
requireRole('client');
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/mailer.php';
$uid = $_SESSION['user_id'];
$msg='';

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(isset($_POST['accept_bid'])){
        $bid_id = (int)$_POST['bid_id'];
        $bid = $pdo->prepare("SELECT b.*,j.client_id FROM bids b JOIN job_requests j ON b.job_id=j.job_id WHERE b.bid_id=? AND j.client_id=?");
        $bid->execute([$bid_id,$uid]); $b=$bid->fetch();
        if($b){
            $pdo->prepare("UPDATE bids SET status='accepted' WHERE bid_id=?")->execute([$bid_id]);
            $pdo->prepare("UPDATE bids SET status='rejected' WHERE job_id=? AND bid_id!=?")->execute([$b['job_id'],$bid_id]);
            $pdo->prepare("UPDATE job_requests SET status='in_progress', hired_professional=? WHERE job_id=?")->execute([$b['professional_id'],$b['job_id']]);
            $pdo->prepare("INSERT INTO bookings (job_id,client_id,professional_id,bid_id,agreed_amount,status,payment_status) VALUES (?,?,?,?,?,?,?)")->execute([$b['job_id'],$uid,$b['professional_id'],$bid_id,$b['bid_amount'],'confirmed','pending']);
            $newBookingId = (int)$pdo->lastInsertId();
            notifyProBidAccepted($pdo, $bid_id);
            notifyProNewBooking($pdo, $newBookingId);
            $msg="✅ Bid accepted! Booking created. The professional has been notified.";
        }
    }
    if(isset($_POST['reject_bid'])){
        $pdo->prepare("UPDATE bids SET status='rejected' WHERE bid_id=?")->execute([(int)$_POST['bid_id']]);
        $msg="Bid rejected.";
    }
    if(isset($_POST['cancel_job'])){
        $pdo->prepare("UPDATE job_requests SET status='cancelled' WHERE job_id=? AND client_id=?")->execute([(int)$_POST['job_id'],$uid]);
        $msg="Job cancelled.";
    }
}

$view = (int)($_GET['job']??0);
$jobs = $pdo->prepare("SELECT j.*,(SELECT COUNT(*) FROM bids WHERE job_id=j.job_id) as bid_count FROM job_requests j WHERE j.client_id=? ORDER BY j.created_at DESC");
$jobs->execute([$uid]); $myJobs=$jobs->fetchAll();

$bids=[];
if($view){
    $bs = $pdo->prepare("SELECT b.*,u.full_name as pro_name,u.phone,u.location,pp.trade,pp.rating_avg,pp.jobs_completed,pp.years_experience FROM bids b JOIN users u ON b.professional_id=u.user_id JOIN professional_profiles pp ON u.user_id=pp.user_id WHERE b.job_id=? ORDER BY b.bid_amount ASC");
    $bs->execute([$view]); $bids=$bs->fetchAll();
}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Jobs — QuickFix ZW</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head><body>
<?php include '../includes/navbar.php'; ?>
<div class="container"><br>
<?php if($msg): ?><div class="alert alert-<?=str_starts_with($msg,'✅')?'success':'warning'?>"><?=$msg?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:300px 1fr;gap:1.5rem;min-height:500px">
  <!-- Jobs list -->
  <div class="card" style="height:fit-content">
    <div class="card-header">📋 My Job Requests</div>
    <div>
      <?php foreach($myJobs as $j): ?>
      <a href="?job=<?=$j['job_id']?>" style="display:block;padding:0.9rem 1.2rem;border-left:4px solid <?=$view==$j['job_id']?'var(--primary)':'transparent'?>;background:<?=$view==$j['job_id']?'rgba(230,92,0,0.05)':'white'?>;border-bottom:1px solid var(--border);transition:all 0.2s">
        <div style="font-weight:600;font-size:0.9rem"><?=htmlspecialchars(substr($j['title'],0,38))?><?=strlen($j['title'])>38?'...':''?></div>
        <div style="font-size:0.78rem;color:#999;margin-top:0.2rem">
          <?=tradeIcon($j['trade'])?> <?=$j['trade']?> · 
          <span class="badge badge-<?=$j['status']==='open'?'success':($j['status']==='in_progress'?'info':'warning')?>" style="font-size:0.7rem"><?=ucfirst($j['status'])?></span>
          · <?=$j['bid_count']?> bid(s)
        </div>
      </a>
      <?php endforeach; ?>
      <?php if(empty($myJobs)): ?><p style="padding:1.2rem;color:#999;font-size:0.88rem">No jobs yet. <a href="<?= BASE_URL ?>/client/post_job.php" style="color:var(--primary)">Post one!</a></p><?php endif; ?>
    </div>
  </div>

  <!-- Bids panel -->
  <div>
    <?php if($view && !empty($bids)): ?>
    <?php $job = array_values(array_filter($myJobs, fn($j)=>$j['job_id']==$view))[0]??null; ?>
    <?php if($job): ?>
    <div class="card" style="margin-bottom:1rem">
      <div class="card-body" style="padding:1rem 1.5rem">
        <h3 style="margin-bottom:0.3rem"><?=htmlspecialchars($job['title'])?></h3>
        <div style="display:flex;gap:1rem;flex-wrap:wrap;font-size:0.85rem;color:var(--gray)">
          <span><?=tradeIcon($job['trade'])?> <?=$job['trade']?></span>
          <span>📍 <?=$job['location']?></span>
          <span>💵 Budget: $<?=number_format($job['client_budget'],2)?></span>
          <span><?=urgencyBadge($job['urgency'])?></span>
        </div>
        <?php if($job['status']==='open'): ?>
        <form method="POST" style="display:inline;margin-top:0.5rem" onsubmit="return confirm('Cancel this job?')">
          <input type="hidden" name="job_id" value="<?=$job['job_id']?>">
          <button name="cancel_job" class="btn btn-danger btn-sm">🗑 Cancel Job</button>
        </form>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <h3 style="margin-bottom:1rem">💰 <?=count($bids)?> Bid(s) — Lowest First</h3>
    <?php foreach($bids as $b): ?>
    <div class="bid-card <?=$b['status']==='accepted'?'accepted':''?>">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:0.5rem">
        <div>
          <strong style="font-size:1rem"><?=$b['pro_name']?></strong>
          <div style="font-size:0.82rem;color:var(--gray);margin-top:0.2rem">
            <?=tradeIcon($b['trade'])?> <?=$b['trade']?> · 📍 <?=$b['location']?> · 📞 <?=$b['phone']?>
          </div>
          <div style="font-size:0.82rem;margin-top:0.2rem">
            <?=stars($b['rating_avg'])?> · ✅ <?=$b['jobs_completed']?> jobs · <?=$b['years_experience']?> yrs exp
          </div>
        </div>
        <div style="text-align:right">
          <div class="bid-amount">$<?=number_format($b['bid_amount'],2)?></div>
          <div style="font-size:0.8rem;color:#999">⏱ <?=$b['estimated_days']?> day(s)</div>
          <span class="badge badge-<?=$b['status']==='accepted'?'success':($b['status']==='rejected'?'danger':'warning')?>"><?=ucfirst($b['status'])?></span>
        </div>
      </div>
      <?php if($b['message']): ?><p style="margin:0.7rem 0;font-size:0.88rem;line-height:1.5;color:var(--dark)"><?=htmlspecialchars($b['message'])?></p><?php endif; ?>
      <?php if($b['status']==='pending' && ($job['status']??'')==='open'): ?>
      <div style="display:flex;gap:0.5rem;margin-top:0.8rem">
        <form method="POST"><input type="hidden" name="bid_id" value="<?=$b['bid_id']?>"><button name="accept_bid" class="btn btn-success btn-sm" onclick="return confirm('Accept this bid? A booking will be created.')">✅ Accept Bid</button></form>
        <form method="POST"><input type="hidden" name="bid_id" value="<?=$b['bid_id']?>"><button name="reject_bid" class="btn btn-danger btn-sm">❌ Reject</button></form>
        <a href="<?= BASE_URL ?>/messages.php?with=<?=$b['professional_id']?>" class="btn btn-outline btn-sm">💬 Message</a>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <?php elseif($view): ?>
    <div class="alert alert-info" style="margin-top:1rem">No bids yet on this job. Professionals will bid soon!</div>
    <?php else: ?>
    <div class="card"><div class="card-body" style="text-align:center;padding:3rem;color:#999">
      <div style="font-size:3rem;margin-bottom:1rem">👈</div>
      <p>Select a job from the left to see its bids</p>
    </div></div>
    <?php endif; ?>
  </div>
</div>
</div>
</body></html>
