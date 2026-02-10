<?php
declare(strict_types=1);

ob_start(); 


require_once dirname(__DIR__) . '/ok-core/load.php';

// მონაცემების წამოღება
$site_logo    = get_ok_option('site_logo', '/assets/img/site-logo.png'); 
$site_title   = get_ok_option('site_title', 'OK CMS');
$site_tagline = get_ok_option('site_tagline', '');

// თუ უკვე შესულია
if (isset($_SESSION['user_id'])) {
    $user_role = $_SESSION['user_role'] ?? '';
    $target = (in_array($user_role, ['admin', 'administrator'])) ? '/ok-admin/' : '/';
    ob_end_clean();
    header("Location: $target");
    exit;
}

$error = '';
$shake_form = false;

if (empty($_SESSION['login_nonce'])) {
    $_SESSION['login_nonce'] = bin2hex(random_bytes(32));
}

// ---------------------------------------------------------
// ავტორიზაციის დამუშავება
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password'])) {
    $posted_nonce = (string)($_POST['_nonce'] ?? '');
    
    if (!hash_equals((string)$_SESSION['login_nonce'], $posted_nonce)) {
        $error = 'უსაფრთხოების ტოკენი არასწორია.';
    } else {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);

        if ($username === '' || $password === '') {
            $error = 'შეავსეთ ყველა ველი.';
            $shake_form = true;
        } else {
            global $ok_db;
            $user = $ok_db->get_row("SELECT id, username, display_name, user_role, password FROM ok_users WHERE username = ? LIMIT 1", [$username]);

            if ($user && isset($user->password) && password_verify($password, $user->password)) {
                // პირდაპირი ავტორიზაცია 2FA-ს გარეშე
                ok_complete_login($user);
            } else {
                $error = 'მომხმარებელი ან პაროლი არასწორია.';
                $shake_form = true;
            }
        }
    }
}

/**
 * ლოგინის დასრულება
 */
function ok_complete_login($user) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    
    if (!headers_sent()) {
        session_regenerate_id(true);
    }
    
    $_SESSION['user_id']      = (int)$user->id;
    $_SESSION['username']     = (string)$user->username;
    $_SESSION['display_name'] = (string)$user->display_name;
    $_SESSION['user_role']    = (string)$user->user_role;
    $_SESSION['token']        = bin2hex(random_bytes(32));
    
    // ვასუფთავებთ ბუფერს რედირექტამდე
    if (ob_get_length()) ob_clean();

    $redirect = (in_array($user->user_role, ['admin', 'administrator'])) ? '/ok-admin/' : '/';
    
    if (!headers_sent()) {
        header('Location: ' . $redirect);
    } else {
        echo '<script>window.location.href="' . $redirect . '";</script>';
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>შესვლა - <?php echo $site_title; ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        :root { --ok-primary: #0d6efd; --ok-bg: #f8f9fa; }
        body { font-family: "Noto Sans Georgian", sans-serif; background: var(--ok-bg); }
        .brand-side { background: #111; position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: center; align-items: center; color: white; min-height: 100vh; }
        .bg-slider { position: absolute; inset: 0; opacity: 0.6; background-size: cover; background-position: center; }
        .branding-container { position: relative; z-index: 2; display: flex; align-items: center; justify-content: center; gap: 45px; width: 100%; padding: 0 50px; }
        .brand-item { flex: 1; display: flex; flex-direction: column; height: 180px; justify-content: flex-start; }
        .brand-item.ok-brand { align-items: flex-end; text-align: right; }
        .brand-item.client-brand { align-items: flex-start; text-align: left; }
        .vertical-divider { width: 2px; height: 180px; background-color: rgba(255,255,255,0.3); border-radius: 2px; }
        .ok-logo-box { width: 80px; height: 80px; border: 4px solid var(--ok-primary); padding: 15px; display: inline-flex; margin-bottom: 15px; }
        .ok-logo-inner { width: 100%; height: 100%; background-color: var(--ok-primary); }
        .client-logo-wrap { height: 80px; display: flex; align-items: center; margin-bottom: 15px; }
        .client-logo-img { max-width: 140px; height: auto; max-height: 80px; object-fit: contain; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3)); }
        .brand-title { font-weight: 800; font-size: 1.8rem; margin-bottom: 5px; text-transform: uppercase; }
        .brand-tag { font-size: 0.9rem; opacity: 0.7; font-weight: 300; min-height: 20px; }
        .brand-footer-text { position: absolute; bottom: 40px; left: 40px; z-index: 2; font-weight: 700; font-size: 1.5rem; line-height: 1.2; border-left: 4px solid var(--ok-primary); padding-left: 15px; color: #fff; }
        .form-side { background: white; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .auth-card { width: 100%; max-width: 400px; padding: 2.5rem; text-align: center; }
        .shake { animation: shake 0.5s both; }
        @keyframes shake { 10%, 90% { transform: translate3d(-1px, 0, 0); } 20%, 80% { transform: translate3d(2px, 0, 0); } 30%, 50%, 70% { transform: translate3d(-4px, 0, 0); } 40%, 60% { transform: translate3d(4px, 0, 0); } }
        
        @media (max-width: 991px) {
            .branding-container { flex-direction: column; gap: 30px; }
            .vertical-divider { display: none; }
            .brand-item { height: auto !important; align-items: center !important; text-align: center !important; }
        }
    </style>
</head>
<body>

<div class="container-fluid p-0">
    <div class="row g-0">
        <div class="col-lg-7 d-none d-lg-flex brand-side">
            <div class="bg-slider" style="background-image: url('https://images.unsplash.com/photo-1519389950473-47ba0277781c?q=80&w=1920&auto=format&fit=crop');"></div>
            
            <div class="branding-container">
                <div class="brand-item ok-brand">
                    <div class="ok-logo-box">
                        <div class="ok-logo-inner"></div>
                    </div>
                    <div class="brand-title">OK CMS</div>
                    <div class="brand-tag">სწრაფი და მოქნილი სისტემა</div>
                </div>

                <div class="vertical-divider"></div>

                <div class="brand-item client-brand">
                    <div class="client-logo-wrap">
                        <img src="<?php echo htmlspecialchars($site_logo); ?>" alt="Site Logo" class="client-logo-img">
                    </div>
                    <div class="brand-title"><?php echo htmlspecialchars($site_title); ?></div>
                    <div class="brand-tag"><?php echo !empty($site_tagline) ? htmlspecialchars($site_tagline) : '&nbsp;'; ?></div>
                </div>
            </div>

            <div class="brand-footer-text">ყველაფერი<br>იქნება<br>OK</div>
        </div>

        <div class="col-lg-5 form-side">
            <div class="auth-card <?php echo $shake_form ? 'shake' : ''; ?>">
                
                <div class="mb-4 d-lg-none">
                    <div class="ok-logo-box" style="width: 60px; height: 60px; border-width: 3px; padding: 10px;">
                        <div class="ok-logo-inner"></div>
                    </div>
                </div>

                <div class="mb-5">
                    <h3 class="fw-bold">ავტორიზაცია</h3>
                    <p class="text-muted">მართვის პანელში შესვლა</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small border-0 bg-danger-subtle text-danger mb-4 text-start">
                        <i class="bi bi-exclamation-circle me-1"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                    <input type="hidden" name="_nonce" value="<?php echo $_SESSION['login_nonce']; ?>">
                    
                    <div class="input-group mb-3">
                        <span class="input-group-text bg-white text-muted"><i class="bi bi-person"></i></span>
                        <div class="form-floating">
                            <input type="text" class="form-control border-start-0 ps-2" id="floatingUser" name="username" placeholder="სახელი" required autofocus>
                            <label for="floatingUser" class="ps-2">მომხმარებელი</label>
                        </div>
                    </div>

                    <div class="input-group mb-4">
                        <span class="input-group-text bg-white text-muted"><i class="bi bi-key"></i></span>
                        <div class="form-floating">
                            <input type="password" class="form-control border-start-0 ps-2" id="floatingPass" name="password" placeholder="პაროლი" required>
                            <label for="floatingPass" class="ps-2">პაროლი</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 py-2 fw-bold shadow-sm">შესვლა</button>
                    
                    <div class="mt-4 text-center">
                        <a href="../index.php" class="text-decoration-none small text-muted">მთავარ გვერდზე დაბრუნება</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

</body>
</html>
<?php
ob_end_flush(); 
?>