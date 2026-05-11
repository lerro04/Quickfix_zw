<?php
require_once __DIR__.'/config.php';
if(is_file(__DIR__.'/sms_config.php')) require_once __DIR__.'/sms_config.php';

if(!defined('SMS_ENABLED'))        define('SMS_ENABLED',        true);
if(!defined('SMS_HOST'))           define('SMS_HOST',           'https://txt.tilltrackpos.co.zw');
if(!defined('SMS_USERNAME'))       define('SMS_USERNAME',       'remote_elphasmasuka');
if(!defined('SMS_SENDING_NUMBER')) define('SMS_SENDING_NUMBER', '');
if(!defined('SMS_TIMEOUT'))        define('SMS_TIMEOUT',        15);
if(!defined('SMS_DEBUG'))          define('SMS_DEBUG',          true);

function smsNormalizePhone(string $raw): string {
 $digits = preg_replace('/\D+/', '', $raw);
 // leading 0 (e.g. 0771234567) → keep as-is; TXT replaces with country code automatically.
 // Already has 263 prefix → keep. Reject anything too short.
 return $digits;
}

function sendSMS(string $phone, string $message): bool {
 if(!SMS_ENABLED) return false;
 if(SMS_USERNAME === '' || SMS_HOST === '') return false;
 $to = smsNormalizePhone($phone);
 if(strlen($to) < 9) return false;
 $body = trim($message);
 if($body === '') return false;
 // TXT messages > 160 chars get split & charged per segment — trim long ones to one segment by default.
 if(mb_strlen($body) > 320){
 $body = mb_substr($body, 0, 317).'...';
 }

 $params = [
 'Username'   => SMS_USERNAME,
 'Recipients' => $to,
 'Body'       => $body,
 ];
 if(SMS_SENDING_NUMBER !== ''){
 $params['sending_number'] = SMS_SENDING_NUMBER;
 }
 $url = 'https://'.SMS_HOST.'/Remote/SendMessage';

 $ch = curl_init($url);
 curl_setopt_array($ch, [
 CURLOPT_RETURNTRANSFER => true,
 CURLOPT_POST           => true,
 CURLOPT_POSTFIELDS     => http_build_query($params, '', '&'),
 CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
 CURLOPT_TIMEOUT        => (int)SMS_TIMEOUT,
 CURLOPT_SSL_VERIFYPEER => true,
 ]);
 $resp = curl_exec($ch);
 $err  = curl_error($ch);
 curl_close($ch);

 $ok = ($resp !== false) && stripos((string)$resp, 'SUCCESS') === 0;

 if(SMS_DEBUG){
 $logDir = __DIR__.'/../logs';
 if(!is_dir($logDir)) @mkdir($logDir, 0755, true);
 @file_put_contents($logDir.'/sms.log',
 '['.date('c')."] to=$to ok=".($ok?'1':'0')." resp=".str_replace(["\r","\n"], ' ', (string)$resp)." err=$err\n",
 FILE_APPEND);
 }
 if(!$ok){
 $logDir = __DIR__.'/../logs';
 if(!is_dir($logDir)) @mkdir($logDir, 0755, true);
 @file_put_contents($logDir.'/sms_failures.log',
 '['.date('c')."] to=$to resp=".str_replace(["\r","\n"], ' ', (string)$resp)." err=$err\n",
 FILE_APPEND);
 }
 return $ok;
}
