<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/mailer.php';

$pdo->exec("CREATE TABLE IF NOT EXISTS contact_messages (
    contact_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name  VARCHAR(100) NOT NULL,
    email      VARCHAR(100) NOT NULL,
    phone      VARCHAR(30) DEFAULT NULL,
    subject    VARCHAR(200) NOT NULL,
    message    TEXT NOT NULL,
    is_read    TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$success = '';
$error = '';
$old = ['full_name'=>'','email'=>'','phone'=>'','subject'=>'','message'=>''];

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])){
    $old['full_name'] = trim($_POST['full_name'] ?? '');
    $old['email']     = trim($_POST['email'] ?? '');
    $old['phone']     = trim($_POST['phone'] ?? '');
    $old['subject']   = trim($_POST['subject'] ?? '');
    $old['message']   = trim($_POST['message'] ?? '');

    if($old['full_name'] === '' || $old['email'] === '' || $old['subject'] === '' || $old['message'] === ''){
        $error = 'Please fill in your name, email, subject and message.';
    } elseif(!filter_var($old['email'], FILTER_VALIDATE_EMAIL)){
        $error = 'Please enter a valid email address.';
    } else {
        $pdo->prepare("INSERT INTO contact_messages (full_name,email,phone,subject,message) VALUES (?,?,?,?,?)")
            ->execute([
                htmlspecialchars($old['full_name']),
                $old['email'],
                htmlspecialchars($old['phone']),
                htmlspecialchars($old['subject']),
                htmlspecialchars($old['message'])
            ]);
        notifyAdminContactForm($old['full_name'], $old['email'], $old['subject'], $old['message']);
        $success = 'Thanks! Your message has been received. Our team will get back to you within one working day.';
        $old = ['full_name'=>'','email'=>'','phone'=>'','subject'=>'','message'=>''];
    }
}

$prefillName  = $_SESSION['full_name'] ?? '';
$prefillEmail = $_SESSION['email'] ?? '';
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Contact Us - QuickFix ZW</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head><body>
<?php include 'includes/navbar.php'; ?>
<div class="container"><br>
  <div class="page-title"><?=icon('headset')?> Contact Us</div>
  <p style="color:var(--gray);margin-bottom:1.5rem">Got a question, a problem with a booking, or want to partner with us? Send us a message and we'll get back to you.</p>

  <div style="display:grid;grid-template-columns:1fr 1.4fr;gap:1.5rem;align-items:flex-start" class="contact-grid">
    <div>
      <div class="section-card" style="margin-bottom:1rem">
        <h3 style="margin-bottom:0.6rem"><?=icon('envelope')?> Email</h3>
        <p>support@quickfixzw.co.zw</p>
      </div>
      <div class="section-card" style="margin-bottom:1rem">
        <h3 style="margin-bottom:0.6rem"><?=icon('phone')?> Phone / WhatsApp</h3>
        <p>+263 77 100 0000</p>
      </div>
      <div class="section-card">
        <h3 style="margin-bottom:0.6rem"><?=icon('clock')?> Hours</h3>
        <p>Mon &ndash; Sat &middot; 8:00 AM &ndash; 6:00 PM</p>
      </div>
    </div>

    <div class="section-card">
      <h3 style="margin-bottom:1rem"><?=icon('paper-plane')?> Send us a message</h3>
      <?php if($success): ?><div class="alert alert-success"><?=$success?></div><?php endif; ?>
      <?php if($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
      <form method="POST">
        <input type="hidden" name="contact_submit" value="1">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Your Name *</label>
            <input type="text" name="full_name" class="form-control" required value="<?=htmlspecialchars($old['full_name'] ?: $prefillName)?>" placeholder="Tatenda Moyo">
          </div>
          <div class="form-group">
            <label class="form-label">Email *</label>
            <input type="email" name="email" class="form-control" required value="<?=htmlspecialchars($old['email'] ?: $prefillEmail)?>" placeholder="you@email.com">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Phone (optional)</label>
            <input type="text" name="phone" class="form-control" value="<?=htmlspecialchars($old['phone'])?>" placeholder="+263771234567">
          </div>
          <div class="form-group">
            <label class="form-label">Subject *</label>
            <input type="text" name="subject" class="form-control" required value="<?=htmlspecialchars($old['subject'])?>" placeholder="e.g. Booking issue, Verification, General enquiry">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Message *</label>
          <textarea name="message" class="form-control" rows="6" required placeholder="Tell us how we can help..." style="resize:vertical"><?=htmlspecialchars($old['message'])?></textarea>
        </div>
        <button type="submit" class="btn btn-primary"><?=icon('paper-plane')?> Send Message</button>
      </form>
    </div>
  </div>
</div>
<style>
@media(max-width:780px){
  .contact-grid{grid-template-columns:1fr !important}
}
</style>
<?php include 'includes/footer.php'; ?>
</body></html>
