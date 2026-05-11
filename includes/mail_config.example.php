<?php
// =============================================================
// SMTP / Mail Settings — copy to mail_config.php and fill in.
// mail_config.php is gitignored so credentials never leak.
// =============================================================
// Common SMTP examples:
//   Gmail:      host=smtp.gmail.com  port=587 secure=tls  user=you@gmail.com   pass=APP_PASSWORD (not your login pw — use https://myaccount.google.com/apppasswords)
//   ZOL:        host=mail.zol.co.zw  port=587 secure=tls  user=you@zol.co.zw   pass=...
//   Mailgun:    host=smtp.mailgun.org port=587 secure=tls user=postmaster@... pass=...
//   SendGrid:   host=smtp.sendgrid.net port=587 secure=tls user=apikey         pass=YOUR_API_KEY
//   Local/Mailhog test: host=localhost port=1025 secure=none user='' pass=''
// =============================================================

if(!defined('MAIL_FROM'))      define('MAIL_FROM',      'no-reply@quickfixzw.co.zw');
if(!defined('MAIL_FROM_NAME')) define('MAIL_FROM_NAME', 'QuickFix ZW');
if(!defined('MAIL_ADMIN'))     define('MAIL_ADMIN',     'admin@quickfixzw.co.zw');
if(!defined('MAIL_ENABLED'))   define('MAIL_ENABLED',   true);

if(!defined('SMTP_HOST'))     define('SMTP_HOST',     'smtp.gmail.com');
if(!defined('SMTP_PORT'))     define('SMTP_PORT',     587);
if(!defined('SMTP_SECURE'))   define('SMTP_SECURE',   'tls');   // 'tls' | 'ssl' | 'none'
if(!defined('SMTP_USERNAME')) define('SMTP_USERNAME', 'you@example.com');
if(!defined('SMTP_PASSWORD')) define('SMTP_PASSWORD', 'CHANGE-ME');
if(!defined('SMTP_TIMEOUT'))  define('SMTP_TIMEOUT',  20);
if(!defined('SMTP_DEBUG'))    define('SMTP_DEBUG',    false);    // logs the SMTP conversation to logs/smtp.log
