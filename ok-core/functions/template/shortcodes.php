<?php
declare(strict_types=1);

/**
 * OK Shortcodes — შორტკოდების სისტემა
 *
 * @package OK_Engine
 * @version 1.2 (Singleton & Optimized Regex)
 */

if (!defined('OK_LOADED')) {
    if (!headers_sent()) { http_response_code(403); }
    exit('Access Denied.');
}

/**
 * შორტკოდების მენეჯერი (Singleton)
 */
final class OK_Shortcode_Manager {
    private static $instance = null;
    private $shortcodes = [];

    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function add(string $tag, callable $callback): void {
        $tag = trim($tag);
        if ($tag && preg_match('/^[a-z0-9_\-]+$/i', $tag)) {
            $this->shortcodes[$tag] = $callback;
        }
    }

    public function remove(string $tag): void {
        unset($this->shortcodes[$tag]);
    }

    public function exists(string $tag): bool {
        return isset($this->shortcodes[$tag]);
    }

    public function get_all(): array {
        return $this->shortcodes;
    }

    /**
     * მთავარი პარსერი
     */
    public function do_shortcode(string $content): string {
        if (empty($this->shortcodes) || strpos($content, '[') === false) {
            return $content;
        }

        // Regex pattern-ის აწყობა
        $tagnames = array_keys($this->shortcodes);
        $tagregexp = join('|', array_map('preg_quote', $tagnames));

        // WordPress-ის სტილის Regex (ყველაზე ოპტიმიზებული)
        $pattern =
              '\\['                              // Opening bracket
            . '(\\[?)'                           // 1: Optional second opening bracket for escaping: [[tag]]
            . "($tagregexp)"                     // 2: Shortcode name
            . '(?![\\w-])'                       // Not followed by word character or hyphen
            . '('                                // 3: Unroll the loop: Inside the opening shortcode tag
            .     '[^\\]\\/]*'                   // Not a closing bracket or forward slash
            .     '(?:'
            .         '\\/(?!\\])'               // A forward slash not followed by a closing bracket
            .         '[^\\]\\/]*'               // Not a closing bracket or forward slash
            .     ')*?'
            . ')'
            . '(?:'
            .     '(\\/)'                        // 4: Self closing tag ...
            .     '\\]'                          // ... and closing bracket
            . '|'
            .     '\\]'                          // Closing bracket
            .     '(?:'
            .         '('                        // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags
            .             '[^\\[]*+'             // Not an opening bracket
            .             '(?:'
            .                 '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
            .                 '[^\\[]*+'         // Not an opening bracket
            .             ')*+'
            .         ')'
            .         '\\[\\/\\2\\]'             // Closing shortcode tag
            .     ')?'
            . ')'
            . '(\\]?)';                          // 6: Optional second closing brocket for escaping: [[tag]]

        return preg_replace_callback("/$pattern/", [$this, 'do_shortcode_tag'], $content);
    }

    /**
     * Callback თითოეული შორტკოდისთვის
     */
    public function do_shortcode_tag(array $m): string {
        // Escaping: [[tag]] -> [tag]
        if ($m[1] === '[' && $m[6] === ']') {
            return substr($m[0], 1, -1);
        }

        $tag   = $m[2];
        $attr  = shortcode_parse_atts($m[3]);
        $content = isset($m[5]) ? $m[5] : null;

        if (isset($this->shortcodes[$tag])) {
            // რეკურსია: შიგთავსშიც ვეძებთ შორტკოდებს
            if ($content !== null) {
                $content = $this->do_shortcode($content);
            }
            
            return call_user_func($this->shortcodes[$tag], $attr, $content, $tag);
        }

        return $m[0];
    }
}

/* --------------------------------------------------------------------------
 * Public API (WordPress Compatible)
 * -------------------------------------------------------------------------- */

function add_shortcode($tag, $callback) {
    OK_Shortcode_Manager::instance()->add($tag, $callback);
}

function remove_shortcode($tag) {
    OK_Shortcode_Manager::instance()->remove($tag);
}

function shortcode_exists($tag) {
    return OK_Shortcode_Manager::instance()->exists($tag);
}

function do_shortcode($content) {
    return OK_Shortcode_Manager::instance()->do_shortcode((string)$content);
}

/**
 * ატრიბუტების პარსერი (იგივე, რაც შენთან, უბრალოდ გასუფთავებული)
 */
function shortcode_parse_atts($text) {
    $atts = [];
    $pattern = '/(\w+)\s*=\s*"([^"]*)"|(\w+)\s*=\s*\'([^\']*)\'|(\w+)\s*=\s*([^\s\'"]+)|(\w+)/';
    $text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);
    
    if (preg_match_all($pattern, $text, $match, PREG_SET_ORDER)) {
        foreach ($match as $m) {
            if (!empty($m[1]))
                $atts[strtolower($m[1])] = stripcslashes($m[2]);
            elseif (!empty($m[3]))
                $atts[strtolower($m[3])] = stripcslashes($m[4]);
            elseif (!empty($m[5]))
                $atts[strtolower($m[5])] = stripcslashes($m[6]);
            elseif (isset($m[7]) && strlen($m[7]))
                $atts[strtolower($m[7])] = $m[7];
        }
    }
    return $atts;
}

/**
 * ატრიბუტების გაერთიანება Default-ებთან
 */
function shortcode_atts($pairs, $atts, $shortcode = '') {
    $atts = (array)$atts;
    $out = array();
    foreach ($pairs as $name => $default) {
        if (array_key_exists($name, $atts))
            $out[$name] = $atts[$name];
        else
            $out[$name] = $default;
    }
    
    if ($shortcode && function_exists('apply_filters')) {
        return apply_filters("shortcode_atts_$shortcode", $out, $pairs, $atts);
    }

    return $out;
}