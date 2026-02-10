<?php
/**
 * Theme Header — OK Paata Theme
 * შესწორებულია: ლოგო და ტექსტი ჩანს ერთად (გვერდიგვერდ)
 */
if (!function_exists('get_ok_option')) { die('OK Engine core not loaded.'); }

$site_title   = get_ok_option('site_title', 'OK Engine');
$site_tagline = get_ok_option('site_tagline', '');
$site_logo    = get_ok_option('site_logo', '');
$site_favicon = get_ok_option('site_favicon', '');
?>
<!DOCTYPE html>
<html lang="ka">
<head>
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-HMPMFJLWD6"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-HMPMFJLWD6');
    </script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php 
          if (isset($ok_query['is_single']) || isset($ok_query['is_page'])) {
            the_title(); echo ' - ';
          } elseif (isset($ok_query['is_category_archive'])) {
            echo get_archive_title() . ' - ';
          }
          echo htmlspecialchars($site_title); 
        ?>
    </title>

    <?php if (!empty($site_favicon)): ?>
        <link rel="icon" href="<?php echo htmlspecialchars($site_favicon); ?>" type="image/x-icon">
        <link rel="shortcut icon" href="<?php echo htmlspecialchars($site_favicon); ?>" type="image/x-icon">
    <?php endif; ?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/ok-content/themes/paata/style.css?v=2.18">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        .site-header-logo {
            max-height: 50px;
            width: auto;
            object-fit: contain;
        }
    </style>
</head>
<body>

<header class="site-header sticky-top bg-dark py-2">
  <div class="container d-flex justify-content-between align-items-center">
    
    <div class="site-branding">
        <a href="/" class="text-decoration-none text-white d-flex align-items-center gap-3">
            
            <?php if (!empty($site_logo)): ?>
                <img src="<?php echo htmlspecialchars($site_logo); ?>" alt="<?php echo htmlspecialchars($site_title); ?>" class="site-header-logo">
            <?php endif; ?>

            <div class="d-flex flex-column lh-1">
                <span class="site-title fw-bold fs-5 text-uppercase letter-spacing-1"><?php echo htmlspecialchars($site_title); ?></span>
                
                <?php if(!empty($site_tagline)): ?>
                    <span class="site-description small text-white-50 mt-1" style="font-size: 0.75rem;"><?php echo htmlspecialchars($site_tagline); ?></span>
                <?php endif; ?>
            </div>

        </a>
    </div>

    <button id="navToggleBtn"
            class="navbar-toggler d-lg-none ms-auto text-white border-0"
            type="button"
            aria-controls="mainNav"
            aria-expanded="false"
            aria-label="მენიუ">
      <i class="bi bi-list fs-1"></i>
    </button>

    <div id="mainNav" class="collapse d-lg-flex ms-lg-auto">
      <nav class="main-navigation">
        <?php
          if (function_exists('ok_nav_menu')) {
              ok_nav_menu([
                'theme_location' => 'primary_menu',
                'menu_class'     => 'nav text-white align-items-center gap-3'
              ]);
          }
        ?>
      </nav>
    </div>
  </div>
</header>