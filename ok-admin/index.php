<?php
declare(strict_types=1);

/**
 * OK Admin Panel - სისტემის მთავარი შესასვლელი
 * Fix: ჰედერების და სესიის შეცდომების გამოსწორება
 */

// 1. ყოველთვის პირველივე ხაზზე ჩავრთოთ Output Buffering
ob_start(); 

require_once dirname(__DIR__) . '/ok-core/load.php';

// ─────────────────────────────────────────────────────────────────────────────
// 0) კრიტიკული შეცდომების დამმუშავებელი
// ─────────────────────────────────────────────────────────────────────────────
if (defined('OK_DEBUG') && OK_DEBUG === true) {
    function ok_fatal_error_handler(): void
    {
        $last_error = error_get_last();
        if ($last_error && in_array($last_error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR], true)) {
            while (ob_get_level()) { ob_end_clean(); }
            echo '<div style="padding:20px;background:#fff3cd;border:1px solid #ffeeba;color:#856404;margin:20px;font-family:sans-serif;">';
            echo '<strong>Critical Error:</strong> ' . htmlspecialchars((string)$last_error['message']) . '<br>';
            echo '<strong>File:</strong> ' . htmlspecialchars((string)$last_error['file']) . ' on line ' . (int)$last_error['line'];
            echo '</div>';
        }
    }
    register_shutdown_function('ok_fatal_error_handler');
}

// ─────────────────────────────────────────────────────────────────────────────
// 1) Admin Guard (გაუმჯობესებული)
// ─────────────────────────────────────────────────────────────────────────────
if (function_exists('ok_admin_require_login')) {
    ok_admin_require_login();
} else {
    // თუ სესია ცარიელია ან მომხმარებელი არ არის ადმინი
    if (empty($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        if (ob_get_length()) ob_clean(); // ვასუფთავებთ ბუფერს რედირექტამდე
        header('Location: login.php');
        exit;
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 2) სისტემური ფაილები
// ─────────────────────────────────────────────────────────────────────────────
// (დარწმუნდით, რომ ეს ფაილები არსებობს)
$core_admin_files = [
    'ok-main.php', 'ok-profile.php', 'ok-settings.php', 'ok-credits.php',
    'ok-posts.php', 'ok-post-editor.php', 'ok-users.php', 'ok-notifications.php',
    'ok-user-editor.php', 'ok-plugins.php', 'ok-upload-plugin.php', 'ok-pages.php',
    'ok-page-editor.php', 'ok-themes.php', 'ok-theme-upload.php', 'ok-gallery.php',
    'ok-categories.php', 'ok-widgets.php', 'ok-menus.php', 'ok-logs.php'
];

foreach ($core_admin_files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        require_once $path;
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 3) მენიუს სისტემის ინიციალიზაცია
// ─────────────────────────────────────────────────────────────────────────────
global $ok_dynamic_menus, $ok_dynamic_submenus;
$ok_dynamic_menus = [];
$ok_dynamic_submenus = [];

if (!function_exists('add_menu_page')) {
    function add_menu_page(string $page_title, string $menu_title, string $capability, string $menu_slug, callable $callback, string $icon = '', $position = null): void {
        global $ok_dynamic_menus;
        $ok_dynamic_menus[$menu_slug] = [
            'page_title' => $page_title,
            'menu_title' => $menu_title,
            'capability' => $capability,
            'menu_slug'  => $menu_slug,
            'function'   => $callback,
            'icon_class' => $icon,
            'position'   => $position
        ];
    }
}

if (!function_exists('add_submenu_page')) {
    function add_submenu_page(string $parent_slug, string $page_title, string $menu_title, string $capability, string $menu_slug, callable $callback): void {
        global $ok_dynamic_submenus;
        $ok_dynamic_submenus[$parent_slug][] = [
            'parent_slug'=> $parent_slug,
            'page_title' => $page_title,
            'menu_title' => $menu_title,
            'capability' => $capability,
            'menu_slug'  => $menu_slug,
            'function'   => $callback
        ];
    }
}

// ჰუკი პლაგინებისთვის
do_ok_action('admin_menu');

// ─────────────────────────────────────────────────────────────────────────────
// 4) Routing
// ─────────────────────────────────────────────────────────────────────────────
global $ok_admin_menu, $ok_admin_submenu;

$current_page_slug = (string)($_GET['page'] ?? 'ok-main');
$current_page_slug = preg_replace('/[^a-zA-Z0-9_-]/', '', $current_page_slug);

$current_route = null;

// Routing Logic (Core & Dynamic)
// [აქ რჩება თქვენი არსებული Routing ლოგიკა...]
if (isset($ok_dynamic_menus[$current_page_slug])) {
    $current_route = $ok_dynamic_menus[$current_page_slug];
}
if (!$current_route && !empty($ok_dynamic_submenus)) {
    foreach ($ok_dynamic_submenus as $parent => $subs) {
        foreach ($subs as $sub) {
            if ($sub['menu_slug'] === $current_page_slug) {
                $current_route = $sub;
                break 2;
            }
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 5) Layout რენდერი
// ─────────────────────────────────────────────────────────────────────────────
get_admin_header();
get_admin_sidebar();
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-4">
    <div class="fade-in pb-5">
        <?php
        if ($current_route) {
            $cap = (string)($current_route['capability'] ?? '');
            if ($cap !== '' && function_exists('current_user_can') && !current_user_can($cap)) {
                echo '<div class="alert alert-danger shadow-sm border-0 m-4">წვდომა შეზღუდულია.</div>';
            } else {
                $fn = $current_route['function'] ?? null;
                if (is_callable($fn)) {
                    call_user_func($fn);
                } else {
                    echo '<div class="alert alert-warning shadow-sm border-0 m-4">ფუნქცია ვერ მოიძებნა.</div>';
                }
            }
        } else {
            echo '<div class="container mt-5 text-center py-5">
                    <h2 class="text-muted fw-bold">გვერდი ვერ მოიძებნა (404)</h2>
                    <a href="index.php" class="btn btn-primary mt-3">მთავარზე დაბრუნება</a>
                  </div>';
        }
        ?>
    </div>
</main>

<?php
get_admin_footer();
// ბოლოს ვუშვებთ ბუფერს
ob_end_flush();