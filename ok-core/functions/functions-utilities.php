<?php
declare(strict_types=1);

/**
 * OK ძრავის ზოგადი დანიშნულების დამხმარე ფუნქციები (Utilities)
 *
 * @package OK_Engine
 * @version 2.7.0 (Added ok_pagination)
 */

if (!defined('OK_LOADED')) {
    if (!headers_sent()) { http_response_code(403); }
    exit('Access Denied.');
}

/* --------------------------------------------------------------------------
 * 1. HTTP & Responses
 * -------------------------------------------------------------------------- */

if (!function_exists('ok_abort_403')) {
    function ok_abort_403(
        string $message = 'ამ გვერდის სანახავად არ გაგაჩნიათ საჭირო უფლებები.',
        string $title = 'წვდომა შეზღუდულია',
        string $home_url = '/'
    ): void {
        if (!headers_sent()) { http_response_code(403); }

        $tpl = dirname(__DIR__) . '/errors/403.php';
        if (is_file($tpl)) {
            $error_title   = $title;
            $error_message = $message;
            $error_home    = $home_url;
            require $tpl;
        } else {
            $t = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
            $m = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
            echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>403 Forbidden</title></head><body><h1>403 — {$t}</h1><p>{$m}</p></body></html>";
        }
        exit;
    }
}

if (!function_exists('ok_redirect')) {
    function ok_redirect(string $url): void
    {
        $url = trim($url);
        if ($url === '') { $url = '/'; }

        if (!headers_sent()) {
            header('Location: ' . $url, true, 302);
            exit;
        }

        $safe = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        echo "<script>window.location.href='{$safe}';</script>";
        echo "<meta http-equiv='refresh' content='0;url={$safe}'>";
        exit;
    }
}

if (!function_exists('ok_send_json_success')) {
    function ok_send_json_success($data = null): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('ok_send_json_error')) {
    function ok_send_json_error($data = null): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['success' => false, 'data' => $data], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/* --------------------------------------------------------------------------
 * 1.1 Debug logging & sandbox safety
 * -------------------------------------------------------------------------- */

if (!function_exists('ok_get_debug_log_path')) {
    function ok_get_debug_log_path(): string
    {
        return dirname(dirname(__DIR__)) . '/ok-content/debug.log';
    }
}

if (!function_exists('ok_log_debug')) {
    function ok_log_debug(string $message, array $context = [], string $level = 'INFO'): void
    {
        $safeLevel = strtoupper(preg_replace('/[^A-Z]/', '', $level));
        if ($safeLevel === '') {
            $safeLevel = 'INFO';
        }

        $safeMessage = str_replace(["\r", "\n"], ['\\r', '\\n'], $message);
        $line = '[' . date('Y-m-d H:i:s') . "] [{$safeLevel}] " . $safeMessage;
        $context['request_id'] = defined('OK_REQUEST_ID') ? OK_REQUEST_ID : 'n/a';

        if (!empty($context)) {
            $json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json !== false) {
                $line .= ' | ' . $json;
            }
        }
        $line .= PHP_EOL;

        $path = ok_get_debug_log_path();
        file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
        if (is_file($path)) {
            @chmod($path, 0600);
        }
    }
}

if (!function_exists('ok_is_sandbox_mode')) {
    function ok_is_sandbox_mode(): bool
    {
        return OK_SANDBOX_MODE === true;
    }
}

if (!function_exists('ok_run_sandboxed')) {
    function ok_run_sandboxed(callable $callback, array $context = [])
    {
        ok_log_debug('Sandbox execution started.', $context);
        return $callback();
    }
}

/* --------------------------------------------------------------------------
 * 2. Date & Time (Settings-Driven & Multilingual)
 * -------------------------------------------------------------------------- */

if (!function_exists('ok_date')) {
    /**
     * ok_date($format = null, $timestamp = null): string
     *
     * @param string|null $format    PHP date() format; if null uses option date_format
     * @param mixed       $timestamp int|numeric-string|datetime-string|null
     */
    function ok_date($format = null, $timestamp = null): string
    {
        // 1) Timestamp normalize (NEVER allow false to reach date())
        if ($timestamp === null) {
            $ts = time();
        } elseif (is_int($timestamp)) {
            $ts = $timestamp;
        } elseif (is_numeric($timestamp)) {
            // allow numeric-string epoch (e.g. "1700000000")
            $ts = (int)$timestamp;
            if ($ts <= 0) {
                $ts = time();
            }
        } else {
            $parsed = strtotime((string)$timestamp);
            $ts = ($parsed !== false) ? $parsed : time();
        }

        // 2) Language & format
        $lang = function_exists('get_ok_option') ? (string)get_ok_option('site_language', 'ka') : 'ka';

        if ($format === null || $format === '') {
            $format = function_exists('get_ok_option') ? (string)get_ok_option('date_format', 'j F, Y') : 'j F, Y';
        }
        $format = (string)$format;

        // 3) Output
        $output = date($format, $ts);

        // 4) Localize (KA only)
        if ($lang === 'ka') {
            $ka_map = [
                'January' => 'იანვარი', 'February' => 'თებერვალი', 'March' => 'მარტი',
                'April' => 'აპრილი', 'May' => 'მაისი', 'June' => 'ივნისი',
                'July' => 'ივლისი', 'August' => 'აგვისტო', 'September' => 'სექტემბერი',
                'October' => 'ოქტომბერი', 'November' => 'ნოემბერი', 'December' => 'დეკემბერი',

                'Jan' => 'იან', 'Feb' => 'თებ', 'Mar' => 'მარ',
                'Apr' => 'აპრ', 'May' => 'მაის', 'Jun' => 'ივნ', 'Jul' => 'ივლ',
                'Aug' => 'აგვ', 'Sep' => 'სექ', 'Oct' => 'ოქტ',
                'Nov' => 'ნოე', 'Dec' => 'დეკ',

                'Monday' => 'ორშაბათი', 'Tuesday' => 'სამშაბათი', 'Wednesday' => 'ოთხშაბათი',
                'Thursday' => 'ხუთშაბათი', 'Friday' => 'პარასკევი', 'Saturday' => 'შაბათი', 'Sunday' => 'კვირა',
            ];
            $output = strtr($output, $ka_map);
        }

        return $output;
    }
}

/* --------------------------------------------------------------------------
 * 2.1 Category helper (Loop-based) — ok_category()
 * -------------------------------------------------------------------------- */

if (!function_exists('ok_category')) {
    /**
     * ok_category(): ?object
     * - მუშაობს ლუპის კონტექსტში (global $post)
     * - აბრუნებს ობიექტს: {id, name, slug, url} ან null
     */
    function ok_category()
    {
        global $post, $ok_db;

        if (!$post || !is_object($post)) {
            return null;
        }

        $cat_id = 0;
        if (isset($post->category_id) && is_numeric($post->category_id)) {
            $cat_id = (int)$post->category_id;
        }

        if ($cat_id < 1 || !isset($ok_db) || !is_object($ok_db)) {
            return null;
        }

        // უსაფრთხოდ: რადგან prepare wrapper ყველა პროექტში ერთნაირად არაა, აქ int-cast საკმარისია
        $cat = $ok_db->get_row("SELECT id, name, slug FROM ok_categories WHERE id = " . $cat_id);

        if (!$cat || !is_object($cat)) {
            return null;
        }

        $slug = (string)($cat->slug ?? '');
        $cat->url = '/' . ltrim($slug, '/');

        return $cat;
    }
}

/* --------------------------------------------------------------------------
 * 2.2 Pagination Helper (New)
 * -------------------------------------------------------------------------- */

if (!function_exists('ok_pagination')) {
    function ok_pagination() {
        global $ok_query;
        
        // 1. შემოწმება: არსებობს თუ არა მონაცემები
        if (!isset($ok_query['max_num_pages']) || !isset($ok_query['current_page'])) { return; }

        $total_pages = (int)$ok_query['max_num_pages'];
        $current     = (int)$ok_query['current_page'];

        // თუ მხოლოდ 1 გვერდია, პაგინაცია არ გვჭირდება
        if ($total_pages <= 1) { return; }

        // 2. ლინკის აწყობა (რომ ?page=X მივაბათ არსებულ URL-ს)
        $base_url = strtok($_SERVER["REQUEST_URI"], '?');
        $query_params = $_GET;
        unset($query_params['page']); // ძველ page-ს ვშლით
        
        $get_page_link = function($num) use ($base_url, $query_params) {
            $params = $query_params;
            $params['page'] = $num;
            return $base_url . '?' . http_build_query($params);
        };

        // 3. HTML გამოტანა (Bootstrap სტილი)
        echo '<nav aria-label="Page navigation" class="d-flex justify-content-center mt-4">';
        echo '<ul class="pagination">';

        // « უკან
        if ($current > 1) {
            echo '<li class="page-item"><a class="page-link" href="' . $get_page_link($current - 1) . '">«</a></li>';
        } else {
            echo '<li class="page-item disabled"><span class="page-link">«</span></li>';
        }

        // გვერდების ნომრები
        for ($i = 1; $i <= $total_pages; $i++) {
            $active = ($current === $i) ? 'active' : '';
            if ($current === $i) {
                 echo '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
            } else {
                 echo '<li class="page-item"><a class="page-link" href="' . $get_page_link($i) . '">' . $i . '</a></li>';
            }
        }

        // წინ »
        if ($current < $total_pages) {
            echo '<li class="page-item"><a class="page-link" href="' . $get_page_link($current + 1) . '">»</a></li>';
        } else {
            echo '<li class="page-item disabled"><span class="page-link">»</span></li>';
        }

        echo '</ul>';
        echo '</nav>';
    }
}


/* --------------------------------------------------------------------------
 * 3. String & Formatting
 * -------------------------------------------------------------------------- */

if (!function_exists('generate_slug')) {
    function generate_slug(string $text): string
    {
        $geo_to_latin = [
            'ა' => 'a','ბ' => 'b','გ' => 'g','დ' => 'd','ე' => 'e','ვ' => 'v','ზ' => 'z','თ' => 't','ი' => 'i',
            'კ' => 'k','ლ' => 'l','მ' => 'm','ნ' => 'n','ო' => 'o','პ' => 'p','ჟ' => 'zh','რ' => 'r','ს' => 's',
            'ტ' => 't','უ' => 'u','ფ' => 'f','ქ' => 'q','ღ' => 'gh','ყ' => 'y','შ' => 'sh','ჩ' => 'ch','ც' => 'ts',
            'ძ' => 'dz','წ' => 'w','ჭ' => 'tch','ხ' => 'kh','ჯ' => 'j','ჰ' => 'h',
            ' ' => '-', '_' => '-',
        ];

        $text = strtr($text, $geo_to_latin);
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9-]/', '', $text) ?? '';
        $text = preg_replace('/-+/', '-', $text) ?? '';
        return trim($text, '-');
    }
}

/* --------------------------------------------------------------------------
 * 4. File & Attachments
 * -------------------------------------------------------------------------- */

if (!function_exists('get_attachment_thumb_url')) {
    function get_attachment_thumb_url(int $attachment_id, string $size = '150x150')
    {
        if (!function_exists('get_post_meta')) return false;

        $file_path = get_post_meta($attachment_id, '_file_path', true);
        if (!$file_path || !is_string($file_path)) return false;

        $path_parts = pathinfo($file_path);
        $ext = $path_parts['extension'] ?? '';
        if ($ext === '') return '/ok-content/' . ltrim($file_path, '/');

        $thumb_path = ($path_parts['dirname'] ?? '') . '/' . ($path_parts['filename'] ?? '') . '-' . $size . '.' . $ext;
        $thumb_path = ltrim($thumb_path, '/');

        $abs_path = dirname(__DIR__, 2) . '/ok-content/' . $thumb_path;

        return is_file($abs_path) ? '/ok-content/' . $thumb_path : '/ok-content/' . ltrim($file_path, '/');
    }
}

/* --------------------------------------------------------------------------
 * 5. Form Helpers
 * -------------------------------------------------------------------------- */

if (!function_exists('__ok_form_helper')) {
    function __ok_form_helper(string $val1, string $val2, string $type, bool $echo): string
    {
        if ($val1 === $val2) {
            $result = " {$type}='{$type}'";
            if ($echo) echo $result;
            return $result;
        }
        return '';
    }
}

if (!function_exists('selected')) {
    function selected($selected, $current = true, bool $echo = true): string
    {
        return __ok_form_helper((string)$selected, (string)$current, 'selected', $echo);
    }
}

if (!function_exists('checked')) {
    function checked($checked, $current = true, bool $echo = true): string
    {
        return __ok_form_helper((string)$checked, (string)$current, 'checked', $echo);
    }
}

if (!function_exists('disabled')) {
    function disabled($disabled, $current = true, bool $echo = true): string
    {
        return __ok_form_helper((string)$disabled, (string)$current, 'disabled', $echo);
    }
}

/* --------------------------------------------------------------------------
 * 6. Conditional Tags
 * -------------------------------------------------------------------------- */

if (!function_exists('is_home')) {
    function is_home(): bool { global $ok_query; return (bool)($ok_query['is_home'] ?? false); }
}
if (!function_exists('is_404')) {
    function is_404(): bool { global $ok_query; return (bool)($ok_query['is_404'] ?? false); }
}
if (!function_exists('is_single')) {
    function is_single(): bool { global $ok_query; return (bool)($ok_query['is_single'] ?? false); }
}
if (!function_exists('is_page')) {
    function is_page(): bool { global $ok_query; return (bool)($ok_query['is_page'] ?? false); }
}
if (!function_exists('is_category')) {
    function is_category(): bool { global $ok_query; return (bool)($ok_query['is_category_archive'] ?? false); }
}
if (!function_exists('is_tag')) {
    function is_tag(): bool { global $ok_query; return (bool)($ok_query['is_tag_archive'] ?? false); }
}
if (!function_exists('is_archive')) {
    function is_archive(): bool { return is_category() || is_tag(); }
}

/* --------------------------------------------------------------------------
 * 7. Auto Output Injection (ავტომატური ჩასმა <head>-ში)
 * -------------------------------------------------------------------------- */

if (!function_exists('ok_start_engine_buffering')) {
    function ok_start_engine_buffering(): void
    {
        ob_start();

        register_shutdown_function(function () {
            $buffer = ob_get_contents();
            @ob_end_clean();

            if (!$buffer) return;

            // head injection
            ob_start();
            if (function_exists('ok_do_action')) {
                ok_do_action('ok_head');
            }
            $head_content = ob_get_clean();

            if (stripos($buffer, '</head>') !== false) {
                $buffer = str_ireplace('</head>', $head_content . "\n</head>", $buffer);
            } elseif (stripos($buffer, '<body') !== false) {
                $buffer = preg_replace('/<body\b/i', $head_content . "\n<body", $buffer, 1) ?: $buffer;
            }

            echo $buffer;
        });
    }
}

/* --------------------------------------------------------------------------
 * 8. Developer Mode Logic & Modern Visual Bar
 * -------------------------------------------------------------------------- */

if (!function_exists('ok_handle_dev_mode')) {
    function ok_handle_dev_mode(): void
    {
        if (!function_exists('get_ok_option') || !function_exists('ok_add_action')) return;

        $is_dev = (int)get_ok_option('dev_mode', 0);

        if ($is_dev === 1) {
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            error_reporting(E_ALL);

            ok_add_action('ok_head', function () {
                ?>
                <style>
                    .ok-dev-badge {
                        position: fixed;
                        bottom: 24px;
                        left: 50%;
                        transform: translateX(-50%);
                        z-index: 99999999;

                        display: flex;
                        align-items: center;
                        gap: 10px;

                        background: rgba(20, 20, 20, 0.85);
                        backdrop-filter: blur(8px);
                        -webkit-backdrop-filter: blur(8px);

                        padding: 8px 20px;
                        border-radius: 50px;
                        border: 1px solid rgba(255, 255, 255, 0.1);
                        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);

                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                        font-size: 13px;
                        font-weight: 600;
                        color: #e0e0e0;
                        letter-spacing: 0.5px;
                        pointer-events: none;
                        user-select: none;
                    }

                    .ok-dev-badge .status-dot {
                        width: 8px;
                        height: 8px;
                        background-color: #00e676;
                        border-radius: 50%;
                        box-shadow: 0 0 8px #00e676;
                        animation: ok-pulse 2s infinite;
                    }

                    .ok-dev-badge .label { color: #fff; }

                    .ok-dev-badge .version {
                        opacity: 0.6;
                        font-weight: 400;
                        font-size: 11px;
                        border-left: 1px solid rgba(255,255,255,0.2);
                        padding-left: 8px;
                        margin-left: 2px;
                    }

                    @keyframes ok-pulse {
                        0%    { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 230, 118, 0.7); }
                        70%   { transform: scale(1);    box-shadow: 0 0 0 6px rgba(0, 230, 118, 0); }
                        100%  { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 230, 118, 0); }
                    }
                </style>

                <div class="ok-dev-badge">
                    <div class="status-dot"></div>
                    <span class="label">დეველოპერის რეჟიმი</span>
                    <span class="version">DEBUG ON</span>
                </div>
                <?php
            });
        } else {
            ini_set('display_errors', '0');
            ini_set('display_startup_errors', '0');
            error_reporting(0);
        }
    }
}
