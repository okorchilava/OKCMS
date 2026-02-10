<?php
// page.php (Theme: drpaata) — Single Page (engine-first, minimal theme logic)

global $ok_query;

/* Ensure we have a page object */
$page = $ok_query['object'] ?? null;

if (!$page || !is_object($page)) {
    get_header();
    echo "<div class='container py-5 text-center'>გვერდი ვერ მოიძებნა</div>";
    get_footer();
    exit;
}

/* Minimal OG/meta vars for header.php */
$og_title = (string)($page->post_title ?? '');
$plain    = trim(strip_tags((string)($page->post_content ?? '')));
$og_desc  = function_exists('mb_substr') ? mb_substr($plain, 0, 160, 'UTF-8') : substr($plain, 0, 160);
$og_desc  = $og_desc !== '' ? ($og_desc . '...') : '';
$og_image = (string)($page->post_image ?? '');
$meta_desc = $og_desc;

get_header();

$show_sidebar = (bool)($ok_query['has_sidebar'] ?? false);

$has_cover = !empty($page->post_image);
$cover_url = $has_cover ? (string)$page->post_image : '';
$title     = (string)($page->post_title ?? '');
?>

<?php if ($has_cover): ?>
    <div class="page-hero mb-5"
         style="background-image:url('<?php echo htmlspecialchars($cover_url, ENT_QUOTES, 'UTF-8'); ?>');">
        <div class="page-hero-overlay"></div>
        <div class="container h-100 position-relative">
            <div class="row h-100 align-items-center justify-content-center text-center">
                <div class="col-lg-10">
                    <h1 class="h2 fw-normal text-white text-shadow">
                        <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>
                    </h1>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="container mt-5 mb-4 text-center">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <h1 class="h2 fw-normal text-dark">
                    <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>
                </h1>
                <hr class="mt-4 mx-auto" style="width:100px;height:3px;background:#0d6efd;opacity:1;border:none;">
            </div>
        </div>
    </div>
<?php endif; ?>

<main class="site-main">
    <div class="container mb-5 pb-5">
        <div class="row <?php echo $show_sidebar ? '' : 'justify-content-center'; ?>">

            <div class="<?php echo $show_sidebar ? 'col-lg-8' : 'col-lg-10'; ?>">
                <div class="page-content post-content">
                    <?php 
                    // 1. PLUGINS BEFORE CONTENT
                    if (function_exists('do_ok_action')) {
                        do_ok_action('ok_before_content');
                    } 
                    ?>

                    <?php
                        // 2. MAIN CONTENT
                        the_content();
                    ?>

                    <?php 
                    // 3. PLUGINS AFTER CONTENT
                    if (function_exists('do_ok_action')) {
                        do_ok_action('ok_after_content');
                    } 
                    ?>
                </div>
            </div>

            <?php if ($show_sidebar): ?>
                <div class="col-lg-4">
                    <div class="ps-lg-4 mt-5 mt-lg-0 sidebar-sticky">
                        <?php include __DIR__ . '/sidebar.php'; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</main>

<style>
.page-hero{
    height:300px;
    width:100%;
    background-size:cover;
    background-position:center;
    background-repeat:no-repeat;
    background-attachment:fixed;
    margin-top:-30px;
    position:relative;
}
@media (max-width:768px){
    .page-hero{ background-attachment:scroll; height:200px; }
}
.page-hero-overlay{
    position:absolute;
    inset:0;
    background:#000;
    opacity:.5;
}
.text-shadow{
    text-shadow:2px 2px 10px rgba(0,0,0,.8);
}

/* Content */
.post-content{ color:#333; line-height:1.8; }
.post-content p{ margin-bottom:1.5rem; }
.post-content img{
    max-width:100%;
    height:auto;
    border-radius:12px;
    margin:30px 0;
    box-shadow:0 5px 15px rgba(0,0,0,.08);
}
.post-content h2{
    font-size:1.75rem;
    margin-top:2.5rem;
    margin-bottom:1.2rem;
    font-weight:normal;
    color:#1a1a1a;
}
.post-content h3{
    font-size:1.5rem;
    margin-top:2rem;
    margin-bottom:1rem;
    font-weight:normal;
}
.post-content blockquote{
    border-left:4px solid #0d6efd;
    padding:20px 30px;
    margin:30px 0;
    font-style:italic;
    background:#f8f9fa;
    color:#555;
}
@media(min-width:992px){
    .sidebar-sticky{ position:sticky; top:110px; }
}
</style>

<?php get_footer(); ?>