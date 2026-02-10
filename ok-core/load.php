<?php
declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

/**
 * FILE: ok-core/load.php
 * OK Engine — Core Loader
 */

if (defined('OK_LOADED')) {
    return;
}
define('OK_LOADED', true);

/* --------------------------------------------------------------------------
 * 0) PHP 7.x helpers (avoid PHP8 str_* functions)
 * -------------------------------------------------------------------------- */
if (!function_exists('ok_str_starts_with')) {
    function ok_str_starts_with(string $haystack, string $needle): bool {
        return $needle === '' || substr($haystack, 0, strlen($needle)) === $needle;
    }
}
if (!function_exists('ok_str_ends_with')) {
    function ok_str_ends_with(string $haystack, string $needle): bool {
        if ($needle === '') return true;
        $len = strlen($needle);
        return substr($haystack, -$len) === $needle;
    }
}

/* --------------------------------------------------------------------------
 * 1) Paths
 * -------------------------------------------------------------------------- */
define('OK_VERSION', '1.6.0');
define('OK_START_TIME', microtime(true));

$OK_CORE = __DIR__;
$OK_ROOT = dirname(__DIR__);

/* --------------------------------------------------------------------------
 * 2) Config
 * -------------------------------------------------------------------------- */
$config_path = $OK_ROOT . '/ok-config.php';
if (is_file($config_path)) {
    require_once $config_path;
} else {
    $current_script = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($current_script !== 'install.php') {
        if (!headers_sent()) {
            header('Location: /ok-admin/install.php');
        }
        exit;
    }
}

/* --------------------------------------------------------------------------
 * 3) Error handling
 * -------------------------------------------------------------------------- */
if (defined('OK_DEBUG') && OK_DEBUG === true) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', $OK_ROOT . '/ok-content/debug.log');
}

/* --------------------------------------------------------------------------
 * 4) Session
 * -------------------------------------------------------------------------- */
$is_https =
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) ||
    (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

if (session_status() === PHP_SESSION_NONE) {
    $opts = [
        'cookie_httponly' => true,
        'cookie_secure'   => $is_https,
        'use_strict_mode' => true,
    ];
    if (PHP_VERSION_ID >= 70300) {
        $opts['cookie_samesite'] = 'Lax';
    }
    session_start($opts);
}

/* --------------------------------------------------------------------------
 * 5) Core classes
 * -------------------------------------------------------------------------- */
require_once $OK_CORE . '/classes/class-db.php';
require_once $OK_CORE . '/classes/class-hook-manager.php';
require_once $OK_CORE . '/classes/class-ok-widget.php';

/* --------------------------------------------------------------------------
 * 6) Init globals
 * -------------------------------------------------------------------------- */
global $ok_db, $ok_hooks;

$ok_db    = OK_DB::instance();
$ok_hooks = HookManager::instance();

/* --------------------------------------------------------------------------
 * 7) Load core functions
 * -------------------------------------------------------------------------- */
$core_functions = [
    'functions-utilities',
    'functions-options',
    'functions-hooks',
    'functions-filters',
    'ok-registered-hooks',
    'functions-post-meta',
    'functions-users',
    'functions-admin',
    'functions-security',
    'functions-widgets',
    'uploader',
    'functions-notifications',
    'function-notification-window',
    'functions-shortcodes',
    'functions-menus',
    'functions-editor',
    'template/general',
    'template/post',
    'template/media',
    'template/loop',
    'functions-userprofile',
];

foreach ($core_functions as $file) {
    $path = $OK_CORE . '/functions/' . $file . '.php';
    if (is_file($path)) {
        require_once $path;
    } else {
        if (defined('OK_DEBUG') && OK_DEBUG) {
            echo "Missing core file: " . htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . "<br>";
        }
    }
}

/* --------------------------------------------------------------------------
 * 8) Core setup
 * -------------------------------------------------------------------------- */
$core_setup = $OK_CORE . '/core-setup.php';
if (is_file($core_setup)) {
    require_once $core_setup;
}

/* --------------------------------------------------------------------------
 * 9) Timezone
 * -------------------------------------------------------------------------- */
if (function_exists('get_ok_option')) {
    $tz = (string)get_ok_option('timezone_string');
    if (!$tz && defined('OK_DEFAULT_TIMEZONE')) {
        $tz = (string)OK_DEFAULT_TIMEZONE;
    }
    date_default_timezone_set($tz ?: 'Asia/Tbilisi');
} else {
    date_default_timezone_set('Asia/Tbilisi');
}

/* --------------------------------------------------------------------------
 * 10) Register init callbacks
 * -------------------------------------------------------------------------- */
if (function_exists('add_ok_action') && function_exists('ok_setup_main_query')) {
    add_ok_action('init', 'ok_setup_main_query');
}

/* --------------------------------------------------------------------------
 * 11) LOAD PLUGINS
 * -------------------------------------------------------------------------- */
require_once $OK_CORE . '/functions/function-pluginloader.php';

if (function_exists('ok_core_load_plugins')) {
    ok_core_load_plugins();
}

/* --------------------------------------------------------------------------
 * 12) LOAD PUBLIC DIRECTORY (NEW)
 * -------------------------------------------------------------------------- */
// ეს სექცია ავტომატურად ტვირთავს ყველა .php ფაილს ok-public საქაღალდიდან
$ok_public_dir = $OK_ROOT . '/ok-public/';

if (is_dir($ok_public_dir)) {
    // მოძებნოს ყველა .php ფაილი
    $public_files = glob($ok_public_dir . '*.php');
    
    if ($public_files) {
        foreach ($public_files as $p_file) {
            if (is_file($p_file)) {
                require_once $p_file;
            }
        }
    }
}

/* --------------------------------------------------------------------------
 * 13) Load theme
 * -------------------------------------------------------------------------- */
$theme_slug = 'default';
if (function_exists('get_ok_option')) {
    $theme_slug = (string)get_ok_option('active_theme', 'default');
}
$theme_slug = preg_replace('/[^a-zA-Z0-9_-]/', '', $theme_slug) ?: 'default';

$theme_base = $OK_ROOT . '/ok-content/themes/';
$theme_functions = $theme_base . $theme_slug . '/functions.php';
if (!is_file($theme_functions)) {
    $theme_functions = $theme_base . 'default/functions.php';
}
if (is_file($theme_functions)) {
    require_once $theme_functions;
}

/* --------------------------------------------------------------------------
 * 14) Fire init
 * -------------------------------------------------------------------------- */
if (function_exists('do_ok_action')) {
    do_ok_action('init');
} else {
    if (function_exists('ok_setup_main_query')) {
        ok_setup_main_query();
    }
}

/* --------------------------------------------------------------------------
 * 15) Defensive check for main query
 * -------------------------------------------------------------------------- */
if (function_exists('ok_setup_main_query')) {
    if (!isset($GLOBALS['ok_query']) || !is_array($GLOBALS['ok_query']) || !isset($GLOBALS['ok_query']['posts'])) {
        ok_setup_main_query();
    }
}