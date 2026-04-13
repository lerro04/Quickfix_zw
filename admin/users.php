<?php
require_once '../includes/auth.php';
requireRole('admin');
require_once '../includes/db.php';
require_once '../includes/functions.php';
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(isset($_POST['verify']))   { $pdo->prepare("UPDATE users SET verified=1 WHERE user_id=?")->execute([(int)$_POST['user_id']]); $msg="✅ User verified."; }
    if(isset($_POST['unverify'])) { $pdo->prepare("UPDATE users SET verified=0 WHERE user_id=?")->execute([(int)$_POST['user_id']]); $msg="User unverified."; }
    if(isset($_POST['delete']))   { $pdo->prepare("DELETE FROM users WHERE user_id=? AND role!='admin'")->execute([(int)$_POST['user_id']]); $msg="User removed."; }
}
$filter=$_GET['filter']??'all';
$q="SELECT u.*,pp.trade,pp.rating_avg,pp.jobs_completed FROM users u LEFT JOIN professional_profiles pp ON u.user_id=pp.user_id";
if($filter==='professionals') $q.=" WHERE u.role='professional'";
elseif($filter==='clients')   $q.=" WHERE u.role='client'";
elseif($filter==='pending')   $q.=" WHERE u.verified=0 AND u.role!='admin'";
$q.=" ORDER BY u.verified ASC, u.created_at DESC";
$users=$pdo->query($q)->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Users — QuickFix ZW Admin</title>
<link rel="stylesheet" href="/quickfix/css/style.css">
</head><body>
<?php include '../includes/navbar.php'; ?>
<div class="container"><br>
<?php if($msg): ?><div class="alert alert-success"><?=$msg?></div><?php endif; ?>
<div style="display:flex;gap:0.5rem;margin-bottom:1.5rem;flex-wrap:wrap">
  <a href="?filter=all" class="btn btn-<?=$filter==='all'?'primary':'outline'?> btn-sm">All Users</a>
  <a href="?filter=professionals" class="btn btn-<?=$filter==='professionals'?'primary':'outline'?> btn-sm">🔧 Professionals</a>
  <a href="?filter=clients" class="btn btn-<?=$filter==='clients'?'primary':'outline'?> btn-sm">🏠 Clients</a>
  <a href="?filter=pending" class="btn btn-<?=$filter==='pending'?'danger':'outline'?> btn-sm">⏳ Pending Verification</a>
</div>
<div class="card">
  <div class="card-header">👥 Users (<?=count($users)?>)</div>
  <div class="card-body">
    <div class="table-wrap"><table>
      <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Trade</th><th>Location</th><th>Phone</th><th>National ID</th><th>Rating</th><th>Verified</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach($users as $u): ?>
      <tr>
        <td><strong><?=htmlspecialchars($u['full_name'])?></strong></td>
        <td style="font-size:0.82rem"><?=$u['email']?></td>
        <td><span class="badge badge-<?=$u['role']==='admin'?'danger':($u['role']==='professional'?'primary':'success')?>"><?=ucfirst($u['role'])?></span></td>
        <td><?=$u['trade']?tradeIcon($u['trade']).' '.$u['trade']:'—'?></td>
        <td><?=$u['location']??'—'?></td>
        <td><?=$u['phone']??'—'?></td>
        <td style="font-size:0.8rem"><?=$u['national_id']??'Not provided'?></td>
        <td><?=$u['trade']?(stars($u['rating_avg']).' ('.$u['jobs_completed'].')'):'—'?></td>
        <td><?=$u['verified']?'<span class="badge badge-success">✅</span>':'<span class="badge badge-warning">⏳</span>'?></td>
        <td>
          <div style="display:flex;gap:0.3rem;flex-wrap:wrap">
            <?php if(!$u['verified'] && $u['role']!=='admin'): ?>
            <form method="POST"><input type="hidden" name="user_id" value="<?=$u['user_id']?>"><button name="verify" class="btn btn-success btn-sm">✅ Verify</button></form>
            <?php elseif($u['role']==='professional'): ?>
            <form method="POST"><input type="hidden" name="user_id" value="<?=$u['user_id']?>"><button name="unverify" class="btn btn-warning btn-sm">Unverify</button></form>
            <?php endif; ?>
            <?php if($u['role']!=='admin'): ?>
            <form method="POST" onsubmit="return confirm('Delete this user?')"><input type="hidden" name="user_id" value="<?=$u['user_id']?>"><button name="delete" class="btn btn-danger btn-sm">🗑</button></form>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>
</div>
</div>
</body></html>
