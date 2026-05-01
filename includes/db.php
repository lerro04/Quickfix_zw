<?php
require_once __DIR__.'/config.php';
$host='localhost'; $dbname='quickfix_db'; $username='root'; $password='';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS professional_portfolio_images (
            image_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) DEFAULT NULL,
            mime_type VARCHAR(100) DEFAULT NULL,
            file_size INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS job_request_images (
            image_id INT AUTO_INCREMENT PRIMARY KEY,
            job_id INT NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) DEFAULT NULL,
            mime_type VARCHAR(100) DEFAULT NULL,
            file_size INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (job_id) REFERENCES job_requests(job_id) ON DELETE CASCADE
        )
    ");
} catch(PDOException $e) { die('DB Error: '.$e->getMessage()); }
require_once __DIR__.'/migrate.php';
?>
