<?php
/**
 * შეტყობინებების მართვა (Fixed: AJAX Handler + Icons ✅)
 */

// 🛑 1. AJAX Handler - სტატუსის შესაცვლელად (ეს კოდი აუცილებელია რეფრეშის გარეშე მუშაობისთვის)
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'ok_toggle_read_status') {
    global $ok_db;
    
    // უსაფრთხოება
    if (!isset($ok_db)) { echo json_encode(['status' => 'error', 'message' => 'DB Error']); exit; }

    $n_id = intval($_POST['n_id']);
    
    // გავიგოთ ამჟამინდელი სტატუსი
    $current_status = $ok_db->get_var("SELECT is_read FROM ok_notifications WHERE id = $n_id");
    
    // შევაბრუნოთ სტატუსი (თუ 0-ია გახდეს 1, თუ 1-ია გახდეს 0)
    $new_status = ($current_status == 0) ? 1 : 0;
    
    $update = $ok_db->query("UPDATE ok_notifications SET is_read = $new_status WHERE id = $n_id");

    if ($update !== false) {
        echo json_encode([
            'status' => 'success', 
            'new_state' => $new_status // 1 = წაკითხული, 0 = წაუკითხავი
        ]);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit; // აუცილებელია, რომ მთლიანი გვერდი არ ჩაიტვირთოს და მხოლოდ JSON დაბრუნდეს
}

// 🛑 2. მენიუს რეგისტრაცია
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

    if (!isset($ok_db)) return;

    // 🛑 3. სტანდარტული ქმედებები (ყველას წაკითხვა, წაშლა - რეფრეშით)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
        ok_sec_check('ok_notif_action'); 
        $ok_db->query("UPDATE ok_notifications SET is_read = 1 WHERE is_read = 0");
        echo '<div class="alert alert-success shadow-sm mb-4"><i class="bi bi-check-circle me-2"></i>ყველა მონიშნულია წაკითხულად.</div>';
    }

    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['n_id'])) {
        $n_id = (int)$_GET['n_id'];
        $ok_db->query("DELETE FROM ok_notifications WHERE id = ?", [$n_id]);
        echo "<script>window.location.href='index.php?page=ok-notifications';</script>"; 
        return;
    }

    if (isset($_GET['action']) && $_GET['action'] === 'read' && isset($_GET['n_id'])) {
        $n_id = (int)$_GET['n_id'];
        $ok_db->query("UPDATE ok_notifications SET is_read = 1 WHERE id = ?", [$n_id]);
        echo "<script>window.location.href='index.php?page=ok-notifications';</script>"; 
        return;
    }

    // 🛑 4. მონაცემები
    $per_page = 15;
    $page_num = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
    $offset   = ($page_num - 1) * $per_page;

    $total_rows    = $ok_db->get_var("SELECT COUNT(*) FROM ok_notifications");
    $total_pages   = ceil($total_rows / $per_page);
    $notifications = $ok_db->get_results("SELECT * FROM ok_notifications ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
    
    $unread_count = $ok_db->get_var("SELECT COUNT(*) FROM ok_notifications WHERE is_read = 0");
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
                                $is_unread = ($notif->is_read == 0);
                                $bg_class = $is_unread ? 'bg-alice-blue' : 'bg-white';
                                
                                // აიქონების ლოგიკა (ძველი სტილი დაბრუნებულია)
                                $main_icon = 'bi-info-circle-fill'; 
                                $icon_color = 'text-info';
                                if ($notif->type === 'success') { $main_icon = 'bi-check-circle-fill'; $icon_color = 'text-success'; }
                                if ($notif->type === 'warning') { $main_icon = 'bi-exclamation-triangle-fill'; $icon_color = 'text-warning'; }
                                if ($notif->type === 'danger')  { $main_icon = 'bi-x-circle-fill'; $icon_color = 'text-danger'; }

                                $envelope_icon = $is_unread ? 'bi-envelope-fill' : 'bi-envelope-open';
                                $envelope_color = $is_unread ? 'text-primary' : 'text-muted opacity-50';
                                $link = !empty($notif->link) ? $notif->link : '#';
                            ?>
                            
                            <div id="notif-item-<?php echo $notif->id; ?>" class="list-group-item p-3 <?php echo $bg_class; ?> d-flex align-items-start gap-3 position-relative notif-item border-bottom">
                                
                                <div class="icon-circle shadow-sm flex-shrink-0">
                                    <i class="bi <?php echo $main_icon . ' ' . $icon_color; ?> fs-5"></i>
                                </div>

                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fw-bold <?php echo $is_unread ? 'text-dark' : 'text-secondary'; ?> title-text">
                                                <?php echo ucfirst($notif->type); ?>
                                            </span>
                                            
                                            <i id="env-icon-<?php echo $notif->id; ?>" class="bi <?php echo $envelope_icon . ' ' . $envelope_color; ?> env-indicator" style="font-size: 0.9rem;" title="<?php echo $is_unread ? 'წაუკითხავი' : 'წაკითხული'; ?>"></i>

                                            <span id="badge-<?php echo $notif->id; ?>" class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1" style="font-size: 9px; display: <?php echo $is_unread ? 'inline-block' : 'none'; ?>;">ახალი</span>
                                        </div>

                                        <small class="text-muted" style="font-size: 11px;">
                                            <?php echo date('d M Y, H:i', strtotime($notif->created_at)); ?>
                                        </small>
                                    </div>
                                    
                                    <div class="text-secondary small text-break pe-4">
                                        <?php if($link !== '#'): ?>
                                            <a href="<?php echo htmlspecialchars($link); ?>" class="text-decoration-none text-dark fw-medium stretched-link-custom">
                                                <?php echo htmlspecialchars($notif->message); ?>
                                            </a>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($notif->message); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="dropdown z-2 ms-2">
                                    <button class="btn btn-sm btn-light rounded-circle shadow-sm" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                        <li>
                                            <a class="dropdown-item small toggle-read-btn" href="#" data-id="<?php echo $notif->id; ?>">
                                                <i class="bi bi-envelope-open text-primary me-2"></i> 
                                                <span class="btn-text"><?php echo $is_unread ? 'წაკითხვა' : 'წაუკითხავად მონიშვნა'; ?></span>
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item small text-danger delete-confirm" href="index.php?page=ok-notifications&action=delete&n_id=<?php echo $notif->id; ?>">
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
        
        /* 🎨 Icon Circle Style (From Image) */
        .icon-circle {
            width: 48px; height: 48px; background-color: #ffffff; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08); border: 1px solid #f1f5f9;
        }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Delete Confirmation
        document.querySelectorAll('.delete-confirm').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const url = this.getAttribute('href');
                Swal.fire({
                    title: 'წავშალოთ?',
                    text: "შეტყობინება ამოიშლება.",
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

        // 2. AJAX Read/Unread Toggle 🚀 (გვერდის გადატვირთვის გარეშე)
        document.querySelectorAll('.toggle-read-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault(); // არ გადავიდეს ლინკზე
                const notifId = this.getAttribute('data-id');
                const btnTextSpan = this.querySelector('.btn-text');
                const rowItem = document.getElementById('notif-item-' + notifId);
                const envIcon = document.getElementById('env-icon-' + notifId);
                const badge = document.getElementById('badge-' + notifId);
                const titleText = rowItem.querySelector('.title-text');

                // ვაგზავნით მონაცემებს
                const formData = new FormData();
                formData.append('ajax_action', 'ok_toggle_read_status');
                formData.append('n_id', notifId);

                // fetch-ი აგზავნის POST მოთხოვნას მიმდინარე გვერდზე (index.php)
                // რადგან ფაილის თავში გვაქვს ჰენდლერი, ის დაიჭერს და დააბრუნებს JSON-ს
                fetch('index.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // UI განახლება რეფრეშის გარეშე
                        if (data.new_state === 1) { 
                            // გახდა წაკითხული
                            rowItem.classList.remove('bg-alice-blue');
                            rowItem.classList.add('bg-white');
                            
                            envIcon.classList.remove('bi-envelope-fill', 'text-primary');
                            envIcon.classList.add('bi-envelope-open', 'text-muted');
                            envIcon.style.opacity = '0.5';

                            badge.style.display = 'none';
                            titleText.classList.remove('text-dark');
                            titleText.classList.add('text-secondary');

                            btnTextSpan.textContent = 'წაუკითხავად მონიშვნა';
                        } else {
                            // გახდა წაუკითხავი
                            rowItem.classList.remove('bg-white');
                            rowItem.classList.add('bg-alice-blue');

                            envIcon.classList.remove('bi-envelope-open', 'text-muted');
                            envIcon.classList.add('bi-envelope-fill', 'text-primary');
                            envIcon.style.opacity = '1';

                            badge.style.display = 'inline-block';
                            titleText.classList.remove('text-secondary');
                            titleText.classList.add('text-dark');

                            btnTextSpan.textContent = 'წაკითხვა';
                        }
                    } else {
                        console.error('შეცდომა სტატუსის განახლებისას');
                    }
                })
                .catch(error => console.error('Error:', error));
            });
        });
    });
    </script>
    <?php
}