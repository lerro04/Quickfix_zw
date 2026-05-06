<?php
require_once '../includes/auth.php';
requireRole('client');
header('Location: '.BASE_URL.'/browse.php');
exit;
