<?php
/**
 * OK CMS - Side Slide-out Profile Card
 * File: /ok-public/user-small-profile.php
 * Hook: ok_head
 */

function ok_render_side_slide_large_badge() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start(); 
    }

    // --- LOGOUT LOGIC (Fixed for ok_head safety) ---
    if (isset($_GET['action']) && $_GET['action'] === 'logout') {
        session_destroy();
        $redirect_uri = strtok($_SERVER["REQUEST_URI"], '?');
        
        // ვამოწმებთ, გაგზავნილია თუ არა ჰედერები
        if (!headers_sent()) {
            header("Location: $redirect_uri");
        } else {
            // თუ ჰედერები გაგზავნილია, ვიყენებთ JS-ს
            echo "<script>window.location.href = '$redirect_uri';</script>";
        }
        exit;
    }

    $is_logged_in = isset($_SESSION['user_id']);
    $widget_link = '/login'; 
    $user_avatar = '';
    $notif_count = 0;
    $chat_count = 99; 

    if ($is_logged_in) {
        global $ok_db;
        $uid = (int)$_SESSION['user_id'];
        
        if (!isset($_SESSION['user_email']) && isset($ok_db)) {
            $u_data = $ok_db->get_row("SELECT email FROM ok_users WHERE id = '$uid' LIMIT 1");
            if ($u_data) $_SESSION['user_email'] = $u_data->email;
        }

        if (isset($ok_db)) {
            $notif_count = (int)$ok_db->get_var("SELECT COUNT(*) FROM ok_notifications WHERE user_id = '$uid' AND is_read = 0");
        }

        $email = $_SESSION['user_email'] ?? 'user@example.com';
        $hash = md5(strtolower(trim($email)));
        $user_avatar = "https://www.gravatar.com/avatar/$hash?s=100&d=mp";
        $widget_link = (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'administrator'])) ? '/ok-admin/' : '/profile';
    }
    ?>
    <style>
        /* ანიმაცია წითელი ბეიჯისთვის */
        @keyframes pulse-red-fixed {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
            70% { transform: scale(1.1); box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
        }

        /* ანიმაცია ლურჯი ჩატის ბეიჯისთვის */
        @keyframes pulse-blue-fixed {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 123, 255, 0.7); }
            70% { transform: scale(1.1); box-shadow: 0 0 0 10px rgba(0, 123, 255, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 123, 255, 0); }
        }

        #ok-side-slide-card {
            position: fixed !important;
            top: 25vh !important;
            right: -135px !important;
            z-index: 2147483647 !important;
            width: 195px; height: 65px;
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 35px 0 0 35px;
            box-shadow: -8px 8px 30px rgba(0, 0, 0, 0.12);
            display: flex; align-items: center; padding: 6px;
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.1);
        }

        #ok-side-slide-card:hover { right: 0 !important; background: rgba(255, 255, 255, 0.95); }

        .ok-card-icon-wrapper { position: relative; width: 53px; height: 53px; min-width: 53px; }

        .ok-card-icon-box {
            width: 100%; height: 100%; border-radius: 50%; background: #fff;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden; box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            border: 2px solid rgba(255, 255, 255, 0.8);
        }

        .ok-card-img { width: 100%; height: 100%; object-fit: cover; }

        /* წითელი ნოთიფიკაციის ბეიჯი */
        .ok-notif-badge-sync {
            position: absolute; 
            top: -8px; left: -8px;
            background-color: #dc3545; color: white;
            font-size: 13px; font-weight: 800;
            min-width: 26px; height: 26px;
            border-radius: 50%; display: flex;
            align-items: center; justify-content: center;
            padding: 0 5px; border: 2px solid #fff;
            z-index: 15; animation: pulse-red-fixed 2s infinite;
            line-height: 1;
        }

        /* ლურჯი ჩატის ბეიჯი ციმციმით */
        .ok-chat-bubble-badge {
            position: absolute; 
            bottom: -8px; left: -8px;
            background-color: #007bff; color: white;
            font-size: 14px; font-weight: 800;
            min-width: 26px; height: 26px;
            border-radius: 13px 13px 13px 2px; 
            display: flex;
            align-items: center; justify-content: center;
            border: 2px solid #fff;
            z-index: 14; 
            animation: pulse-blue-fixed 2.5s infinite;
            line-height: 1;
        }

        .ok-card-info {
            margin-left: 12px; flex-grow: 1; opacity: 0;
            transition: opacity 0.3s ease; white-space: nowrap;
        }
        #ok-side-slide-card:hover .ok-card-info { opacity: 1; }
        .ok-card-label { display: block; font-size: 10px; text-transform: uppercase; color: #888; font-weight: 700; }
        .ok-card-name { display: block; font-size: 14px; font-weight: 600; color: #222; }

        .ok-logout-btn { margin: 0 10px; color: #dc3545; font-size: 1.2rem; opacity: 0; transition: all 0.3s ease; display: flex; }
        #ok-side-slide-card:hover .ok-logout-btn { opacity: 0.7; }
        .ok-logout-btn:hover { opacity: 1 !important; transform: scale(1.1); }
    </style>

    <div id="ok-side-slide-card">
        <a href="<?php echo $widget_link; ?>" style="display: flex; align-items: center; text-decoration: none; flex-grow: 1;">
            <div class="ok-card-icon-wrapper">
                <?php if ($notif_count > 0): ?>
                    <span class="ok-notif-badge-sync"><?php echo $notif_count; ?></span>
                <?php endif; ?>

                <div class="ok-chat-bubble-badge" title="ონლაინ დახმარება">
                    <span><?php echo $chat_count; ?></span>
                </div>

                <div class="ok-card-icon-box">
                    <?php if ($is_logged_in): ?>
                        <img src="<?php echo $user_avatar; ?>" class="ok-card-img" alt="User">
                    <?php else: ?>
                        <i class="bi bi-lock-fill" style="font-size: 1.3rem; color: #444;"></i>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="ok-card-info">
                <span class="ok-card-label"><?php echo $is_logged_in ? 'პროფილი' : 'შესვლა'; ?></span>
                <span class="ok-card-name"><?php echo $is_logged_in ? 'კაბინეტი' : 'ავტორიზაცია'; ?></span>
            </div>
        </a>

        <?php if ($is_logged_in): ?>
            <a href="?action=logout" class="ok-logout-btn" title="გასვლა">
                <i class="bi bi-power"></i>
            </a>
        <?php endif; ?>
    </div>

    <?php if (!defined('BI_ICONS_LOADED')): ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <?php define('BI_ICONS_LOADED', true); ?>
    <?php endif; ?>
    <?php
}

// ─────────────────────────────────────────────────────────────────────────────
// Hook-ის შეცვლა ok_head-ზე
// ─────────────────────────────────────────────────────────────────────────────
if (function_exists('add_ok_action')) {
    add_ok_action('ok_head', 'ok_render_side_slide_large_badge'); 
} else {
    ok_render_side_slide_large_badge();
}