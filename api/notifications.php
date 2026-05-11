<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!isLoggedIn()) {
    echo json_encode(['ok' => false, 'error' => 'auth']);
    exit;
}

$uid = (int)$_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';
$since = trim($_GET['since'] ?? '');
if ($since === '' || !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $since)) {
    // Default to 5 minutes ago to seed clients without state.
    $since = date('Y-m-d H:i:s', time() - 300);
}
$now = date('Y-m-d H:i:s');
$items = [];

try {
    // Unread chat messages — for everyone
    $msgQ = $pdo->prepare("SELECT m.message_id, m.message, m.created_at, s.user_id AS sender_id, s.full_name AS sender_name
 FROM messages m JOIN users s ON m.sender_id=s.user_id
 WHERE m.receiver_id=? AND m.created_at > ? ORDER BY m.created_at ASC LIMIT 20");
    $msgQ->execute([$uid, $since]);
    foreach ($msgQ->fetchAll() as $m) {
        $items[] = [
            'id' => 'msg-' . $m['message_id'],
            'kind' => 'message',
            'title' => 'New message from ' . $m['sender_name'],
            'body' => mb_substr($m['message'], 0, 140),
            'url' => '/messages.php?with=' . (int)$m['sender_id'],
            'created_at' => $m['created_at'],
        ];
    }

    if ($role === 'client') {
        // New bids on this client's jobs
        $bidQ = $pdo->prepare("SELECT b.bid_id, b.bid_amount, b.created_at, b.job_id, j.title, p.full_name AS pro_name
 FROM bids b JOIN job_requests j ON b.job_id=j.job_id JOIN users p ON b.professional_id=p.user_id
 WHERE j.client_id=? AND b.created_at > ? ORDER BY b.created_at ASC LIMIT 20");
        $bidQ->execute([$uid, $since]);
        foreach ($bidQ->fetchAll() as $b) {
            $items[] = [
                'id' => 'bid-' . $b['bid_id'],
                'kind' => 'bid',
                'title' => 'New bid on ' . mb_substr($b['title'], 0, 60),
                'body' => $b['pro_name'] . ' offered $' . number_format((float)$b['bid_amount'], 2),
                'url' => '/client/my_jobs.php?job=' . (int)$b['job_id'],
                'created_at' => $b['created_at'],
            ];
        }
    } elseif ($role === 'professional') {
        // Bids of this pro that flipped to "accepted" since timestamp (use bid created_at as fallback marker)
        $accQ = $pdo->prepare("SELECT b.bid_id, b.bid_amount, b.job_id, j.title, c.full_name AS client_name, COALESCE(bk.created_at, b.created_at) AS event_at
 FROM bids b
 JOIN job_requests j ON b.job_id=j.job_id
 JOIN users c ON j.client_id=c.user_id
 LEFT JOIN bookings bk ON bk.bid_id=b.bid_id
 WHERE b.professional_id=? AND b.status='accepted' AND COALESCE(bk.created_at, b.created_at) > ?
 ORDER BY event_at ASC LIMIT 20");
        $accQ->execute([$uid, $since]);
        foreach ($accQ->fetchAll() as $r) {
            $items[] = [
                'id' => 'bidaccept-' . $r['bid_id'],
                'kind' => 'bid_accepted',
                'title' => 'Bid accepted!',
                'body' => $r['client_name'] . ' accepted your bid of $' . number_format((float)$r['bid_amount'], 2),
                'url' => '/professional/bookings.php',
                'created_at' => $r['event_at'],
            ];
        }
        // New bookings
        $bkQ = $pdo->prepare("SELECT bk.booking_id, bk.agreed_amount, bk.created_at, c.full_name AS client_name
 FROM bookings bk JOIN users c ON bk.client_id=c.user_id
 WHERE bk.professional_id=? AND bk.created_at > ? ORDER BY bk.created_at ASC LIMIT 20");
        $bkQ->execute([$uid, $since]);
        foreach ($bkQ->fetchAll() as $b) {
            $items[] = [
                'id' => 'booking-' . $b['booking_id'],
                'kind' => 'booking',
                'title' => 'New booking from ' . $b['client_name'],
                'body' => '$' . number_format((float)$b['agreed_amount'], 2) . ' — open to confirm details',
                'url' => '/professional/bookings.php',
                'created_at' => $b['created_at'],
            ];
        }
        // New matching jobs in pro's trade
        $jobQ = $pdo->prepare("SELECT j.job_id, j.title, j.trade, j.location, j.client_budget, j.created_at
 FROM job_requests j JOIN professional_profiles pp ON pp.trade=j.trade
 WHERE pp.user_id=? AND j.status='open' AND j.created_at > ? ORDER BY j.created_at ASC LIMIT 20");
        $jobQ->execute([$uid, $since]);
        foreach ($jobQ->fetchAll() as $j) {
            $items[] = [
                'id' => 'newjob-' . $j['job_id'],
                'kind' => 'new_job',
                'title' => 'New ' . $j['trade'] . ' job posted',
                'body' => mb_substr($j['title'], 0, 60) . ' — ' . $j['location'] . ' · $' . number_format((float)$j['client_budget'], 0),
                'url' => '/professional/job_board.php',
                'created_at' => $j['created_at'],
            ];
        }
    } elseif ($role === 'admin') {
        $disputeQ = $pdo->prepare("SELECT booking_id FROM bookings WHERE status='disputed' AND COALESCE(updated_at, created_at) > ? ORDER BY booking_id DESC LIMIT 20");
        try {
            $disputeQ->execute([$since]);
            $disputes = $disputeQ->fetchAll();
        } catch (PDOException $e) {
            // bookings may not have updated_at — fall back to created_at
            $disputeQ = $pdo->prepare("SELECT booking_id, created_at FROM bookings WHERE status='disputed' AND created_at > ? LIMIT 20");
            $disputeQ->execute([$since]);
            $disputes = $disputeQ->fetchAll();
        }
        foreach ($disputes as $d) {
            $items[] = [
                'id' => 'dispute-' . $d['booking_id'],
                'kind' => 'dispute',
                'title' => 'New dispute raised',
                'body' => 'Booking #' . $d['booking_id'] . ' needs review',
                'url' => '/admin/disputes.php',
                'created_at' => $d['created_at'] ?? $now,
            ];
        }
        $proQ = $pdo->prepare("SELECT user_id, full_name, created_at FROM users WHERE role='professional' AND verified=0 AND created_at > ? ORDER BY created_at ASC LIMIT 20");
        $proQ->execute([$since]);
        foreach ($proQ->fetchAll() as $p) {
            $items[] = [
                'id' => 'prosignup-' . $p['user_id'],
                'kind' => 'pro_signup',
                'title' => 'New pro awaiting verification',
                'body' => $p['full_name'],
                'url' => '/admin/users.php?filter=pending',
                'created_at' => $p['created_at'],
            ];
        }
    }
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'error' => 'db', 'detail' => $e->getMessage()]);
    exit;
}

usort($items, fn($a, $b) => strcmp($a['created_at'], $b['created_at']));

echo json_encode([
    'ok' => true,
    'now' => $now,
    'unread_messages' => countUnreadMessages($pdo, $uid),
    'items' => $items,
]);
