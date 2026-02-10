<?php
/**
 * FILE: ok-core/functions/function-pluginloader.php
 * OK Engine - Hardened Plugin Loader
 */

function ok_normalize_plugin_entry(string $plugin_file): string {
    $plugin_file = trim(str_replace('\\', '/', $plugin_file));
    $plugin_file = ltrim($plugin_file, '/');

    if (strpos($plugin_file, '..') !== false) {
        return '';
    }

    if (!preg_match('/^[a-zA-Z0-9_\/-]+\.php$/', $plugin_file)) {
        return '';
    }

    return $plugin_file;
}

function ok_detect_plugin_entry(string $dir): ?string {
    $dirname = basename($dir);

    $primary = $dir . '/' . $dirname . '.php';
    if (is_file($primary)) {
        return $dirname . '/' . $dirname . '.php';
    }

    $index = $dir . '/index.php';
    if (is_file($index)) {
        return $dirname . '/index.php';
    }

    return null;
}

function ok_core_load_plugins() {
    $plugins_root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/') . '/ok-content/plugins/';

    if (!is_dir($plugins_root)) {
        if (function_exists('ok_log_debug')) {
            ok_log_debug('Plugin root directory not found.', ['plugins_root' => $plugins_root], 'WARNING');
        }
        return;
    }

    $active_plugins = get_ok_option('active_plugins', []);
    if (!is_array($active_plugins)) {
        $active_plugins = [];
    }

    if (empty($active_plugins)) {
        $dirs = glob($plugins_root . '*', GLOB_ONLYDIR);
        $found_new = false;

        if ($dirs) {
            foreach ($dirs as $dir) {
                $entry = ok_detect_plugin_entry($dir);
                if ($entry !== null) {
                    $active_plugins[] = $entry;
                    $found_new = true;
                }
            }
        }

        if ($found_new) {
            $active_plugins = array_values(array_unique($active_plugins));
            update_ok_option('active_plugins', $active_plugins);
            if (function_exists('ok_log_debug')) {
                ok_log_debug('Auto-discovered plugins and updated active list.', ['count' => count($active_plugins)]);
            }
        }
    }

    $filtered_active = [];
    $has_crash = false;

    foreach ($active_plugins as $plugin_file) {
        $normalized = ok_normalize_plugin_entry((string)$plugin_file);
        if ($normalized === '') {
            $has_crash = true;
            if (function_exists('ok_log_debug')) {
                ok_log_debug('Invalid plugin path rejected.', ['plugin' => $plugin_file], 'WARNING');
            }
            continue;
        }

        $full_path = $plugins_root . $normalized;
        $real_root = realpath($plugins_root);
        $real_full = realpath($full_path);

        if ($real_root === false || $real_full === false || strpos($real_full, $real_root) !== 0 || !is_file($real_full)) {
            $has_crash = true;
            if (function_exists('ok_log_debug')) {
                ok_log_debug('Plugin file missing or outside allowed path.', ['plugin' => $normalized], 'WARNING');
            }
            continue;
        }

        $filtered_active[] = $normalized;

        $runner = function () use ($real_full, $normalized) {
            ob_start();
            include_once $real_full;
            $buffer = ob_get_clean();
            if ($buffer !== '' && function_exists('ok_log_debug')) {
                ok_log_debug('Plugin produced output during load.', ['plugin' => $normalized], 'WARNING');
            }
        };

        if (function_exists('ok_run_sandboxed')) {
            $loaded = ok_run_sandboxed($runner, false, ['plugin' => $normalized]);
            if ($loaded === false) {
                $has_crash = true;
                $filtered_active = array_values(array_diff($filtered_active, [$normalized]));
            }
        } else {
            try {
                $runner();
            } catch (Throwable $e) {
                $has_crash = true;
                $filtered_active = array_values(array_diff($filtered_active, [$normalized]));
                error_log('Plugin Crash: ' . $e->getMessage());
            }
        }
    }

    $filtered_active = array_values(array_unique($filtered_active));
    if ($has_crash || $filtered_active !== array_values(array_unique($active_plugins))) {
        update_ok_option('active_plugins', $filtered_active);
    }

    if (function_exists('do_ok_action')) {
        do_ok_action('ok_plugins_loaded');
    }
}
