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
    define('PAYNOW_INTEGRATION_ID', 1201);
}

if(!defined('PAYNOW_INTEGRATION_KEY')){
    // Test integration key — replace with your live key for production
    define('PAYNOW_INTEGRATION_KEY', '3e9fed89-60e1-4ce5-ab6e-6b1eb2d4f977');
}

if(!defined('PAYNOW_TEST_MODE')){
    define('PAYNOW_TEST_MODE', true);
}
