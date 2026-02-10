<?php
/**
 * OK Engine — Template Tags: General (FULL)
 * Provides: get_header/get_footer/get_sidebar/get_template_part + permalink helpers.
 *
 * Notes:
 * - No declare(strict_types=1) here to avoid BOM/whitespace fatal errors in XAMPP.
 * - Uses OK options: active_theme, permalink_structure
 * - Uses post fields: id, post_name, post_date
 */

if (!defined('OK_LOADED')) {
    if (!headers_sent()) { http_response_code(403); }
    exit('Access Denied.');
}

/* ----------------------------------------------------------------------------
 * Theme path helpers
 * ------------------------------------------------------------------------- */

if (!function_exists('ok_theme_slug')) {
    function ok_theme_slug(): string
    {
        $slug = function_exists('get_ok_option') ? (string)get_ok_option('active_theme', 'default') : 'default';
        $slug = preg_replace('/[^a-zA-Z0-9_-]/', '', $slug) ?: 'default';
        return $slug;
    }
}

if (!function_exists('ok_root_path')) {
    function ok_root_path(): string
    {
        // ok-core/functions/template -> ok-core/functions -> ok-core -> project root
        return dirname(dirname(dirname(__DIR__)));
    }
}

if (!function_exists('ok_theme_path')) {
    function ok_theme_path(): string
    {
        return rtrim(ok_root_path(), '/\\') . '/ok-content/themes/' . ok_theme_slug();
    }
}

/* ----------------------------------------------------------------------------
 * Template parts
 * ------------------------------------------------------------------------- */

if (!function_exists('get_template_part')) {
    function get_template_part(string $slug, ?string $name = null, array $args = []): bool
    {
        $slug = preg_replace('/[^a-zA-Z0-9_-]/', '', $slug);
        if ($slug === '') return false;

        $templates = [];
        if ($name !== null && $name !== '') {
            $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $name);
            if ($name !== '') $templates[] = "{$slug}-{$name}.php";
        }
        $templates[] = "{$slug}.php";

        $root  = ok_root_path();
        $theme = ok_theme_path();

        $located = '';
        foreach ($templates as $file) {
            if (strpos($file, '..') !== false) continue;

            $p1 = $theme . '/' . $file;
            if (is_file($p1)) { $located = $p1; break; }

            // Optional fallback: allow root-level templates (rare)
            $p2 = $root . '/' . $file;
            if (is_file($p2)) { $located = $p2; break; }
        }

        if ($located === '') return false;

        if (!empty($args)) {
            // Safe-ish extract: do not overwrite existing vars
            extract($args, EXTR_SKIP);
        }

        require $located;
        return true;
    }
}

if (!function_exists('get_header')) {
    function get_header(?string $name = null, array $args = []): void
    {
        if (!get_template_part('header', $name, $args)) {
            $fallback = ok_theme_path() . '/header.php';
            if (is_file($fallback)) require $fallback;
        }
    }
}

if (!function_exists('get_footer')) {
    function get_footer(?string $name = null, array $args = []): void
    {
        if (!get_template_part('footer', $name, $args)) {
            $fallback = ok_theme_path() . '/footer.php';
            if (is_file($fallback)) require $fallback;
        }
    }
}

if (!function_exists('get_sidebar')) {
    function get_sidebar(?string $name = null, array $args = []): void
    {
        if (!get_template_part('sidebar', $name, $args)) {
            $fallback = ok_theme_path() . '/sidebar.php';
            if (is_file($fallback)) require $fallback;
        }
    }
}

/* ----------------------------------------------------------------------------
 * Permalink helpers (matches Settings: permalink_structure)
 * Supported:
 *  - plain => ?p=ID
 *  - post_name => /%postname%/
 *  - custom => tokens: %postname%, %post_id%, %year%, %monthnum%, %day%
 * ------------------------------------------------------------------------- */

if (!function_exists('ok__base_url_path')) {
    function ok__base_url_path(): string
    {
        // If installed under subdir (e.g. /ok), keep it; else ''
        $script = (string)($_SERVER['SCRIPT_NAME'] ?? '');
        $base = rtrim(dirname($script), '/\\');
        if ($base === '/' || $base === '\\') return '';
        return $base;
    }
}

if (!function_exists('ok__normalize_permalink_structure')) {
    function ok__normalize_permalink_structure(string $struct): string
    {
        $struct = trim($struct);

        if ($struct === '' || $struct === 'plain') return 'plain';
        if ($struct === 'post_name') return '/%postname%/';

        // Ensure leading/trailing slashes for patterns like %postname%
        if ($struct[0] !== '/') $struct = '/' . $struct;
        if (substr($struct, -1) !== '/') $struct .= '/';

        return $struct;
    }
}

if (!function_exists('get_the_permalink')) {
    function get_the_permalink($post_obj = null): string
    {
        if (!$post_obj) {
            global $post;
            $post_obj = $post;
        }

        $id = 0;
        $slug = '';
        $dateStr = '';

        if (is_array($post_obj)) {
            $id      = (int)($post_obj['id'] ?? 0);
            $slug    = (string)($post_obj['post_name'] ?? '');
            $dateStr = (string)($post_obj['post_date'] ?? '');
        } elseif (is_object($post_obj)) {
            $id      = (int)($post_obj->id ?? 0);
            $slug    = (string)($post_obj->post_name ?? '');
            $dateStr = (string)($post_obj->post_date ?? '');
        }

        $base = ok__base_url_path();

        $structOpt = function_exists('get_ok_option')
            ? (string)get_ok_option('permalink_structure', 'plain')
            : 'plain';

        $struct = ok__normalize_permalink_structure($structOpt);

        // No ID => home
        if ($id <= 0) {
            return $base . '/';
        }

        // Plain
        if ($struct === 'plain') {
            return $base . '/?p=' . $id;
        }

        // Slug missing => fallback to plain
        $slugSafe = preg_replace('/[^a-zA-Z0-9_-]/', '', $slug);
        if ($slugSafe === '') {
            return $base . '/?p=' . $id;
        }

        // Date tokens
        $ts = $dateStr ? strtotime($dateStr) : false;
        $year     = $ts ? date('Y', $ts) : '';
        $monthnum = $ts ? date('m', $ts) : '';
        $day      = $ts ? date('d', $ts) : '';

        // Replace tokens
        $path = $struct;
        $path = str_replace('%postname%', rawurlencode($slugSafe), $path);
        $path = str_replace('%post_id%', (string)$id, $path);
        $path = str_replace('%year%', $year, $path);
        $path = str_replace('%monthnum%', $monthnum, $path);
        $path = str_replace('%day%', $day, $path);

        // Clean double slashes
        $path = preg_replace('#/+#', '/', $path);

        return $base . $path;
    }
}

if (!function_exists('the_permalink')) {
    function the_permalink(): void
    {
        echo htmlspecialchars(get_the_permalink(), ENT_QUOTES, 'UTF-8');
    }
}
