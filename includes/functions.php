<?php
require_once __DIR__.'/config.php';
const PLATFORM_COMMISSION_RATE = 0.10;
const MAX_IMAGE_UPLOADS = 5;
const MAX_IMAGE_SIZE_BYTES = 5 * 1024 * 1024;

function sanitize($d){ return htmlspecialchars(strip_tags(trim($d))); }
const PHONE_REGEX = '/^0\d{9}$/';
function isValidPhone(string $phone): bool {
 return $phone === '' || (bool)preg_match(PHONE_REGEX, $phone);
}
function normalizePhone(string $phone): string {
 return preg_replace('/\s+/', '', trim($phone));
}
function countUnreadMessages($pdo, $userId){
 if(!$userId) return 0;
 try {
 $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id=? AND is_read=0");
 $stmt->execute([(int)$userId]);
 return (int)$stmt->fetchColumn();
 } catch(PDOException $e){
 return 0;
 }
}
function platformCommissionAmount(float $amount): float {
 return round($amount * PLATFORM_COMMISSION_RATE, 2);
}
function bookingPaidOnlineAmount(PDO $pdo, int $bookingId): float {
 $stmt = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN paid_amount IS NULL OR paid_amount = 0 THEN amount ELSE paid_amount END),0) FROM payments WHERE booking_id=? AND LOWER(status) IN ('paid','awaiting delivery','delivered','completed')");
 $stmt->execute([$bookingId]);
 return round((float)$stmt->fetchColumn(), 2);
}
function bookingPaymentBreakdown(PDO $pdo, array $booking): array {
 $agreed = round((float)$booking['agreed_amount'], 2);
 $onlinePaid = bookingPaidOnlineAmount($pdo, (int)$booking['booking_id']);
 $fee = platformCommissionAmount($agreed);
 $netPayout = max(0, round($agreed - $fee, 2));
 $cashExpected = max(0, round($agreed - $onlinePaid, 2));
 $platformPayout = max(0, round($onlinePaid - $fee, 2));
 $commissionDue = max(0, round($fee - $onlinePaid, 2));
 return [
 'agreed' => $agreed,
 'online_paid' => $onlinePaid,
 'cash_expected' => $cashExpected,
 'commission' => $fee,
 'commission_due' => $commissionDue,
 'commission_covered' => $commissionDue <= 0,
 'net_payout' => $netPayout,
 'platform_payout' => $platformPayout,
 'remaining_online' => max(0, round($agreed - $onlinePaid, 2)),
 ];
}
function timeAgo($datetime){
 $t = time() - strtotime($datetime);
 if($t < 60) return 'just now';
 if($t < 3600) return floor($t / 60).'m ago';
 if($t < 86400) return floor($t / 3600).'h ago';
 return floor($t / 86400).'d ago';
}
function icon($name, $label=''){
 $labelAttr = $label !== '' ? ' aria-label="'.htmlspecialchars($label, ENT_QUOTES).'"' : ' aria-hidden="true"';
 return '<i class="fa-solid fa-'.$name.'"'.$labelAttr.'></i>';
}
function stars($r){
 $o = '';
 for($i = 1; $i <= 5; $i++){
 $o .= $i <= $r ? '<i class="fa-solid fa-star" aria-hidden="true"></i>' : '<i class="fa-regular fa-star" aria-hidden="true"></i>';
 }
 return '<span class="star-rating" aria-label="Rating '.number_format((float)$r, 1).' out of 5">'.$o.'</span>';
}
function urgencyBadge($u){
 return match($u){
 'urgent' => '<span class="badge badge-danger">'.icon('circle-exclamation').' Urgent</span>',
 'within_week' => '<span class="badge badge-warning">'.icon('calendar-week').' This Week</span>',
 default => '<span class="badge badge-success">'.icon('calendar-check').' Flexible</span>'
 };
}
function tradeIcon($t){
 return match($t){
 'Plumbing' => icon('wrench'),
 'Electrical' => icon('bolt'),
 'Painting' => icon('paint-roller'),
 'Carpentry' => icon('hammer'),
 'Tiling' => icon('border-all'),
 'Roofing' => icon('house'),
 'Welding' => icon('fire-flame-curved'),
 'Landscaping' => icon('leaf'),
 default => icon('screwdriver-wrench')
 };
}
function paginationData($totalItems, $perPage, $currentPage){
 $totalPages = max(1, (int)ceil($totalItems / max(1, $perPage)));
 $page = min(max(1, $currentPage), $totalPages);
 return [
 'page' => $page,
 'total_pages' => $totalPages,
 'offset' => ($page - 1) * $perPage
 ];
}
function pageUrl($page){
 $params = $_GET;
 $params['page'] = $page;
 return htmlspecialchars($_SERVER['PHP_SELF'].'?'.http_build_query($params));
}
function renderPagination($page, $totalPages){
 if($totalPages <= 1) return '';
 $html = '<div class="pagination" aria-label="Pagination">';
 if($page > 1){
 $html .= '<a class="pagination-link" href="'.pageUrl($page - 1).'">'.icon('chevron-left').' Previous</a>';
 }
 for($i = 1; $i <= $totalPages; $i++){
 $class = $i === $page ? 'pagination-link active' : 'pagination-link';
 $html .= '<a class="'.$class.'" href="'.pageUrl($i).'">'.$i.'</a>';
 }
 if($page < $totalPages){
 $html .= '<a class="pagination-link" href="'.pageUrl($page + 1).'">Next '.icon('chevron-right').'</a>';
 }
 $html .= '</div>';
 return $html;
}
function uploadRootPath(){
 return dirname(__DIR__).DIRECTORY_SEPARATOR.'uploads';
}
function uploadPublicPath($folder, $filename){
 return BASE_URL.'/uploads/'.$folder.'/'.$filename;
}
function ensureUploadDirectory($folder){
 $path = uploadRootPath().DIRECTORY_SEPARATOR.$folder;
 if(!is_dir($path)){
 mkdir($path, 0777, true);
 }
 return $path;
}
function normalizeUploadFiles($files){
 $normalized = [];
 if(!isset($files['name']) || !is_array($files['name'])){
 return $normalized;
 }
 foreach($files['name'] as $index => $name){
 $normalized[] = [
 'name' => $name,
 'type' => $files['type'][$index] ?? '',
 'tmp_name' => $files['tmp_name'][$index] ?? '',
 'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
 'size' => $files['size'][$index] ?? 0
 ];
 }
 return $normalized;
}
function processImageUploads($files, $folder){
 $uploads = [];
 $errors = [];
 $normalized = normalizeUploadFiles($files);
 if(empty($normalized)){
 return ['uploads' => [], 'errors' => []];
 }

 $allowedMime = [
 'image/jpeg' => 'jpg',
 'image/png' => 'png',
 'image/webp' => 'webp'
 ];
 $destinationDir = ensureUploadDirectory($folder);
 $finfo = finfo_open(FILEINFO_MIME_TYPE);
 $count = 0;

 foreach($normalized as $file){
 if($file['error'] === UPLOAD_ERR_NO_FILE || $file['name'] === ''){
 continue;
 }
 $count++;
 if($count > MAX_IMAGE_UPLOADS){
 $errors[] = 'You can upload up to '.MAX_IMAGE_UPLOADS.' images at a time.';
 break;
 }
 if($file['error'] !== UPLOAD_ERR_OK){
 $errors[] = 'One of the selected images could not be uploaded.';
 continue;
 }
 if($file['size'] > MAX_IMAGE_SIZE_BYTES){
 $errors[] = $file['name'].' is larger than 5MB.';
 continue;
 }
 $mime = finfo_file($finfo, $file['tmp_name']);
 if(!isset($allowedMime[$mime])){
 $errors[] = $file['name'].' must be a JPG, PNG, or WEBP image.';
 continue;
 }
 $extension = $allowedMime[$mime];
 $filename = uniqid($folder.'_', true).'.'.$extension;
 $target = $destinationDir.DIRECTORY_SEPARATOR.$filename;
 if(!move_uploaded_file($file['tmp_name'], $target)){
 $errors[] = 'Failed to save '.$file['name'].'.';
 continue;
 }
 $uploads[] = [
 'filename' => $filename,
 'original_name' => basename($file['name']),
 'mime_type' => $mime,
 'file_size' => (int)$file['size'],
 'path' => uploadPublicPath($folder, $filename)
 ];
 }

 finfo_close($finfo);
 return ['uploads' => $uploads, 'errors' => $errors];
}
function deleteUploadedFile($publicPath){
 $prefix = BASE_URL.'/uploads/';
 if(!str_starts_with($publicPath, $prefix)){
 return;
 }
 $relative = substr($publicPath, strlen($prefix));
 $fullPath = uploadRootPath().DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
 if(is_file($fullPath)){
 unlink($fullPath);
 }
}

$TRADES = ['Plumbing','Electrical','Painting','Carpentry','Tiling','Roofing','Welding','Landscaping','General Handyman'];
?>
