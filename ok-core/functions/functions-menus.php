<?php
declare(strict_types=1);

/**
 * OK Engine - Menu API
 * განახლებულია: მხარს უჭერს ვიჯეტებს და ვერტიკალურ მენიუს.
 */

if (!defined('OK_LOADED')) die('Access Denied.');

// 1. მენიუს ზონების რეგისტრაცია
global $ok_registered_nav_menus;
$ok_registered_nav_menus = [
    'header-menu'  => 'მთავარი მენიუ (Header)',
    'footer-menu'  => 'ფუთერის მენიუ (Footer)',
    'sidebar-menu' => 'საიდბარის მენიუ' // 🛑 დაემატა ახალი ზონა
];

// 2. მენიუს გამოტანა
function ok_nav_menu($location_id = 'header-menu', $args = []) {
    global $ok_db;

    // მონაცემების წამოღება ბაზიდან
    $all_menus_json = get_ok_option('ok_nav_menus', '');
    $all_menus = !empty($all_menus_json) ? json_decode($all_menus_json, true) : [];

    // თუ ამ ზონაში არაფერია შენახული, არაფერს ვაკეთებთ
    if (empty($all_menus[$location_id])) return;

    $items = $all_menus[$location_id];
    
    // სორტირება
    uasort($items, function($a, $b) { return (int)($a['order']??0) <=> (int)($b['order']??0); });

    // 🛑 ლოგიკა: არის თუ არა ვერტიკალური მენიუ?
    // თუ ზონაა 'sidebar-menu' ან არგუმენტებში მოვიდა 'vertical' => true
    $is_vertical = ($location_id === 'sidebar-menu' || !empty($args['vertical']));

    // კონტეინერის კლასების შერჩევა
    if ($is_vertical) {
        $ul_class = 'list-group list-group-flush';
    } else {
        // Default (Header)
        $ul_class = $args['class'] ?? 'navbar-nav mb-2 mb-lg-0 gap-3 fw-medium';
    }

    echo '<ul class="' . $ul_class . '">';
    
    foreach ($items as $item) {
        $label = htmlspecialchars($item['label'] ?? '');
        $type  = $item['type'] ?? 'custom';
        $url   = '#';
        $active_class = '';

        // URL-ის გენერაცია
        if ($type === 'custom') {
            $url = htmlspecialchars($item['url'] ?? '#');
        } 
        elseif ($type === 'page') {
            $page_id = (int)($item['object_id'] ?? 0);
            $page = $ok_db->get_row("SELECT * FROM ok_posts WHERE id=$page_id");
            if ($page) {
                $perm = get_ok_option('permalink_structure', 'plain');
                $url = ($perm === 'plain') ? "/?p=" . $page->id : "/" . $page->post_name;
            } else {
                continue;
            }
        }
        elseif ($type === 'category') {
            $cat_id = (int)($item['object_id'] ?? 0);
            $cat = $ok_db->get_row("SELECT * FROM ok_categories WHERE id=$cat_id");
            if ($cat) $url = "/" . $cat->slug;
            else continue;
        }

        // Active კლასის მინიჭება
        $current_uri = $_SERVER['REQUEST_URI'];
        // მარტივი შემოწმება: თუ მიმდინარე ლინკი ემთხვევა URL-ს
        if (($url !== '/' && strpos($current_uri, $url) !== false) || ($url === '/' && $current_uri === '/')) {
            $active_class = 'active text-primary fw-bold';
        }

        // 🛑 HTML რენდერი (განსხვავდება სტილის მიხედვით)
        if ($is_vertical) {
            // საიდბარის სტილი (List Group Item)
            echo '<li class="list-group-item px-0 border-0 border-bottom bg-transparent">';
            echo '<a class="text-decoration-none text-dark d-block ' . $active_class . '" href="' . $url . '">';
            echo '<i class="bi bi-chevron-right text-muted small me-2"></i>' . $label;
            echo '</a></li>';
        } else {
            // ჰედერის სტილი (Nav Item)
            echo '<li class="nav-item">';
            echo '<a class="nav-link ' . $active_class . '" href="' . $url . '">' . $label . '</a>';
            echo '</li>';
        }
    }

    echo '</ul>';
}
?>