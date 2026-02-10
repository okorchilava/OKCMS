<?php include 'header.php'; 
$post = $ok_query['object']; 
$show_sidebar = isset($ok_query['has_sidebar']) ? $ok_query['has_sidebar'] : false;
$date_format = get_ok_option('date_format', 'd M, Y');
$has_cover = !empty($post->post_image);

// კატეგორია
$category = null;
if (!empty($post->category_id)) {
    $category = $ok_db->get_row("SELECT name, slug FROM ok_categories WHERE id = " . (int)$post->category_id);
}
?>

<?php if ($has_cover): ?>
    <div class="post-hero position-relative mb-5" style="background-image: url('<?php echo htmlspecialchars($post->post_image); ?>');">
        <div class="container h-100">
            <div class="row h-100 align-items-end justify-content-center pb-5">
                <div class="col-lg-10"> 
                    <div class="hero-content text-start text-white">
                        <h1 class="fw-bold fs-2 mb-2 text-shadow lh-sm"><?php echo htmlspecialchars($post->post_title); ?></h1>
                        <div class="text-white fw-bold small text-shadow mb-3"><i class="bi bi-calendar3 me-2"></i> <?php echo ok_date_ka($date_format, strtotime($post->post_date)); ?></div>
                        <?php if ($category): ?>
                            <div><a href="/<?php echo $category->slug; ?>" class="cat-badge text-white text-decoration-none px-3 py-1 rounded-pill small backdrop-blur border border-white border-opacity-50"><?php echo htmlspecialchars($category->name); ?></a></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="container mt-5 mb-4">
        <div class="row justify-content-center">
            <div class="col-lg-10 text-center">
                <h1 class="fw-bold fs-2 mb-2"><?php echo htmlspecialchars($post->post_title); ?></h1>
                <div class="text-muted fw-light small mb-3"><i class="bi bi-calendar3 me-1"></i> <?php echo ok_date_ka($date_format, strtotime($post->post_date)); ?></div>
                <?php if ($category): ?>
                    <div class="mb-3"><a href="/<?php echo $category->slug; ?>" class="badge bg-secondary bg-opacity-10 text-secondary text-decoration-none border border-secondary border-opacity-10 rounded-pill px-3 py-2 fw-normal"><?php echo htmlspecialchars($category->name); ?></a></div>
                <?php endif; ?>
                <hr class="mt-4 mx-auto" style="width: 100px; height: 3px; background-color: #0d6efd; opacity: 1;">
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="container my-5">
    <div class="row <?php echo $show_sidebar ? '' : 'justify-content-center'; ?>">
        
        <div class="<?php echo $show_sidebar ? 'col-lg-8' : 'col-lg-10'; ?>">
            <article class="blog-post">
                <section class="post-content">
                    <?php echo ok_do_shortcode($post->post_content); ?>
                </section>
            </article>
        </div>

        <?php if ($show_sidebar): ?>
            <div class="col-lg-4">
                <div class="ps-lg-4 mt-5 mt-lg-0">
                    <?php include 'sidebar.php'; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<style>
    .post-hero { height: 50vh; min-height: 350px; background-size: cover; background-position: center; background-repeat: no-repeat; position: relative; }
    .text-shadow { text-shadow: 2px 2px 8px rgba(0,0,0,0.9); }
    .cat-badge { background: rgba(0, 0, 0, 0.4); transition: all 0.3s ease; font-weight: 500; letter-spacing: 0.5px; }
    .cat-badge:hover { background: rgba(0, 0, 0, 0.7); color: #fff; }
    .backdrop-blur { backdrop-filter: blur(3px); }
    .post-content { font-size: 1rem; line-height: 1.8; color: #333; }
    .post-content p { margin-bottom: 1.5rem; }
    .post-content img { max-width: 100%; height: auto; border-radius: 8px; margin: 30px 0; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
    .post-content h2 { margin-top: 2.5rem; font-weight: 800; color: #1a1a1a; }
    .post-content blockquote { border-left: 5px solid #0d6efd; padding: 20px 30px; margin: 30px 0; font-style: italic; background: #f8f9fa; color: #555; }
</style>

</main>
<?php include 'footer.php'; ?>