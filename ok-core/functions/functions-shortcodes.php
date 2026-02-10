<?php
/**
 * FILE: ok-core/functions/functions-shortcodes.php
 * OK Engine - Safer Shortcode System
 */

global $ok_shortcodes;
$ok_shortcodes = [];

function add_ok_shortcode($tag, $callback) {
    global $ok_shortcodes;

    $tag = strtolower(trim((string)$tag));
    if ($tag === '' || !preg_match('/^[a-z0-9_-]+$/', $tag)) {
        if (function_exists('ok_log_debug')) {
            ok_log_debug('Rejected invalid shortcode tag.', ['tag' => $tag], 'WARNING');
        }
        return false;
    }

    if (!is_callable($callback)) {
        if (function_exists('ok_log_debug')) {
            ok_log_debug('Rejected shortcode with non-callable callback.', ['tag' => $tag], 'WARNING');
        }
        return false;
    }

    $ok_shortcodes[$tag] = $callback;
    return true;
}

function do_ok_shortcode($content) {
    global $ok_shortcodes;

    if (empty($content)) return '';
    if (empty($ok_shortcodes)) return $content;

    $content = str_replace(['&#91;', '&#93;'], ['[', ']'], $content);
    $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
    $content = str_replace(["\xC2\xA0", "&nbsp;"], ' ', $content);

    foreach ($ok_shortcodes as $tag => $callback) {
        $regex = '/\[' . preg_quote((string)$tag, '/') . '\b([^\]]*)\](?:((?:(?!\[\/' . preg_quote((string)$tag, '/') . '\]).)*)\[\/' . preg_quote((string)$tag, '/') . '\])?/us';

        if (!preg_match_all($regex, $content, $matches, PREG_SET_ORDER)) {
            continue;
        }

        foreach ($matches as $match) {
            $full_string = (string)($match[0] ?? '');
            $atts_string = (string)($match[1] ?? '');
            $inner = array_key_exists(2, $match) ? $match[2] : null;

            if ($full_string === '') {
                continue;
            }

            $atts = ok_simple_parse_atts($atts_string);

            $output = function_exists('ok_run_sandboxed')
                ? ok_run_sandboxed(function () use ($callback, $atts, $inner, $tag) {
                    return call_user_func($callback, $atts, $inner, $tag);
                }, '', ['shortcode' => $tag, 'atts' => $atts])
                : call_user_func($callback, $atts, $inner, $tag);

            $content = str_replace($full_string, (string)$output, $content);
        }
    }

    return $content;
}

function ok_simple_parse_atts($text) {
    $atts = [];
    $text = trim((string)$text);
    if ($text === '') {
        return $atts;
    }

    if (preg_match_all("/([a-zA-Z0-9_-]+)\\s*=\\s*(?:\"([^\"]*)\"|'([^']*)'|([^\\s\\]]+))/u", $text, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $key = strtolower((string)$m[1]);
            $value = '';
            if (isset($m[2]) && $m[2] !== '') {
                $value = $m[2];
            } elseif (isset($m[3]) && $m[3] !== '') {
                $value = $m[3];
            } elseif (isset($m[4])) {
                $value = $m[4];
            }
            $atts[$key] = trim((string)$value);
        }
    }

    return $atts;
}

if (function_exists('add_ok_filter')) {
    add_ok_filter('the_content', 'do_ok_shortcode', 11);
}
