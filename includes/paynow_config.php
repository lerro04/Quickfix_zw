<?php
// =============================================================
// PayNow Integration Settings
// =============================================================
// 1. Register at https://www.paynow.co.zw/Customer/Register
// 2. Create an Advanced Integration to get your Integration ID
// 3. Email yourself the Integration Key from the dashboard
// 4. Replace the values below for production
// =============================================================

if(!defined('PAYNOW_INTEGRATION_ID')){
 // Test integration ID — replace with your live ID for production
 define('PAYNOW_INTEGRATION_ID', 18574);
}

if(!defined('PAYNOW_INTEGRATION_KEY')){
 // Test integration key — replace with your live key for production
 define('PAYNOW_INTEGRATION_KEY', '690290f8-1865-41ad-ac78-5a23fc1740c6');
}

if(!defined('PAYNOW_TEST_MODE')){
 define('PAYNOW_TEST_MODE', false);
}
