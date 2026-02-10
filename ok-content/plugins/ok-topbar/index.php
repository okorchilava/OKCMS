<?php
/**
 * Plugin Name: OK Top Bar (Color & Logic)
 * Description: Premium Top Bar with precise center alignment and full-page content shift.
 * Version: 12.0
 * Author: OK Engine Team
 */

if (!defined('OK_LOADED')) exit;

// 1. მენიუს რეგისტრაცია ადმინ პანელში
add_ok_action('admin_menu', function() {
    add_menu_page('ტოპ ბარი', 'Top Bar', 'manage_options', 'ok-topbar', 'ok_topbar_settings_page', 'bi bi-layout-text-window-reverse', 91);
});

// 2. ადმინ პანელი
function ok_topbar_settings_page() {
    $fields = ['ok_tb_enable', 'ok_tb_bg', 'ok_tb_phone', 'ok_tb_hours', 'ok_tb_msg', 'ok_tb_fb', 'ok_tb_insta'];
    if (isset($_POST['save_tb'])) {
        update_ok_option('ok_tb_enable', isset($_POST['ok_tb_enable']) ? 1 : 0);
        foreach ($fields as $f) { if($f !== 'ok_tb_enable') update_ok_option($f, $_POST[$f] ?? ''); }
        echo '<div class="alert alert-success m-3 shadow-sm border-0">პარამეტრები წარმატებით შენახულია!</div>';
    }
    $opt = [];
    foreach ($fields as $f) { $opt[$f] = get_ok_option($f, ''); }
    if (empty($opt['ok_tb_bg'])) $opt['ok_tb_bg'] = '#111111';

    ?>
    <div class="p-4 bg-light min-vh-100">
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
            <h2 class="fw-bold m-0"><i class="bi bi-layout-text-window-reverse me-2 text-primary"></i>Top Bar მართვა</h2>
            <button type="submit" form="tbForm" name="save_tb" class="btn btn-primary px-5 shadow-sm">შენახვა</button>
        </div>
        <form method="post" id="tbForm" class="bg-white p-4 rounded shadow-sm border">
            <div class="row g-4">
                <div class="col-md-6 border-end pe-md-4">
                    <h5 class="fw-bold mb-3 text-secondary border-bottom pb-2">ვიზუალი & სტატუსი</h5>
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" name="ok_tb_enable" style="width: 2.5em; height: 1.25em;" value="1" <?php echo ($opt['ok_tb_enable'] == 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-bold ms-2">ტოპბარის ჩართვა</label>
                    </div>
                    <label class="form-label small fw-bold">ფონის ფერი</label>
                    <input type="color" name="ok_tb_bg" class="form-control form-control-color w-100 mb-4" value="<?php echo $opt['ok_tb_bg']; ?>">
                    <label class="form-label small fw-bold text-primary">ცენტრალური შეტყობინება</label>
                    <input type="text" name="ok_tb_msg" class="form-control" value="<?php echo htmlspecialchars($opt['ok_tb_msg']); ?>">
                </div>
                <div class="col-md-6 ps-md-4">
                    <h5 class="fw-bold mb-3 text-secondary border-bottom pb-2">კონტაქტი & სოციალური</h5>
                    <div class="mb-3"><label class="form-label small fw-bold">ტელეფონი</label><input type="text" name="ok_tb_phone" class="form-control" value="<?php echo htmlspecialchars($opt['ok_tb_phone']); ?>"></div>
                    <div class="mb-3"><label class="form-label small fw-bold">საათები</label><input type="text" name="ok_tb_hours" class="form-control" value="<?php echo htmlspecialchars($opt['ok_tb_hours']); ?>"></div>
                    <div class="row">
                        <div class="col-6"><label class="form-label small fw-bold">Facebook</label><input type="text" name="ok_tb_fb" class="form-control" value="<?php echo htmlspecialchars($opt['ok_tb_fb']); ?>"></div>
                        <div class="col-6"><label class="form-label small fw-bold">Instagram</label><input type="text" name="ok_tb_insta" class="form-control" value="<?php echo htmlspecialchars($opt['ok_tb_insta']); ?>"></div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <?php
}

// 3. ფრონტენდი
add_ok_action('ok_head', 'ok_topbar_render_frontend', 1);

function ok_topbar_render_frontend() {
    if (strpos($_SERVER['REQUEST_URI'] ?? '', 'ok-admin') !== false) return;
    if (get_ok_option('ok_tb_enable', 1) != 1) return;

    $bg = get_ok_option('ok_tb_bg', '#111111');
    $phone = get_ok_option('ok_tb_phone', '');
    $hours = get_ok_option('ok_tb_hours', '');
    $msg = get_ok_option('ok_tb_msg', '');
    $fb = get_ok_option('ok_tb_fb', '');
    $insta = get_ok_option('ok_tb_insta', '');
    ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        /* ტოპბარის ფიქსირებული სტილი */
        #ok-tb-main {
            position: fixed !important; 
            top: 0 !important; 
            left: 0 !important; 
            width: 100% !important; 
            height: auto !important;
            min-height: 40px;
            background: <?php echo $bg; ?>; 
            color: #fff; 
            z-index: 2147483647 !important; /* უმაღლესი პრიორიტეტი */
            display: flex; 
            align-items: center; 
            font-family: sans-serif;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .ok-tb-grid { 
            width: 100%; max-width: 1300px; margin: 0 auto; padding: 5px 20px; 
            display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; 
        }
        .ok-tb-left { display: flex; align-items: center; gap: 15px; justify-content: flex-start; }
        .ok-tb-center { text-align: center; font-weight: 600; font-size: 14.5px; }
        .ok-tb-right { display: flex; align-items: center; gap: 15px; justify-content: flex-end; }
        .ok-tb-link { color: #fff; text-decoration: none; opacity: 0.9; transition: 0.2s; display: flex; align-items: center; gap: 6px; }
        .ok-tb-link:hover { opacity: 1; color: #fff; }
        
        /* ანიმაცია, რომ დაწევა არ იყოს უხეში */
        html, body { transition: margin-top 0.1s ease-in-out; }

        @media (max-width: 992px) { 
            .ok-tb-m-hide { display: none !important; } 
            .ok-tb-grid { grid-template-columns: 1fr 1fr; } 
        }
    </style>

    <div id="ok-tb-main">
        <div class="ok-tb-grid">
            <div class="ok-tb-left">
                <?php if($phone): ?><a href="tel:<?php echo $phone; ?>" class="ok-tb-link"><i class="bi bi-telephone-fill"></i> <b><?php echo htmlspecialchars($phone); ?></b></a><?php endif; ?>
                <?php if($hours): ?><span class="ok-tb-link ok-tb-m-hide"><i class="bi bi-clock-fill"></i> <?php echo htmlspecialchars($hours); ?></span><?php endif; ?>
            </div>
            <div class="ok-tb-center ok-tb-m-hide"><?php if($msg): ?><span><?php echo htmlspecialchars($msg); ?></span><?php endif; ?></div>
            <div class="ok-tb-right">
                <?php if($fb): ?><a href="<?php echo $fb; ?>" target="_blank" class="ok-tb-link"><i class="bi bi-facebook fs-6"></i></a><?php endif; ?>
                <?php if($insta): ?><a href="<?php echo $insta; ?>" target="_blank" class="ok-tb-link"><i class="bi bi-instagram fs-6"></i></a><?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        (function() {
            function updateLayoutShift() {
                const tb = document.getElementById('ok-tb-main');
                if (!tb) return;
                
                const height = tb.offsetHeight;
                
                // 1. მთლიან HTML/Body-ს ვწევთ ქვემოთ (ეს წევს მთელ კონტენტს)
                document.documentElement.style.marginTop = height + 'px';
                
                // 2. ვპოულობთ ფიქსირებულ ელემენტებს (Header და ა.შ.) და მათაც ვწევთ
                const allNodes = document.querySelectorAll('*');
                for (let i = 0; i < allNodes.length; i++) {
                    const node = allNodes[i];
                    if (node.id === 'ok-tb-main') continue;
                    
                    const style = window.getComputedStyle(node);
                    if (style.position === 'fixed' || style.position === 'sticky') {
                        // თუ ელემენტი ეკრანის თავშია, ჩამოვწიოთ ტოპბარის სიმაღლით
                        if (parseInt(style.top) <= height) {
                            node.style.top = height + 'px';
                        }
                    }
                }
            }

            // გაშვება ჩატვირთვისას და ზომის შეცვლისას
            window.addEventListener('load', updateLayoutShift);
            window.addEventListener('resize', updateLayoutShift);
            // დამატებითი შემოწმება DOM-ის ცვლილებაზე (ზოგჯერ დინამიურად იტვირთება ჰედერი)
            setTimeout(updateLayoutShift, 500);
        })();
    </script>
    <?php
}