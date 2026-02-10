<?php
declare(strict_types=1);

/**
 * OK Engine — Configuration
 */

// DB
define('DB_NAME',     'ok_3');
define('DB_USER',     'root');
define('DB_PASSWORD', '');
define('DB_HOST',     'localhost');
define('DB_CHARSET',  'utf8mb4');
define('DB_TIMEOUT',  5);

// Engine
define('OK_DEBUG', true);
define('OK_DEFAULT_TIMEZONE', 'Asia/Tbilisi');

// Global secret for signatures/cookies
define('OK_SECRET_KEY', '5b773e060ca82db8767c39205a482011f4ae71ee73f72f589fdedcce07512f61');
