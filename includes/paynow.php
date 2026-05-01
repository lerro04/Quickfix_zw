<?php
require_once __DIR__.'/paynow_config.php';

class Paynow {
 const URL_INITIATE = 'https://www.paynow.co.zw/interface/initiatetransaction';
 const URL_REMOTE = 'https://www.paynow.co.zw/interface/remotetransaction';
 const URL_UPDATE = 'https://www.paynow.co.zw/interface/updatestatus';

 private int $id;
 private string $key;
 private string $returnUrl;
 private string $resultUrl;

 public function __construct(int $id, string $key, string $returnUrl, string $resultUrl){
 $this->id = $id;
 $this->key = $key;
 $this->returnUrl = $returnUrl;
 $this->resultUrl = $resultUrl;
 }

 public function createHash(array $values): string {
 $string = '';
 foreach($values as $k => $v){
 if(strtoupper($k) === 'HASH') continue;
 $string .= $v;
 }
 $string .= $this->key;
 return strtoupper(hash('sha512', $string));
 }

 public function initiate(string $reference, float $amount, string $additionalInfo = '', string $authEmail = ''): array {
 $payload = [
 'id' => $this->id,
 'reference' => $reference,
 'amount' => number_format($amount, 2, '.', ''),
 'additionalinfo' => $additionalInfo !== '' ? $additionalInfo : ('QuickFix booking '.$reference),
 'returnurl' => $this->returnUrl,
 'resulturl' => $this->resultUrl,
 'authemail' => $authEmail,
 'status' => 'Message',
 ];
 $payload['hash'] = $this->createHash($payload);
 $body = $this->postUrlencoded(self::URL_INITIATE, $payload);
 return $this->parseResponse($body);
 }

 public function poll(string $pollUrl): array {
 $body = $this->getRaw($pollUrl);
 return $this->parseResponse($body);
 }

 public function validateInboundHash(array $values): bool {
 if(!isset($values['hash'])) return false;
 $received = strtoupper($values['hash']);
 $copy = $values;
 unset($copy['hash']);
 return hash_equals($received, $this->createHash($copy));
 }

 public function parsePostBody(string $raw): array {
 $out = [];
 if($raw === '') return $out;
 foreach(explode('&', $raw) as $pair){
 if($pair === '') continue;
 $kv = explode('=', $pair, 2);
 $k = urldecode($kv[0]);
 $v = isset($kv[1]) ? urldecode($kv[1]) : '';
 $out[$k] = $v;
 }
 return $out;
 }

 private function parseResponse(string $body): array {
 $vals = $this->parsePostBody($body);
 $out = [];
 foreach($vals as $k => $v) $out[strtolower($k)] = $v;
 return $out;
 }

 private function postUrlencoded(string $url, array $payload): string {
 $body = http_build_query($payload, '', '&');
 $ch = curl_init($url);
 curl_setopt_array($ch, [
 CURLOPT_RETURNTRANSFER => true,
 CURLOPT_POST => true,
 CURLOPT_POSTFIELDS => $body,
 CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
 CURLOPT_TIMEOUT => 30,
 CURLOPT_SSL_VERIFYPEER => true,
 ]);
 $response = curl_exec($ch);
 if($response === false){
 $err = curl_error($ch);
 curl_close($ch);
 throw new RuntimeException('Paynow request failed: '.$err);
 }
 curl_close($ch);
 return (string)$response;
 }

 private function getRaw(string $url): string {
 $ch = curl_init($url);
 curl_setopt_array($ch, [
 CURLOPT_RETURNTRANSFER => true,
 CURLOPT_TIMEOUT => 30,
 CURLOPT_SSL_VERIFYPEER => true,
 ]);
 $response = curl_exec($ch);
 if($response === false){
 $err = curl_error($ch);
 curl_close($ch);
 throw new RuntimeException('Paynow poll failed: '.$err);
 }
 curl_close($ch);
 return (string)$response;
 }
}

function paynowClient(string $returnUrl, string $resultUrl): Paynow {
 return new Paynow(PAYNOW_INTEGRATION_ID, PAYNOW_INTEGRATION_KEY, $returnUrl, $resultUrl);
}
