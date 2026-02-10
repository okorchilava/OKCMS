<?php
/**
 * მომხმარებლის რედაქტორი (FB Style Profile Edit) - Secure 🛡️
 * Features: Taller Cover, Text inside Cover, Modern UI.
 */

// 1. მენიუს რეგისტრაცია
add_ok_action('admin_menu', function() {
    add_submenu_page('ok-users', 'მომხმარებლის რედაქტირება', 'ახალი მომხმარებელი', 'create_users', 'ok-user-editor', 'ok_render_user_editor');
});

// 2. ლოგიკა და ვიზუალი
function ok_render_user_editor() {
    global $ok_db;

    $user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $is_edit = ($user_id > 0);
    $user    = null;

    if ($is_edit) {
        $user = $ok_db->get_row("SELECT * FROM ok_users WHERE id = " . $user_id);
        if (!$user) { echo '<div class="alert alert-danger m-4">მომხმარებელი არ მოიძებნა.</div>'; return; }
    } else {
        $user = (object)[
            'username' => '', 'email' => '', 'display_name' => '', 'user_role' => 'subscriber'
        ];
    }

    // --- შენახვა (დაცული 🛡️) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
        
        // 🛡️ უსაფრთხოების შემოწმება
        ok_sec_check('save_user_action');

        $username = trim($_POST['username']);
        $email    = trim($_POST['email']);
        $name     = trim($_POST['display_name']);
        $role     = $_POST['role']; 
        $pass     = $_POST['password'];

        if (empty($username) || empty($email)) {
            $error = "სახელი და ელ.ფოსტა სავალდებულოა.";
        } else {
            // 🛡️ SQL Injection-ისგან დაცვა: ვიყენებთ პარამეტრებს (?) პირდაპირი ჩასმის ნაცვლად
            $check_sql = "SELECT id FROM ok_users WHERE (username = ? OR email = ?)";
            $check_params = [$username, $email];

            if ($is_edit) {
                $check_sql .= " AND id != ?";
                $check_params[] = $user_id;
            }
            
            // შემოწმება (get_var-ს გადავცემთ პარამეტრებს)
            if ($ok_db->get_var($check_sql, $check_params)) {
                $error = "მომხმარებელი ან ელ.ფოსტა უკვე არსებობს.";
            } else {
                if ($is_edit) {
                    $sql = "UPDATE ok_users SET username=?, email=?, display_name=?, user_role=?";
                    $params = [$username, $email, $name, $role];
                    
                    if (!empty($pass)) {
                        $sql .= ", password=?";
                        $params[] = password_hash($pass, PASSWORD_DEFAULT);
                    }
                    $sql .= " WHERE id=?";
                    $params[] = $user_id;
                    
                    $ok_db->query($sql, $params);
                    $msg = "ცვლილებები შენახულია.";
                    $user = $ok_db->get_row("SELECT * FROM ok_users WHERE id = " . $user_id);
                } else {
                    if (empty($pass)) {
                        $error = "პაროლი სავალდებულოა ახალი მომხმარებლისთვის.";
                    } else {
                        $hash = password_hash($pass, PASSWORD_DEFAULT);
                        $ok_db->query(
                            "INSERT INTO ok_users (username, email, display_name, user_role, password, registered_at) VALUES (?, ?, ?, ?, ?, NOW())",
                            [$username, $email, $name, $role, $hash]
                        );
                        echo "<script>window.location.href='index.php?page=ok-user-editor&id=".$ok_db->insert_id."&msg=created';</script>";
                        exit;
                    }
                }
            }
        }
    }

    if (isset($error)) echo '<div class="alert alert-danger m-4 shadow-sm border-0"><i class="bi bi-exclamation-circle me-2"></i>'.$error.'</div>';
    if (isset($msg) || (isset($_GET['msg']) && $_GET['msg']=='created')) echo '<div class="alert alert-success m-4 shadow-sm border-0"><i class="bi bi-check-circle me-2"></i>მომხმარებელი შენახულია.</div>';

    // ვიზუალი
    $gravatar = "https://www.gravatar.com/avatar/" . md5(strtolower(trim($user->email))) . "?s=200&d=mp";
    $bg_color = '#' . substr(md5($user->display_name ?: $user->username), 0, 6);
    ?>

    <style>
        .profile-cover {
            height: 280px; 
            background: linear-gradient(135deg, <?php echo $bg_color; ?>, #1a252f);
            border-radius: 0 0 16px 16px;
            position: relative;
            margin-bottom: 60px;
        }
        .profile-avatar-container {
            position: absolute;
            bottom: -40px;
            left: 40px;
            padding: 5px;
            background: white;
            border-radius: 50%;
            z-index: 2;
        }
        .profile-avatar {
            width: 130px; height: 130px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
        }
        .profile-text-overlay {
            position: absolute;
            bottom: 30px;
            left: 190px;
            color: white;
            z-index: 1;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        .info-card {
            border: 1px solid #eee;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        }
        .info-label { font-size: 0.85rem; color: #6c757d; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 5px; }
        .info-value { font-size: 1.1rem; font-weight: 500; color: #212529; }
        .edit-btn { color: #0d6efd; cursor: pointer; font-size: 0.9rem; transition: 0.2s; text-decoration: none; }
        .edit-btn:hover { text-decoration: underline; }
    </style>

    <div class="container-fluid p-0">
        
        <div class="profile-cover shadow-sm">
            <div class="profile-avatar-container shadow">
                <img src="<?php echo $gravatar; ?>" class="profile-avatar">
            </div>
            
            <div class="profile-text-overlay">
                <h1 class="fw-bold mb-1 display-6"><?php echo htmlspecialchars($user->display_name ?: $user->username); ?></h1>
                <span class="badge bg-white bg-opacity-25 border border-white border-opacity-50 text-white px-3 py-2 rounded-pill fw-normal" style="backdrop-filter: blur(5px);">
                    <?php echo ucfirst(htmlspecialchars($user->user_role)); ?>
                </span>
            </div>
        </div>

        <div class="container pb-5">
            <div class="row">
                <form method="post" class="col-lg-9 mx-auto">
                    
                    <?php ok_nonce_field('save_user_action'); ?>
                    
                    <div class="info-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="info-label">მომხმარებლის დეტალები</div>
                                <div class="info-value"><?php echo htmlspecialchars($user->username ?: 'N/A'); ?></div>
                                <div class="text-muted mt-1"><?php echo htmlspecialchars($user->email); ?></div>
                            </div>
                            <a class="edit-btn" data-bs-toggle="collapse" href="#editBasicInfo"><i class="bi bi-pencil-square me-1"></i>რედაქტირება</a>
                        </div>

                        <div class="collapse mt-4 pt-3 border-top bg-light rounded p-3" id="editBasicInfo">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Username</label>
                                    <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user->username); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Email</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user->email); ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold">Display Name</label>
                                    <input type="text" name="display_name" class="form-control" value="<?php echo htmlspecialchars($user->display_name); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="info-label">სისტემური უფლებები</div>
                                <div class="info-value d-flex align-items-center gap-2">
                                    <i class="bi bi-shield-check text-success fs-4"></i>
                                    <?php echo ucfirst(htmlspecialchars($user->user_role)); ?>
                                </div>
                            </div>
                            <a class="edit-btn" data-bs-toggle="collapse" href="#editRole"><i class="bi bi-pencil-square me-1"></i>შეცვლა</a>
                        </div>

                        <div class="collapse mt-4 pt-3 border-top bg-light rounded p-3" id="editRole">
                            <label class="form-label small fw-bold">აირჩიეთ ახალი როლი</label>
                            <select name="role" class="form-select w-50">
                                <option value="subscriber" <?php echo ($user->user_role == 'subscriber') ? 'selected' : ''; ?>>Subscriber</option>
                                <option value="editor" <?php echo ($user->user_role == 'editor') ? 'selected' : ''; ?>>Editor</option>
                                <option value="admin" <?php echo ($user->user_role == 'admin') ? 'selected' : ''; ?>>Administrator</option>
                            </select>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="info-label">უსაფრთხოება</div>
                                <div class="info-value text-muted" style="letter-spacing: 3px;">••••••••••••</div>
                            </div>
                            <a class="edit-btn" data-bs-toggle="collapse" href="#editSecurity"><i class="bi bi-key me-1"></i>პაროლის შეცვლა</a>
                        </div>

                        <div class="collapse mt-4 pt-3 border-top bg-light rounded p-3" id="editSecurity">
                            <div class="d-flex align-items-center text-warning mb-2 small">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <span>თუ არ გსურთ პაროლის შეცვლა, დატოვეთ ეს ველი ცარიელი.</span>
                            </div>
                            <input type="password" name="password" class="form-control w-50" placeholder="შეიყვანეთ ახალი პაროლი">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 mb-5">
                        <a href="index.php?page=ok-users" class="btn btn-light border px-4 py-2 fw-medium">გაუქმება</a>
                        <button type="submit" name="save_user" class="btn btn-primary px-5 py-2 fw-bold shadow-sm">
                            <i class="bi bi-check-lg me-2"></i>შენახვა
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
    <?php
}