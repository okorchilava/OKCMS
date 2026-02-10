<?php get_header(); ?>

<main class="container py-5">
    <div class="row g-5">
        <div class="col-md-8">
            <?php if ( have_posts() ) : ?>
                <?php while ( have_posts() ) : the_post(); ?>
                    
                    <article class="blog-post">
                        <div class="blog-post-content">
                            <h1 class="blog-post-title"><?php the_title(); ?></h1>
                            <hr>
                            <div class="post-content">
                                <?php the_content(); ?>
                            </div>
                        </div>
                    </article>

                <?php endwhile; ?>
            <?php endif; // <<-- ეს ხაზი იყო გამორჩენილი ?>
        </div> <?php get_sidebar(); ?>

    </div> </main>

<?php get_footer(); ?>