<?php
session_start();
function isLoggedIn(){ return isset($_SESSION['user_id']); }
function requireLogin(){ if(!isLoggedIn()){ header('Location: /quickfix/index.php?auth=login#auth'); exit; } }
function requireRole($role){ requireLogin(); if($_SESSION['role']!==$role){ header('Location: /quickfix/browse.php'); exit; } }
?>
