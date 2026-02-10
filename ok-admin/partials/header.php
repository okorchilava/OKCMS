<?php
declare(strict_types=1);

/**
 * OK Admin Header
 * შესწორებულია: სკრიპტები გადატანილია ფუტერში.
 */

if (!defined('OK_LOADED')) {
    if (!headers_sent()) { http_response_code(403); }
    exit('Access Denied.');
}

global $ok_db;

$current_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// საწყისი მნიშვნელობები
$user_display_name = 'Admin';
$user_email        = '';
$user_role         = 'subscriber';

$site_title   = get_ok_option('site_title', 'OK Admin');
$site_favicon = get_ok_option('site_favicon', '');

// Front site URL
$site_url = get_ok_option('site_url', '');
$site_url = is_string($site_url) ? trim($site_url) : '';
if ($site_url === '') {
    $site_url = '/';
}
if (!preg_match('~^https?://~i', $site_url) && ($site_url[0] ?? '') !== '/') {
    $site_url = '/' . $site_url;
}

if (isset($ok_db) && $current_user_id > 0) {
    $u_row = $ok_db->get_row(
        "SELECT display_name, username, email, user_role FROM ok_users WHERE id = ?",
        [$current_user_id]
    );
    if ($u_row) {
        $user_display_name = !empty($u_row->display_name) ? (string)$u_row->display_name : ((string)($u_row->username ?? 'User'));
        $user_email        = (string)($u_row->email ?? '');
        $user_role         = (string)($u_row->user_role ?? 'subscriber');
    }
}

// Gravatar & Color logic
if (!function_exists('ok_get_gravatar')) {
    function ok_get_gravatar(string $email, int $size = 80): string
    {
        $hash = md5(trim(strtolower($email)));
        return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=mp";
    }
}
$avatar_small = ok_get_gravatar($user_email, 64);
$avatar_large = ok_get_gravatar($user_email, 200);

if (!function_exists('ok_string_to_color')) {
    function ok_string_to_color(string $str): string
    {
        return '#' . substr(md5($str), 0, 6);
    }
}
$header_bg_color = ok_string_to_color($user_display_name);
?>
<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars((string)$site_title, ENT_QUOTES, 'UTF-8'); ?> - Admin</title>

    <?php if (!empty($site_favicon)): ?>
        <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars((string)$site_favicon, ENT_QUOTES, 'UTF-8'); ?>">
        <link rel="shortcut icon" href="<?php echo htmlspecialchars((string)$site_favicon, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@100..900&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --header-height: 60px;
        }

        body {
            font-family: "Noto Sans Georgian", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: .875rem;
            background-color: #f8f9fa;
            padding-top: var(--header-height);
        }

        /* --- HEADER STYLES --- */
        .navbar {
            height: var(--header-height);
            background-color: #212529;
            padding: 0 1rem;
            z-index: 1030;
        }

        .navbar-brand {
            font-size: 1.25rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            height: 100%;
            background-color: transparent;
            box-shadow: none;
            padding: 0;
        }

        .navbar-toggler {
            border: none;
            padding: 0;
            color: rgba(255,255,255,0.7);
        }
        .navbar-toggler:focus { box-shadow: none; color: white; }

        .navbar .btn.btn-outline-light { border-color: rgba(255,255,255,0.35); }
        .navbar .btn.btn-outline-light:hover { border-color: rgba(255,255,255,0.6); }

        /* Avatars */
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.2); }
        .user-avatar-large { width: 90px; height: 90px; border-radius: 50%; object-fit: cover; border: 4px solid rgba(255,255,255,0.3); margin-bottom: 10px; background-color: #fff; position: relative; z-index: 2; }

        /* General Dropdown Tweaks */
        .dropdown-menu { border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15); overflow: hidden; }

        /* Profile Dropdown */
        .profile-dropdown-menu { min-width: 300px; padding: 0; margin-top: 15px !important; }
        .profile-header { position: relative; background-color: <?php echo htmlspecialchars($header_bg_color, ENT_QUOTES, 'UTF-8'); ?>; background-image: linear-gradient(135deg, rgba(0,0,0,0) 0%, rgba(0,0,0,0.4) 100%); color: white; padding: 30px 20px; text-align: center; }
        .profile-role-badge { background: rgba(0, 0, 0, 0.3); padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; border: 1px solid rgba(255,255,255,0.2); }

        /* --- NOTIFICATION STYLES --- */
        .ok-notif-dropdown {
            width: 360px;
            max-width: 90vw;
            padding: 0;
            margin-top: 15px !important;
        }
        .ok-notif-header {
            padding: 12px 15px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .ok-notif-list {
            max-height: 400px;
            overflow-y: auto;
        }
        .ok-notif-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f1f1f1;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            text-decoration: none;
            color: #333;
            transition: background 0.2s;
            position: relative;
        }
        .ok-notif-item:hover { background: #f8f9fa; color: #333; }
        .ok-notif-item.unread { background: #eef6fc; }
        
        .ok-notif-icon-box {
            width: 32px; height: 32px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: white; flex-shrink: 0;
            font-size: 1rem;
        }
        .bg-n-success { background-color: #198754; }
        .bg-n-info    { background-color: #0dcaf0; }
        .bg-n-warning { background-color: #ffc107; color: #000; }
        .bg-n-danger  { background-color: #dc3545; }

        .ok-notif-content { flex-grow: 1; font-size: 0.85rem; line-height: 1.4; }
        .ok-notif-time { display: block; font-size: 0.7rem; color: #999; margin-top: 4px; }
        
        .ok-read-toggler {
            width: 10px; height: 10px;
            border-radius: 50%;
            border: 2px solid #ddd;
            flex-shrink: 0;
            margin-top: 5px;
            cursor: pointer;
        }
        .ok-notif-item.unread .ok-read-toggler {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        .ok-badge-adjusted {
            position: absolute;
            top: 8px;
            left: 65%;
            transform: translate(-50%, -50%);
            z-index: 5;
        }

        .notif-badge-anim { animation: pulse-red 2s infinite; }
        @keyframes pulse-red {
            0% { transform: translate(-50%, -50%) scale(0.95); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
            70% { transform: translate(-50%, -50%) scale(1); box-shadow: 0 0 0 6px rgba(220, 53, 69, 0); }
            100% { transform: translate(-50%, -50%) scale(0.95); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
        }

        @media (max-width: 767.98px) {
            .navbar-brand { position: absolute; left: 50%; transform: translateX(-50%); }
            .navbar-toggler { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); }
        }
    </style>
</head>
<body>

<header class="navbar navbar-dark fixed-top bg-dark shadow">
    <button class="navbar-toggler d-md-none" type="button"
            data-bs-toggle="collapse" data-bs-target="#sidebarMenu"
            aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <i class="bi bi-list fs-2"></i>
    </button>

    <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3 text-white text-decoration-none" href="index.php">
        <i class="bi bi-speedometer2 me-2"></i> OK Engine
    </a>

    <div class="d-flex align-items-center ms-auto">

        <a href="<?php echo htmlspecialchars($site_url, ENT_QUOTES, 'UTF-8'); ?>"
           class="btn btn-outline-light btn-sm me-3 d-flex align-items-center"
           target="_blank" rel="noopener noreferrer">
            <i class="bi bi-box-arrow-up-right me-2"></i>
            <span class="d-none d-sm-inline">საიტზე გადასვლა</span>
        </a>

        <?php if (function_exists('ok_render_notification_dropdown')) {
            ok_render_notification_dropdown();
        } ?>

        <div class="dropdown me-3">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
               id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="<?php echo htmlspecialchars($avatar_small, ENT_QUOTES, 'UTF-8'); ?>" alt="User" class="user-avatar me-2">
                <span class="d-none d-sm-inline small fw-medium"><?php echo htmlspecialchars($user_display_name, ENT_QUOTES, 'UTF-8'); ?></span>
            </a>

            <ul class="dropdown-menu dropdown-menu-end shadow profile-dropdown-menu" aria-labelledby="dropdownUser1">
                <li class="profile-header">
                    <div class="profile-header-content" style="position: relative; z-index: 2;">
                        <img src="<?php echo htmlspecialchars($avatar_large, ENT_QUOTES, 'UTF-8'); ?>" alt="User" class="user-avatar-large shadow-lg">
                        <h5 class="mb-1 fw-bold text-white text-shadow"><?php echo htmlspecialchars($user_display_name, ENT_QUOTES, 'UTF-8'); ?></h5>
                        <div class="small text-white-50 mb-3"><?php echo htmlspecialchars($user_email, ENT_QUOTES, 'UTF-8'); ?></div>
                        <span class="profile-role-badge"><?php echo ucfirst(htmlspecialchars($user_role, ENT_QUOTES, 'UTF-8')); ?></span>
                    </div>
                </li>

                <li class="py-2">
                    <a class="dropdown-item py-2 d-flex align-items-center" href="index.php?page=ok-profile">
                        <i class="bi bi-person-gear me-3 fs-5 text-primary"></i>
                        <div>
                            <span class="d-block fw-medium">პროფილის რედაქტირება</span>
                            <span class="small text-muted">პაროლის და სახელის შეცვლა</span>
                        </div>
                    </a>

                    <a class="dropdown-item py-2 d-flex align-items-center" href="index.php?page=ok-settings">
                        <i class="bi bi-sliders me-3 fs-5 text-secondary"></i>
                        <div>
                            <span class="d-block fw-medium">პარამეტრები</span>
                            <span class="small text-muted">სისტემური გამართვა</span>
                        </div>
                    </a>
                </li>

                <li><hr class="dropdown-divider m-0"></li>

                <li class="py-2 bg-light">
                    <a class="dropdown-item text-danger fw-bold d-flex justify-content-between align-items-center" href="logout.php">
                        <span>გასვლა</span><i class="bi bi-box-arrow-right"></i>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>

<div class="container-fluid">
    <div class="row">