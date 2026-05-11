<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'auth']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method']);
    exit;
}

$lat = $_POST['latitude'] ?? null;
$lng = $_POST['longitude'] ?? null;
$label = trim($_POST['location_label'] ?? '');

if (!is_numeric($lat) || !is_numeric($lng)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'coords']);
    exit;
}
$lat = (float)$lat;
$lng = (float)$lng;
if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'range']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE users SET latitude=?, longitude=?, location_label=? WHERE user_id=?");
    $stmt->execute([$lat, $lng, $label !== '' ? mb_substr($label, 0, 120) : null, (int)$_SESSION['user_id']]);
    echo json_encode(['ok' => true, 'latitude' => $lat, 'longitude' => $lng]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'db', 'detail' => $e->getMessage()]);
}
