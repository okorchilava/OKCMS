<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php 
            if (isset($ok_query['is_single']) || isset($ok_query['is_page'])) {
                the_title(); echo ' - ';
            } elseif (isset($ok_query['is_category_archive'])) {
                echo get_archive_title() . ' - ';
            }
            echo get_ok_option('site_title', 'OK Engine'); 
        ?>
    </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/ok-content/themes/default-theme/style.css?v=2.7">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

</head>
<body>

<header class="site-header sticky-top">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="site-branding">
            <div class="site-title"><a href="/"><?php echo get_ok_option('site_title', 'OK Engine'); ?></a></div>
            <div class="site-description"><?php echo get_ok_option('site_tagline', 'Another OK Engine Site'); ?></div>
        </div>
        
        <nav class="main-navigation">
            <?php 
                // ვიძახებთ მენიუს, რომელიც "primary_menu" ლოკაციაზეა მიბმული
                ok_nav_menu(['theme_location' => 'primary_menu']); 
            ?>
        </nav>
        </div>
</header>