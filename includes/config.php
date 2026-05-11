<?php
if(!defined('BASE_URL')){
 $appRootFs = str_replace('\\', '/', dirname(__DIR__));
 $scriptFs = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
 $base = '';
 if($scriptFs !== '' && strpos($scriptFs, $appRootFs) === 0){
 $relScript = substr($scriptFs, strlen($appRootFs));
 $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
 if($relScript !== '' && substr($scriptName, -strlen($relScript)) === $relScript){
 $base = substr($scriptName, 0, -strlen($relScript));
 }
 }
 // BASE_URL must be just the path (e.g. "/quickfix_zw" or ""), never a full URL.
 // Code that builds absolute URLs uses getSiteUrl() to prepend scheme+host.
 define('BASE_URL', $base);
}

if(!function_exists('getSiteUrl')){
 function getSiteUrl(): string {
 $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
 $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
 return $scheme.'://'.$host;
 }
}
