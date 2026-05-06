<?php
require_once '../includes/auth.php';
requireRole('admin');
require_once '../includes/db.php';
require_once '../includes/functions.php';

$msg = '';
if($_SERVER['REQUEST_METHOD'] === 'POST'){
 if(isset($_POST['verify'])){
 $pdo->prepare("UPDATE users SET verified=1 WHERE user_id=?")->execute([(int)$_POST['user_id']]);
 $msg = 'User verified.';
 }
 if(isset($_POST['unverify'])){
 $pdo->prepare("UPDATE users SET verified=0 WHERE user_id=?")->execute([(int)$_POST['user_id']]);
 $msg = 'User unverified.';
 }
 if(isset($_POST['delete'])){
 $userId = (int)$_POST['user_id'];
 $historyCheck = $pdo->prepare("
 SELECT
 (SELECT COUNT(*) FROM bookings WHERE client_id=? OR professional_id=?) +
 (SELECT COUNT(*) FROM bids WHERE professional_id=?) +
 (SELECT COUNT(*) FROM job_requests WHERE client_id=? OR hired_professional=?) +
 (SELECT COUNT(*) FROM reviews WHERE reviewer_id=? OR reviewee_id=?) +
 (SELECT COUNT(*) FROM messages WHERE sender_id=? OR receiver_id=?) AS related_count
 ");
 $historyCheck->execute([$userId,$userId,$userId,$userId,$userId,$userId,$userId,$userId,$userId]);
 $relatedCount = (int)$historyCheck->fetchColumn();
 if($relatedCount > 0){
 $msg = 'User has history linked to jobs, bookings, payments, reviews, or messages and cannot be hard deleted safely.';
 } else {
 $pdo->prepare("DELETE FROM users WHERE user_id=? AND role!='admin'")->execute([$userId]);
 $msg = 'User removed.';
 }
 }
}

$filter = $_GET['filter'] ?? 'all';
$perPage = 25;
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$baseFrom = " FROM users u LEFT JOIN professional_profiles pp ON u.user_id=pp.user_id";
$where = "";
if($filter === 'professionals') $where = " WHERE u.role='professional'";
elseif($filter === 'clients') $where = " WHERE u.role='client'";
elseif($filter === 'pending') $where = " WHERE u.verified=0 AND u.role!='admin'";

$countStmt = $pdo->query("SELECT COUNT(*)".$baseFrom.$where);
$totalUsers = (int)$countStmt->fetchColumn();
$pagination = paginationData($totalUsers, $perPage, $currentPage);
$query = "SELECT u.*,pp.trade,pp.rating_avg,pp.jobs_completed".$baseFrom.$where." ORDER BY u.verified ASC, u.created_at DESC LIMIT ".$perPage." OFFSET ".$pagination['offset'];
$users = $pdo->query($query)->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Users - QuickFix ZW Admin</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head><body>
<?php include '../includes/navbar.php'; ?>
<div class="container"><br>
<?php if($msg): ?><div class="alert alert-success"><?=$msg?></div><?php endif; ?>
<div style="display:flex;gap:0.5rem;margin-bottom:1.5rem;flex-wrap:wrap">
 <a href="?filter=all" class="btn btn-<?=$filter==='all'?'primary':'outline'?> btn-sm">All Users</a>
 <a href="?filter=professionals" class="btn btn-<?=$filter==='professionals'?'primary':'outline'?> btn-sm"><?=icon('user-gear')?> Professionals</a>
 <a href="?filter=clients" class="btn btn-<?=$filter==='clients'?'primary':'outline'?> btn-sm"><?=icon('house-user')?> Clients</a>
 <a href="?filter=pending" class="btn btn-<?=$filter==='pending'?'danger':'outline'?> btn-sm"><?=icon('hourglass-half')?> Pending Verification</a>
</div>
<div class="card">
 <div class="card-header"><?=icon('users')?> Users (<?=$totalUsers?>)</div>
 <div class="card-body">
 <div class="table-wrap"><table>
 <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Trade</th><th>Location</th><th>Phone</th><th>National ID</th><th>Rating</th><th>Verified</th><th>Actions</th></tr></thead>
 <tbody>
 <?php foreach($users as $u): ?>
 <tr>
 <td><strong><?=htmlspecialchars($u['full_name'])?></strong></td>
 <td style="font-size:0.82rem"><?=htmlspecialchars($u['email'])?></td>
 <td><span class="badge badge-<?=$u['role']==='admin'?'danger':($u['role']==='professional'?'primary':'success')?>"><?=ucfirst($u['role'])?></span></td>
 <td><?=$u['trade'] ? tradeIcon($u['trade']).' '.$u['trade'] : '-'?></td>
 <td><?=$u['location'] ?? '-'?></td>
 <td><?=$u['phone'] ?? '-'?></td>
 <td style="font-size:0.8rem">
 <?=$u['national_id'] ? htmlspecialchars($u['national_id']) : 'Not provided'?>
 <?php if(!empty($u['national_id_file'])): ?>
 <div style="margin-top:0.3rem;display:flex;gap:0.25rem;flex-wrap:wrap">
 <a href="<?= BASE_URL ?>/admin/view_id.php?user_id=<?=$u['user_id']?>" target="_blank" class="btn btn-outline btn-sm" style="padding:0.25rem 0.5rem;font-size:0.72rem"><?=icon('id-card')?> View ID</a>
 <a href="<?= BASE_URL ?>/admin/view_id.php?user_id=<?=$u['user_id']?>&download=1" class="btn btn-outline btn-sm" style="padding:0.25rem 0.5rem;font-size:0.72rem" title="Download"><?=icon('download')?></a>
 </div>
 <?php else: ?>
 <div style="font-size:0.72rem;color:#bbb;margin-top:0.2rem">No ID file</div>
 <?php endif; ?>
 </td>
 <td><?=$u['trade'] ? stars($u['rating_avg']).' ('.$u['jobs_completed'].')' : '-'?></td>
 <td><?=$u['verified'] ? '<span class="badge badge-success">Verified</span>' : '<span class="badge badge-warning">Pending</span>'?></td>
 <td>
 <div style="display:flex;gap:0.3rem;flex-wrap:wrap">
 <?php if(!$u['verified'] && $u['role'] !== 'admin'): ?>
 <form method="POST"><input type="hidden" name="user_id" value="<?=$u['user_id']?>"><button name="verify" class="btn btn-success btn-sm"><?=icon('check')?> Verify</button></form>
 <?php elseif($u['role'] === 'professional'): ?>
 <form method="POST"><input type="hidden" name="user_id" value="<?=$u['user_id']?>"><button name="unverify" class="btn btn-warning btn-sm">Unverify</button></form>
 <?php endif; ?>
 <?php if($u['role'] !== 'admin'): ?>
 <form method="POST" onsubmit="return confirm('Delete this user?')"><input type="hidden" name="user_id" value="<?=$u['user_id']?>"><button name="delete" class="btn btn-danger btn-sm"><?=icon('trash')?></button></form>
 <?php endif; ?>
 </div>
 </td>
 </tr>
 <?php endforeach; ?>
 </tbody>
 </table></div>
 <?=renderPagination($pagination['page'], $pagination['total_pages'])?>
 </div>
</div>
</div>
<?php include '../includes/footer.php'; ?>
</body></html>
