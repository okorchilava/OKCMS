<?php include 'header.php'; 

global $ok_query;
$show_sidebar = isset($ok_query['has_sidebar']) ? $ok_query['has_sidebar'] : false;
$perm_structure = get_ok_option('permalink_structure', 'plain');
$date_format = get_ok_option('date_format', 'd M, Y');

// ─────────────────────────────────────────────────────────────────────────────
// 1. თუ ეს არის თარიღის არქივი (Date Archive)
// ─────────────────────────────────────────────────────────────────────────────
if (isset($ok_query['is_date']) && $ok_query['is_date'] === true) {
    // როუტერმა უკვე მოგვაწოდა პოსტები, SQL აღარ გვინდა
    $posts = $ok_query['posts'];
    $title = $ok_query['object']->name;
    $desc  = $ok_query['object']->description;
    
    // ამ ეტაპზე პაგინაცია თარიღებზე გამორთულია (რადგან ყველა პოსტი მოგვაქვს ერთბაშად)
    $total_pages = 1; 
    $page = 1;
} 

// ─────────────────────────────────────────────────────────────────────────────
// 2. თუ ეს არის კატეგორია (Category Archive)
// ─────────────────────────────────────────────────────────────────────────────
elseif (isset($ok_query['object']) && isset($ok_query['object']->id)) {
    
    $category = $ok_query['object'];
    $title = htmlspecialchars($category->name);
    $desc  = htmlspecialchars($category->description);
    $cat_id = (int)$category->id;

    // პაგინაციის ლოგიკა მხოლოდ კატეგორიებისთვის
    $page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
    $per_page = (int)get_ok_option('posts_per_page', 10);
    $offset = ($page - 1) * $per_page;

    // SQL მოთხოვნები
    $count_sql = "SELECT COUNT(*) FROM ok_posts WHERE category_id = $cat_id AND post_type = 'post' AND post_status = 'published'";
    $total_posts = (int)$ok_db->get_var($count_sql);
    $total_pages = ceil($total_posts / $per_page);

    $posts_sql = "SELECT * FROM ok_posts WHERE category_id = $cat_id AND post_type = 'post' AND post_status = 'published' ORDER BY post_date DESC LIMIT $per_page OFFSET $offset";
    $posts = $ok_db->get_results($posts_sql);

} 
// 3. თუ არცერთია (შეცდომა)
else {
    echo "<div class='container py-5 text-center'><h3>არქივი ვერ მოიძებნა.</h3></div>"; 
    include 'footer.php'; 
    exit;
}
?>

<div class="container py-5">
    
    <div class="row mb-5">
        <div class="col-12 text-center">
            <span class="badge bg-primary bg-opacity-10 text-primary mb-2 px-3 py-2 rounded-pill">
                <?php echo isset($ok_query['is_date']) ? 'თარიღი' : 'კატეგორია'; ?>
            </span>
            <h1 class="fw-bold display-5"><?php echo $title; ?></h1>
            <?php if(!empty($desc)): ?>
                <p class="text-muted mt-2 lead"><?php echo $desc; ?></p>
            <?php endif; ?>
            <hr class="mx-auto mt-4" style="width: 60px; height: 3px; background-color: #0d6efd; opacity: 1;">
        </div>
    </div>

    <div class="row <?php echo $show_sidebar ? '' : 'justify-content-center'; ?>">
        
        <div class="<?php echo $show_sidebar ? 'col-lg-8' : 'col-lg-10'; ?>">
            <div class="row g-4">
                <?php if ($posts): foreach ($posts as $post):
                    $img = !empty($post->post_image) ? $post->post_image : ''; 
                    if ($perm_structure === 'plain') { $link = "/?p=" . $post->id; } elseif ($perm_structure === 'post_name') { $link = "/" . $post->post_name; } else { $link = str_replace(['%postname%', '%post_id%'], [$post->post_name, $post->id], $perm_structure); $link = '/' . ltrim($link, '/'); }
                ?>
                    <div class="<?php echo $show_sidebar ? 'col-md-6' : 'col-md-4'; ?>">
                        <article class="card h-100 shadow-sm border-0 hover-shadow transition">
                            <?php if($img): ?>
                            <a href="<?php echo $link; ?>" class="overflow-hidden card-img-wrapper">
                                <img src="<?php echo $img; ?>" class="card-img-top zoom-effect" alt="<?php echo htmlspecialchars($post->post_title); ?>" style="height: 220px; width: 100%; object-fit: cover;">
                            </a>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <div class="small text-muted mb-2"><i class="bi bi-calendar3 me-1"></i> <?php echo ok_date_ka($date_format, strtotime($post->post_date)); ?></div>
                                <h3 class="h5 card-title fw-bold mb-3"><a href="<?php echo $link; ?>" class="text-dark text-decoration-none stretched-link"><?php echo htmlspecialchars($post->post_title); ?></a></h3>
                                <div class="card-text text-secondary line-clamp-3 mb-4"><?php echo mb_substr(strip_tags($post->post_content), 0, 150) . '...'; ?></div>
                                <div class="mt-auto pt-3 border-top d-flex align-items-center justify-content-between"><span class="small text-primary fw-bold">სრულად <i class="bi bi-arrow-right"></i></span></div>
                            </div>
                        </article>
                    </div>
                <?php endforeach; else: ?>
                    <div class="col-12 text-center py-5"><p class="text-muted">პოსტები არ არის.</p></div>
                <?php endif; ?>
            </div>

            <?php if ($total_pages > 1): ?>
            <div class="row mt-5"><div class="col-12 d-flex justify-content-center"><nav><ul class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>"><a class="page-link" href="?p=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                <?php endfor; ?>
            </ul></nav></div></div>
            <?php endif; ?>
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

</main>
<?php include 'footer.php'; ?>