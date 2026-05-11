<?php
// Minimal SMTP client (TLS/SSL/STARTTLS, AUTH LOGIN, multipart text/html via Content-Type: text/html).
// Pure PHP — no Composer required.

class SmtpException extends RuntimeException {}

class SmtpClient {
 private $host;
 private int $port;
 private string $secure;
 private string $user;
 private string $pass;
 private int $timeout;
 private bool $debug;
 private $sock = null;
 private array $log = [];

 public function __construct(string $host, int $port, string $secure, string $user, string $pass, int $timeout = 20, bool $debug = false){
 $this->host = $host;
 $this->port = $port;
 $this->secure = strtolower($secure);
 $this->user = $user;
 $this->pass = $pass;
 $this->timeout = $timeout;
 $this->debug = $debug;
 }

 public function send(string $fromEmail, string $fromName, string $to, string $subject, string $htmlBody, string $replyTo = ''): bool {
 try {
 $this->connect();
 $this->ehlo();
 if($this->secure === 'tls'){
 $this->cmd('STARTTLS', 220);
 if(!stream_socket_enable_crypto($this->sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)){
 throw new SmtpException('Could not enable TLS on SMTP socket.');
 }
 $this->ehlo();
 }
 if($this->user !== ''){
 $this->cmd('AUTH LOGIN', 334);
 $this->cmd(base64_encode($this->user), 334);
 $this->cmd(base64_encode($this->pass), 235);
 }
 $this->cmd('MAIL FROM:<'.$fromEmail.'>', 250);
 $this->cmd('RCPT TO:<'.$to.'>', [250, 251]);
 $this->cmd('DATA', 354);

 $headers = [];
 $headers[] = 'Date: '.date('r');
 $headers[] = 'From: '.$this->encodeHeader($fromName).' <'.$fromEmail.'>';
 $headers[] = 'To: <'.$to.'>';
 if($replyTo !== '') $headers[] = 'Reply-To: <'.$replyTo.'>';
 $headers[] = 'Subject: '.$this->encodeHeader($subject);
 $headers[] = 'Message-ID: <'.bin2hex(random_bytes(8)).'@'.($this->host ?: 'localhost').'>';
 $headers[] = 'MIME-Version: 1.0';
 $headers[] = 'Content-Type: text/html; charset=UTF-8';
 $headers[] = 'Content-Transfer-Encoding: 8bit';
 $headers[] = 'X-Mailer: QuickFixZW/1.0';

 $body = preg_replace('/^\./m', '..', $htmlBody);
 $payload = implode("\r\n", $headers)."\r\n\r\n".$body."\r\n.";
 $this->cmd($payload, 250);

 $this->cmd('QUIT', 221);
 $this->close();
 $this->dumpLog();
 return true;
 } catch(SmtpException $e){
 $this->log[] = '[ERROR] '.$e->getMessage();
 $this->close();
 $this->dumpLog();
 return false;
 }
 }

 private function connect(): void {
 $hostPrefix = $this->secure === 'ssl' ? 'ssl://' : '';
 $errno = 0; $errstr = '';
 $sock = @stream_socket_client($hostPrefix.$this->host.':'.$this->port, $errno, $errstr, $this->timeout);
 if(!$sock){
 throw new SmtpException("Connect failed to {$this->host}:{$this->port} — $errstr ($errno)");
 }
 stream_set_timeout($sock, $this->timeout);
 $this->sock = $sock;
 $this->expect(220);
 }

 private function ehlo(): void {
 $this->cmd('EHLO '.($this->host ?: 'localhost'), 250);
 }

 private function cmd(string $line, $expect): string {
 $this->log[] = '> '.(strlen($line) > 200 ? substr($line, 0, 200).'…' : $line);
 fwrite($this->sock, $line."\r\n");
 return $this->expect($expect);
 }

 private function expect($expectedCode): string {
 $expected = (array)$expectedCode;
 $response = '';
 while(($line = fgets($this->sock, 1024)) !== false){
 $response .= $line;
 $this->log[] = '< '.rtrim($line);
 if(isset($line[3]) && $line[3] === ' ') break;
 }
 if(!preg_match('/^(\d{3})/', $response, $m)){
 throw new SmtpException('Empty/invalid SMTP response.');
 }
 $code = (int)$m[1];
 if(!in_array($code, $expected, true)){
 throw new SmtpException('SMTP expected '.implode('/', $expected).' got '.$code.' — '.trim($response));
 }
 return $response;
 }

 private function close(): void {
 if($this->sock){
 @fclose($this->sock);
 $this->sock = null;
 }
 }

 private function dumpLog(): void {
 if(!$this->debug) return;
 $logDir = __DIR__.'/../logs';
 if(!is_dir($logDir)) @mkdir($logDir, 0755, true);
 @file_put_contents($logDir.'/smtp.log', '['.date('c')."]\n".implode("\n", $this->log)."\n\n", FILE_APPEND);
 }

 private function encodeHeader(string $value): string {
 if(preg_match('/[\x80-\xff]/', $value)){
 return '=?UTF-8?B?'.base64_encode($value).'?=';
 }
 return $value;
 }
}
