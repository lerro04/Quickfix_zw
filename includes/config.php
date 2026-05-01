<?php
if(!defined('BASE_URL')){
    $appRootFs = str_replace('\\', '/', dirname(__DIR__));
    $scriptFs  = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
    $base = '';
    if($scriptFs !== '' && strpos($scriptFs, $appRootFs) === 0){
        $relScript  = substr($scriptFs, strlen($appRootFs));
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        if($relScript !== '' && substr($scriptName, -strlen($relScript)) === $relScript){
            $base = substr($scriptName, 0, -strlen($relScript));
        }
    }
    define('BASE_URL', rtrim($base, '/'));
}
