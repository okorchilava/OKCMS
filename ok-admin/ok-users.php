<?php
/**
 * მომხმარებლების სია (Users List) - Secure 🛡️
 */

// 1. მენიუს რეგისტრაცია
add_ok_action('admin_menu', function() {
    add_menu_page('მომხმარებლები', 'მომხმარებლები', 'edit_users', 'ok-users', 'ok_render_users', 'bi bi-people', 30);
    add_submenu_page('ok-users', 'ყველა მომხმარებელი', 'ყველა მომხმარებელი', 'edit_users', 'ok-users', 'ok_render_users');
});

// 2. ვიზუალი და ლოგიკა
function ok_render_users() {
    global $ok_db;

    // --- წაშლა (დაცული 🛡️) ---
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $del_id = (int)$_GET['id'];
        
        // 🛡️ უსაფრთხოების შემოწმება GET მოთხოვნაზე
        // Action სახელი უნიკალურია თითოეული მომხმარებლისთვის: 'delete_user_1', 'delete_user_2' ...
        if (function_exists('check_admin_referer')) {
            check_admin_referer('delete_user_' . $del_id);
        }

        // საკუთარი თავის წაშლის აკრძალვა
        if ($del_id == $_SESSION['user_id']) {
            echo '<div class="alert alert-warning shadow-sm border-0 mb-4"><i class="bi bi-exclamation-triangle me-2"></i>საკუთარი თავის წაშლა შეუძლებელია.</div>';
        } else {
            $ok_db->query("DELETE FROM ok_users WHERE id = ?", [$del_id]);
            echo '<div class="alert alert-success shadow-sm border-0 mb-4"><i class="bi bi-check-circle me-2"></i>მომხმარებელი წაიშალა.</div>';
        }
    }

    // --- მონაცემები ---
    $per_page = 20;
    $current_page = isset($_GET['paged']) ? max(1, (int)$_GET['paged']) : 1;
    $offset = ($current_page - 1) * $per_page;
    
    $search_query = isset($_GET['s']) ? trim($_GET['s']) : '';
    $where_sql = "";

    if (!empty($search_query)) {
        $safe_search = addslashes($search_query);
        $where_sql = "WHERE username LIKE '%$safe_search%' OR email LIKE '%$safe_search%' OR display_name LIKE '%$safe_search%'";
    }

    $total_users = (int)$ok_db->get_var("SELECT COUNT(*) FROM ok_users $where_sql");
    $total_pages = ceil($total_users / $per_page);
    
    $users = $ok_db->get_results("SELECT * FROM ok_users $where_sql ORDER BY id DESC LIMIT $per_page OFFSET $offset");
    ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-baseline">
            <h1 class="h3 mb-0 text-dark fw-bold">მომხმარებლები</h1>
            <span class="text-muted small ms-3">სულ: <?php echo $total_users; ?></span>
        </div>
        <a href="index.php?page=ok-user-editor" class="btn btn-primary btn-sm px-3 rounded-pill shadow-sm">
            <i class="bi bi-person-plus me-1"></i>დამატება
        </a>
    </div>

    <div class="bg-white p-2 rounded shadow-sm mb-3 border d-flex justify-content-between align-items-center">
        <ul class="nav nav-pills nav-sm small">
            <li class="nav-item">
                <a class="nav-link active bg-light text-dark fw-bold border py-1 px-3" href="index.php?page=ok-users">ყველა</a>
            </li>
        </ul>
        <form method="get" class="d-flex">
            <input type="hidden" name="page" value="ok-users">
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
                        <th class="ps-4 py-3 border-bottom" style="width: 50px;"><i class="bi bi-person-circle"></i></th>
                        <th class="py-3 border-bottom">მომხმარებელი</th>
                        <th class="py-3 border-bottom">სახელი</th>
                        <th class="py-3 border-bottom">ელ.ფოსტა</th>
                        <th class="py-3 border-bottom">როლი</th>
                        <th class="py-3 border-bottom text-end pe-4">რეგისტრირებული</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    <?php if ($users): foreach ($users as $user): 
                        $edit_url = "index.php?page=ok-user-editor&id=" . $user->id;
                        
                        // 🛡️ წაშლის ლინკის დაცვა
                        // ვქმნით Nonce-ს კონკრეტულად ამ მომხმარებლის წაშლისთვის
                        $del_nonce = ok_create_nonce('delete_user_' . $user->id);
                        $del_url   = "index.php?page=ok-users&action=delete&id=" . $user->id . "&_ok_nonce=" . $del_nonce;
                        
                        $gravatar = "https://www.gravatar.com/avatar/" . md5(strtolower(trim($user->email))) . "?s=64&d=mp";
                    ?>
                    <tr class="group-action-hover">
                        <td class="ps-4">
                            <img src="<?php echo $gravatar; ?>" alt="img" class="rounded-circle" width="32" height="32">
                        </td>
                        <td>
                            <div class="mb-1">
                                <a href="<?php echo $edit_url; ?>" class="fw-bold text-dark text-decoration-none">
                                    <?php echo htmlspecialchars($user->username); ?>
                                </a>
                            </div>
                            <div class="small opacity-75 row-actions">
                                <a href="<?php echo $edit_url; ?>" class="text-primary text-decoration-none">რედაქტირება</a>
                                <?php if ($user->id != $_SESSION['user_id']): ?>
                                    <span class="text-muted mx-1">|</span>
                                    <a href="<?php echo $del_url; ?>" class="text-danger text-decoration-none" onclick="return confirm('წავშალოთ მომხმარებელი?');">წაშლა</a>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($user->display_name); ?></td>
                        <td><a href="mailto:<?php echo htmlspecialchars($user->email); ?>" class="text-secondary text-decoration-none small"><?php echo htmlspecialchars($user->email); ?></a></td>
                        <td>
                            <span class="badge bg-light text-dark border fw-normal">
                                <?php echo ucfirst($user->user_role ?? 'subscriber'); ?>
                            </span>
                        </td>
                        <td class="text-end pe-4 small text-muted">
                            <?php echo date('d.m.Y', strtotime($user->registered_at ?? 'now')); ?>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">მომხმარებლები არ მოიძებნა</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_pages > 1): ?>
        <div class="card-footer bg-white py-2 border-top d-flex justify-content-between align-items-center">
            <div class="small text-muted">ნაჩვენებია <?php echo count($users); ?> / <?php echo $total_users; ?></div>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($current_page == $i) ? 'active' : ''; ?>">
                            <a class="page-link border-0 rounded mx-1 <?php echo ($current_page == $i) ? 'bg-primary text-white' : 'text-dark bg-transparent'; ?>" href="index.php?page=ok-users&paged=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
    <?php
}