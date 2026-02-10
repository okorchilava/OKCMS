<?php
declare(strict_types=1);

if (!function_exists('ok_admin_redirect')) {
    function ok_admin_redirect(string $path): void
    {
        $path = str_replace(["\r", "\n"], '', $path);

        if (!headers_sent()) {
            header('Location: ' . $path);
        }
        exit;
    }
}

if (!function_exists('ok_admin_require_login')) {
    function ok_admin_require_login(): void
    {
        if (empty($_SESSION['user_id'])) {
            ok_admin_redirect('login.php');
        }

        $uid = (int)$_SESSION['user_id'];

        if ($uid <= 0) {
            session_destroy();
            ok_admin_redirect('login.php');
        }

        if (empty($_SESSION['token']) || !is_string($_SESSION['token']) || strlen($_SESSION['token']) < 32) {
            session_destroy();
            ok_admin_redirect('login.php');
        }

        global $ok_db;
        if (!isset($ok_db) || !is_object($ok_db)) {
            session_destroy();
            ok_admin_redirect('login.php');
        }

        $user_check = $ok_db->get_row(
            "SELECT id, user_role, username, display_name FROM ok_users WHERE id = ? LIMIT 1",
            [$uid]
        );

        if (!$user_check) {
            session_destroy();
            ok_admin_redirect('login.php');
        }

        if (!empty($user_check->user_role)) {
            $_SESSION['user_role'] = (string)$user_check->user_role;
        }
        if (!empty($user_check->username)) {
            $_SESSION['username'] = (string)$user_check->username;
        }
        if (!empty($user_check->display_name)) {
            $_SESSION['display_name'] = (string)$user_check->display_name;
        }
    }
}

if (!function_exists('ok_admin_partials_path')) {
    function ok_admin_partials_path(): string
    {
        $root = dirname(__DIR__, 2);
        return $root . DIRECTORY_SEPARATOR . 'ok-admin' . DIRECTORY_SEPARATOR . 'partials';
    }
}

if (!function_exists('get_admin_header')) {
    function get_admin_header(): void
    {
        $file = ok_admin_partials_path() . DIRECTORY_SEPARATOR . 'header.php';
        if (is_file($file)) { require $file; return; }

        echo '<!doctype html><html lang="ka"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>OK Admin</title></head><body>';
    }
}

if (!function_exists('get_admin_sidebar')) {
    function get_admin_sidebar(): void
    {
        $file = ok_admin_partials_path() . DIRECTORY_SEPARATOR . 'sidebar.php';
        if (is_file($file)) { require $file; return; }

        echo '<div style="padding:16px;">Admin sidebar partial not found.</div>';
    }
}

if (!function_exists('get_admin_footer')) {
    function get_admin_footer(): void
    {
        $file = ok_admin_partials_path() . DIRECTORY_SEPARATOR . 'footer.php';
        if (is_file($file)) { require $file; return; }

        echo '</body></html>';
    }
}


// Theme/Plugin dynamic menus storage
global $ok_dynamic_menus, $ok_dynamic_submenus;
$ok_dynamic_menus = $ok_dynamic_menus ?? [];
$ok_dynamic_submenus = $ok_dynamic_submenus ?? [];

if (!function_exists('add_menu_page')) {
    function add_menu_page($page_title, $menu_title, $capability, $menu_slug, $callback, $icon = '', $position = null) {
        global $ok_dynamic_menus;
        $ok_dynamic_menus[$menu_slug] = [
            'page_title' => (string)$page_title,
            'menu_title' => (string)$menu_title,
            'capability' => (string)$capability,
            'menu_slug'  => (string)$menu_slug,
            'function'   => $callback,
            'icon_class' => (string)$icon,
            'position'   => $position,
        ];
    }
}

if (!function_exists('add_submenu_page')) {
    function add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback) {
        global $ok_dynamic_submenus;

        $parent_slug = (string)$parent_slug;
        $menu_slug   = (string)$menu_slug;

        if ($parent_slug === '' || $menu_slug === '') {
            return false;
        }

        $ok_dynamic_submenus[$parent_slug][$menu_slug] = [
            'parent_slug'=> $parent_slug,
            'page_title' => (string)$page_title,
            'menu_title' => (string)$menu_title,
            'capability' => (string)$capability,
            'menu_slug'  => $menu_slug,
            'function'   => $callback,
        ];

        return true;
    }
}
