<?php
/**
 * OK Engine Template Tags - Post (CLEAN OUTPUT CONTRACT)
 *
 * Dependencies (MUST be loaded before this file):
 * - ok_date($format = null, $timestamp = null): string      (from function_utilities.php)
 * - ok_category(): ?object                                  (from function_utilities.php)
 *
 * Theme Tags:
 * - get_the_title(), the_title()
 * - get_the_ID(), the_ID()
 * - get_the_content(), the_content()
 * - get_the_excerpt(), the_excerpt()
 * - get_ok_date_text(), the_ok_date()
 * - get_ok_category()
 * - get_ok_author(), the_ok_author()
 */

if (!defined('OK_LOADED')) {
    if (!headers_sent()) { http_response_code(403); }
    exit('Access Denied.');
}

/* -------------------------------------------------------
 * Internals
 * -----------------------------------------------------*/
if (!function_exists('_ok_resolve_post')) {
    function _ok_resolve_post($post_obj = null)
    {
        return $post_obj ?: ($GLOBALS['post'] ?? null);
    }
}

if (!function_exists('_ok_post_field')) {
    function _ok_post_field($post_obj, string $key, $default = null)
    {
        if (is_array($post_obj))  return $post_obj[$key] ?? $default;
        if (is_object($post_obj)) return $post_obj->{$key} ?? $default;
        return $default;
    }
}

/* -------------------------------------------------------
 * Title
 * -----------------------------------------------------*/
if (!function_exists('get_the_title')) {
    function get_the_title($post_obj = null): string
    {
        $post_obj = _ok_resolve_post($post_obj);
        $v = _ok_post_field($post_obj, 'post_title', '');
        return is_string($v) ? $v : (string)$v;
    }
}

if (!function_exists('the_title')) {
    function the_title(string $before = '', string $after = '', bool $echo = true): ?string
    {
        $title = get_the_title();
        if ($title === '') return null;

        $out = $before . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . $after;
        if ($echo) { echo $out; return null; }
        return $out;
    }
}

/* -------------------------------------------------------
 * ID
 * -----------------------------------------------------*/
if (!function_exists('get_the_ID')) {
    function get_the_ID(): int
    {
        $post = $GLOBALS['post'] ?? null;
        if (is_object($post)) return (int)($post->id ?? $post->ID ?? 0);
        if (is_array($post))  return (int)($post['id'] ?? $post['ID'] ?? 0);
        return 0;
    }
}

if (!function_exists('the_ID')) {
    function the_ID(): void
    {
        echo (string)get_the_ID();
    }
}

/* -------------------------------------------------------
 * Content (ONLY OK Shortcodes)
 * -----------------------------------------------------*/
if (!function_exists('get_the_content')) {
    function get_the_content($post_obj = null): string
    {
        $post_obj = _ok_resolve_post($post_obj);
        $v = _ok_post_field($post_obj, 'post_content', '');
        $content = is_string($v) ? $v : (string)$v;

        // 🟢 მხოლოდ შენი სისტემის შორთკოდების დამუშავება
        if (function_exists('do_ok_shortcode')) {
            $content = do_ok_shortcode($content);
        }

        return $content;
    }
}

if (!function_exists('the_content')) {
    function the_content(): void
    {
        echo get_the_content();
    }
}

/* -------------------------------------------------------
 * Excerpt
 * -----------------------------------------------------*/
if (!function_exists('get_the_excerpt')) {
    function get_the_excerpt(int $limit = 100): string
    {
        $text = trim(strip_tags(get_the_content()));
        if ($text === '') return '';

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($text, 'UTF-8') <= $limit) return $text;
            return mb_substr($text, 0, $limit, 'UTF-8') . '...';
        }

        if (strlen($text) <= $limit) return $text;
        return substr($text, 0, $limit) . '...';
    }
}

if (!function_exists('the_excerpt')) {
    function the_excerpt(int $limit = 100): void
    {
        echo htmlspecialchars(get_the_excerpt($limit), ENT_QUOTES, 'UTF-8');
    }
}

/* -------------------------------------------------------
 * Date — ONLY ok_date() (no fallbacks, no HTML)
 * NOTE: ok_date() internally reads post date from global $post (your engine design)
 * -----------------------------------------------------*/
if (!function_exists('get_ok_date_text')) {
    function get_ok_date_text(): string
    {
        if (!function_exists('ok_date')) {
            trigger_error('Missing dependency: ok_date() must be defined in function_utilities.php before template tags.', E_USER_ERROR);
        }

        return (string) ok_date();
    }
}

if (!function_exists('the_ok_date')) {
    function the_ok_date(): void
    {
        $text = trim(get_ok_date_text());
        if ($text !== '') {
            echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        }
    }
}

/* -------------------------------------------------------
 * Category — ONLY get_ok_category()
 * -----------------------------------------------------*/
if (!function_exists('get_ok_category')) {
    function get_ok_category(): ?object
    {
        if (!function_exists('ok_category')) {
            trigger_error('Missing dependency: ok_category() must be defined in function_utilities.php before template tags.', E_USER_ERROR);
        }

        $raw = ok_category();
        if (!is_object($raw)) return null;

        $name = trim((string)($raw->name ?? ''));
        $url  = trim((string)($raw->url  ?? ''));

        if ($name === '' || $url === '') return null;

        $cat = new stdClass();
        $cat->id    = (int)($raw->id ?? 0);
        $cat->slug  = (string)($raw->slug ?? '');
        $cat->href  = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $cat->label = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $cat->raw   = $raw;

        return $cat;
    }
}

/* -------------------------------------------------------
 * Author — DB driven:
 * - Source: $post->post_author (user id)
 * - Table: ok_users (id, username, display_name)
 * - Output: display_name if not empty else username
 * -----------------------------------------------------*/
if (!function_exists('get_ok_author')) {
    function get_ok_author($post_obj = null): ?object
    {
        global $ok_db;

        $post_obj = _ok_resolve_post($post_obj);
        if (!$post_obj) return null;

        $author_id = _ok_post_field($post_obj, 'post_author', 0);
        $author_id = is_numeric($author_id) ? (int)$author_id : 0;
        if ($author_id < 1) return null;

        if (!isset($ok_db) || !is_object($ok_db) || !method_exists($ok_db, 'get_row')) {
            trigger_error('Missing dependency: global $ok_db with get_row() is required for get_ok_author().', E_USER_ERROR);
        }

        // Use prepare() if your db wrapper supports it; otherwise safe int-cast is acceptable here.
        if (method_exists($ok_db, 'prepare')) {
            $sql = $ok_db->prepare(
                "SELECT id, username, display_name FROM ok_users WHERE id = %d LIMIT 1",
                $author_id
            );
            $user = $ok_db->get_row($sql);
        } else {
            $user = $ok_db->get_row(
                "SELECT id, username, display_name FROM ok_users WHERE id = " . $author_id . " LIMIT 1"
            );
        }

        if (!$user || !is_object($user)) return null;

        $name = trim((string)($user->display_name ?? ''));
        if ($name === '') {
            $name = trim((string)($user->username ?? ''));
        }
        if ($name === '') return null;

        $a = new stdClass();
        $a->id    = (int)($user->id ?? $author_id);
        $a->name  = $name; // raw
        $a->label = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $a->raw   = $user;

        return $a;
    }
}

if (!function_exists('the_ok_author')) {
    function the_ok_author($post_obj = null): void
    {
        $a = get_ok_author($post_obj);
        if ($a && $a->label !== '') {
            echo $a->label;
        }
    }
}