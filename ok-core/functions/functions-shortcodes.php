<?php
/**
 * FILE: ok-core/functions/functions-shortcodes.php
 * OK Engine - Strict Shortcode System
 */

global $ok_shortcodes;
$ok_shortcodes = [];

function add_ok_shortcode($tag, $callback) {
    global $ok_shortcodes;

    $tag = strtolower(trim((string)$tag));
    if ($tag === '' || !preg_match('/^[a-z0-9_-]+$/', $tag)) {
        ok_log_debug('Invalid shortcode tag.', ['tag' => $tag], 'ERROR');
        throw new InvalidArgumentException('Shortcode tag is invalid.');
    }

    if (!is_callable($callback)) {
        ok_log_debug('Shortcode callback is not callable.', ['tag' => $tag], 'ERROR');
        throw new InvalidArgumentException('Shortcode callback must be callable.');
    }

    $ok_shortcodes[$tag] = $callback;
}

function do_ok_shortcode($content) {
    global $ok_shortcodes;

    static $depth = 0;
    $depth++;

    if ($depth > 10) {
        $depth--;
        throw new RuntimeException('Shortcode max recursion depth exceeded.');
    }

    try {
        if ($content === null || $content === '') {
            return '';
        }

        if (!is_string($content)) {
            throw new InvalidArgumentException('Shortcode content must be string.');
        }

        $content = str_replace(['&#91;', '&#93;'], ['[', ']'], $content);
        $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
        $content = str_replace(["\xC2\xA0", '&nbsp;'], ' ', $content);

        foreach ($ok_shortcodes as $tag => $callback) {
            $quotedTag = preg_quote((string)$tag, '/');
            $regex = '/\[' . $quotedTag . '\b([^\]]*)\](?:((?:(?!\[\/' . $quotedTag . '\]).)*)\[\/' . $quotedTag . '\])?/us';

            preg_match_all($regex, $content, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $full = (string)$match[0];
                $atts = ok_simple_parse_atts((string)($match[1] ?? ''));
                $inner = array_key_exists(2, $match) ? $match[2] : null;

                $output = ok_run_sandboxed(function () use ($callback, $atts, $inner, $tag) {
                    return call_user_func($callback, $atts, $inner, $tag);
                }, ['shortcode' => $tag, 'atts' => $atts]);

                if (!is_scalar($output) && $output !== null) {
                    throw new RuntimeException('Shortcode output must be scalar/null. Tag: ' . $tag);
                }

                $content = str_replace($full, (string)$output, $content);
            }
        }

        return $content;
    } finally {
        $depth--;
    }
}

function ok_simple_parse_atts($text) {
    $atts = [];
    $text = trim((string)$text);

    preg_match_all("/([a-zA-Z0-9_-]+)\\s*=\\s*(?:\"([^\"]*)\"|'([^']*)'|([^\\s\\]]+))/u", $text, $matches, PREG_SET_ORDER);
    foreach ($matches as $m) {
        $key = strtolower((string)$m[1]);
        $value = isset($m[2]) && $m[2] !== '' ? $m[2] : (isset($m[3]) && $m[3] !== '' ? $m[3] : ($m[4] ?? ''));
        $atts[$key] = trim((string)$value);
    }

    return $atts;
}

add_ok_filter('the_content', 'do_ok_shortcode', 11);
