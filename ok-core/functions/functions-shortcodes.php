<?php
/**
 * FILE: ok-core/functions/functions-shortcodes.php
 * OK Engine - Simple Shortcode System
 */

global $ok_shortcodes;
$ok_shortcodes = [];

function add_ok_shortcode($tag, $callback) {
    global $ok_shortcodes;
    $ok_shortcodes[$tag] = $callback;
}

function do_ok_shortcode($content) {
    global $ok_shortcodes;

    if (empty($content)) return '';
    if (empty($ok_shortcodes)) return $content;

    // 1. გასუფთავება (აუცილებელია HTML კოდების მოსაცილებლად)
    $content = str_replace(['&#91;', '&#93;'], ['[', ']'], $content);
    $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
    // უხილავი სფეისების წაშლა
    $content = str_replace(["\xC2\xA0", "&nbsp;"], ' ', $content);

    // 2. მარტივი ძებნა: ეძებს [tag ...] სტრუქტურას
    foreach ($ok_shortcodes as $tag => $callback) {
        // ეს Regex ეძებს: [tag (ნებისმიერი სიმბოლო გარდა ]-ისა) ]
        if (preg_match_all('/\[' . $tag . '\b([^\]]*)\]/u', $content, $matches, PREG_SET_ORDER)) {
            
            foreach ($matches as $match) {
                $full_string = $match[0]; // მთლიანი: [ok_quiz id="55"]
                $atts_string = $match[1]; // შიგთავსი: id="55"
                
                // ატრიბუტების დაშლა
                $atts = ok_simple_parse_atts($atts_string);
                
                // ფუნქციის გამოძახება
                $output = call_user_func($callback, $atts, null, $tag);
                
                // ტექსტში ჩანაცვლება
                $content = str_replace($full_string, $output, $content);
            }
        }
    }

    return $content;
}

// ატრიბუტების მარტივი დამშლელი
function ok_simple_parse_atts($text) {
    $atts = [];
    // ეძებს: key="value" ან key='value'
    if (preg_match_all('/(\w+)\s*=\s*["\'](.*?)["\']/u', $text, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $atts[strtolower($m[1])] = $m[2];
        }
    }
    return $atts;
}

// ფილტრის მიბმა
if (function_exists('add_ok_filter')) {
    add_ok_filter('the_content', 'do_ok_shortcode', 11);
}