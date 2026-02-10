<?php
/**
 * FILE: ok-core/functions/function-pluginloader.php
 * OK Engine - Smart Plugin Loader
 */

function ok_core_load_plugins() {
    $plugins_root = $_SERVER['DOCUMENT_ROOT'] . '/ok-content/plugins/';
    $active_plugins = get_ok_option('active_plugins', []);

    if (!is_array($active_plugins)) $active_plugins = [];

    // Auto-Discovery: თუ სია ცარიელია, ვეძებთ და ვამატებთ
    if (empty($active_plugins)) {
        if (is_dir($plugins_root)) {
            $dirs = glob($plugins_root . '*', GLOB_ONLYDIR);
            $found_new = false;
            if ($dirs) {
                foreach ($dirs as $dir) {
                    $dirname = basename($dir);
                    if (file_exists($dir . '/' . $dirname . '.php')) {
                        $active_plugins[] = $dirname . '/' . $dirname . '.php';
                        $found_new = true;
                    } elseif (file_exists($dir . '/index.php')) {
                        $active_plugins[] = $dirname . '/index.php';
                        $found_new = true;
                    }
                }
            }
            if ($found_new) {
                update_ok_option('active_plugins', array_values(array_unique($active_plugins)));
            }
        }
    }

    $has_crash = false;
    foreach ($active_plugins as $key => $plugin_file) {
        $full_path = $plugins_root . $plugin_file;

        if (!file_exists($full_path)) {
            unset($active_plugins[$key]);
            $has_crash = true;
            continue;
        }

        ob_start();
        try {
            include_once $full_path;
            ob_end_flush();
        } catch (Throwable $e) {
            ob_end_clean();
            error_log("Plugin Crash: " . $e->getMessage());
            unset($active_plugins[$key]);
            $has_crash = true;
        }
    }

    if ($has_crash) {
        update_ok_option('active_plugins', array_values($active_plugins));
    }

    if (function_exists('do_ok_action')) {
        do_ok_action('ok_plugins_loaded');
    }
}