<?php get_header(); ?>

<main class="container py-5">
    <div class="row g-5">
        <div class="col-md-8">
            <?php if ( have_posts() ) : ?>
                <?php while ( have_posts() ) : the_post(); ?>
                    
                    <?php 
                    $thumbnail_url = get_the_post_thumbnail_url(); 
                    ?>

                    <?php if ( $thumbnail_url ): ?>
                        <header class="post-cover" style="background-image: url('<?php echo $thumbnail_url; ?>');">
                            <div class="post-cover-content">
                                <h1 class="post-cover-title"><?php the_title(); ?></h1>
                                <p class="post-cover-meta">
                                    <?php echo date('d M, Y', strtotime($ok_post_in_loop->post_date)); ?> by <?php the_author(); ?>
                                    <br>
                                    კატეგორია: <?php the_categories(); ?>
                                </p>
                            </div>
                        </header>
                        <article class="blog-post p-4">
                             <div class="post-content">
                                <?php the_content(); ?>
                            </div>
                            </article>
                    <?php else: ?>
                        <article class="blog-post p-4">
                            <h1 class="blog-post-title"><?php the_title(); ?></h1>
                            <p class="blog-post-meta">
                                <?php echo date('d M, Y', strtotime($ok_post_in_loop->post_date)); ?> by <?php the_author(); ?>
                                | კატეგორია: <?php the_categories(); ?>
                            </p>
                            <hr>
                            <div class="post-content">
                                <?php the_content(); ?>
                            </div>
                            </article>
                    <?php endif; ?>

                    <div class="post-tags mt-4">
                        <?php the_tags(); ?>
                    </div>

                    <?php comments_template(); ?>

                <?php endwhile; ?>
            <?php endif; ?>
        </div>

        <?php get_sidebar(); ?>
    </div>
</main>

<?php get_footer(); ?>