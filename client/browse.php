<?php
require_once '../includes/auth.php';
requireRole('client');
header('Location: /quickfix/browse.php');
exit;
