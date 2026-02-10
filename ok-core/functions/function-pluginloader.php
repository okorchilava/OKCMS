<?php
/**
 * FILE: ok-core/functions/function-pluginloader.php
 * OK Engine - Strict Plugin Loader
 */

function ok_normalize_plugin_entry(string $plugin_file): string {
    $plugin_file = trim(str_replace('\\', '/', $plugin_file));
    $plugin_file = ltrim($plugin_file, '/');

    if ($plugin_file === '' || strpos($plugin_file, '..') !== false) {
        throw new RuntimeException('Invalid plugin path.');
    }

    if (!preg_match('/^[a-zA-Z0-9_\/-]+\.php$/', $plugin_file)) {
        throw new RuntimeException('Plugin path format is invalid.');
    }

    return $plugin_file;
}

function ok_detect_plugin_entry(string $dir): string {
    $dirname = basename($dir);

    $primary = $dir . '/' . $dirname . '.php';
    if (is_file($primary)) {
        return $dirname . '/' . $dirname . '.php';
    }

    $index = $dir . '/index.php';
    if (is_file($index)) {
        return $dirname . '/index.php';
    }

    throw new RuntimeException('Plugin entry file not found for: ' . $dirname);
}

function ok_core_load_plugins() {
    $plugins_root = rtrim((string)$_SERVER['DOCUMENT_ROOT'], '/') . '/ok-content/plugins/';
    if (!is_dir($plugins_root)) {
        throw new RuntimeException('Plugin root directory not found: ' . $plugins_root);
    }

    $active_plugins = get_ok_option('active_plugins', []);
    if (!is_array($active_plugins)) {
        throw new RuntimeException('active_plugins must be an array.');
    }

    if (empty($active_plugins)) {
        $dirs = glob($plugins_root . '*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            $active_plugins[] = ok_detect_plugin_entry($dir);
        }
        $active_plugins = array_values(array_unique($active_plugins));
        update_ok_option('active_plugins', $active_plugins);
        ok_log_debug('Auto-discovered plugins.', ['count' => count($active_plugins)]);
    }

    $real_root = realpath($plugins_root);
    if ($real_root === false) {
        throw new RuntimeException('Plugin root realpath failed.');
    }

    foreach ($active_plugins as $plugin_file) {
        $normalized = ok_normalize_plugin_entry((string)$plugin_file);
        $full_path = $plugins_root . $normalized;
        $real_full = realpath($full_path);

        if ($real_full === false || strpos($real_full, $real_root) !== 0 || !is_file($real_full)) {
            ok_log_debug('Plugin path validation failed.', ['plugin' => $normalized], 'ERROR');
            throw new RuntimeException('Plugin file invalid: ' . $normalized);
        }

        ok_run_sandboxed(function () use ($real_full, $normalized) {
            ob_start();
            include_once $real_full;
            $buffer = ob_get_clean();
            if ($buffer !== '') {
                ok_log_debug('Plugin printed output while loading.', ['plugin' => $normalized], 'WARNING');
            }
        }, ['plugin' => $normalized]);
    }

    do_ok_action('ok_plugins_loaded');
}
