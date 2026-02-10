<?php
/**
 * შეტყობინებების მართვა (Normalized recipients)
 */

ok_require_capability('manage_options');

global $ok_db;
$current_user_id = (int)($_SESSION['user_id'] ?? 0);
if ($current_user_id <= 0) {
    ok_require_login();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'ok_toggle_read_status') {
    ok_sec_check('ok_notif_action');

    $n_id = (int)($_POST['n_id'] ?? 0);
    $row = $ok_db->get_row(
        "SELECT is_read FROM ok_notification_recipients WHERE notification_id = ? AND user_id = ? LIMIT 1",
        [$n_id, $current_user_id]
    );

    if (!$row) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'Not found']);
        exit;
    }

    $new_status = ((int)$row->is_read === 1) ? 0 : 1;
    $ok_db->query(
        "UPDATE ok_notification_recipients SET is_read = ?, read_at = ? WHERE notification_id = ? AND user_id = ?",
        [$new_status, $new_status ? date('Y-m-d H:i:s') : null, $n_id, $current_user_id]
    );

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'success', 'new_state' => $new_status], JSON_UNESCAPED_UNICODE);
    exit;
}

add_ok_action('admin_menu', function() {
    add_menu_page(
        'შეტყობინებები',
        'შეტყობინებები',
        'manage_options',
        'ok-notifications',
        'ok_render_notifications_page',
        'bi bi-bell',
        26
    );
});

function ok_render_notifications_page() {
    global $ok_db;
    $uid = (int)($_SESSION['user_id'] ?? 0);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
        ok_sec_check('ok_notif_action');
        $ok_db->query("UPDATE ok_notification_recipients SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0", [$uid]);
        echo '<div class="alert alert-success shadow-sm mb-4"><i class="bi bi-check-circle me-2"></i>ყველა მონიშნულია წაკითხულად.</div>';
    }

    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['n_id'])) {
        $n_id = (int)$_GET['n_id'];
        $ok_db->query("DELETE FROM ok_notification_recipients WHERE notification_id = ? AND user_id = ?", [$n_id, $uid]);
        echo "<script>window.location.href='index.php?page=ok-notifications';</script>";
        return;
    }

    if (isset($_GET['action']) && $_GET['action'] === 'read' && isset($_GET['n_id'])) {
        $n_id = (int)$_GET['n_id'];
        $ok_db->query("UPDATE ok_notification_recipients SET is_read = 1, read_at = NOW() WHERE notification_id = ? AND user_id = ?", [$n_id, $uid]);
        echo "<script>window.location.href='index.php?page=ok-notifications';</script>";
        return;
    }

    $per_page = 15;
    $page_num = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
    $offset   = ($page_num - 1) * $per_page;

    $total_rows = (int)$ok_db->get_var("SELECT COUNT(*) FROM ok_notification_recipients WHERE user_id = ?", [$uid]);
    $total_pages = max(1, (int)ceil($total_rows / $per_page));
    $notifications = $ok_db->get_results(
        "SELECT n.*, r.is_read, r.user_id as recipient_user_id
         FROM ok_notification_recipients r
         INNER JOIN ok_notifications n ON n.id = r.notification_id
         WHERE r.user_id = ?
         ORDER BY n.created_at DESC, n.id DESC
         LIMIT {$per_page} OFFSET {$offset}",
        [$uid]
    );

    $unread_count = (int)$ok_db->get_var("SELECT COUNT(*) FROM ok_notification_recipients WHERE user_id = ? AND is_read = 0", [$uid]);
    ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h1 class="h3 fw-bold text-dark"><i class="bi bi-bell me-2"></i>შეტყობინებები</h1>

        <?php if ($unread_count > 0): ?>
            <form method="post" class="d-inline">
                <?php ok_nonce_field('ok_notif_action'); ?>
                <button type="submit" name="mark_all_read" class="btn btn-primary rounded-pill px-4 shadow">
                    <i class="bi bi-check-all me-2"></i> ყველას წაკითხვა
                </button>
            </form>
        <?php endif; ?>
    </div>

    <div class="row g-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-uppercase small text-muted">თქვენი ისტორია</h6>
                </div>

                <div class="card-body p-0">
                    <?php if (empty($notifications)): ?>
                        <div class="text-center py-5">
                            <div class="mb-3 opacity-50">
                                <i class="bi bi-bell-slash" style="font-size: 3rem; color: #cbd5e1;"></i>
                            </div>
                            <h5 class="text-muted fw-bold">შეტყობინებები არ არის</h5>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($notifications as $notif):
                                $is_unread = ((int)$notif->is_read === 0);
                                $bg_class = $is_unread ? 'bg-alice-blue' : 'bg-white';
                                $icon = 'bi-info-circle';
                                if (($notif->type ?? '') === 'success') $icon = 'bi-check-circle';
                                if (($notif->type ?? '') === 'warning') $icon = 'bi-exclamation-triangle';
                                if (($notif->type ?? '') === 'danger') $icon = 'bi-shield-x';
                            ?>
                            <div class="list-group-item border-0 p-3 position-relative notif-item <?php echo $bg_class; ?>" id="notif-item-<?php echo (int)$notif->id; ?>">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="icon-circle"><i id="env-icon-<?php echo (int)$notif->id; ?>" class="bi <?php echo $icon; ?> fs-5"></i></div>
                                    <div class="flex-grow-1 position-relative">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <div class="fw-semibold title-text"><?php echo $is_unread ? 'ახალი შეტყობინება' : 'შეტყობინება'; ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars((string)$notif->created_at); ?></small>
                                        </div>
                                        <?php if (!empty($notif->link)): ?>
                                            <a href="<?php echo htmlspecialchars((string)$notif->link); ?>" class="text-decoration-none stretched-link-custom">
                                                <?php echo htmlspecialchars((string)$notif->message); ?>
                                            </a>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars((string)$notif->message); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="dropdown z-2 ms-2">
                                    <button class="btn btn-sm btn-light rounded-circle shadow-sm" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                        <li>
                                            <a class="dropdown-item small toggle-read-btn" href="#" data-id="<?php echo (int)$notif->id; ?>">
                                                <i class="bi bi-envelope-open text-primary me-2"></i>
                                                <span class="btn-text"><?php echo $is_unread ? 'წაკითხვა' : 'წაუკითხავად მონიშვნა'; ?></span>
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item small text-danger delete-confirm" href="index.php?page=ok-notifications&action=delete&n_id=<?php echo (int)$notif->id; ?>">
                                                <i class="bi bi-trash me-2"></i> წაშლა
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                <div class="card-footer bg-light py-3 border-top">
                    <nav>
                        <ul class="pagination justify-content-center mb-0 pagination-sm">
                            <li class="page-item <?php echo ($page_num <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link rounded-start-pill" href="index.php?page=ok-notifications&p=<?php echo $page_num - 1; ?>"><i class="bi bi-chevron-left"></i></a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($i == $page_num) ? 'active' : ''; ?>">
                                    <a class="page-link" href="index.php?page=ok-notifications&p=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo ($page_num >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link rounded-end-pill" href="index.php?page=ok-notifications&p=<?php echo $page_num + 1; ?>"><i class="bi bi-chevron-right"></i></a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <style>
        .bg-alice-blue { background-color: #f1f5f9; transition: background-color 0.3s ease; }
        .bg-white { background-color: #fff; transition: background-color 0.3s ease; }
        .notif-item:hover { background-color: #f8fafc; }
        .stretched-link-custom::after { position: absolute; top: 0; right: 50px; bottom: 0; left: 0; z-index: 1; content: ""; }
        .icon-circle {
            width: 48px; height: 48px; background-color: #ffffff; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08); border: 1px solid #f1f5f9;
        }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const nonce = <?php echo json_encode(ok_create_nonce('ok_notif_action')); ?>;

        document.querySelectorAll('.delete-confirm').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const url = this.getAttribute('href');
                Swal.fire({
                    title: 'წავშალოთ?',
                    text: 'შეტყობინება ამოიშლება.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'დიახ',
                    cancelButtonText: 'არა'
                }).then((result) => {
                    if (result.isConfirmed) window.location.href = url;
                });
            });
        });

        document.querySelectorAll('.toggle-read-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const notifId = this.getAttribute('data-id');
                const btnTextSpan = this.querySelector('.btn-text');
                const rowItem = document.getElementById('notif-item-' + notifId);

                const formData = new FormData();
                formData.append('ajax_action', 'ok_toggle_read_status');
                formData.append('n_id', notifId);
                formData.append('_ok_nonce', nonce);

                fetch(window.location.href, { method: 'POST', body: formData, credentials: 'same-origin' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            const isUnread = parseInt(data.new_state, 10) === 0;
                            rowItem.classList.toggle('bg-alice-blue', isUnread);
                            rowItem.classList.toggle('bg-white', !isUnread);
                            btnTextSpan.textContent = isUnread ? 'წაკითხვა' : 'წაუკითხავად მონიშვნა';
                        }
                    });
            });
        });
    });
    </script>
    <?php
}
