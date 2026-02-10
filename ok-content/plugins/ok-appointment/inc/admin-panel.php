<?php
// inc/admin-panel.php

add_ok_action('admin_menu', function() {
    add_menu_page('ჯავშნები', 'ჯავშნები', 'manage_options', 'ok-app', 'ok_app_admin_page', 'bi bi-calendar-check', 30);
});

function ok_app_admin_page() {
    global $ok_db;
    $sub = $_GET['sub'] ?? 'list';
    
    // --- SweetAlert2 CDN ჩასმა ---
    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

    // --- A. ჯავშნის ხელით წაშლა (გადატანა ისტორიაში) ---
    if (isset($_POST['ok_delete_app'])) {
        $app_id = intval($_POST['app_id']);
        $app = $ok_db->get_row("SELECT * FROM ok_appointments WHERE id = ?", [$app_id]);
        
        if ($app) {
            $reason = "ადმინისტრატორმა წაშალა";
            // ლოგირება
            $ok_db->query("INSERT INTO ok_appointment_logs 
                (original_app_id, customer_id, service_id, appointment_date, appointment_time, reason) 
                VALUES (?, ?, ?, ?, ?, ?)", 
                [$app->id, $app->customer_id, $app->service_id, $app->appointment_date, $app->appointment_time, $reason]
            );
            // წაშლა
            $ok_db->query("DELETE FROM ok_appointments WHERE id = ?", [$app_id]);
            echo '<div class="alert alert-warning m-3 shadow-sm border-start border-5 border-warning">ჯავშანი წაიშალა და გადავიდა ისტორიაში.</div>';
        }
    }

    echo '<div class="p-4 bg-light min-vh-100"><div class="container bg-white p-4 shadow-sm rounded">';
    
    // Header
    echo '<div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
            <h3 class="fw-bold m-0 text-primary">სისტემის მართვა</h3>
            <span class="badge bg-secondary">OK Appointment</span>
          </div>';

    // Navigation Tabs
    echo '<div class="nav nav-pills mb-4 gap-2">
            <a href="?page=ok-app&sub=list" class="nav-link '.($sub=='list'?'active':'').'"><i class="bi bi-calendar-event"></i> ჯავშნები</a>
            <a href="?page=ok-app&sub=logs" class="nav-link '.($sub=='logs'?'active bg-danger':'text-danger').'"><i class="bi bi-trash"></i> ისტორია</a>
            <a href="?page=ok-app&sub=providers" class="nav-link '.($sub=='providers'?'active':'').'"><i class="bi bi-people"></i> პროვაიდერები</a>
            <a href="?page=ok-app&sub=services" class="nav-link '.($sub=='services'?'active':'').'"><i class="bi bi-gear"></i> სერვისები</a>
            <a href="?page=ok-app&sub=shortcodes" class="nav-link '.($sub=='shortcodes'?'active':'').'"><i class="bi bi-code-slash"></i> შორთკოდები</a>
          </div>';

    // --- 1. შორთკოდები ---
    if ($sub == 'shortcodes') {
        ?>
        <h5 class="fw-bold mb-3 text-dark">სისტემის შორთკოდები</h5>
        <div class="row g-4">
            <div class="col-md-4"><div class="p-3 border rounded bg-light h-100"><h6 class="fw-bold text-primary">კლიენტის კაბინეტი</h6><code>[ok_user_cabinet]</code></div></div>
            <div class="col-md-4"><div class="p-3 border rounded bg-light h-100"><h6 class="fw-bold text-success">პროვაიდერის პანელი</h6><code>[ok_provider_cabinet]</code></div></div>
            <div class="col-md-4"><div class="p-3 border rounded bg-light h-100"><h6 class="fw-bold text-warning">ჯავშნის ფორმა</h6><code>[ok_booking_form]</code></div></div>
        </div>
        <?php
    }
    // --- 2. პროვაიდერები ---
    elseif ($sub == 'providers') {
        // პროვაიდერის დამატება
        if (isset($_POST['add_provider'])) {
            $uid = intval($_POST['user_id']);
            if (!$ok_db->get_var("SELECT id FROM ok_providers WHERE user_id = ?", [$uid])) {
                $ok_db->query("INSERT INTO ok_providers (user_id) VALUES (?)", [$uid]);
                echo '<div class="alert alert-success">დაემატა!</div>';
            }
        }
        // პროვაიდერის წაშლა
        if (isset($_POST['remove_provider'])) {
            $uid = intval($_POST['user_id']);
            $ok_db->query("DELETE FROM ok_providers WHERE user_id = ?", [$uid]);
            $ok_db->query("UPDATE ok_services SET is_active = 0 WHERE provider_id = ?", [$uid]);
            echo '<div class="alert alert-warning">წაიშალა.</div>';
        }

        $candidates = $ok_db->get_results("SELECT u.id, u.email, u.display_name FROM ok_users u LEFT JOIN ok_providers p ON u.id = p.user_id WHERE p.id IS NULL");
        $providers = $ok_db->get_results("SELECT p.*, u.display_name, u.email FROM ok_providers p JOIN ok_users u ON p.user_id = u.id");
        ?>
        <div class="row">
            <div class="col-md-4 border-end">
                <h5 class="fw-bold mb-3 text-success">ახალი პროვაიდერი</h5>
                <form method="post" class="card p-3 bg-light border-0">
                    <select name="user_id" class="form-select mb-3" required>
                        <option value="">აირჩიეთ...</option>
                        <?php foreach($candidates as $u): ?>
                            <option value="<?php echo $u->id; ?>"><?php echo $u->display_name; ?> (<?php echo $u->email; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="add_provider" class="btn btn-primary w-100">დამატება</button>
                </form>
            </div>
            <div class="col-md-8">
                <h5 class="fw-bold mb-3 text-primary">აქტიური პროვაიდერები</h5>
                <table class="table table-hover align-middle border">
                    <thead class="table-light"><tr><th>ID</th><th>სახელი</th><th>მოქმედება</th></tr></thead>
                    <tbody>
                        <?php foreach($providers as $p): ?>
                        <tr>
                            <td>#<?php echo $p->user_id; ?></td>
                            <td><strong><?php echo $p->display_name; ?></strong><br><small><?php echo $p->email; ?></small></td>
                            <td>
                                <form method="post" class="ok-sweet-form" data-title="დარწმუნებული ხართ?" data-text="პროვაიდერის წაშლა გათიშავს მის ყველა სერვისს." data-btn-text="კი, წაშლა">
                                    <input type="hidden" name="user_id" value="<?php echo $p->user_id; ?>">
                                    <input type="hidden" name="remove_provider" value="1">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">წაშლა</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    // --- 3. სერვისები ---
    elseif ($sub == 'services') {
        $services = $ok_db->get_results("SELECT s.*, u.display_name FROM ok_services s JOIN ok_users u ON s.provider_id = u.id");
        ?>
        <h5 class="fw-bold mb-3">ყველა სერვისი</h5>
        <table class="table table-sm border table-hover">
            <thead class="table-light"><tr><th>სერვისი</th><th>პროვაიდერი</th><th>ფასი</th><th>სტატუსი</th></tr></thead>
            <tbody>
                <?php foreach($services as $s): ?>
                <tr>
                    <td><?php echo $s->title; ?></td>
                    <td><?php echo $s->display_name; ?></td>
                    <td><?php echo $s->price; ?> GEL</td>
                    <td><?php echo $s->is_active ? '<span class="badge bg-success">აქტიური</span>' : '<span class="badge bg-secondary">გათიშული</span>'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    // --- 4. ისტორია (LOGS) ---
    elseif ($sub == 'logs') {
        $logs = $ok_db->get_results("SELECT l.*, s.title, u.display_name as client_name 
                                     FROM ok_appointment_logs l 
                                     LEFT JOIN ok_services s ON l.service_id = s.id 
                                     LEFT JOIN ok_users u ON l.customer_id = u.id 
                                     ORDER BY l.log_date DESC LIMIT 50");
        ?>
        <h5 class="fw-bold mb-3 text-danger"><i class="bi bi-trash"></i> წაშლილი ჯავშნები (ისტორია)</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle border shadow-sm">
                <thead class="table-light">
                    <tr><th>ID (Original)</th><th>კლიენტი</th><th>სერვისი</th><th>ჯავშნის დრო</th><th>მიზეზი</th><th>წაშლის დრო</th></tr>
                </thead>
                <tbody>
                <?php if($logs): foreach($logs as $l): ?>
                    <tr>
                        <td>#<?php echo $l->original_app_id; ?></td>
                        <td><?php echo $l->client_name ?: 'უცნობი'; ?></td>
                        <td><?php echo $l->title ?: '-'; ?></td>
                        <td><?php echo $l->appointment_date . ' <br><small class="text-muted">' . $l->appointment_time . '</small>'; ?></td>
                        <td class="text-danger fw-bold"><i class="bi bi-info-circle"></i> <?php echo $l->reason; ?></td>
                        <td><small><?php echo $l->log_date; ?></small></td>
                    </tr>
                <?php endforeach; else: echo '<tr><td colspan="6" class="text-center p-4 text-muted">ისტორია ცარიელია</td></tr>'; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    // --- 5. ჯავშნები (ACTIVE LIST) ---
    else {
        $apps = $ok_db->get_results("SELECT a.*, s.title, u.display_name as client_name, p.display_name as prov_name 
                                     FROM ok_appointments a 
                                     JOIN ok_services s ON a.service_id = s.id 
                                     LEFT JOIN ok_users u ON a.customer_id = u.id
                                     LEFT JOIN ok_users p ON s.provider_id = p.id
                                     ORDER BY a.created_at DESC");
        ?>
        <h5 class="fw-bold mb-3 text-primary">აქტიური ჯავშნები</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle border shadow-sm">
                <thead class="table-light"><tr><th>ID</th><th>კლიენტი</th><th>პროვაიდერი</th><th>სერვისი</th><th>დრო</th><th>სტატუსი</th><th>მოქმედება</th></tr></thead>
                <tbody>
                <?php if($apps): foreach($apps as $a): 
                    $badge = 'bg-secondary';
                    $status_text = $a->status;
                    
                    if($a->status == 'paid') { $badge = 'bg-success'; $status_text = 'გადახდილია'; }
                    if($a->status == 'on_hold') { $badge = 'bg-warning text-dark'; $status_text = 'ელოდება გადახდას'; }
                    if($a->status == 'cancelled') { $badge = 'bg-danger'; $status_text = 'გაუქმებული'; }
                ?>
                    <tr>
                        <td>#<?php echo $a->id; ?></td>
                        <td><?php echo $a->client_name; ?></td>
                        <td><?php echo $a->prov_name; ?></td>
                        <td><?php echo $a->title; ?></td>
                        <td><?php echo $a->appointment_date . ' <br><small class="text-muted">' . $a->appointment_time . '</small>'; ?></td>
                        <td><span class='badge <?php echo $badge; ?>'><?php echo $status_text; ?></span></td>
                        <td>
                            <form method="post" class="ok-sweet-form" data-title="ნამდვილად გსურთ წაშლა?" data-text="ჯავშანი გადავა ისტორიაში." data-btn-text="წაშლა">
                                <input type="hidden" name="app_id" value="<?php echo $a->id; ?>">
                                <input type="hidden" name="ok_delete_app" value="1">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="წაშლა და არქივში გადატანა"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; else: echo '<tr><td colspan="7" class="text-center p-4 text-muted">ჯავშნები არ არის</td></tr>'; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    echo '</div></div>';

    // --- JavaScript SweetAlert-ისთვის ---
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('.ok-sweet-form');

        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // ვაჩერებთ გაგზავნას

                const title = this.getAttribute('data-title') || 'დარწმუნებული ხართ?';
                const text = this.getAttribute('data-text') || '';
                const btnText = this.getAttribute('data-btn-text') || 'კი';

                Swal.fire({
                    title: title,
                    text: text,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: btnText,
                    cancelButtonText: 'გაუქმება'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit(); // მხოლოდ დასტურის შემთხვევაში ვაგზავნით
                    }
                });
            });
        });
    });
    </script>
    <?php
}