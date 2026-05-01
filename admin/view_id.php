<?php
require_once __DIR__.'/../includes/auth.php';
requireRole('admin');
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/id_upload.php';

$userId = (int)($_GET['user_id'] ?? 0);
if($userId <= 0){
    http_response_code(400);
    exit('Missing user_id.');
}

$stmt = $pdo->prepare("SELECT full_name, national_id_file FROM users WHERE user_id=?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if(!$user || empty($user['national_id_file'])){
    http_response_code(404);
    exit('No ID document on file for this user.');
}

$file = $user['national_id_file'];
if(strpos($file, '/') !== false || strpos($file, '\\') !== false || strpos($file, '..') !== false){
    http_response_code(400);
    exit('Invalid file reference.');
}

$path = ID_UPLOAD_DIR.'/'.$file;
if(!is_file($path)){
    http_response_code(404);
    exit('ID document file is missing on disk.');
}

$mime = idFileMimeFromName($file);
$disposition = ($_GET['download'] ?? '') === '1' ? 'attachment' : 'inline';
$safeName = preg_replace('/[^A-Za-z0-9 _\-]/', '_', $user['full_name']).'_ID.'.pathinfo($file, PATHINFO_EXTENSION);

header('Content-Type: '.$mime);
header('Content-Length: '.filesize($path));
header('Content-Disposition: '.$disposition.'; filename="'.$safeName.'"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($path);
exit;
