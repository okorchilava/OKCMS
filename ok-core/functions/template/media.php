<?php
if (!defined('OK_LOADED')) {
    if (!headers_sent()) { http_response_code(403); }
    exit('Access Denied.');
}

/**
 * Convert stored post_image value to absolute URL.
 * - If already absolute URL => return as-is
 * - If relative path => resolve under /ok-content/uploads/
 */
if (!function_exists('ok_media_resolve_url')) {
    function ok_media_resolve_url(string $img): string
    {
        $img = trim($img);
        if ($img === '') return '';

        if (filter_var($img, FILTER_VALIDATE_URL)) {
            return $img;
        }

        $base = rtrim(dirname((string)($_SERVER['SCRIPT_NAME'] ?? '')), '/\\');
        if ($base === '/' || $base === '\\') $base = '';

        return $base . '/ok-content/uploads/' . ltrim($img, '/');
    }
}

/**
 * Try to get a local filesystem path from an URL (only if under this host).
 */
if (!function_exists('ok_media_url_to_local_path')) {
    function ok_media_url_to_local_path(string $url): string
    {
        $url = trim($url);
        if ($url === '') return '';

        $parts = @parse_url($url);
        $path  = (string)($parts['path'] ?? '');

        // If relative URL like /ok-content/uploads/...
        if ($path !== '' && $path[0] === '/') {
            $docroot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
            if ($docroot !== '') {
                $full = $docroot . $path;
                return $full;
            }
        }

        return '';
    }
}

/**
 * Attempt to locate a thumbnail variant on disk.
 * Supported patterns (common):
 *  - file-thumb.ext
 *  - file-thumbnail.ext
 *  - file-300x200.ext (and a few common sizes)
 * If none exists => returns original URL.
 */
if (!function_exists('ok_media_variant_url')) {
    function ok_media_variant_url(string $url, string $size): string
    {
        $url  = trim($url);
        $size = trim($size);
        if ($url === '' || $size === '' || $size === 'full') return $url;

        $local = ok_media_url_to_local_path($url);
        if ($local === '' || !is_file($local)) return $url;

        $dir  = dirname($local);
        $base = basename($local);

        $dot = strrpos($base, '.');
        if ($dot === false) return $url;

        $name = substr($base, 0, $dot);
        $ext  = substr($base, $dot); // includes "."

        $candidates = [];

        if ($size === 'thumbnail' || $size === 'thumb') {
            $candidates[] = $name . '-thumb' . $ext;
            $candidates[] = $name . '-thumbnail' . $ext;

            // common "WxH" variants
            foreach (['150x150', '300x200', '300x300', '400x250', '600x400'] as $wh) {
                $candidates[] = $name . '-' . $wh . $ext;
            }
        }

        foreach ($candidates as $cand) {
            $candPath = $dir . DIRECTORY_SEPARATOR . $cand;
            if (is_file($candPath)) {
                // Replace basename in URL path
                return preg_replace('#' . preg_quote($base, '#') . '$#', $cand, $url) ?: $url;
            }
        }

        return $url;
    }
}

/**
 * Return image URL for current post (size-aware).
 */
if (!function_exists('get_the_post_image_url')) {
    function get_the_post_image_url(string $size = 'full'): string
    {
        global $post;

        if (!$post) return '';

        // შენ თქვი: post_image გაქვს მხოლოდ
        $raw = is_object($post) ? (string)($post->post_image ?? '') : (string)($post['post_image'] ?? '');
        if ($raw === '') return '';

        $url = ok_media_resolve_url($raw);
        return ok_media_variant_url($url, $size);
    }
}

/**
 * Return ready <img> HTML (size-aware).
 * Usage:
 *  - get_the_post_image('full', ['class' => 'img-fluid rounded'])
 *  - get_the_post_image('thumbnail', ['class' => 'news-img'])
 */
if (!function_exists('get_the_post_image')) {
    function get_the_post_image(string $size = 'thumbnail', array $attrs = []): string
    {
        $url = get_the_post_image_url($size);
        if ($url === '') return '';

        $class   = isset($attrs['class']) ? (string)$attrs['class'] : 'img-fluid';
        $alt     = isset($attrs['alt']) ? (string)$attrs['alt'] : (function_exists('get_the_title') ? (string)get_the_title() : '');
        $loading = isset($attrs['loading']) ? (string)$attrs['loading'] : 'lazy';
        $width   = isset($attrs['width']) ? (string)$attrs['width'] : '';
        $height  = isset($attrs['height']) ? (string)$attrs['height'] : '';

        $html  = '<img';
        $html .= ' src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"';
        $html .= ' alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . '"';
        $html .= ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"';
        $html .= ' loading="' . htmlspecialchars($loading, ENT_QUOTES, 'UTF-8') . '"';

        if ($width !== '')  $html .= ' width="' . htmlspecialchars($width, ENT_QUOTES, 'UTF-8') . '"';
        if ($height !== '') $html .= ' height="' . htmlspecialchars($height, ENT_QUOTES, 'UTF-8') . '"';

        $html .= '>';
        return $html;
    }
}

if (!function_exists('the_post_image')) {
    function the_post_image(string $size = 'thumbnail', array $attrs = []): void
    {
        echo get_the_post_image($size, $attrs);
    }
}
