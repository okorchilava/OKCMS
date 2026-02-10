<?php
/**
 * OK System Logs Viewer 📜
 * ვერსია: 2.1 (Loop Fix & Secure Auth)
 */

// სესიის ინიციალიზაცია (თუ ჯერ არ დაწყებულა)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─────────────────────────────────────────────────────────────────────────────
// 0. უსაფრთხოების ფენა (მხოლოდ ადმინებს)
// ─────────────────────────────────────────────────────────────────────────────
if (empty($_SESSION['user_id']) || empty($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'administrator'])) {
    die('<div style="font-family:sans-serif; text-align:center; padding:50px; color:#dc3545;">
            <h2>🚫 წვდომა შეზღუდულია</h2>
            <p>თქვენ არ გაქვთ ამ გვერდის ნახვის უფლება.</p>
         </div>');
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. ფაილის გზის დადგენა
// ─────────────────────────────────────────────────────────────────────────────
$possible_paths = [
    'ok-content/debug.log',
    '../ok-content/debug.log',
    $_SERVER['DOCUMENT_ROOT'] . '/ok-content/debug.log',
    dirname(__DIR__) . '/ok-content/debug.log'
];

$log_file = '';
foreach ($possible_paths as $path) {
    if (file_exists($path)) { $log_file = $path; break; }
}
if (empty($log_file)) $log_file = 'debug.log';

// ─────────────────────────────────────────────────────────────────────────────
// 2. ექსპორტი (მხოლოდ დადასტურების შემდეგ)
// ─────────────────────────────────────────────────────────────────────────────
if (isset($_GET['page']) && $_GET['page'] === 'ok-logs' && isset($_GET['action']) && $_GET['action'] === 'download_log') {
    
    if (empty($_SESSION['ok_logs_verified'])) {
        die('გთხოვთ გაიაროთ ავტორიზაცია ლოგების სანახავად.');
    }

    if (file_exists($log_file)) {
        $filename = 'debug_log_' . date('Y-m-d_H-i-s') . '.txt';
        while (ob_get_level()) ob_end_clean(); 
        
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($log_file));
        readfile($log_file);
        exit;
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 3. მენიუს რეგისტრაცია
// ─────────────────────────────────────────────────────────────────────────────
add_ok_action('admin_menu', function() {
    add_menu_page('სისტემური ლოგები', 'ლოგები', 'manage_options', 'ok-logs', 'ok_render_logs_page', 'bi bi-shield-lock', 99);
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. გვერდის რენდერი
// ─────────────────────────────────────────────────────────────────────────────
function ok_render_logs_page() {
    global $ok_db, $log_file;

    // A. "ჩაკეტვა" (Lock Screen)
    if (isset($_GET['action']) && $_GET['action'] === 'lock_logs') {
        unset($_SESSION['ok_logs_verified']);
        session_write_close();
        echo "<script>window.location.href='index.php?page=ok-logs';</script>";
        exit;
    }

    // B. პაროლის შემოწმება (POST)
    $auth_error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_password'])) {
        ok_sec_check('ok_logs_auth'); // CSRF
        
        $password_input = trim($_POST['password']);
        $current_user_id = (int)$_SESSION['user_id']; 

        // 1. ვიღებთ მიმდინარე მომხმარებლის ჰეშს ბაზიდან
        $user_data = $ok_db->get_row("SELECT password FROM ok_users WHERE id = '$current_user_id'");

        if ($user_data) {
            // 2. ვადარებთ შეყვანილ პაროლს ბაზის ჰეშს
            if (password_verify($password_input, $user_data->password)) {
                
                // ✅ სწორია! ვანიჭებთ უფლებას
                $_SESSION['ok_logs_verified'] = true;
                
                // 🛑 Fix Loop: იძულებით ვინახავთ სესიას და გადავდივართ სუფთა URL-ზე
                session_write_close();
                echo "<script>window.location.href = 'index.php?page=ok-logs';</script>"; 
                exit;

            } else {
                $auth_error = 'პაროლი არასწორია!';
            }
        } else {
             $auth_error = 'მომხმარებლის მონაცემები ვერ მოიძებნა.';
        }
    }

    // C. თუ არ არის ვერიფიცირებული -> ვაჩვენებთ პაროლის ველს
    if (empty($_SESSION['ok_logs_verified'])) {
        ?>
        <div class="container d-flex justify-content-center align-items-center" style="min-height: 60vh;">
            <div class="card shadow border-0" style="max-width: 400px; width: 100%;">
                <div class="card-body p-5 text-center">
                    <div class="mb-4">
                        <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                            <i class="bi bi-shield-lock text-primary" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold mb-3">უსაფრთხოების შემოწმება</h5>
                    <p class="text-muted small mb-4">გთხოვთ შეიყვანოთ თქვენი პაროლი ლოგების სანახავად.</p>
                    
                    <?php if($auth_error): ?>
                        <div class="alert alert-danger py-2 small mb-3 border-0 bg-danger-subtle text-danger">
                            <i class="bi bi-exclamation-circle me-1"></i> <?php echo $auth_error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <?php ok_nonce_field('ok_logs_auth'); ?>
                        <div class="form-floating mb-3 text-start">
                            <input type="password" name="password" class="form-control" id="secPass" required autofocus placeholder="პაროლი">
                            <label for="secPass">პაროლი</label>
                        </div>
                        <button type="submit" name="confirm_password" class="btn btn-primary w-100 fw-bold py-2">დადასტურება</button>
                    </form>
                </div>
            </div>
        </div>
        <?php
        return; // აქ სრულდება ფუნქცია, ლოგებს არ აჩვენებს
    }

    // =========================================================================
    // D. ლოგების გამოჩენა (მხოლოდ თუ if გავიარეთ)
    // =========================================================================

    // გასუფთავება
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_logs'])) {
        ok_sec_check('ok_logs_action');
        if (file_exists($log_file)) {
            $f = @fopen($log_file, "w");
            if ($f) fclose($f);
        }
        // გასუფთავების შემდეგაც რეფრეში, რომ ფორმა არ დარჩეს
        echo "<script>window.location.href = 'index.php?page=ok-logs';</script>";
        exit;
    }

    // მონაცემები
    $log_content = file_exists($log_file) ? file_get_contents($log_file) : '';
    $filesize = file_exists($log_file) ? filesize($log_file) : 0;
    
    // ფორმატირება
    $file_size_formatted = ($filesize > 1024 * 1024) 
        ? round($filesize / 1024 / 1024, 2) . ' MB' 
        : round($filesize / 1024, 2) . ' KB';

    $formatted_logs = htmlspecialchars($log_content);
    // ფერები
    $formatted_logs = preg_replace('/(PHP Fatal error:)/', '<span class="text-danger fw-bold">$1</span>', $formatted_logs);
    $formatted_logs = preg_replace('/(PHP Warning:)/', '<span class="text-warning fw-bold">$1</span>', $formatted_logs);
    $formatted_logs = preg_replace('/(PHP Notice:)/', '<span class="text-info fw-bold">$1</span>', $formatted_logs);
    $formatted_logs = preg_replace('/(\[.*?\])/', '<span class="text-secondary">$1</span>', $formatted_logs);
    ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1"><i class="bi bi-shield-check me-2 text-success"></i>სისტემური ლოგები</h1>
            <small class="text-muted">
                ფაილი: <code><?php echo htmlspecialchars($log_file); ?></code> 
                | ზომა: <strong><?php echo $file_size_formatted; ?></strong>
            </small>
        </div>
        
        <div class="d-flex gap-2">
            <a href="index.php?page=ok-logs&action=lock_logs" class="btn btn-secondary shadow-sm" title="სესიის დახურვა">
                <i class="bi bi-lock-fill"></i>
            </a>
            
            <?php if(file_exists($log_file) && $filesize > 0): ?>
            <a href="index.php?page=ok-logs&action=download_log" target="_blank" class="btn btn-outline-primary shadow-sm">
                <i class="bi bi-download me-2"></i> ექსპორტი
            </a>
            <form method="post" id="clearLogsForm">
                <?php ok_nonce_field('ok_logs_action'); ?>
                <input type="hidden" name="clear_logs" value="1">
                <button type="button" class="btn btn-danger shadow-sm" onclick="confirmClear()">
                    <i class="bi bi-trash me-2"></i> გასუფთავება
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0 bg-dark rounded overflow-hidden">
             <div class="d-flex justify-content-between align-items-center px-3 py-2 bg-secondary bg-opacity-25 border-bottom border-secondary">
                <span class="text-white-50 small"><i class="bi bi-code-square me-2"></i>Console Output</span>
                <button class="btn btn-sm btn-link text-white-50 text-decoration-none p-0" onclick="copyLogs()">
                    <i class="bi bi-clipboard me-1"></i> Copy
                </button>
            </div>
            <pre class="m-0 p-3 text-light" id="logViewer" style="max-height: 70vh; overflow-y: auto; font-family: 'Consolas', monospace; font-size: 0.85rem; white-space: pre-wrap;"><?php echo empty($formatted_logs) ? '<span class="text-white-50 opacity-50">Log file is empty.</span>' : $formatted_logs; ?></pre>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var logContainer = document.getElementById("logViewer");
            if(logContainer) logContainer.scrollTop = logContainer.scrollHeight;
        });
        function copyLogs() {
            var txt = document.getElementById("logViewer").innerText;
            navigator.clipboard.writeText(txt).then(() => {
                Swal.fire({ icon: 'success', title: 'Copied!', toast: true, position: 'top-end', showConfirmButton: false, timer: 1000 });
            });
        }
        function confirmClear() {
            Swal.fire({
                title: 'წავშალოთ?', text: "ლოგების ისტორია განულდება.", icon: 'warning',
                showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonColor: '#6c757d', confirmButtonText: 'დიახ', cancelButtonText: 'არა'
            }).then((r) => { if (r.isConfirmed) document.getElementById('clearLogsForm').submit(); });
        }
    </script>
    <?php
}
?>