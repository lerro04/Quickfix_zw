<?php
require_once 'includes/auth.php';
requireLogin();
require_once 'includes/db.php';
require_once 'includes/functions.php';
$uid=$_SESSION['user_id'];
$role=$_SESSION['role'];

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['send'])){
    $to=(int)$_POST['receiver_id'];
    $msg=htmlspecialchars(trim($_POST['message']));
    if($msg && $to) $pdo->prepare("INSERT INTO messages (sender_id,receiver_id,message) VALUES (?,?,?)")->execute([$uid,$to,$msg]);
    header('Location: '.BASE_URL.'/messages.php?with='.$to); exit;
}

$thread=(int)($_GET['with']??0);

// Mark messages as read
if($thread) $pdo->prepare("UPDATE messages SET is_read=1 WHERE sender_id=? AND receiver_id=?")->execute([$thread,$uid]);

// Get all conversation partners
$convos=$pdo->prepare("SELECT DISTINCT u.user_id,u.full_name,u.role,
    (SELECT message FROM messages WHERE (sender_id=? AND receiver_id=u.user_id) OR (sender_id=u.user_id AND receiver_id=?) ORDER BY created_at DESC LIMIT 1) as last_msg,
    (SELECT COUNT(*) FROM messages WHERE sender_id=u.user_id AND receiver_id=? AND is_read=0) as unread
    FROM messages m JOIN users u ON (u.user_id=m.sender_id OR u.user_id=m.receiver_id)
    WHERE (m.sender_id=? OR m.receiver_id=?) AND u.user_id!=?");
$convos->execute([$uid,$uid,$uid,$uid,$uid,$uid]); $conversations=$convos->fetchAll();

// Get messages in thread
$messages=[];
$threadUser=null;
if($thread){
    $msgs=$pdo->prepare("SELECT m.*,u.full_name as sender_name FROM messages m JOIN users u ON m.sender_id=u.user_id WHERE (m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?) ORDER BY m.created_at ASC");
    $msgs->execute([$uid,$thread,$thread,$uid]); $messages=$msgs->fetchAll();
    $tu=$pdo->prepare("SELECT user_id,full_name,role FROM users WHERE user_id=?"); $tu->execute([$thread]); $threadUser=$tu->fetch();
}

// For new conversation — get all possible contacts
$role=$_SESSION['role'];
if($role==='client'){
    $contacts=$pdo->query("SELECT user_id,full_name,role FROM users WHERE role='professional' AND verified=1 ORDER BY full_name")->fetchAll();
} elseif($role==='professional'){
    $contacts=$pdo->query("SELECT user_id,full_name,role FROM users WHERE role='client' ORDER BY full_name")->fetchAll();
} else {
    $contacts=$pdo->query("SELECT user_id,full_name,role FROM users WHERE user_id!=".$uid." ORDER BY full_name")->fetchAll();
}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Messages — QuickFix ZW</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
.convo-item{display:block;padding:0.85rem 1.2rem;border-left:4px solid transparent;border-bottom:1px solid var(--border);transition:all 0.2s;cursor:pointer;}
.convo-item:hover,.convo-item.active{background:rgba(230,92,0,0.06);border-left-color:var(--primary);}
.convo-name{font-weight:600;font-size:0.9rem;}
.convo-preview{font-size:0.78rem;color:#999;margin-top:0.15rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
</style>
</head><body>
<?php include 'includes/navbar.php'; ?>
<div class="container"><br>
<div style="display:grid;grid-template-columns:280px 1fr;gap:1.5rem;height:calc(100vh - 160px);min-height:500px">

  <!-- Sidebar -->
  <div class="card" style="display:flex;flex-direction:column;overflow:hidden">
    <div class="card-header">💬 Messages</div>
    <div style="padding:0.7rem;border-bottom:1px solid var(--border)">
      <select onchange="if(this.value) window.location='<?= BASE_URL ?>/messages.php?with='+this.value" class="form-select" style="font-size:0.82rem">
        <option value="">➕ Start new conversation</option>
        <?php foreach($contacts as $c): ?>
        <option value="<?=$c['user_id']?>"><?=$c['full_name']?> (<?=ucfirst($c['role'])?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="overflow-y:auto;flex:1">
      <?php foreach($conversations as $c): ?>
      <a href="?with=<?=$c['user_id']?>" class="convo-item <?=$thread==$c['user_id']?'active':''?>">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <div class="convo-name"><?=htmlspecialchars($c['full_name'])?></div>
          <?php if($c['unread']>0): ?><span class="badge badge-danger" style="font-size:0.7rem"><?=$c['unread']?></span><?php endif; ?>
        </div>
        <div class="convo-preview"><?=htmlspecialchars(substr($c['last_msg']??'',0,45))?></div>
      </a>
      <?php endforeach; ?>
      <?php if(empty($conversations)): ?><p style="padding:1.2rem;color:#999;font-size:0.85rem">No conversations yet. Start one above!</p><?php endif; ?>
    </div>
  </div>

  <!-- Chat area -->
  <div class="card" style="display:flex;flex-direction:column;overflow:hidden">
    <?php if($thread && $threadUser): ?>
    <div class="card-header">
      💬 <?=htmlspecialchars($threadUser['full_name'])?>
      <span style="font-size:0.78rem;opacity:0.7;margin-left:0.5rem">(<?=ucfirst($threadUser['role'])?>)</span>
    </div>
    <div class="msg-box" id="msgBox">
      <?php if(empty($messages)): ?>
      <div style="margin:auto;text-align:center;color:#999">
        <div style="font-size:2rem;margin-bottom:0.5rem">👋</div>
        <p style="font-size:0.9rem">Start a conversation with <?=htmlspecialchars($threadUser['full_name'])?>!</p>
      </div>
      <?php else: ?>
      <?php foreach($messages as $m): ?>
      <div class="msg <?=$m['sender_id']==$uid?'mine':'theirs'?>">
        <?=htmlspecialchars($m['message'])?>
        <div class="msg-time"><?=timeAgo($m['created_at'])?></div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <div style="padding:1rem;border-top:1px solid var(--border)">
      <form method="POST" style="display:flex;gap:0.6rem">
        <input type="hidden" name="receiver_id" value="<?=$thread?>">
        <input type="text" name="message" class="form-control" placeholder="Type a message..." required autocomplete="off" style="flex:1">
        <button name="send" type="submit" class="btn btn-primary">Send →</button>
      </form>
    </div>
    <?php else: ?>
    <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#999;gap:0.8rem">
      <div style="font-size:3rem">💬</div>
      <p>Select a conversation or start a new one</p>
    </div>
    <?php endif; ?>
  </div>
</div>
</div>
<script>
const b=document.getElementById('msgBox');
if(b) b.scrollTop=b.scrollHeight;
</script>
</body></html>
