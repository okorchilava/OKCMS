<?php
declare(strict_types=1);

/**
 * OK ძრავის ტაქსონომიის დამხმარე ფუნქციები (Categories, Tags, Archive Titles)
 *
 * @package OK_Engine
 * @version 1.3 (Cached & Extended)
 */

if (!defined('OK_LOADED')) {
    if (!headers_sent()) { http_response_code(403); }
    $tpl403 = dirname(__DIR__, 2) . '/errors/403.php';
    if (is_file($tpl403)) { require $tpl403; } else { echo '<h1>403 — Forbidden</h1>'; }
    exit;
}

/**
 * გლობალური ქეში ტერმინებისთვის, რომ ბაზა არ გადაიტვირთოს ციკლში.
 * @var array [post_id => [taxonomy => array_of_terms]]
 */
$GLOBALS['ok_object_terms_cache'] = [];

/* --------------------------------------------------------------------------
 * Data Retrieval (Getters)
 * -------------------------------------------------------------------------- */

/**
 * იღებს პოსტის ტერმინებს (კატეგორია ან თეგი) ბაზიდან ან ქეშიდან.
 *
 * @param int    $post_id
 * @param string $taxonomy 'category' ან 'tag'
 * @return array ობიექტების მასივი
 */
function get_the_terms(int $post_id, string $taxonomy): array {
    global $ok_db, $ok_object_terms_cache;

    // 1. შემოწმება ქეშში
    if (isset($ok_object_terms_cache[$post_id][$taxonomy])) {
        return $ok_object_terms_cache[$post_id][$taxonomy];
    }

    if (!$ok_db) return [];

    // 2. მოთხოვნა ბაზაში
    $sql = "SELECT t.term_id, t.name, t.slug, tt.term_taxonomy_id, tt.description, tt.parent
            FROM terms AS t
            INNER JOIN term_taxonomy AS tt ON t.term_id = tt.term_id
            INNER JOIN term_relationships AS tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
            WHERE tt.taxonomy = ? AND tr.object_id = ?
            ORDER BY t.name ASC";

    $terms = $ok_db->get_results($sql, [$taxonomy, $post_id]);
    
    // 3. შენახვა ქეშში
    $result = is_array($terms) ? $terms : [];
    $ok_object_terms_cache[$post_id][$taxonomy] = $result;

    return $result;
}

/**
 * აბრუნებს კატეგორიების HTML სიას.
 *
 * @param string $separator გამყოფი (მაგ: ', ').
 * @param string $parents   როგორ გამოვაჩინოთ მშობელი კატეგორიები (ამ ეტაპზე დარეზერვებულია).
 * @param int    $post_id   (Optional)
 * @return string HTML
 */
function get_the_category_list(string $separator = ', ', string $parents = '', int $post_id = 0): string {
    if (!$post_id) {
        $post_id = get_the_ID(); // functions-loop.php-დან
    }

    $categories = get_the_terms($post_id, 'category');
    if (empty($categories)) {
        return '';
    }

    $links = [];
    foreach ($categories as $cat) {
        $link = '/category/' . trim($cat->slug) . '/'; // სამომავლოდ აქ get_term_link() ფუნქცია უნდა იყოს
        if (function_exists('ok_abs_url')) {
            $link = ok_abs_url($link);
        }

        $links[] = '<a href="' . htmlspecialchars($link) . '" rel="category tag">' . htmlspecialchars($cat->name) . '</a>';
    }

    return implode($separator, $links);
}

/**
 * აბრუნებს თეგების HTML სიას.
 *
 * @param string $before  სიის წინ.
 * @param string $sep     გამყოფი.
 * @param string $after   სიის შემდეგ.
 * @param int    $post_id (Optional)
 * @return string HTML
 */
function get_the_tag_list(string $before = '', string $sep = ', ', string $after = '', int $post_id = 0): string {
    if (!$post_id) {
        $post_id = get_the_ID();
    }

    $tags = get_the_terms($post_id, 'tag');
    if (empty($tags)) {
        return '';
    }

    $links = [];
    foreach ($tags as $tag) {
        $link = '/tag/' . trim($tag->slug) . '/';
        if (function_exists('ok_abs_url')) {
            $link = ok_abs_url($link);
        }

        $links[] = '<a href="' . htmlspecialchars($link) . '" rel="tag">' . htmlspecialchars($tag->name) . '</a>';
    }

    return $before . implode($sep, $links) . $after;
}

/* --------------------------------------------------------------------------
 * Echoers (Template Tags)
 * -------------------------------------------------------------------------- */

/**
 * ბეჭდავს კატეგორიებს.
 */
function the_categories(string $separator = ', '): void {
    $html = get_the_category_list($separator);
    
    if (function_exists('apply_filters')) {
        $html = apply_filters('the_categories', $html, $separator);
    }
    
    if ($html) {
        echo '<span class="cat-links">' . $html . '</span>';
    }
}

/**
 * ბეჭდავს თეგებს.
 */
function the_tags(string $before = 'Tags: ', string $sep = ', ', string $after = ''): void {
    $html = get_the_tag_list($before, $sep, $after);

    if (function_exists('apply_filters')) {
        $html = apply_filters('the_tags', $html, $before, $sep, $after);
    }

    if ($html) {
        echo '<span class="tags-links">' . $html . '</span>';
    }
}

/* --------------------------------------------------------------------------
 * Archive Titles
 * -------------------------------------------------------------------------- */

/**
 * აბრუნებს არქივის სათაურს (Escaped).
 */
function get_archive_title(): string {
    global $ok_query;
    $title = 'არქივი';

    if (isset($ok_query['is_category_archive']) && $ok_query['is_category_archive']) {
        $name = $ok_query['archive_term_name'] ?? '';
        $title = 'კატეგორია: ' . $name;
    
    } elseif (isset($ok_query['is_tag_archive']) && $ok_query['is_tag_archive']) {
        $name = $ok_query['archive_term_name'] ?? '';
        $title = 'თეგი: ' . $name;
    
    } elseif (isset($ok_query['is_search']) && $ok_query['is_search']) {
        $search_query = $_GET['s'] ?? '';
        $title = 'ძიება: ' . $search_query;
    
    } elseif (isset($ok_query['is_404']) && $ok_query['is_404']) {
        $title = 'გვერდი ვერ მოიძებნა';
    }

    // ფილტრი, რომ თემამ შეძლოს სათაურის შეცვლა
    if (function_exists('apply_filters')) {
        $title = apply_filters('get_archive_title', $title);
    }

    return htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
}

/**
 * ბეჭდავს არქივის სათაურს.
 */
function the_archive_title(string $before = '<h1>', string $after = '</h1>'): void {
    $title = get_archive_title();
    if ($title) {
        echo $before . $title . $after;
    }
}