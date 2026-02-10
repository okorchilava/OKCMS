<?php
// single.php (Theme: drpaata)

get_header();

if (have_posts()) {
    the_post(); 
    global $post;
    
    $has_cover = !empty($post->post_image);
    $cover_url = $has_cover ? (string)$post->post_image : '';
    $cat = function_exists('get_ok_category') ? get_ok_category() : null;
    ?>
    
    <?php if ($has_cover): ?>
        <div class="post-hero mb-3 position-relative" style="background-image:url('<?php echo htmlspecialchars($cover_url, ENT_QUOTES, 'UTF-8'); ?>');">
            <div class="container h-100">
                <div class="row h-100 align-items-end pb-5">
                    <div class="col-lg-10 mx-auto text-white position-relative">
                        <h1 class="fw-bold fs-2 mb-3 text-shadow"><?php the_title(); ?></h1>
                        
                        <div class="d-flex justify-content-between align-items-center small text-shadow mb-2">
                            <div class="ok-meta-left">
                                <?php if (function_exists('the_ok_date')): ?><i class="bi bi-calendar3 me-1"></i><?php the_ok_date(); ?><?php endif; ?>
                            </div>
                            <div class="ok-meta-right">
                                <?php if (function_exists('the_ok_author')): ?><i class="bi bi-person me-1"></i><?php the_ok_author(); ?><?php endif; ?>
                            </div>
                        </div>

                        <?php if ($cat): ?>
                            <div class="mt-2">
                                <a href="<?php echo $cat->href; ?>" class="ok-cat-badge">
                                    <span class="ok-cat-badge-text"><?php echo $cat->label; ?></span>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="container mt-4 mb-2 text-center">
             <h1 class="fw-bold fs-2 mb-2"><?php the_title(); ?></h1>
             <hr class="mt-2 mx-auto" style="width:100px;height:3px;background:#0d6efd;">
        </div>
    <?php endif; ?>

    <div class="container mt-4 mb-5">
        <?php 
        global $ok_query;
        $show_sidebar = isset($ok_query['has_sidebar']) ? (bool)$ok_query['has_sidebar'] : false;
        ?>
        <div class="row <?php echo $show_sidebar ? '' : 'justify-content-center'; ?>">
            <div class="<?php echo $show_sidebar ? 'col-lg-8' : 'col-lg-10'; ?>">
                <article class="blog-post">
                    <section class="post-content pt-0"> 
                        <?php 
                        if (function_exists('do_ok_action')) {
                            do_ok_action('ok_before_content');
                        } 
                        ?>

                        <div class="entry-content-wrap">
                            <?php the_content(); ?>
                        </div>

                        <?php 
                        if (function_exists('do_ok_action')) {
                            do_ok_action('ok_after_content');
                        } 
                        ?>
                    </section>
                </article>
            </div>
            <?php if ($show_sidebar): ?>
                <div class="col-lg-4">
                    <div class="ps-lg-4 mt-4 mt-lg-0">
                        <?php include __DIR__ . '/sidebar.php'; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

<style>
    .post-hero { height: 45vh; min-height: 350px; background-size: cover; background-position: center; position: relative; }
    .text-shadow { text-shadow: 2px 2px 8px rgba(0,0,0,0.9); }
    
    /* აგრესიული გასწორება: ყველაფერი კონტენტში ხდება ზუსტად 1rem */
    .entry-content-wrap, 
    .entry-content-wrap p, 
    .entry-content-wrap span, 
    .entry-content-wrap div, 
    .entry-content-wrap b, 
    .entry-content-wrap strong, 
    .entry-content-wrap li {
        font-size: 1rem !important; 
        line-height: 1.7 !important;   
        color: #2c3e50 !important;
        font-family: inherit !important;
    }

    /* სათაურებს ვუტოვებთ შესაბამის პროპორციას */
    .entry-content-wrap h2, .entry-content-wrap h2 span { font-size: 1.6rem !important; font-weight: 700 !important; margin-top: 2rem; color: #111 !important; }
    .entry-content-wrap h3, .entry-content-wrap h3 span { font-size: 1.4rem !important; font-weight: 700 !important; margin-top: 1.8rem; color: #111 !important; }
    
    .entry-content-wrap p { margin-bottom: 1.4rem !important; }
    
    .entry-content-wrap img { 
        max-width: 100%; 
        height: auto; 
        border-radius: 10px; 
        margin: 1.5rem 0; 
    }

    /* მობილური ეკრანებისთვის */
    @media (max-width: 768px) {
        .post-hero { height: 35vh; }
        .entry-content-wrap, .entry-content-wrap p, .entry-content-wrap span { font-size: 0.95rem !important; }
    }
</style>

<?php 
} else {
    echo "<div class='container py-5 text-center'><h3>პოსტი ვერ მოიძებნა</h3><a href='/' class='btn btn-primary mt-3'>მთავარზე დაბრუნება</a></div>";
}
get_footer();
?>