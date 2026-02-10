<?php
declare(strict_types=1);

/**
 * OK ძრავის ნავიგაციის მენიუს ფუნქციები
 *
 * @package OK_Engine
 * @version 1.3 (Hierarchy Support)
 */

if (!defined('OK_LOADED')) {
    if (!headers_sent()) { http_response_code(403); }
    $tpl403 = dirname(__DIR__, 2) . '/errors/403.php';
    if (is_file($tpl403)) { require $tpl403; } else { echo '<h1>403 — Forbidden</h1>'; }
    exit;
}

/** * გლობალური ლოკაციები 
 * @var array
 */
$GLOBALS['ok_registered_nav_menus'] = [];

/**
 * Register nav menu locations
 * @param array $locations ['primary' => 'მთავარი მენიუ', ...]
 */
function register_nav_menus(array $locations = []): void {
    global $ok_registered_nav_menus;

    foreach ($locations as $k => $v) {
        if (!is_string($k) || !is_string($v)) continue;
        
        $key = strtolower(preg_replace('/[^a-z0-9_\-]/i', '', $k));
        if ($key === '') continue;
        
        $ok_registered_nav_menus[$key] = trim($v);
    }
}

/**
 * აბრუნებს მენიუს HTML-ს.
 *
 * @param array $args {
 * @type string $theme_location სავალდებულო. ლოკაცია.
 * @type string $menu_class    <ul> კლასი (Default: 'nav').
 * @type string $container      მშობელი ელემენტი (Default: 'nav', false ამორთავს).
 * @type string $container_class მშობელი ელემენტის კლასი.
 * @type int    $depth          სიღრმე (0 = ყველა, 1 = მხოლოდ მშობლები).
 * }
 */
function ok_nav_menu(array $args = []): void {
    global $ok_db;

    $defaults = [
        'theme_location'  => '',
        'menu_class'      => 'nav',
        'container'       => 'nav',
        'container_class' => '',
        'depth'           => 0, // 0 = Unlimited
    ];

    $args = array_merge($defaults, $args);
    $location = strtolower((string)$args['theme_location']);

    if ($location === '' || !$ok_db) return;

    // ვიღებთ მენიუს ელემენტებს (მშობლის ID-ით)
    // სტრუქტურა: id, label, url, icon_class, parent_id, menu_order
    $sql = "SELECT * FROM menu_items WHERE menu_location = ? ORDER BY menu_order ASC, id ASC";
    $items = $ok_db->get_results($sql, [$location]);

    if (!$items) return;

    // იერარქიის აწყობა
    $tree = ok_build_menu_tree($items);

    // Container Start
    if ($args['container']) {
        $cont_class = ok_menu_clean_classes($args['container_class']);
        echo '<' . htmlspecialchars($args['container']) . ' class="' . htmlspecialchars($cont_class) . '">';
    }

    $menu_class = ok_menu_clean_classes($args['menu_class']);
    echo '<ul class="' . htmlspecialchars($menu_class) . '">';

    // რეკურსიული რენდერი
    ok_render_menu_level($tree, 0, $args['depth']);

    echo '</ul>';

    // Container End
    if ($args['container']) {
        echo '</' . htmlspecialchars($args['container']) . '>';
    }
}

/**
 * შიდა ფუნქცია: ხის სტრუქტურის აწყობა (Flat Array -> Tree)
 */
function ok_build_menu_tree(array $elements, int $parentId = 0): array {
    $branch = [];

    foreach ($elements as $element) {
        // თუ ელემენტის parent_id ემთხვევა მიმდინარე მშობელს
        $p_id = isset($element->parent_id) ? (int)$element->parent_id : 0;
        
        if ($p_id == $parentId) {
            $children = ok_build_menu_tree($elements, (int)$element->id);
            if ($children) {
                $element->children = $children;
            }
            $branch[] = $element;
        }
    }

    return $branch;
}

/**
 * შიდა ფუნქცია: მენიუს რენდერი (რეკურსია)
 */
function ok_render_menu_level(array $items, int $level, int $max_depth): void {
    // სიღრმის შემოწმება
    if ($max_depth > 0 && $level >= $max_depth) return;

    $req_path = (string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $req_path = '/' . ltrim($req_path, '/');

    foreach ($items as $item) {
        $has_children = !empty($item->children);
        
        // URL
        $href = ok_menu_safe_url($item->url ?? '');
        $item_path = (string)parse_url($href, PHP_URL_PATH);
        $item_path = $item_path !== '' ? ('/' . ltrim($item_path, '/')) : '#';

        // Active State
        $is_active = ($item_path !== '#' && rtrim($req_path, '/') === rtrim($item_path, '/'));
        
        // Classes
        $li_classes = ['nav-item'];
        if ($is_active) $li_classes[] = 'active';
        if ($has_children) $li_classes[] = 'dropdown'; // Bootstrap სტილი

        // Icon
        $icon_html = '';
        if (!empty($item->icon_class)) {
            $ic = ok_menu_clean_classes($item->icon_class);
            if ($ic) $icon_html = '<i class="' . htmlspecialchars($ic) . ' me-2"></i>';
        }

        // Link Attributes
        $link_class = 'nav-link';
        $link_attr  = '';
        if ($has_children) {
            $link_class .= ' dropdown-toggle';
            $link_attr   = ' data-bs-toggle="dropdown" aria-expanded="false"';
        }

        // External Link Check
        $is_external = (bool)preg_match('#^https?://#i', $href) && (parse_url($href, PHP_URL_HOST) !== ($_SERVER['HTTP_HOST'] ?? ''));
        if ($is_external) {
            $link_attr .= ' target="_blank" rel="noopener"';
        }

        echo '<li class="' . implode(' ', $li_classes) . '">';
        echo '<a class="' . $link_class . '" href="' . htmlspecialchars($href) . '"' . $link_attr . '>';
        echo $icon_html . htmlspecialchars($item->label ?? 'Link');
        echo '</a>';

        // ქვემენიუ
        if ($has_children) {
            echo '<ul class="dropdown-menu">';
            ok_render_menu_level($item->children, $level + 1, $max_depth);
            echo '</ul>';
        }

        echo '</li>';
    }
}

/**
 * URL Normalizer (იგივე, რაც შენს კოდში, უბრალოდ გაწმენდილი)
 */
function ok_menu_safe_url($url): string {
    $u = trim((string)$url);
    if ($u === '') return '#';
    if (preg_match('#^(?:javascript|data|vbscript):#i', $u)) return '#';
    if (strpos($u, '/') === 0) return $u;
    if (preg_match('#^https?://#i', $u)) return filter_var($u, FILTER_VALIDATE_URL) ? $u : '#';
    
    // Relative path cleaning logic...
    // (შენი ძველი ლოგიკა აქ სრულიად მისაღებია)
    return $u; 
}

/**
 * Class Cleaner
 */
function ok_menu_clean_classes($classes): string {
    $c = preg_replace('/[^a-z0-9_\-\s]/i', '', (string)$classes);
    return trim(preg_replace('/\s+/', ' ', $c));
}