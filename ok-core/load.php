<?php
declare(strict_types=1);

/**
 * FILE: ok-core/load.php
 * OK Engine — Core Loader
 */

if (defined('OK_LOADED')) {
    return;
}
define('OK_LOADED', true);

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

define('OK_VERSION', '1.6.0');
define('OK_START_TIME', microtime(true));

$OK_CORE = __DIR__;
$OK_ROOT = dirname(__DIR__);
$OK_DEBUG_LOG = $OK_ROOT . '/ok-content/debug.log';

if (!function_exists('ok_loader_log')) {
    function ok_loader_log(string $message, array $context = [], string $level = 'INFO'): void {
        global $OK_DEBUG_LOG;
        $line = '[' . date('Y-m-d H:i:s') . "] [{$level}] " . $message;
        if (!empty($context)) {
            $json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json !== false) {
                $line .= ' | ' . $json;
            }
        }
        $line .= PHP_EOL;
        @file_put_contents($OK_DEBUG_LOG, $line, FILE_APPEND | LOCK_EX);
    }
}

ini_set('log_errors', '1');
ini_set('error_log', $OK_DEBUG_LOG);
error_reporting(E_ALL);

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

if (defined('OK_DEBUG') && OK_DEBUG === true) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
}

if (!defined('OK_SANDBOX_MODE')) {
    define('OK_SANDBOX_MODE', true);
}

set_error_handler(function ($severity, $message, $file, $line) {
    ok_loader_log('PHP Error trapped by sandbox.', [
        'severity' => $severity,
        'message' => $message,
        'file' => $file,
        'line' => $line,
    ], 'ERROR');

    if (defined('OK_SANDBOX_MODE') && OK_SANDBOX_MODE === true) {
        return true;
    }

    return false;
});

set_exception_handler(function (Throwable $e) {
    ok_loader_log('Uncaught exception trapped by sandbox.', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'exception' => get_class($e),
    ], 'ERROR');

    if (defined('OK_SANDBOX_MODE') && OK_SANDBOX_MODE === true) {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
        }
        echo '<h1>System recovered in sandbox mode.</h1>';
        exit;
    }
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        ok_loader_log('Fatal shutdown error.', $error, 'ERROR');
    }
});

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

require_once $OK_CORE . '/classes/class-db.php';
require_once $OK_CORE . '/classes/class-hook-manager.php';
require_once $OK_CORE . '/classes/class-ok-widget.php';

global $ok_db, $ok_hooks;
$ok_db    = OK_DB::instance();
$ok_hooks = HookManager::instance();

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
        ok_loader_log('Missing core file.', ['path' => $path], 'WARNING');
    }
}

$core_setup = $OK_CORE . '/core-setup.php';
if (is_file($core_setup)) {
    require_once $core_setup;
}

if (function_exists('get_ok_option')) {
    $tz = (string)get_ok_option('timezone_string');
    if (!$tz && defined('OK_DEFAULT_TIMEZONE')) {
        $tz = (string)OK_DEFAULT_TIMEZONE;
    }
    date_default_timezone_set($tz ?: 'Asia/Tbilisi');
} else {
    date_default_timezone_set('Asia/Tbilisi');
}

if (function_exists('add_ok_action') && function_exists('ok_setup_main_query')) {
    add_ok_action('init', 'ok_setup_main_query');
}

require_once $OK_CORE . '/functions/function-pluginloader.php';
if (function_exists('ok_core_load_plugins')) {
    ok_core_load_plugins();
}

$ok_public_dir = $OK_ROOT . '/ok-public/';
if (is_dir($ok_public_dir)) {
    $public_files = glob($ok_public_dir . '*.php');
    if ($public_files) {
        foreach ($public_files as $p_file) {
            if (is_file($p_file)) {
                require_once $p_file;
            }
        }
    }
}

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

if (function_exists('do_ok_action')) {
    do_ok_action('init');
}

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