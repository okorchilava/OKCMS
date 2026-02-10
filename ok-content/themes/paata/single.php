<?php get_header(); ?>

<main class="container py-5">
    <div class="row g-5 justify-content-center">
        <div class="col-lg-8">
            <?php if ( have_posts() ) : ?>
                <?php while ( have_posts() ) : the_post(); ?>
                    
                    <article class="blog-post">
                        <?php 
                        $thumbnail_url = get_the_post_thumbnail_url(); 
                        $author_display_option = get_post_meta($ok_post_in_loop->id, '_author_display_option', true);
                        $show_author = ($author_display_option !== 'hide');
                        ?>

                        <?php if ( $thumbnail_url ): ?>
                            <!-- ვერსია გამორჩეული სურათით (ქავერით) -->
                            <header class="post-cover" style="background-image: url('<?php echo htmlspecialchars($thumbnail_url, ENT_QUOTES, 'UTF-8'); ?>');">
                                <div class="post-cover-content">
                                    <h1 class="post-cover-title"><?php the_title(); ?></h1>
                                    <p class="post-cover-meta">
                                        <?php echo date('d M, Y', strtotime($ok_post_in_loop->post_date)); ?>
                                        <?php if ($show_author): ?> by <?php the_author(); ?> <?php endif; ?>
                                        <br>კატეგორია: <?php the_categories(); ?>
                                    </p>
                                </div>
                            </header>
                            <div class="p-4">
                               <div class="post-content">
                                   <?php the_content(); ?>
                               </div>
                            </div>
                        <?php else: ?>
                            <!-- ვერსია სურათის გარეშე -->
                            <div class="p-4">
                                <h1 class="blog-post-title"><?php the_title(); ?></h1>
                                <p class="blog-post-meta">
                                    <?php echo date('d M, Y', strtotime($ok_post_in_loop->post_date)); ?>
                                    <?php if ($show_author): ?> by <?php the_author(); ?> <?php endif; ?>
                                    | კატეგორია: <?php the_categories(); ?>
                                </p>
                                <hr>
                                <div class="post-content">
                                    <?php the_content(); ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="post-footer-meta p-4 pt-0">
                            <?php
                            // ვამოწმებთ, არსებობს თუ არა სოც. ქსელების ფუნქცია, სანამ გამოვიძახებთ
                            if (function_exists('the_social_share_buttons')) {
                                the_social_share_buttons();
                            }
                            ?>
                            <div class="post-tags mt-4">
                                <?php the_tags(); ?>
                            </div>
                            <?php comments_template(); ?>
                        </div>

                    </article>

                <?php endwhile; ?>
            <?php endif; ?>
        </div>
        <?php get_sidebar(); ?>
    </div>
</main>

<?php get_footer(); ?>

