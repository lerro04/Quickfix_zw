<?php
if(!isset($pdo) || !($pdo instanceof PDO)) return;

$pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
 version VARCHAR(80) PRIMARY KEY,
 applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$migrations = [
 '2026-05-01-contact-messages' => "
 CREATE TABLE IF NOT EXISTS contact_messages (
 contact_id INT AUTO_INCREMENT PRIMARY KEY,
 full_name VARCHAR(100) NOT NULL,
 email VARCHAR(100) NOT NULL,
 phone VARCHAR(30) DEFAULT NULL,
 subject VARCHAR(200) NOT NULL,
 message TEXT NOT NULL,
 is_read TINYINT DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
 )
 ",
 '2026-05-01-payments' => "
 CREATE TABLE IF NOT EXISTS payments (
 payment_id INT AUTO_INCREMENT PRIMARY KEY,
 booking_id INT NOT NULL,
 client_id INT NOT NULL,
 amount DECIMAL(10,2) NOT NULL,
 reference VARCHAR(80) NOT NULL UNIQUE,
 paynow_reference VARCHAR(120) DEFAULT NULL,
 poll_url VARCHAR(500) DEFAULT NULL,
 browser_url VARCHAR(500) DEFAULT NULL,
 status VARCHAR(40) DEFAULT 'created',
 paid_amount DECIMAL(10,2) DEFAULT 0.00,
 payment_method VARCHAR(40) DEFAULT NULL,
 raw_response TEXT DEFAULT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
 FOREIGN KEY (client_id) REFERENCES users(user_id)
 )
 ",
 '2026-05-01-trades-varchar-profiles' => "
 ALTER TABLE professional_profiles MODIFY COLUMN trade VARCHAR(60) NOT NULL
 ",
 '2026-05-01-trades-varchar-jobs' => "
 ALTER TABLE job_requests MODIFY COLUMN trade VARCHAR(60) NOT NULL
 ",
 '2026-05-01-trades-table' => "
 CREATE TABLE IF NOT EXISTS trades (
 trade_id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(60) NOT NULL UNIQUE,
 is_active TINYINT DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
 )
 ",
 '2026-05-01-trades-seed' => "
 INSERT IGNORE INTO trades (name) VALUES
 ('Plumbing'),('Electrical'),('Painting'),('Carpentry'),
 ('Tiling'),('Roofing'),('Welding'),('Landscaping'),('General Handyman')
 ",
 '2026-05-01-users-id-file' => "
 ALTER TABLE users ADD COLUMN IF NOT EXISTS national_id_file VARCHAR(120) DEFAULT NULL
 ",
 '2026-05-06-payments-reference' => "
 ALTER TABLE payments MODIFY COLUMN reference VARCHAR(100) DEFAULT NULL
 ",
 '2026-05-06-payments-status-varchar' => "
 ALTER TABLE payments MODIFY COLUMN status VARCHAR(40) DEFAULT 'created'
 ",
 '2026-05-06-payments-paynow-reference' => "
 ALTER TABLE payments ADD COLUMN IF NOT EXISTS paynow_reference VARCHAR(120) DEFAULT NULL
 ",
 '2026-05-06-payments-paid-amount' => "
 ALTER TABLE payments ADD COLUMN IF NOT EXISTS paid_amount DECIMAL(10,2) DEFAULT 0.00
 ",
 '2026-05-06-payments-method' => "
 ALTER TABLE payments ADD COLUMN IF NOT EXISTS payment_method VARCHAR(40) DEFAULT NULL
 ",
 '2026-05-06-payments-updated-at' => "
 ALTER TABLE payments ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
 ",
 '2026-05-06-booking-platform-fee-pct' => "
 ALTER TABLE bookings ADD COLUMN IF NOT EXISTS platform_fee_pct DECIMAL(5,2) DEFAULT 10.00
 ",
 '2026-05-06-booking-platform-fee-amount' => "
 ALTER TABLE bookings ADD COLUMN IF NOT EXISTS platform_fee_amount DECIMAL(10,2) DEFAULT 0.00
 ",
 '2026-05-06-booking-professional-payout' => "
 ALTER TABLE bookings ADD COLUMN IF NOT EXISTS professional_payout DECIMAL(10,2) DEFAULT 0.00
 ",
 '2026-05-06-backfill-booking-payouts' => "
 UPDATE bookings
 SET platform_fee_pct = 10.00,
 platform_fee_amount = ROUND(agreed_amount * 10.00 / 100, 2),
 professional_payout = agreed_amount - ROUND(agreed_amount * 10.00 / 100, 2)
 ",
];

foreach($migrations as $version => $sql){
 try {
 $check = $pdo->prepare("SELECT 1 FROM schema_migrations WHERE version=?");
 $check->execute([$version]);
 if($check->fetch()) continue;
 $pdo->exec($sql);
 $pdo->prepare("INSERT INTO schema_migrations (version) VALUES (?)")->execute([$version]);
 } catch(PDOException $e){
 error_log("Migration $version failed: ".$e->getMessage());
 }
}
