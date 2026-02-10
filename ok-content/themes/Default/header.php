<?php
/**
 * Theme Header — Fixed Background & Sticky Header
 */

if (!function_exists('get_ok_option')) { die('Core not loaded'); }

$site_title   = get_ok_option('site_title', 'OK Engine');
$site_tagline = get_ok_option('site_tagline', '');
$site_logo    = get_ok_option('site_logo', '');
$site_favicon = get_ok_option('site_favicon', '');

$page_title = $site_title;
if (isset($ok_query['is_single']) || isset($ok_query['is_page'])) {
    $page_title = htmlspecialchars($ok_query['object']->post_title) . ' - ' . $site_title;
} elseif (isset($ok_query['is_category'])) {
    $page_title = htmlspecialchars($ok_query['object']->name) . ' - ' . $site_title;
}
?>
<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>

    <meta property="og:site_name" content="<?php echo htmlspecialchars($site_title); ?>">
    <meta property="og:title" content="<?php echo $page_title; ?>">
    <?php if (!empty($site_logo)): ?><meta property="og:image" content="<?php echo htmlspecialchars($site_logo); ?>"><?php endif; ?>
    <?php if (!empty($site_favicon)): ?><link rel="shortcut icon" href="<?php echo htmlspecialchars($site_favicon); ?>" type="image/x-icon"><?php endif; ?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@100..900&display=swap" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="/ok-content/themes/default/style.css?v=<?php echo time(); ?>">
    
    <style>
        /* 🛑 GLOBAL FIXES */
        body {
            background-color: #ffffff !important; /* სულ თეთრი ფონი */
            padding-top: 90px; /* ჰედერის ადგილი */
            color: #333;
        }

        .site-header {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 90px;
            background-color: #ffffff; /* ჰედერიც თეთრი */
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            z-index: 1000;
            display: flex; align-items: center;
        }

        /* მთავარი კონტეინერიც თეთრია, რომ ფერი არ გაწყდეს */
        main.site-main {
            background-color: #ffffff;
            min-height: calc(100vh - 90px); /* ავსებს ეკრანს */
        }

        @media (max-width: 991px) {
            .site-header { height: 80px; }
            body { padding-top: 80px; }
        }
        
        .site-logo { max-height: 50px; width: auto; }
        .navbar-nav .nav-link { font-weight: 500; color: #333; transition: color 0.2s; }
        .navbar-nav .nav-link:hover { color: #0d6efd; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

<header class="site-header border-bottom">
    <div class="container">
        <nav class="navbar navbar-expand-lg navbar-light w-100">
            <div class="container-fluid px-0">
                <a class="navbar-brand d-flex align-items-center gap-3" href="/">
                    <?php if (!empty($site_logo)): ?><img src="<?php echo htmlspecialchars($site_logo); ?>" alt="Logo" class="site-logo"><?php endif; ?>
                    <div class="site-identity lh-1">
                        <span class="site-title d-block fw-bold text-dark"><?php echo htmlspecialchars($site_title); ?></span>
                        <?php if(!empty($site_tagline)): ?><span class="site-tagline d-block small text-muted mt-1" style="font-size: 0.8rem;"><?php echo htmlspecialchars($site_tagline); ?></span><?php endif; ?>
                    </div>
                </a>
                <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainMenu"><span class="navbar-toggler-icon"></span></button>
                <div class="collapse navbar-collapse justify-content-end" id="mainMenu">
                    <?php 
                    if (function_exists('ok_nav_menu')) { ok_nav_menu('header-menu'); } 
                    else { echo '<ul class="navbar-nav mb-2 mb-lg-0 gap-3"><li class="nav-item"><a class="nav-link" href="/">მთავარი</a></li></ul>'; }
                    ?>
                </div>
            </div>
        </nav>
    </div>
</header>

<main class="flex-grow-1 site-main">