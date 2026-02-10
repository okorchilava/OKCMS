<?php get_header(); ?>

<main class="container py-5">
    <div class="row g-5">
        <div class="col-md-8">
            <h2 class="pb-3 mb-4 text-center fst-italic border-bottom">
                ჩვენი ბლოგი
            </h2>

            <?php if ( have_posts() ) : ?>
                <?php while ( have_posts() ) : the_post(); ?>
                    <?php $has_thumb_class = get_the_post_thumbnail_url() ? 'has-thumbnail' : ''; ?>
                    <article class="blog-post mb-5 <?php echo $has_thumb_class; ?>">
                        <div class="row g-0 h-100">
                            <div class="col-md-4">
                                <?php if (get_the_post_thumbnail_url()): ?>
                                    <a href="<?php the_permalink(); ?>" class="d-block h-100">
                                        <?php the_post_thumbnail('img-fluid rounded-start h-100', ['style' => 'object-fit: cover;']); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-8 d-flex flex-column p-4">
                                <h3 class="blog-post-title mb-1"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                                <p class="blog-post-meta">
                                    <?php echo date('d M, Y', strtotime($ok_post_in_loop->post_date)); ?> by <?php the_author(); ?>
                                </p>
                                
                                <div class="post-excerpt flex-grow-1">
                                    <?php the_excerpt(120); // ვიძახებთ ერთიან ფუნქციას ?>
                                </div>
                                
                                <div class="post-meta text-muted mt-auto pt-2">
                                     <small>კატეგორია: <?php the_categories(); ?></small>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php else : ?>
                <p>პოსტები ვერ მოიძებნა.</p>
            <?php endif; ?>
        </div>
        
        <?php get_sidebar(); ?>
    </div>
</main>

<?php get_footer(); ?>