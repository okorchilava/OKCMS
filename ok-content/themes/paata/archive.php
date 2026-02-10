<?php get_header(); ?>

<main class="container py-5">
    <div class="row g-5">
        <div class="col-md-8">
            <h2 class="pb-3 mb-4 fst-italic border-bottom">
                <?php the_archive_title(); ?>
            </h2>

            <div class="row g-4">
                <?php if ( have_posts() ) : ?>
                    <?php while ( have_posts() ) : the_post(); ?>
                        <div class="col-12">
                            <article class="card shadow-sm border-0 blog-post-card overflow-hidden">
                                <div class="row g-0">
                                    <div class="col-md-5 col-lg-4">
                                        <?php if (get_the_post_thumbnail_url()): ?>
                                            <a href="<?php the_permalink(); ?>" class="d-block" style="height: 100%;">
                                                <img src="<?php echo get_the_post_thumbnail_url(); ?>" class="img-fluid" alt="<?php the_title(); ?>" style="height: 100%; width: 100%; object-fit: cover;">
                                            </a>
                                        <?php else: ?>
                                            <a href="<?php the_permalink(); ?>" class="d-flex align-items-center justify-content-center bg-light text-decoration-none" style="height: 100%; min-height: 150px;">
                                                <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-7 col-lg-8">
                                        <div class="card-body d-flex align-items-center h-100 p-4">
                                            <h5 class="card-title blog-post-title">
                                                <a href="<?php the_permalink(); ?>" class="text-decoration-none text-dark stretched-link"><?php the_title(); ?></a>
                                            </h5>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        </div>
                    <?php endwhile; ?>
                <?php else : ?>
                    <div class="col-12">
                        <p>პოსტები ვერ მოიძებნა.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php get_sidebar(); ?>
    </div>
</main>

<?php get_footer(); ?>

