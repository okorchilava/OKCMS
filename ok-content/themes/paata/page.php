<?php get_header(); ?>

<?php
// [!] შესწორება: ვიძახებთ გლობალურ ცვლადს, რომელიც როუტერმა შექმნა
global $ok_post;

// ვქმნით $post ცვლადს, რომ ქვედა კოდმა იმუშაოს
$post = $ok_post;

// უსაფრთხოება: ვამოწმებთ, არსებობს თუ არა პოსტი
if ($post) {
    // წამოიღე ველი "show_sidebar" ამ გვერდისთვის
    $show_sidebar = get_post_meta($post->id, 'show_sidebar', true);
} else {
    $show_sidebar = 'yes';
}

// fallback: თუ meta ცარიელია, ვთვლით რომ sidebar უნდა გამოჩნდეს
if ($show_sidebar === '' || $show_sidebar === null) {
    $show_sidebar = 'yes';
}
?>

<main class="container py-5">
    <div class="row g-5">

        <div class="<?php echo ($show_sidebar === 'yes') ? 'col-md-8' : 'col-12'; ?>">
            <?php if (function_exists('have_posts') && have_posts()) : ?>
                <?php while (have_posts()) : the_post(); ?>
                    
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
            <?php else: ?>
                <article class="blog-post">
                    <div class="blog-post-content">
                        <h1 class="blog-post-title"><?php echo htmlspecialchars($post->post_title ?? ''); ?></h1>
                        <hr>
                        <div class="post-content">
                            <?php echo $post->post_content ?? ''; ?>
                        </div>
                    </div>
                </article>
            <?php endif; ?>
        </div>

        <?php if ($show_sidebar === 'yes') : ?>
             <div class="col-md-4">
                <?php 
                if (function_exists('get_sidebar')) {
                    get_sidebar(); 
                } else {
                    echo '<div class="p-4 mb-3 bg-light rounded"><h4 class="fst-italic">Sidebar</h4><p>აქ იქნება ვიჯეტები.</p></div>';
                }
                ?>
             </div>
        <?php endif; ?>

    </div>
</main>

<?php get_footer(); ?>