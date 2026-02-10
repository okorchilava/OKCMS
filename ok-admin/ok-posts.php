<?php
/**
 * პოსტების მართვა (With Categories & Author Filter)
 */

// 1. მენიუ
    add_ok_action('admin_menu', function() {
        add_menu_page('პოსტები', 'პოსტები', 'edit_posts', 'ok-posts', 'ok_render_posts', 'bi bi-pin-angle', 25);
        add_submenu_page('ok-posts', 'ყველა პოსტი', 'ყველა პოსტი', 'edit_posts', 'ok-posts', 'ok_render_posts');
    });

function ok_get_post_image_src($post) {
    if (!empty($post->post_image)) return $post->post_image;
    if (!empty($post->img)) return $post->img; 
    return null;
}

// 2. გვერდი
function ok_render_posts() {
    global $ok_db;

    // A. Delete
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $delete_id = (int)$_GET['id'];
        $ok_db->query("DELETE FROM ok_posts WHERE id = ?", [$delete_id]);
        echo '<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4"><i class="bi bi-check-circle-fill me-2"></i> წაიშალა.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }

    // B. Data Prep
    $per_page = 15;
    $current_page = isset($_GET['paged']) ? max(1, (int)$_GET['paged']) : 1;
    $offset = ($current_page - 1) * $per_page;
    
    // საწყისი პირობა
    $where_sql = "WHERE p.post_type = 'post'";

    // [!] ძებნა
    $search_query = isset($_GET['s']) ? trim($_GET['s']) : '';
    if (!empty($search_query)) {
        $safe_search = addslashes($search_query);
        $where_sql .= " AND (p.post_title LIKE '%$safe_search%' OR p.post_content LIKE '%$safe_search%')";
    }

    // [!] ავტორის ფილტრი
    $author_filter = isset($_GET['author']) ? (int)$_GET['author'] : 0;
    if ($author_filter > 0) {
        $where_sql .= " AND p.post_author = $author_filter";
    }

    // [!] კატეგორიის ფილტრი (ახალი)
    $cat_filter = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
    if ($cat_filter > 0) {
        $where_sql .= " AND p.category_id = $cat_filter";
    }

    // Count
    $total_posts = (int)$ok_db->get_var("SELECT COUNT(*) FROM ok_posts p $where_sql");
    $total_pages = ceil($total_posts / $per_page);

    // 🛑 Fetch (JOIN with Categories & Users)
    // ვიღებთ პოსტს, ავტორის სახელს და კატეგორიის სახელს ერთ მოთხოვნაში
    $query = "
        SELECT 
            p.*, 
            u.display_name as author_name,
            c.name as category_name,
            c.id as cat_id
        FROM ok_posts p
        LEFT JOIN ok_users u ON p.post_author = u.id
        LEFT JOIN ok_categories c ON p.category_id = c.id
        $where_sql 
        ORDER BY p.post_date DESC 
        LIMIT $per_page OFFSET $offset
    ";
    
    $posts = $ok_db->get_results($query);
    $perm_struct = get_ok_option('permalink_structure', 'plain');
    
    // კატეგორიები ფილტრისთვის
    $categories = $ok_db->get_results("SELECT id, name FROM ok_categories ORDER BY name ASC");
    ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-baseline flex-wrap gap-2">
            <h1 class="h3 mb-0 text-dark fw-bold">პოსტები</h1>
            <span class="text-muted small ms-2">სულ: <?php echo $total_posts; ?></span>
            
            <?php if ($author_filter > 0): 
                $filter_name = $ok_db->get_var("SELECT display_name FROM ok_users WHERE id = $author_filter");     
            ?>
                <span class="badge bg-secondary">
                    ავტორი: <?php echo htmlspecialchars($filter_name); ?>
                    <a href="index.php?page=ok-posts" class="text-white ms-1 text-decoration-none"><i class="bi bi-x"></i></a>
                </span>
            <?php endif; ?>

            <?php if ($cat_filter > 0): 
                $cat_name = $ok_db->get_var("SELECT name FROM ok_categories WHERE id = $cat_filter");     
            ?>
                <span class="badge bg-info text-dark">
                    კატეგორია: <?php echo htmlspecialchars($cat_name); ?>
                    <a href="index.php?page=ok-posts" class="text-dark ms-1 text-decoration-none"><i class="bi bi-x"></i></a>
                </span>
            <?php endif; ?>
        </div>
        <a href="index.php?page=ok-post-editor" class="btn btn-primary btn-sm px-3 rounded-pill shadow-sm">
            <i class="bi bi-plus-lg me-1"></i>ახალი პოსტი
        </a>
    </div>

    <div class="bg-white p-2 rounded shadow-sm mb-3 border d-flex justify-content-between align-items-center flex-wrap gap-2">
        <ul class="nav nav-pills nav-sm small">
            <li class="nav-item">
                <a class="nav-link <?php echo (empty($search_query) && $author_filter == 0 && $cat_filter == 0) ? 'active bg-light text-dark fw-bold border' : 'text-muted'; ?> py-1 px-3" href="index.php?page=ok-posts">ყველა</a>
            </li>
            <li class="nav-item ms-2">
                <select class="form-select form-select-sm border-0 bg-light" onchange="if(this.value) window.location.href=this.value">
                    <option value="index.php?page=ok-posts">-- კატეგორია --</option>
                    <?php if($categories): foreach($categories as $cat): ?>
                        <option value="index.php?page=ok-posts&cat=<?php echo $cat->id; ?>" <?php echo ($cat_filter == $cat->id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat->name); ?>
                        </option>
                    <?php endforeach; endif; ?>
                </select>
            </li>
        </ul>
        <form method="get" class="d-flex">
            <input type="hidden" name="page" value="ok-posts">
            <?php if($author_filter) echo '<input type="hidden" name="author" value="'.$author_filter.'">'; ?>
            <?php if($cat_filter) echo '<input type="hidden" name="cat" value="'.$cat_filter.'">'; ?>
            <div class="input-group input-group-sm" style="width: 250px;">
                <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="s" class="form-control border-start-0 ps-0 bg-transparent" placeholder="ძებნა..." value="<?php echo htmlspecialchars($search_query); ?>">
            </div>
        </form>
    </div>

    <div class="card border shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-uppercase text-muted small" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                    <tr>
                        <th class="ps-4 py-3 border-bottom" style="width: 40px;"><input class="form-check-input" type="checkbox"></th>
                        <th class="py-3 border-bottom text-center" style="width: 60px;"><i class="bi bi-image"></i></th>
                        <th class="py-3 border-bottom" style="min-width: 250px;">სათაური</th>
                        <th class="py-3 border-bottom">ავტორი</th>
                        <th class="py-3 border-bottom">კატეგორია</th> <th class="py-3 border-bottom">სტატუსი</th>
                        <th class="py-3 border-bottom text-end pe-4">თარიღი</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    <?php if ($posts): foreach ($posts as $post): 
                        $edit_url = "index.php?page=ok-post-editor&id=" . $post->id;
                        $delete_url = "index.php?page=ok-posts&action=delete&id=" . $post->id;
                        
                        if ($perm_struct === 'post_name' && !empty($post->post_name)) {
                            $view_url = "../" . $post->post_name;
                        } else {
                            $view_url = "../index.php?p=" . $post->id;
                        }

                        $img_src = ok_get_post_image_src($post);
                    ?>
                    <tr class="group-action-hover">
                        <td class="ps-4"><input class="form-check-input" type="checkbox" value="<?php echo $post->id; ?>"></td>
                        
                        <td class="text-center">
                            <?php if ($img_src): ?>
                                <div class="rounded border bg-light d-inline-block overflow-hidden" style="width: 40px; height: 40px;">
                                    <img src="<?php echo htmlspecialchars($img_src); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                            <?php else: ?>
                                <div class="rounded border bg-light d-inline-block d-flex align-items-center justify-content-center text-muted" style="width: 40px; height: 40px;">
                                    <i class="bi bi-card-image" style="font-size: 1.2rem; opacity: 0.5;"></i>
                                </div>
                            <?php endif; ?>
                        </td>

                        <td class="py-3">
                            <div class="mb-1">
                                <a href="<?php echo $edit_url; ?>" class="fw-bold text-dark text-decoration-none" style="font-size: 0.95rem;">
                                    <?php echo htmlspecialchars($post->post_title); ?>
                                </a>
                                <?php if ($post->post_status === 'draft'): ?>
                                    <span class="badge bg-warning text-dark ms-1" style="font-size: 0.65em;">Draft</span>
                                <?php endif; ?>
                            </div>
                            <div class="small opacity-75 row-actions">
                                <a href="<?php echo $edit_url; ?>" class="text-primary text-decoration-none">რედაქტირება</a>
                                <span class="text-muted mx-1">|</span>
                                <a href="<?php echo $delete_url; ?>" class="text-danger text-decoration-none" onclick="return confirm('ნამდვილად გსურთ წაშლა?');">წაშლა</a>
                                <span class="text-muted mx-1">|</span>
                                <a href="<?php echo $view_url; ?>" target="_blank" class="text-secondary text-decoration-none">ნახვა</a>
                            </div>
                        </td>
                        
                        <td>
                            <div class="d-flex align-items-center">
                                <a href="index.php?page=ok-posts&author=<?php echo $post->post_author; ?>" class="small text-dark text-decoration-none">
                                    <?php echo htmlspecialchars($post->author_name ?? 'Admin'); ?>
                                </a>
                            </div>
                        </td>

                        <td>
                            <?php if (!empty($post->category_name)): ?>
                                <a href="index.php?page=ok-posts&cat=<?php echo $post->cat_id; ?>" class="badge bg-light text-dark border text-decoration-none fw-normal">
                                    <?php echo htmlspecialchars($post->category_name); ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if ($post->post_status === 'published'): ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-10 rounded-pill px-2 fw-normal"><i class="bi bi-check-circle-fill me-1" style="font-size: 0.7rem;"></i> აქტიური</span>
                            <?php else: ?>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-10 rounded-pill px-2 fw-normal"><i class="bi bi-file-earmark me-1" style="font-size: 0.7rem;"></i> დრაფტი</span>
                            <?php endif; ?>
                        </td>

                        <td class="text-end pe-4">
                            <div class="small text-dark"><?php echo ok_date('d.m.Y', strtotime($post->post_date)); ?></div>
                            <div class="text-muted" style="font-size: 0.75rem;">
                                <?php echo ($post->post_status === 'published') ? 'გამოქვეყნდა' : 'ბოლო ცვლილება'; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted">პოსტები არ მოიძებნა</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="card-footer bg-white py-2 border-top d-flex justify-content-between align-items-center">
            <div class="small text-muted">ნაჩვენებია <?php echo count($posts); ?> / <?php echo $total_posts; ?></div>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link border-0 bg-transparent text-secondary" href="index.php?page=ok-posts&paged=<?php echo $current_page - 1; ?>&s=<?php echo urlencode($search_query); ?>&author=<?php echo $author_filter; ?>&cat=<?php echo $cat_filter; ?>"><i class="bi bi-chevron-left"></i></a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($current_page == $i) ? 'active' : ''; ?>">
                        <a class="page-link border-0 rounded mx-1 <?php echo ($current_page == $i) ? 'bg-primary text-white' : 'text-dark bg-transparent'; ?>" href="index.php?page=ok-posts&paged=<?php echo $i; ?>&s=<?php echo urlencode($search_query); ?>&author=<?php echo $author_filter; ?>&cat=<?php echo $cat_filter; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link border-0 bg-transparent text-secondary" href="index.php?page=ok-posts&paged=<?php echo $current_page + 1; ?>&s=<?php echo urlencode($search_query); ?>&author=<?php echo $author_filter; ?>&cat=<?php echo $cat_filter; ?>"><i class="bi bi-chevron-right"></i></a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
    <?php
}