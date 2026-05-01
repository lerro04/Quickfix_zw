<?php
require_once __DIR__.'/config.php';

if(!defined('MAIL_FROM'))       define('MAIL_FROM',       'no-reply@quickfixzw.co.zw');
if(!defined('MAIL_FROM_NAME'))  define('MAIL_FROM_NAME',  'QuickFix ZW');
if(!defined('MAIL_ADMIN'))      define('MAIL_ADMIN',      'admin@quickfixzw.co.zw');
if(!defined('MAIL_ENABLED'))    define('MAIL_ENABLED',    true);

function sendEmail(string $to, string $subject, string $htmlBody): bool {
    if(!MAIL_ENABLED) return false;
    if($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) return false;
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: '.MAIL_FROM_NAME.' <'.MAIL_FROM.'>',
        'Reply-To: '.MAIL_FROM,
        'X-Mailer: QuickFixZW/1.0',
    ];
    $sent = @mail($to, $subject, $htmlBody, implode("\r\n", $headers));
    if(!$sent){
        $logDir = __DIR__.'/../logs';
        if(!is_dir($logDir)) @mkdir($logDir, 0755, true);
        @file_put_contents($logDir.'/mail_failures.log',
            '['.date('c')."] to=$to subject=\"$subject\"\n",
            FILE_APPEND);
    }
    return $sent;
}

function emailLayout(string $heading, string $bodyHtml, string $ctaText = '', string $ctaUrl = ''): string {
    $cta = '';
    if($ctaText !== '' && $ctaUrl !== ''){
        $cta = '<p style="margin:1.5rem 0"><a href="'.htmlspecialchars($ctaUrl).'" style="background:#e65c00;color:#fff;padding:0.7rem 1.4rem;text-decoration:none;border-radius:6px;font-weight:600;display:inline-block">'.htmlspecialchars($ctaText).'</a></p>';
    }
    return '<!DOCTYPE html><html><body style="font-family:Arial,sans-serif;background:#f4f5f9;margin:0;padding:24px">
        <div style="max-width:560px;margin:0 auto;background:#fff;border-radius:10px;padding:28px;border-top:4px solid #e65c00">
            <div style="font-size:1.3rem;font-weight:800;color:#1a1a2e;margin-bottom:1rem">QuickFix ZW</div>
            <h2 style="font-size:1.15rem;color:#1a1a2e;margin:0 0 1rem">'.htmlspecialchars($heading).'</h2>
            <div style="color:#333;line-height:1.6;font-size:0.95rem">'.$bodyHtml.'</div>
            '.$cta.'
            <p style="color:#999;font-size:0.78rem;margin-top:2rem;border-top:1px solid #eee;padding-top:1rem">You are receiving this because of activity on your QuickFix ZW account.</p>
        </div>
    </body></html>';
}

function notifyAdminNewProfessional(PDO $pdo, int $userId): void {
    $u = $pdo->prepare("SELECT u.*, pp.trade FROM users u LEFT JOIN professional_profiles pp ON u.user_id=pp.user_id WHERE u.user_id=?");
    $u->execute([$userId]);
    $user = $u->fetch();
    if(!$user) return;
    $body = emailLayout(
        'New professional needs verification',
        '<p>A new service provider has signed up and is awaiting your verification:</p>'
        .'<ul style="line-height:1.8">'
        .'<li><strong>Name:</strong> '.htmlspecialchars($user['full_name']).'</li>'
        .'<li><strong>Trade:</strong> '.htmlspecialchars($user['trade'] ?? 'Not set').'</li>'
        .'<li><strong>Email:</strong> '.htmlspecialchars($user['email']).'</li>'
        .'<li><strong>Phone:</strong> '.htmlspecialchars($user['phone'] ?? '—').'</li>'
        .'<li><strong>Location:</strong> '.htmlspecialchars($user['location'] ?? '—').'</li>'
        .'<li><strong>National ID:</strong> '.htmlspecialchars($user['national_id'] ?? '—').'</li>'
        .'</ul>'
        .'<p>Verify them so they can start bidding on jobs.</p>',
        'Open admin users',
        rtrim(getSiteUrl(), '/').BASE_URL.'/admin/users.php?filter=pending'
    );
    sendEmail(MAIL_ADMIN, 'New professional awaiting verification — '.$user['full_name'], $body);
}

function notifyProsOfNewJob(PDO $pdo, int $jobId): void {
    $j = $pdo->prepare("SELECT j.*, u.full_name AS client FROM job_requests j JOIN users u ON j.client_id=u.user_id WHERE j.job_id=?");
    $j->execute([$jobId]);
    $job = $j->fetch();
    if(!$job) return;
    $pros = $pdo->prepare("SELECT u.user_id, u.full_name, u.email FROM users u JOIN professional_profiles pp ON u.user_id=pp.user_id WHERE pp.trade=? AND u.verified=1 AND pp.is_available=1");
    $pros->execute([$job['trade']]);
    $jobUrl = rtrim(getSiteUrl(), '/').BASE_URL.'/professional/job_board.php';
    foreach($pros->fetchAll() as $pro){
        $body = emailLayout(
            'New '.$job['trade'].' job posted',
            '<p>Hi '.htmlspecialchars($pro['full_name']).',</p>'
            .'<p>A new job has been posted in your trade:</p>'
            .'<div style="background:#f6f6fa;padding:1rem;border-radius:8px;margin:0.8rem 0">'
            .'<strong>'.htmlspecialchars($job['title']).'</strong><br>'
            .'<span style="color:#666;font-size:0.9rem">'.htmlspecialchars($job['trade']).' &middot; '.htmlspecialchars($job['location']).' &middot; Budget $'.number_format((float)$job['client_budget'], 2).'</span>'
            .'<p style="font-size:0.9rem;line-height:1.5;margin:0.5rem 0 0">'.nl2br(htmlspecialchars(mb_substr($job['description'], 0, 220))).'</p>'
            .'</div>'
            .'<p>Place your bid early to win the job.</p>',
            'View job board',
            $jobUrl
        );
        sendEmail($pro['email'], 'New '.$job['trade'].' job: '.$job['title'], $body);
    }
}

function notifyClientOfNewBid(PDO $pdo, int $bidId): void {
    $q = $pdo->prepare("SELECT b.*, j.title, j.client_id, c.full_name AS client_name, c.email AS client_email, p.full_name AS pro_name FROM bids b JOIN job_requests j ON b.job_id=j.job_id JOIN users c ON j.client_id=c.user_id JOIN users p ON b.professional_id=p.user_id WHERE b.bid_id=?");
    $q->execute([$bidId]);
    $bid = $q->fetch();
    if(!$bid) return;
    $url = rtrim(getSiteUrl(), '/').BASE_URL.'/client/my_jobs.php?job='.$bid['job_id'];
    $body = emailLayout(
        'New bid on your job',
        '<p>Hi '.htmlspecialchars($bid['client_name']).',</p>'
        .'<p><strong>'.htmlspecialchars($bid['pro_name']).'</strong> placed a bid of <strong>$'.number_format((float)$bid['bid_amount'],2).'</strong> ('.((int)$bid['estimated_days']).' day(s)) on your job <em>'.htmlspecialchars($bid['title']).'</em>.</p>'
        .($bid['message'] ? '<blockquote style="border-left:3px solid #e65c00;padding:0.4rem 0.8rem;color:#444">'.nl2br(htmlspecialchars($bid['message'])).'</blockquote>' : '')
        .'<p>Review all bids and choose the right professional.</p>',
        'Review bids',
        $url
    );
    sendEmail($bid['client_email'], 'New bid on "'.$bid['title'].'"', $body);
}

function notifyProBidAccepted(PDO $pdo, int $bidId): void {
    $q = $pdo->prepare("SELECT b.*, j.title, p.full_name AS pro_name, p.email AS pro_email, c.full_name AS client_name FROM bids b JOIN job_requests j ON b.job_id=j.job_id JOIN users p ON b.professional_id=p.user_id JOIN users c ON j.client_id=c.user_id WHERE b.bid_id=?");
    $q->execute([$bidId]);
    $bid = $q->fetch();
    if(!$bid) return;
    $url = rtrim(getSiteUrl(), '/').BASE_URL.'/professional/bookings.php';
    $body = emailLayout(
        'Your bid was accepted!',
        '<p>Hi '.htmlspecialchars($bid['pro_name']).',</p>'
        .'<p>Great news — '.htmlspecialchars($bid['client_name']).' has accepted your bid of <strong>$'.number_format((float)$bid['bid_amount'],2).'</strong> on <em>'.htmlspecialchars($bid['title']).'</em>.</p>'
        .'<p>A booking has been created. Open your bookings to start the job.</p>',
        'Open my bookings',
        $url
    );
    sendEmail($bid['pro_email'], 'Bid accepted — '.$bid['title'], $body);
}

function notifyProNewBooking(PDO $pdo, int $bookingId): void {
    $q = $pdo->prepare("SELECT bk.*, p.full_name AS pro_name, p.email AS pro_email, c.full_name AS client_name FROM bookings bk JOIN users p ON bk.professional_id=p.user_id JOIN users c ON bk.client_id=c.user_id WHERE bk.booking_id=?");
    $q->execute([$bookingId]);
    $bk = $q->fetch();
    if(!$bk) return;
    $url = rtrim(getSiteUrl(), '/').BASE_URL.'/professional/bookings.php';
    $body = emailLayout(
        'You have a new booking',
        '<p>Hi '.htmlspecialchars($bk['pro_name']).',</p>'
        .'<p><strong>'.htmlspecialchars($bk['client_name']).'</strong> just booked you for <strong>$'.number_format((float)$bk['agreed_amount'],2).'</strong> on '.htmlspecialchars($bk['scheduled_date'] ?? 'a date to be confirmed').'.</p>'
        .'<p>Open your bookings to message the client and confirm the details.</p>',
        'View booking',
        $url
    );
    sendEmail($bk['pro_email'], 'New booking on QuickFix ZW', $body);
}

function notifyProPaymentReleased(PDO $pdo, int $bookingId): void {
    $q = $pdo->prepare("SELECT bk.*, p.full_name AS pro_name, p.email AS pro_email, c.full_name AS client_name FROM bookings bk JOIN users p ON bk.professional_id=p.user_id JOIN users c ON bk.client_id=c.user_id WHERE bk.booking_id=?");
    $q->execute([$bookingId]);
    $bk = $q->fetch();
    if(!$bk) return;
    $body = emailLayout(
        'Payment released for your job',
        '<p>Hi '.htmlspecialchars($bk['pro_name']).',</p>'
        .'<p>Payment of <strong>$'.number_format((float)$bk['agreed_amount'],2).'</strong> has been released for the job you completed for '.htmlspecialchars($bk['client_name']).'.</p>'
        .'<p>Funds will reflect at the next settlement window. Thank you for using QuickFix ZW.</p>',
        '',
        ''
    );
    sendEmail($bk['pro_email'], 'Payment released — $'.number_format((float)$bk['agreed_amount'],2), $body);
}

function notifyAdminDispute(PDO $pdo, int $bookingId): void {
    $q = $pdo->prepare("SELECT bk.*, p.full_name AS pro_name, c.full_name AS client_name FROM bookings bk JOIN users p ON bk.professional_id=p.user_id JOIN users c ON bk.client_id=c.user_id WHERE bk.booking_id=?");
    $q->execute([$bookingId]);
    $bk = $q->fetch();
    if(!$bk) return;
    $url = rtrim(getSiteUrl(), '/').BASE_URL.'/admin/disputes.php';
    $body = emailLayout(
        'New dispute raised',
        '<p>A booking is now in dispute and needs admin review:</p>'
        .'<ul style="line-height:1.8">'
        .'<li><strong>Client:</strong> '.htmlspecialchars($bk['client_name']).'</li>'
        .'<li><strong>Professional:</strong> '.htmlspecialchars($bk['pro_name']).'</li>'
        .'<li><strong>Amount:</strong> $'.number_format((float)$bk['agreed_amount'],2).'</li>'
        .'</ul>',
        'Resolve dispute',
        $url
    );
    sendEmail(MAIL_ADMIN, 'Dispute raised on booking #'.$bk['booking_id'], $body);
}

function notifyAdminContactForm(string $name, string $email, string $subject, string $message): void {
    $body = emailLayout(
        'New contact form submission',
        '<ul style="line-height:1.8">'
        .'<li><strong>From:</strong> '.htmlspecialchars($name).' &lt;'.htmlspecialchars($email).'&gt;</li>'
        .'<li><strong>Subject:</strong> '.htmlspecialchars($subject).'</li>'
        .'</ul>'
        .'<div style="background:#f6f6fa;padding:1rem;border-radius:8px;line-height:1.6">'.nl2br(htmlspecialchars($message)).'</div>',
        '',
        ''
    );
    sendEmail(MAIL_ADMIN, '[Contact] '.$subject, $body);
}

function getSiteUrl(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme.'://'.$host;
}
