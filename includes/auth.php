<?php
require_once __DIR__.'/config.php';
session_start();
function isLoggedIn(){ return isset($_SESSION['user_id']); }
function requireLogin(){ if(!isLoggedIn()){ header('Location: '.BASE_URL.'/login.php'); exit; } }
function requireRole($role){ requireLogin(); if($_SESSION['role']!==$role){ header('Location: '.BASE_URL.'/login.php'); exit; } }
?>
