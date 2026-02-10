<?php
/**
 * ჩემი პროფილი (Final Polished Version)
 * Layout: Left (Activity/Wide) | Right (Forms/Narrow)
 */

// 1. მენიუს რეგისტრაცია (სახელი გასწორდა: add_ok_action)
add_ok_action('admin_menu', function() {
    add_menu_page(
        'ჩემი პროფილი',      // Page Title
        'პროფილი',           // Menu Title
        'read',              // Capability
        'ok-profile',        // Slug
        'ok_render_profile', // Callback
        'bi bi-person-circle', // Icon
        999                  // Position (ბოლოში)
    );
});

// 2. ლოგიკა და ვიზუალი
function ok_render_profile() {
    global $ok_db;
    $user_id = $_SESSION['user_id'];
    
    // --- განახლება ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
        $name  = trim($_POST['display_name']);
        $email = trim($_POST['email']);
        $pass  = $_POST['password'];
        
        $errors = [];
        if (empty($email)) $errors[] = "ელ.ფოსტა სავალდებულოა.";

        if (empty($errors)) {
            $sql = "UPDATE ok_users SET display_name = ?, email = ?";
            $params = [$name, $email];

            if (!empty($pass)) {
                $sql .= ", password = ?";
                $params[] = password_hash($pass, PASSWORD_DEFAULT);
            }

            $sql .= " WHERE id = ?";
            $params[] = $user_id;

            $ok_db->query($sql, $params);
            $_SESSION['display_name'] = $name; // სესიის განახლება
            
            echo '<div class="alert alert-success m-4 shadow-sm border-0"><i class="bi bi-check-circle-fill me-2"></i>პროფილი განახლდა წარმატებით!</div>';
        } else {
            foreach($errors as $err) {
                echo '<div class="alert alert-danger m-4 shadow-sm border-0"><i class="bi bi-exclamation-circle-fill me-2"></i>'.$err.'</div>';
            }
        }
    }

    // მონაცემების წამოღება
    $user = $ok_db->get_row("SELECT * FROM ok_users WHERE id = ?", [$user_id]);
    
    // Gravatar
    $grav_url = "https://www.gravatar.com/avatar/" . md5(strtolower(trim($user->email))) . "?s=128&d=mp";
    ?>

    <div class="container-fluid px-4 py-4">
        <div class="row g-4">
            
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100 overflow-hidden">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-activity text-primary me-2"></i>აქტივობა</h5>
                    </div>
                    <div class="card-body p-4 text-center text-muted">
                        <div class="py-5 bg-light rounded-3 border border-light border-dashed">
                            <i class="bi bi-bar-chart-steps fs-1 text-secondary opacity-25 mb-3 d-block"></i>
                            <p class="mb-0">აქტივობის ისტორია ჯერ ცარიელია.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center pt-5 pb-4 bg-primary bg-gradient text-white rounded-top" style="background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);">
                        <img src="<?php echo $grav_url; ?>" class="rounded-circle shadow-lg border border-4 border-white mb-3" width="96" height="96">
                        <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($user->display_name); ?></h5>
                        <p class="mb-0 opacity-75 small"><?php echo htmlspecialchars($user->email); ?></p>
                        <span class="badge bg-white text-primary mt-3 px-3 rounded-pill text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px;"><?php echo $user->role; ?></span>
                    </div>
                    
                    <form method="post" class="p-4">
                        <h6 class="text-uppercase text-muted fw-bold mb-4 small" style="letter-spacing: 1px;">პერსონალური ინფორმაცია</h6>
                        
                        <div class="mb-4">
                            <label class="form-label text-muted small fw-bold">სახელი</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                                <input type="text" name="display_name" class="form-control bg-light border-start-0 ps-0" value="<?php echo htmlspecialchars($user->display_name); ?>">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-muted small fw-bold">ელ.ფოსტა</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                                <input type="email" name="email" class="form-control bg-light border-start-0 ps-0" value="<?php echo htmlspecialchars($user->email); ?>">
                            </div>
                        </div>

                        <hr class="my-4 text-muted opacity-25">

                        <div class="mb-2">
                            <style>
                                .info-group { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
                                .info-label { font-size: 0.85rem; font-weight: 600; color: #555; }
                                .edit-btn { font-size: 0.8rem; text-decoration: none; color: #4e73df; font-weight: 600; }
                                .edit-btn:hover { text-decoration: underline; }
                            </style>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="info-label">უსაფრთხოება</div>
                                    <div class="info-value text-muted" style="letter-spacing: 3px;">••••••••••••</div>
                                </div>
                                <a class="edit-btn" data-bs-toggle="collapse" href="#editMySecurity"><i class="bi bi-key me-1"></i>შეცვლა</a>
                            </div>

                            <div class="collapse mt-4 pt-3 border-top bg-light rounded p-3" id="editMySecurity">
                                <div class="d-flex align-items-center text-warning mb-2 small">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    <span>დატოვეთ ცარიელი, თუ არ ცვლით.</span>
                                </div>
                                <input type="password" name="password" class="form-control w-100" placeholder="ახალი პაროლი">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4 mb-5">
                            <button type="submit" name="update_profile" class="btn btn-primary px-5 py-2 fw-bold shadow-sm rounded-pill">
                                <i class="bi bi-check-lg me-2"></i>განახლება
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
    <?php
}