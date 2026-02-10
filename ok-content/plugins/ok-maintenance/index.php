<?php
/*
Plugin Name: OK Maintenance Mode (Social Share Ready)
Description: Facebook OG Tags ინტეგრაცია + სტაბილური Countdown.
Version: 8.0.5
Author: OK Engine Team
*/

if (!defined('OK_LOADED')) { die('Access Denied.'); }

/* =========================================================
   1. HOOKS & MENU REGISTRATION
   ========================================================= */

if (function_exists('add_ok_action')) {
    // Maintenance რეჟიმის შემოწმება საიტის ჩატვირთვისას
    add_ok_action('init', 'ok_mm_check_mode');

    // მენიუს რეგისტრაცია ზუსტად იმ სტილში, როგორც Ads და Top Bar პლაგინებშია
    add_ok_action('admin_menu', function() {
        add_menu_page(
            'Maintenance',           // გვერდის სათაური
            'Maintenance',           // მენიუს სათაური
            'manage_options',        // უფლებები
            'ok-maintenance',        // სლაგი
            'ok_mm_settings_page',   // ფუნქცია
            'bi bi-clock-history',   // ბუტსტრაპ აიქონი
            95                       // პოზიცია მენიუში
        );
    });
}

/* =========================================================
   2. LOGIC
   ========================================================= */

function ok_mm_check_mode() {
    $is_active = get_ok_option('ok_mm_enabled', 0);
    if (!$is_active) return;

    $uri = $_SERVER['REQUEST_URI'];
    // ადმინ პანელზე და ლოგინზე არ უნდა იმოქმედოს
    if (strpos($uri, 'admin') !== false || strpos($uri, 'login') !== false || isset($_GET['page'])) {
        return; 
    }
    // ავტორიზებულ იუზერებს (ადმინებს) საიტი ჩვეულებრივ უნდა უჩვენოს
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
        return; 
    }

    ok_mm_render_page();
    exit;
}

/* =========================================================
   3. FRONTEND RENDER
   ========================================================= */

function ok_mm_render_page() {
    $heading = get_ok_option('ok_mm_heading', 'მიმდინარეობს განახლება');
    $text = get_ok_option('ok_mm_text', 'საიტი დროებით გათიშულია. გთხოვთ გვეწვიოთ მოგვიანებით.');
    $end_date = get_ok_option('ok_mm_end_date', '');
    $og_image = get_ok_option('ok_mm_og_image', '');
    
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $current_url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

    header('HTTP/1.1 503 Service Unavailable');
    header('Retry-After: 3600');
    ?>
    <!DOCTYPE html>
    <html lang="ka">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($heading); ?></title>
        
        <meta property="og:type" content="website">
        <meta property="og:url" content="<?php echo $current_url; ?>">
        <meta property="og:title" content="<?php echo htmlspecialchars($heading); ?>">
        <meta property="og:description" content="<?php echo htmlspecialchars(strip_tags($text)); ?>">
        <?php if(!empty($og_image)): ?>
        <meta property="og:image" content="<?php echo htmlspecialchars($og_image); ?>">
        <?php endif; ?>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+Georgian:wght@400;700;900&display=swap" rel="stylesheet">
        
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body { 
                background-color: #FFC203; 
                color: #000000; 
                font-family: 'Inter', 'Noto Sans Georgian', sans-serif; 
                height: 100vh; 
                overflow: hidden; 
                display: flex; 
                align-items: center; 
                justify-content: flex-end; 
                padding-right: 80px; 
            }
            .content-wrapper { text-align: right; max-width: 650px; }
            h1 { font-size: 4rem; font-weight: 900; line-height: 1.1; margin-bottom: 20px; text-transform: uppercase; letter-spacing: -1px; }
            p { font-size: 1.5rem; font-weight: 500; line-height: 1.5; opacity: 0.8; margin-bottom: 40px; }
            .countdown { display: flex; justify-content: flex-end; gap: 20px; }
            .timer-box { text-align: center; width: 90px; }
            .timer-num { display: block; font-size: 3.5rem; font-weight: 900; line-height: 1; font-family: 'Inter', monospace; }
            .timer-label { font-size: 0.85rem; text-transform: uppercase; font-weight: 700; opacity: 0.6; }
            @media (max-width: 768px) { 
                body { padding: 20px; align-items: flex-start; justify-content: center; } 
                .content-wrapper { text-align: center; margin-top: 50px; } 
                h1 { font-size: 2.2rem; } 
                .countdown { justify-content: center; } 
                .timer-num { font-size: 2.5rem; }
            }
        </style>
    </head>
    <body>
        <div class="content-wrapper">
            <h1><?php echo htmlspecialchars($heading); ?></h1>
            <p><?php echo nl2br(htmlspecialchars($text)); ?></p>
            <?php if(!empty($end_date)): ?>
                <div id="countdown-ui" class="countdown" data-end="<?php echo htmlspecialchars($end_date); ?>">
                    <div class="timer-box"><span class="timer-num" id="d">00</span><span class="timer-label">დღე</span></div>
                    <div class="timer-box"><span class="timer-num" id="h">00</span><span class="timer-label">სთ</span></div>
                    <div class="timer-box"><span class="timer-num" id="m">00</span><span class="timer-label">წთ</span></div>
                    <div class="timer-box"><span class="timer-num" id="s">00</span><span class="timer-label">წმ</span></div>
                </div>
            <?php endif; ?>
        </div>
        <script>
            (function() {
                const ui = document.getElementById('countdown-ui');
                if(!ui) return;
                const endDate = new Date(ui.getAttribute('data-end')).getTime();
                const els = { d: document.getElementById('d'), h: document.getElementById('h'), m: document.getElementById('m'), s: document.getElementById('s') };
                setInterval(() => {
                    const distance = endDate - new Date().getTime();
                    if (distance < 0) return;
                    els.d.innerText = String(Math.floor(distance / 86400000)).padStart(2, '0');
                    els.h.innerText = String(Math.floor((distance % 86400000) / 3600000)).padStart(2, '0');
                    els.m.innerText = String(Math.floor((distance % 3600000) / 60000)).padStart(2, '0');
                    els.s.innerText = String(Math.floor((distance % 60000) / 1000)).padStart(2, '0');
                }, 1000);
            })();
        </script>
    </body>
    </html>
    <?php
}

/* =========================================================
   4. ADMIN PANEL (GUI)
   ========================================================= */

function ok_mm_settings_page() {
    if (isset($_POST['save_mm'])) {
        update_ok_option('ok_mm_enabled', isset($_POST['ok_mm_enabled']) ? 1 : 0);
        update_ok_option('ok_mm_heading', trim($_POST['ok_mm_heading']));
        update_ok_option('ok_mm_text', trim($_POST['ok_mm_text']));
        update_ok_option('ok_mm_end_date', trim($_POST['ok_mm_end_date']));
        update_ok_option('ok_mm_og_image', trim($_POST['ok_mm_og_image']));
        echo "<script>Swal.fire('მზადაა', 'Maintenance რეჟიმი განახლებულია', 'success');</script>";
    }

    $opt = [
        'enabled' => get_ok_option('ok_mm_enabled', 0),
        'heading' => get_ok_option('ok_mm_heading', 'საიტზე მიმდინარეობს განახლება'),
        'text' => get_ok_option('ok_mm_text', 'საიტი დროებით გათიშულია. გთხოვთ გვეწვიოთ მოგვიანებით.'),
        'end_date' => get_ok_option('ok_mm_end_date', ''),
        'og_image' => get_ok_option('ok_mm_og_image', '')
    ];
    ?>
    <div class="p-4 bg-light min-vh-100">
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
            <h2 class="fw-bold m-0"><i class="bi bi-clock-history me-2 text-warning"></i>Maintenance Mode</h2>
            <button type="submit" form="mmForm" name="save_mm" class="btn btn-warning px-5 shadow-sm fw-bold">შენახვა</button>
        </div>
        
        <div class="row">
            <div class="col-lg-7">
                <form method="post" id="mmForm" class="bg-white p-4 rounded shadow-sm border">
                    <div class="form-check form-switch mb-4 p-3 rounded bg-light border">
                        <label class="form-check-label fw-bold" for="mmSwitch">Maintenance რეჟიმის ჩართვა</label>
                        <input class="form-check-input float-end" type="checkbox" name="ok_mm_enabled" id="mmSwitch" <?php echo $opt['enabled'] ? 'checked' : ''; ?> style="transform: scale(1.4); cursor:pointer;">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Social Image URL (OG Image)</label>
                        <input type="url" name="ok_mm_og_image" class="form-control" placeholder="https://..." value="<?php echo htmlspecialchars($opt['og_image']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">გახსნის თარიღი და დრო</label>
                        <input type="datetime-local" name="ok_mm_end_date" class="form-control" value="<?php echo htmlspecialchars($opt['end_date']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">მთავარი სათაური</label>
                        <input type="text" name="ok_mm_heading" class="form-control" value="<?php echo htmlspecialchars($opt['heading']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">აღწერის ტექსტი</label>
                        <textarea name="ok_mm_text" class="form-control" rows="4"><?php echo htmlspecialchars($opt['text']); ?></textarea>
                    </div>
                </form>
            </div>
            
            <div class="col-lg-5">
                <div class="alert alert-info border-0 shadow-sm">
                    <h5 class="fw-bold"><i class="bi bi-info-circle me-2"></i>ინსტრუქცია</h5>
                    <p class="small mb-0">როცა ეს რეჟიმი ჩართულია, საიტის ნახვა შეეძლებათ მხოლოდ ავტორიზებულ ადმინისტრატორებს. ჩვეულებრივი მომხმარებლები იხილავენ "Coming Soon" გვერდს Countdown ტაიმერით.</p>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php
}