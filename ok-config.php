<?php
declare(strict_types=1);

/**
 * OK Engine — Configuration
 * @package OK_Engine
 */

// =============================================================================
// 1. DATABASE SETTINGS
// =============================================================================
define('DB_NAME',     'jtfugplo_paata');
define('DB_USER',     'root');
define('DB_PASSWORD', '');             // ⚠️ PROD: გამოიყენე რთული პაროლი
define('DB_HOST',     'localhost');
define('DB_CHARSET',  'utf8mb4');
define('DB_COLLATE',  'utf8mb4_unicode_ci');
define('DB_PREFIX',   'ok_');          // ცხრილების პრეფიქსი (მაგ: ok_users)
define('DB_TIMEOUT',  5);              // კავშირის ტაიმაუტი (წამებში)

// =============================================================================
// 2. SECURITY KEYS
// =============================================================================
// გენერირებისთვის გამოიყენე: bin2hex(random_bytes(32))
// არასდროს გააზიარო ეს გასაღები!
define('OK_SECRET_KEY', 'CHANGE_THIS_TO_A_VER_LONG_RANDOM_HASH_STRING_FOR_SECURITY_!!!');

// =============================================================================
// 3. SYSTEM SETTINGS
// =============================================================================
// Hard Debug Mode. თუ ეს true არის, ბაზის პარამეტრს აზრი არ აქვს (სულ ჩართული იქნება)
// დატოვე false, რათა load.php-მ ბაზიდან მართოს დეველოპერ მოდი.
define('OK_DEBUG', false);

define('OK_DEFAULT_TIMEZONE', 'Asia/Tbilisi');
define('OK_MEMORY_LIMIT',     '256M'); // PHP მეხსიერების ლიმიტი