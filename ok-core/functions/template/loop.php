<?php
/**
 * OK Engine — Loop (Strict Mode + Reliable Pagination)
 */

if (!defined('OK_LOADED')) {
    if (!headers_sent()) { http_response_code(403); }
    exit('Access Denied.');
}

$GLOBALS['ok_query'] = $GLOBALS['ok_query'] ?? null;
$GLOBALS['post']     = $GLOBALS['post'] ?? null;

// --- დამხმარე ფუნქციები ---

if (!function_exists('ok__detect_posts_table')) {
    function ok__detect_posts_table(): string {
        global $ok_db;
        if (!$ok_db) return 'ok_posts';
        
        $prefix = '';
        if (isset($ok_db->prefix) && is_string($ok_db->prefix)) $prefix = $ok_db->prefix;
        
        if ($prefix !== '') {
            $cand = $prefix . 'posts';
            $cand_safe = preg_replace('/[^a-zA-Z0-9_]/', '', $cand);
            $rows = $ok_db->get_results("SHOW TABLES LIKE '$cand_safe'");
            if (is_array($rows) && count($rows) > 0) return $cand;
        }

        $rows = $ok_db->get_results("SHOW TABLES LIKE 'ok_posts'");
        if (is_array($rows) && count($rows) > 0) return 'ok_posts';
        
        return 'ok_posts';
    }
}

// --- მონაცემების წამოღება (PAGINATION FIX: COUNT(*)) ---

if (!function_exists('ok_get_posts')) {
    function ok_get_posts(array $args = []): array {
        global $ok_db, $ok_query;

        // 1. ლიმიტის განსაზღვრა (პრიორიტეტი: არგუმენტი -> ოფცია -> დეფოლტი)
        $per_page = 10;
        
        if (isset($args['posts_per_page'])) {
            $per_page = (int)$args['posts_per_page'];
        } elseif (function_exists('get_ok_option')) {
            $per_page = (int)get_ok_option('posts_per_page', 10);
        } elseif ($ok_db) {
            $opt_row = $ok_db->get_row("SELECT option_value FROM ok_options WHERE option_name = 'posts_per_page' LIMIT 1");
            if ($opt_row && isset($opt_row->option_value)) {
                $per_page = (int)$opt_row->option_value;
            }
        }
        
        if ($per_page < 1) $per_page = 10;

        // 2. მიმდინარე გვერდი (?page= ან ?paged=)
        $paged = 1;
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $paged = (int)$_GET['page'];
        } elseif (isset($_GET['paged']) && is_numeric($_GET['paged'])) {
            $paged = (int)$_GET['paged'];
        }
        $paged = max(1, $paged);
        
        $offset = ($paged - 1) * $per_page;

        // 3. ცხრილის დადგენა
        $table = ok__detect_posts_table();
        
        // 4. 🔥 სრული რაოდენობის დათვლა (ცალკე მოთხოვნით - ყველაზე საიმედო გზა)
        $total_posts = 0;
        if ($ok_db) {
            $count_sql = "SELECT COUNT(*) as total FROM {$table} WHERE post_status = 'published' AND post_type = 'post'";
            $count_res = $ok_db->get_row($count_sql);
            $total_posts = (int)($count_res->total ?? 0);
        }

        // 5. პოსტების წამოღება (LIMIT-ით)
        $sql = "
            SELECT * FROM {$table} 
            WHERE post_status = 'published' 
              AND post_type = 'post' 
            ORDER BY post_date DESC 
            LIMIT {$offset}, {$per_page}
        ";

        $posts = [];
        if ($ok_db) {
            $posts = $ok_db->get_results($sql);
            if (!is_array($posts)) $posts = [];
        }

        // 6. გლობალური Query ობიექტის შევსება
        $ok_query['posts'] = $posts;
        $ok_query['post_count'] = count($posts); // მიმდინარე გვერდზე ნაპოვნი
        $ok_query['found_posts'] = $total_posts; // სულ ბაზაში
        
        // გვერდების რაოდენობის გამოთვლა: Total / PerPage
        $ok_query['max_num_pages'] = ($total_posts > 0) ? ceil($total_posts / $per_page) : 1;
        
        $ok_query['current_page'] = $paged;
        $ok_query['current_post_index'] = -1;
        $ok_query['posts_per_page'] = $per_page;

        return $posts;
    }
}

// --- MAIN QUERY SETUP (NO FALLBACK) ---

if (!function_exists('ok_setup_main_query')) {
    function ok_setup_main_query(): void {
        global $ok_query;

        // თუ უკვე ჩატვირთულია, არაფერს ვეხებით
        if (is_array($ok_query) && isset($ok_query['posts']) && is_array($ok_query['posts'])) {
            return;
        }

        // 1. სიაა? (Blog, Archive, Home, Search)
        $is_list_view = false;
        if (!empty($ok_query['is_home']) || !empty($ok_query['is_blog_page']) || !empty($ok_query['is_archive']) || !empty($ok_query['is_search'])) {
            $is_list_view = true;
        }

        if ($is_list_view) {
            // აქ შეგვიძლია გადავცეთ ლიმიტი, თუ გვინდა რომ hardcode იყოს (მაგ: 3)
            // ok_get_posts(['posts_per_page' => 3]); 
            ok_get_posts(); 
            return;
        }

        // 2. ობიექტია? (Single Post/Page)
        if (isset($ok_query['object']) && is_object($ok_query['object'])) {
            $obj = $ok_query['object'];
            
            if (isset($obj->id)) {
                $ok_query['posts'] = [$obj];
                $ok_query['post_count'] = 1;
                $ok_query['found_posts'] = 1;
                $ok_query['max_num_pages'] = 1;
                $ok_query['current_post_index'] = -1;
                $ok_query['is_single'] = true;
                return;
            }
        }

        // 3. Fallback წაშლილია (სხვა შემთხვევაში არაფერს ტვირთავს)
    }
}

// --- STANDARD LOOP ITERATORS ---

if (!function_exists('ok__ensure_main_query')) {
    function ok__ensure_main_query(): void {
        static $done = false;
        if ($done) return;
        ok_setup_main_query();
        $done = true;
    }
}

if (!function_exists('have_posts')) {
    function have_posts(): bool {
        ok__ensure_main_query();
        global $ok_query;
        if (!is_array($ok_query) || !isset($ok_query['posts']) || !is_array($ok_query['posts'])) return false;
        $idx = (int)($ok_query['current_post_index'] ?? -1);
        return array_key_exists($idx + 1, $ok_query['posts']);
    }
}

if (!function_exists('the_post')) {
    function the_post(): void {
        ok__ensure_main_query();
        global $ok_query, $post;
        if (!is_array($ok_query) || !isset($ok_query['posts']) || !is_array($ok_query['posts'])) return;
        
        $ok_query['current_post_index'] = (int)($ok_query['current_post_index'] ?? -1) + 1;
        $current = $ok_query['posts'][$ok_query['current_post_index']] ?? null;
        
        if (is_array($current)) $current = (object)$current;
        $post = $current;
    }
}

if (!function_exists('rewind_posts')) {
    function rewind_posts(): void {
        global $ok_query;
        if (is_array($ok_query)) $ok_query['current_post_index'] = -1;
    }
}
?>