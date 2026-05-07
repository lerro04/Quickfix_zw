<?php
require_once '../includes/auth.php';
requireRole('admin');
require_once '../includes/db.php';
require_once '../includes/functions.php';

$msg = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
 if(isset($_POST['add_trade'])){
 $name = htmlspecialchars(trim($_POST['name'] ?? ''));
 if($name !== ''){
 $stmt = $pdo->prepare("INSERT IGNORE INTO trades (name) VALUES (?)");
 $stmt->execute([$name]);
 $msg = $stmt->rowCount() > 0 ? " Trade '$name' added." : "Trade '$name' already exists.";
 }
 }
 if(isset($_POST['toggle'])){
 $id = (int)$_POST['trade_id'];
 $pdo->prepare("UPDATE trades SET is_active = 1 - is_active WHERE trade_id=?")->execute([$id]);
 $msg = ' Trade updated.';
 }
 if(isset($_POST['delete'])){
 $id = (int)$_POST['trade_id'];
 $name = $pdo->prepare("SELECT name FROM trades WHERE trade_id=?");
 $name->execute([$id]);
 $row = $name->fetch();
 if($row){
 $usage = $pdo->prepare("SELECT (SELECT COUNT(*) FROM professional_profiles WHERE trade=?) + (SELECT COUNT(*) FROM job_requests WHERE trade=?)");
 $usage->execute([$row['name'], $row['name']]);
 $count = (int)$usage->fetchColumn();
 if($count > 0){
 $msg = " Trade '{$row['name']}' is in use by $count record(s). Deactivate it instead of deleting.";
 } else {
 $pdo->prepare("DELETE FROM trades WHERE trade_id=?")->execute([$id]);
 $msg = " Trade removed.";
 }
 }
 }
}

$trades = $pdo->query("SELECT t.*,
 (SELECT COUNT(*) FROM professional_profiles WHERE trade=t.name) AS pro_count,
 (SELECT COUNT(*) FROM job_requests WHERE trade=t.name) AS job_count
 FROM trades t ORDER BY t.is_active DESC, t.name")->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Trades - QuickFix ZW Admin</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head><body>
<?php include '../includes/navbar.php'; ?>
<div class="container"><br>
<?php if($msg): ?><div class="alert alert-info"><?=$msg?></div><?php endif; ?>
<div class="page-title"><?=icon('list')?> Manage Trades / Services</div>
<p style="color:var(--gray);margin-bottom:1.2rem">These are the service categories shown in registration, profile editing and job posting forms. Professionals can also add their own &mdash; admin can curate them here.</p>

<div class="card" style="margin-bottom:1.5rem;max-width:600px">
 <div class="card-header"><?=icon('plus')?> Add a new trade</div>
 <div class="card-body">
 <form method="POST" style="display:flex;gap:0.6rem;flex-wrap:wrap">
 <input type="text" name="name" class="form-control" placeholder="e.g. Solar Installation" required style="flex:1;min-width:220px;margin:0">
 <button name="add_trade" class="btn btn-primary"><?=icon('plus')?> Add Trade</button>
 </form>
 </div>
</div>

<div class="card">
 <div class="card-header"><?=icon('list')?> All trades (<?=count($trades)?>)</div>
 <div class="card-body">
 <div class="table-wrap"><table>
 <thead><tr><th>Name</th><th>Status</th><th>Professionals</th><th>Job posts</th><th>Actions</th></tr></thead>
 <tbody>
 <?php foreach($trades as $t): ?>
 <tr>
 <td><strong><?=htmlspecialchars($t['name'])?></strong></td>
 <td><?= $t['is_active'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-warning">Hidden</span>' ?></td>
 <td><?=$t['pro_count']?></td>
 <td><?=$t['job_count']?></td>
 <td>
 <div style="display:flex;gap:0.3rem;flex-wrap:wrap">
 <form method="POST"><input type="hidden" name="trade_id" value="<?=$t['trade_id']?>"><button name="toggle" class="btn btn-outline btn-sm"><?= $t['is_active'] ? 'Hide' : 'Activate' ?></button></form>
 <form method="POST" onsubmit="return confirm('Delete this trade?')"><input type="hidden" name="trade_id" value="<?=$t['trade_id']?>"><button name="delete" class="btn btn-danger btn-sm"><?=icon('trash')?></button></form>
 </div>
 </td>
 </tr>
 <?php endforeach; ?>
 </tbody>
 </table></div>
 </div>
</div>
</div>
<?php include '../includes/footer.php'; ?>
</body></html>
