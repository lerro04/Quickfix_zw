<?php
require_once '../includes/auth.php';
requireRole('client');
require_once '../includes/db.php';
require_once '../includes/functions.php';
$uid = $_SESSION['user_id'];
$TRADES=['Plumbing','Electrical','Painting','Carpentry','Tiling','Roofing','Welding','Landscaping','General Handyman'];

$msg = '';
// Direct booking
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['direct_book'])){
    $pro_id = (int)$_POST['pro_id'];
    $amount = (float)$_POST['amount'];
    $sched  = $_POST['scheduled_date'];
    $note   = htmlspecialchars(trim($_POST['note']??''));
    $pdo->prepare("INSERT INTO bookings (client_id,professional_id,agreed_amount,scheduled_date,status,payment_status) VALUES (?,?,?,?,?,?)")->execute([$uid,$pro_id,$amount,$sched,'confirmed','pending']);
    $bid = $pdo->lastInsertId();
    // create a simple job record for it
    $msg = "✅ Booking confirmed! The professional will be notified.";
}

$trade  = $_GET['trade'] ?? '';
$loc    = trim($_GET['loc'] ?? '');
$q = "SELECT u.*,pp.* FROM professional_profiles pp JOIN users u ON pp.user_id=u.user_id WHERE u.verified=1 AND pp.is_available=1";
$params=[];
if($trade){ $q.=" AND pp.trade=?"; $params[]=$trade; }
if($loc)  { $q.=" AND u.location LIKE ?"; $params[]="%$loc%"; }
$q.=" ORDER BY pp.rating_avg DESC, pp.jobs_completed DESC";
$stmt=$pdo->prepare($q); $stmt->execute($params); $pros=$stmt->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Browse Professionals — QuickFix ZW</title>
<link rel="stylesheet" href="/quickfix/css/style.css">
</head><body>
<?php include '../includes/navbar.php'; ?>
<div class="container"><br>
<?php if($msg): ?><div class="alert alert-success"><?=$msg?></div><?php endif; ?>

<div class="search-bar">
  <form method="GET" style="display:flex;gap:0.8rem;flex-wrap:wrap;width:100%;align-items:flex-end">
    <div class="form-group" style="margin:0;flex:2;min-width:160px">
      <label class="form-label">Location</label>
      <input type="text" name="loc" class="form-control" placeholder="e.g. Chinhoyi, Harare" value="<?=htmlspecialchars($loc)?>">
    </div>
    <div class="form-group" style="margin:0;flex:1;min-width:160px">
      <label class="form-label">Trade</label>
      <select name="trade" class="form-select">
        <option value="">All Trades</option>
        <?php foreach($TRADES as $t): ?><option value="<?=$t?>" <?=$trade===$t?'selected':''?>><?=tradeIcon($t)?> <?=$t?></option><?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn btn-primary" style="margin-bottom:0">🔍 Search</button>
    <a href="/quickfix/client/browse.php" class="btn btn-outline" style="margin-bottom:0">Clear</a>
  </form>
</div>

<!-- Trade pills -->
<div class="trade-pills">
  <a href="/quickfix/client/browse.php" class="pill <?=$trade===''?'active':''?>">All</a>
  <?php foreach($TRADES as $t): ?>
  <a href="?trade=<?=urlencode($t)?>" class="pill <?=$trade===$t?'active':''?>"><?=tradeIcon($t)?> <?=$t?></a>
  <?php endforeach; ?>
</div>

<p style="color:var(--gray);margin-bottom:1rem"><?=count($pros)?> professional(s) found</p>

<div class="card-grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr))">
<?php foreach($pros as $p): ?>
<div class="pro-card">
  <div class="pro-avatar"><?=strtoupper(substr($p['full_name'],0,1))?></div>
  <div class="pro-name"><?=htmlspecialchars($p['full_name'])?></div>
  <div class="pro-trade"><?=tradeIcon($p['trade'])?> <?=$p['trade']?></div>
  <div class="pro-meta">📍 <?=$p['location']??'Zimbabwe'?></div>
  <div class="pro-meta">📞 <?=$p['phone']?></div>
  <div class="pro-meta">✅ <?=$p['jobs_completed']?> jobs · <?=$p['years_experience']?> yrs exp</div>
  <div style="margin:0.3rem 0;font-size:0.9rem"><?=stars($p['rating_avg'])?> <span style="color:#999;font-size:0.8rem">(<?=$p['total_reviews']?> reviews)</span></div>
  <?php if($p['service_area']): ?><div class="pro-meta">🗺️ <?=$p['service_area']?></div><?php endif; ?>
  <div class="pro-rate">$<?=number_format($p['hourly_rate'],2)?>/hr</div>
  <div style="margin-top:0.3rem;font-size:0.82rem;color:var(--gray);line-height:1.5"><?=htmlspecialchars(substr($p['bio'],0,100))?><?=strlen($p['bio'])>100?'...':''?></div>
  <div style="display:flex;gap:0.5rem;margin-top:1rem">
    <button onclick="openBook(<?=$p['user_id']?>,'<?=htmlspecialchars($p['full_name'],ENT_QUOTES)?>',<?=$p['hourly_rate']?>)" class="btn btn-primary btn-sm" style="flex:1">📅 Book Now</button>
    <a href="/quickfix/messages.php?with=<?=$p['user_id']?>" class="btn btn-outline btn-sm">💬</a>
  </div>
</div>
<?php endforeach; ?>
</div>
<?php if(empty($pros)): ?><div class="alert alert-info">No professionals found for that search. Try a different trade or location.</div><?php endif; ?>
</div>

<!-- Booking Modal -->
<div class="modal-overlay" id="book-modal">
  <div class="modal-box">
    <div class="modal-header">
      📅 Book Professional
      <span class="modal-close" onclick="document.getElementById('book-modal').classList.remove('show')">✕</span>
    </div>
    <div class="modal-body">
      <p id="book-pro-name" style="font-weight:700;font-size:1.05rem;margin-bottom:1rem;color:var(--primary)"></p>
      <form method="POST">
        <input type="hidden" name="direct_book" value="1">
        <input type="hidden" name="pro_id" id="book-pro-id">
        <div class="form-group">
          <label class="form-label">Describe the Job *</label>
          <textarea name="note" class="form-control" rows="3" placeholder="e.g. Fix leaking bathroom pipe under sink..." required style="resize:vertical"></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Agreed Amount (USD) *</label>
            <input type="number" name="amount" id="book-amount" class="form-control" step="0.50" min="1" required>
          </div>
          <div class="form-group">
            <label class="form-label">Preferred Date *</label>
            <input type="date" name="scheduled_date" class="form-control" min="<?=date('Y-m-d')?>" required>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block">✅ Confirm Booking</button>
      </form>
    </div>
  </div>
</div>

<script>
function openBook(id, name, rate){
  document.getElementById('book-pro-id').value = id;
  document.getElementById('book-pro-name').textContent = '📋 Booking: ' + name;
  document.getElementById('book-amount').value = rate;
  document.getElementById('book-modal').classList.add('show');
}
</script>
</body></html>
