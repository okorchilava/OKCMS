<?php 
// 1. იძულებითი ჩატვირთვა (რომ ცვლადები შეივსოს)
if (function_exists('ok_setup_main_query')) {
    ok_setup_main_query();
}

get_header(); 
?>

<style>
    .section-title { font-weight: 800; color: #2c3e50; margin-bottom: 1rem; position: relative; display: inline-block; }
    .news-card { border-radius: 15px; background: #fff; overflow: hidden; transition: transform 0.2s; }
    .news-card:hover { transform: translateY(-5px); }
    .news-img { height: 220px; width: 100%; object-fit: cover; border-bottom: 1px solid rgba(0,0,0,0.05); display: block; }
    .news-link { color: #2c3e50; text-decoration: none; font-weight: 700; transition: color 0.2s; }
    .news-link:hover { color: #0d6efd; }
    .news-excerpt { color: #666; font-size: 0.95rem; line-height: 1.6; }
</style>

<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="section-title">სიახლეები</h1>
    </div>

    <div class="row g-4">
        <?php if (have_posts()): ?>
            
            <?php while (have_posts()): the_post(); ?>
                <div class="col-md-4">
                    <article class="card h-100 shadow-sm border-0 news-card">
                        <?php if (function_exists('the_post_image')) { the_post_image('thumbnail', ['class' => 'card-img-top news-img']); } ?>
                        <div class="card-body p-4 d-flex flex-column">
                            <h3 class="h5 mb-3"><a href="<?php the_permalink(); ?>" class="news-link"><?php the_title(); ?></a></h3>
                            <div class="card-text news-excerpt mb-3">
                                <?php echo mb_substr(strip_tags($GLOBALS['post']->post_content ?? ''), 0, 120) . '...'; ?>
                            </div>
                            <div class="mt-auto">
                                <a href="<?php the_permalink(); ?>" class="btn btn-sm btn-outline-primary w-100">სრულად</a>
                            </div>
                        </div>
                    </article>
                </div>
            <?php endwhile; ?>

            <div class="col-12 mt-5">
                <?php 
                if (function_exists('ok_pagination')) {
                    ok_pagination(); 
                } else {
                    echo '<div class="alert alert-danger">ფუნქცია ok_pagination ვერ მოიძებნა!</div>';
                }
                ?>
            </div>

        <?php else: ?>
            <div class="col-12 text-center py-5">
                <div class="alert alert-light border d-inline-block text-muted mb-0">პოსტები არ არის.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>