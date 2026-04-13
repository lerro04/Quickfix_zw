<?php
require_once '../includes/auth.php';
requireRole('professional');
require_once '../includes/db.php';
require_once '../includes/functions.php';
$uid = $_SESSION['user_id'];
$msg='';

$prof=$pdo->prepare("SELECT * FROM professional_profiles WHERE user_id=?"); $prof->execute([$uid]); $p=$prof->fetch();
$user=$pdo->prepare("SELECT * FROM users WHERE user_id=?"); $user->execute([$uid]); $u=$user->fetch();

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['place_bid'])){
    if(!$u['verified']){ $msg="❌ Your account must be verified before you can bid."; }
    else {
        $job_id=(int)$_POST['job_id'];
        $amount=(float)$_POST['bid_amount'];
        $message=htmlspecialchars(trim($_POST['bid_message']));
        $days=(int)$_POST['estimated_days'];
        $ex=$pdo->prepare("SELECT bid_id FROM bids WHERE job_id=? AND professional_id=?"); $ex->execute([$job_id,$uid]);
        if($ex->fetch()){ $msg="❌ You already placed a bid on this job."; }
        else {
            $pdo->prepare("INSERT INTO bids (job_id,professional_id,bid_amount,message,estimated_days) VALUES (?,?,?,?,?)")->execute([$job_id,$uid,$amount,$message,$days]);
            $msg="✅ Bid placed successfully! You'll be notified if the client accepts.";
        }
    }
}

$filter=$_GET['filter']??'all';
$q="SELECT j.*,u.full_name as client_name,(SELECT COUNT(*) FROM bids WHERE job_id=j.job_id) as bid_count,(SELECT bid_id FROM bids WHERE job_id=j.job_id AND professional_id=$uid LIMIT 1) as my_bid FROM job_requests j JOIN users u ON j.client_id=u.user_id WHERE j.status='open'";
if($filter==='my_trade') $q.=" AND j.trade='".$p['trade']."'";
if($filter==='urgent') $q.=" AND j.urgency='urgent'";
$q.=" ORDER BY FIELD(j.urgency,'urgent','within_week','flexible'), j.created_at DESC";
$stmt=$pdo->query($q); $jobs=$stmt->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Job Board — QuickFix ZW</title>
<link rel="stylesheet" href="/quickfix/css/style.css">
</head><body>
<?php include '../includes/navbar.php'; ?>
<div class="container"><br>
<?php if($msg): ?><div class="alert alert-<?=str_starts_with($msg,'✅')?'success':'danger'?>"><?=$msg?></div><?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1.2rem">
  <div class="page-title" style="margin:0">📋 Job Board</div>
  <div style="display:flex;gap:0.5rem;flex-wrap:wrap">
    <a href="?filter=all" class="btn btn-<?=$filter==='all'?'primary':'outline'?> btn-sm">All Jobs</a>
    <a href="?filter=my_trade" class="btn btn-<?=$filter==='my_trade'?'primary':'outline'?> btn-sm"><?=tradeIcon($p['trade'])?> <?=$p['trade']?> Only</a>
    <a href="?filter=urgent" class="btn btn-<?=$filter==='urgent'?'danger':'outline'?> btn-sm">🔴 Urgent Only</a>
  </div>
</div>

<p style="color:var(--gray);margin-bottom:1rem"><?=count($jobs)?> open job(s)</p>

<?php foreach($jobs as $j): ?>
<div class="job-card">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem">
    <div style="flex:1">
      <div class="job-title"><?=htmlspecialchars($j['title'])?></div>
      <div class="job-meta">
        <?=tradeIcon($j['trade'])?> <strong><?=$j['trade']?></strong> &nbsp;·&nbsp;
        📍 <?=$j['location']?> &nbsp;·&nbsp;
        👤 <?=$j['client_name']?> &nbsp;·&nbsp;
        ⏱ <?=timeAgo($j['created_at'])?>
        &nbsp;·&nbsp; <?=urgencyBadge($j['urgency'])?>
      </div>
      <div class="job-desc"><?=htmlspecialchars(substr($j['description'],0,220))?><?=strlen($j['description'])>220?'...':''?></div>
    </div>
    <div style="text-align:right;min-width:130px">
      <div class="job-budget">$<?=number_format($j['client_budget'],2)?></div>
      <div style="font-size:0.8rem;color:#999">Client Budget</div>
      <div style="margin-top:0.4rem;font-size:0.82rem;color:var(--gray)"><?=$j['bid_count']?> bid(s)</div>
    </div>
  </div>

  <div style="margin-top:0.8rem;display:flex;gap:0.5rem;flex-wrap:wrap">
    <?php if($j['my_bid']): ?>
      <span class="badge badge-success" style="font-size:0.85rem;padding:0.4rem 0.8rem">✅ You already bid on this</span>
    <?php else: ?>
      <button onclick="openBid(<?=$j['job_id']?>,'<?=htmlspecialchars($j['title'],ENT_QUOTES)?>',<?=$j['client_budget']?>)" class="btn btn-primary btn-sm">💰 Place Bid</button>
    <?php endif; ?>
    <a href="/quickfix/messages.php?with=<?=$j['client_id']?>" class="btn btn-outline btn-sm">💬 Message Client</a>
  </div>
</div>
<?php endforeach; ?>
<?php if(empty($jobs)): ?><div class="alert alert-info">No open jobs at the moment. Check back soon!</div><?php endif; ?>
</div>

<!-- Bid Modal -->
<div class="modal-overlay" id="bid-modal">
  <div class="modal-box">
    <div class="modal-header">
      💰 Place Your Bid
      <span class="modal-close" onclick="document.getElementById('bid-modal').classList.remove('show')">✕</span>
    </div>
    <div class="modal-body">
      <p id="bid-job-title" style="font-weight:700;color:var(--primary);margin-bottom:0.3rem"></p>
      <p style="font-size:0.85rem;color:#999;margin-bottom:1rem">Client budget: <strong id="bid-budget"></strong></p>
      <form method="POST">
        <input type="hidden" name="place_bid" value="1">
        <input type="hidden" name="job_id" id="bid-job-id">
        <div class="form-group">
          <label class="form-label">Your Bid Amount (USD) *</label>
          <input type="number" name="bid_amount" id="bid-amount" class="form-control" step="0.50" min="1" required>
          <small style="color:#999">You can bid lower than the client's budget to be competitive</small>
        </div>
        <div class="form-group">
          <label class="form-label">Estimated Days to Complete *</label>
          <input type="number" name="estimated_days" class="form-control" min="1" value="1" required>
        </div>
        <div class="form-group">
          <label class="form-label">Message to Client *</label>
          <textarea name="bid_message" class="form-control" rows="4" placeholder="Briefly explain your approach, experience with this type of job, and why they should choose you..." required style="resize:vertical"></textarea>
        </div>
        <button type="submit" class="btn btn-primary btn-block">🚀 Submit Bid</button>
      </form>
    </div>
  </div>
</div>

<script>
function openBid(id, title, budget){
  document.getElementById('bid-job-id').value = id;
  document.getElementById('bid-job-title').textContent = title;
  document.getElementById('bid-budget').textContent = '$'+parseFloat(budget).toFixed(2);
  document.getElementById('bid-amount').value = budget;
  document.getElementById('bid-modal').classList.add('show');
}
</script>
</body></html>
