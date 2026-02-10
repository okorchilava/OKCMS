<?php
declare(strict_types=1);

if (!defined('OK_LOADED')) {
    // თუ პირდაპირ ფაილს ხსნიან, დაბლოკოს (ისევე როგორც ადმინში)
    if (!headers_sent()) { http_response_code(403); }
    exit('Access Denied.'); 
}

/**
 * Header.php — Public Theme
 * მონაცემების წამოღება ადმინ პანელის ნიმუშის მიხედვით
 */

global $ok_query;

// 1. მონაცემების მიღება (ADMIN STYLE)
$site_title   = get_ok_option('site_title', 'OK CMS');   // სათაური
$site_tagline = get_ok_option('site_tagline', '');       // აღწერა (სლოგანი)
$site_favicon = get_ok_option('site_favicon', '');       // ფაიკონი
$site_logo    = get_ok_option('site_logo', '');          // ლოგო

// 2. დინამიური სათაურის (Page Title) ლოგიკა
$page_title = $site_title;

if (!empty($ok_query['is_single']) || !empty($ok_query['is_page'])) {
    if (!empty($ok_query['object']) && !empty($ok_query['object']->post_title)) {
        $page_title = (string)$ok_query['object']->post_title . ' - ' . $site_title;
    }
} elseif (!empty($ok_query['is_category'])) {
    if (!empty($ok_query['object']) && !empty($ok_query['object']->name)) {
        $page_title = (string)$ok_query['object']->name . ' - ' . $site_title;
    }
}

// 3. მეტა აღწერა (Standard SEO)
$meta_desc = isset($meta_desc) ? (string)$meta_desc : '';
?>
<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars((string)$page_title, ENT_QUOTES, 'UTF-8'); ?></title>

    <?php if (!empty($site_favicon)): ?>
        <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars((string)$site_favicon, ENT_QUOTES, 'UTF-8'); ?>">
        <link rel="shortcut icon" href="<?php echo htmlspecialchars((string)$site_favicon, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>

    <meta name="description" content="<?php echo htmlspecialchars($meta_desc, ENT_QUOTES, 'UTF-8'); ?>">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@100..900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="/ok-content/themes/drpaata/style.css?v=<?php echo time(); ?>">

    <style>
        :root { --header-h-desktop: 90px; --header-h-mobile: 80px; }
        body { padding-top: var(--header-h-desktop); font-family: "Noto Sans Georgian", system-ui, sans-serif; background-color: #fff; }
        .site-header { height: var(--header-h-desktop); background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.05); z-index: 1000; }
        .site-logo { height: 45px; width: auto; object-fit: contain; }
        .site-identity { line-height: 1.1; }
        .site-title { font-weight: 700; font-size: 1.05rem; color: #212529; }
        .site-tagline { font-size: 0.8rem; color: #6c757d; }

        .ok-nav-toggle {
            appearance: none; border: 0; background: rgba(15, 23, 42, 0.04);
            width: 42px; height: 42px; border-radius: 999px;
            display: inline-flex; align-items: center; justify-content: center;
            box-shadow: 0 8px 22px rgba(0,0,0,0.08); transition: all .15s ease;
            position: relative; z-index: 1005;
        }
        .ok-nav-toggle:hover { background: rgba(15, 23, 42, 0.06); transform: translateY(-1px); }
        .ok-nav-toggle-icon {
            width: 18px; height: 12px; position: relative; display: block;
            background: linear-gradient(#0f172a, #0f172a); background-size: 18px 2px;
            background-repeat: no-repeat; background-position: 0 5px; pointer-events: none;
        }
        .ok-nav-toggle-icon::before, .ok-nav-toggle-icon::after {
            content: ""; position: absolute; left: 0; width: 18px; height: 2px;
            background: #0f172a; border-radius: 999px; transition: all .18s ease;
        }
        .ok-nav-toggle-icon::before { top: 2px; }
        .ok-nav-toggle-icon::after { top: 8px; }
        .ok-nav-toggle.is-open .ok-nav-toggle-icon { background-size: 0 0; }
        .ok-nav-toggle.is-open .ok-nav-toggle-icon::before { top: 5px; transform: rotate(45deg); }
        .ok-nav-toggle.is-open .ok-nav-toggle-icon::after { top: 5px; transform: rotate(-45deg); }

        @media (max-width: 991px) {
            body { padding-top: var(--header-h-mobile); }
            .site-header { height: var(--header-h-mobile); }
            .site-tagline { display: none; }
            .ok-mobile-backdrop {
                position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45);
                opacity: 0; pointer-events: none; transition: opacity .18s ease; z-index: 999;
            }
            .ok-mobile-backdrop.show { opacity: 1; pointer-events: auto; }
            #navContent.ok-mobile-menu.navbar-collapse {
                position: fixed; left: 12px; right: 12px; top: calc(var(--header-h-mobile) + 10px);
                background: #fff; border-radius: 14px; box-shadow: 0 18px 55px rgba(0,0,0,0.16);
                padding: 10px 14px 12px; z-index: 1001; max-height: calc(100vh - var(--header-h-mobile) - 30px); overflow: auto;
            }
            #navContent.ok-mobile-menu .navbar-nav .nav-link { padding: 10px 2px; font-weight: 600; color: #0f172a; }
            #navContent.ok-mobile-menu .btn.btn-primary { width: 100%; margin-top: 10px !important; }
        }
    </style>
	 <?php if (function_exists('do_ok_action')) { do_ok_action('ok_head'); } ?>
</head>

<body class="d-flex flex-column min-vh-100">

<header class="site-header fixed-top d-flex align-items-center border-bottom">
    <div class="container position-relative">
        <nav class="navbar navbar-expand-lg navbar-light w-100">

            <a class="navbar-brand d-flex align-items-center gap-3" href="/">
                <?php if (!empty($site_logo)): ?>
                    <img src="<?php echo htmlspecialchars((string)$site_logo, ENT_QUOTES, 'UTF-8'); ?>" alt="Logo" class="site-logo">
                <?php else: ?>
                    <i class="bi bi-heart-pulse-fill text-primary fs-3"></i>
                <?php endif; ?>

                <div class="site-identity">
                    <span class="site-title d-block"><?php echo htmlspecialchars((string)$site_title, ENT_QUOTES, 'UTF-8'); ?></span>
                    
                    <?php if (!empty($site_tagline)): ?>
                        <span class="site-tagline d-block mt-1"><?php echo htmlspecialchars((string)$site_tagline, ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                </div>
            </a>

            <button class="ok-nav-toggle d-lg-none" type="button" aria-label="Toggle navigation">
                <span class="ok-nav-toggle-icon" aria-hidden="true"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-end ok-mobile-menu" id="navContent">
                <?php
                if (function_exists('ok_nav_menu')) {
                    ok_nav_menu('header-menu');
                } else {
                    echo '<ul class="navbar-nav"><li class="nav-item"><a class="nav-link" href="/">მთავარი</a></li></ul>';
                }
                ?>
                <a href="/book" class="btn btn-primary ms-lg-3 mt-3 mt-lg-0 px-4">
                    <i class="bi bi-calendar-check me-2"></i> ჩაწერა
                </a>
            </div>

        </nav>
    </div>
</header>

<div class="ok-mobile-backdrop d-lg-none" id="okMobileBackdrop" aria-hidden="true"></div>

<main class="flex-grow-1">

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const menuEl = document.getElementById('navContent');
    const backdrop = document.getElementById('okMobileBackdrop');
    const toggler = document.querySelector('.ok-nav-toggle');

    if (!menuEl || !backdrop || !toggler || typeof bootstrap === 'undefined') return;

    const bsCollapse = new bootstrap.Collapse(menuEl, { toggle: false });

    toggler.addEventListener('click', function (e) {
        e.preventDefault(); e.stopPropagation(); bsCollapse.toggle();
    });
    menuEl.addEventListener('show.bs.collapse', function () {
        backdrop.classList.add('show'); toggler.classList.add('is-open');
    });
    menuEl.addEventListener('hide.bs.collapse', function () {
        backdrop.classList.remove('show'); toggler.classList.remove('is-open');
    });
    backdrop.addEventListener('click', function () { bsCollapse.hide(); });
    menuEl.addEventListener('click', function (e) {
        if (window.innerWidth > 991) return;
        if (e.target.closest('a')) bsCollapse.hide();
    });
});
</script>