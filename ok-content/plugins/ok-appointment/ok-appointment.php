<?php
/**
 * Plugin Name: OK Appointment System PRO
 */

if (!defined('OK_LOADED')) exit;

define('OK_APP_PATH', __DIR__);

// 1. სესიის სტარტი
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. სესიის ინტეგრაცია (მთავარი სისტემიდან)
global $user_id;
$user_id = 0;

if (isset($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
    $_SESSION['ok_app_user_id'] = $user_id; // თავსებადობისთვის
}

// 3. ადმინ მენიუს რეგისტრაცია
add_ok_action('admin_menu', function() {
    // მხოლოდ მაშინ ვამატებთ, თუ ფუნქცია არსებობს
    if (function_exists('ok_add_admin_menu')) {
        // მთავარი მენიუ: ჯავშნები
        ok_add_admin_menu('ჯავშნები', 'ok-appointments', 'bi-calendar-week', 5);
        
        // ქვემენიუები
        ok_add_admin_submenu('ok-appointments', 'ყველა ჯავშანი', 'ok-appointments');
        ok_add_admin_submenu('ok-appointments', 'სერვისები', 'ok-services');
        ok_add_admin_submenu('ok-appointments', 'სპეციალისტები', 'ok-providers');
        ok_add_admin_submenu('ok-appointments', 'პარამეტრები', 'ok-app-settings');
    }
});

// 4. დამხმარე ფუნქციები
if (!function_exists('is_ok_logged_in')) {
    function is_ok_logged_in() {
        return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
    }
}

// 5. ფაილების ჩატვირთვა
if (file_exists(OK_APP_PATH . '/inc/db-install.php')) require_once OK_APP_PATH . '/inc/db-install.php';

require_once OK_APP_PATH . '/inc/auth.php';        
require_once OK_APP_PATH . '/inc/payments.php';     
require_once OK_APP_PATH . '/inc/helpers.php';        
require_once OK_APP_PATH . '/inc/shortcodes.php'; 
require_once OK_APP_PATH . '/inc/tab.php'; 
require_once OK_APP_PATH . '/inc/admin-panel.php'; 

// 6. CSS და JS
add_ok_action('ok_head', function() {
    echo '<style>
        .ok-calendar-day { cursor: pointer; transition: 0.2s; }
        .ok-calendar-day:hover { background: #f0f0f0; }
        .ok-calendar-day.active { background: #0d6efd; color: white; }
        .ok-time-slot { cursor: pointer; }
        .ok-time-slot:hover { background: #e9ecef; }
    </style>';
});

// 7. 15 წუთიანი ავტომატური წაშლა (CRON-ის იმიტაცია)
add_ok_action('init', function() {
    global $ok_db;
    if (!$ok_db) return; 

    // 15 წუთზე ძველი, გადაუხდელი ჯავშნები
    $expired_apps = $ok_db->get_results("SELECT * FROM ok_appointments 
                                         WHERE status = 'on_hold' 
                                         AND created_at < (NOW() - INTERVAL 15 MINUTE)");

    if ($expired_apps) {
        foreach ($expired_apps as $app) {
            $reason = "ავტომატური გაუქმება (დრო ამოიწურა)";
            
            // ლოგირება (თუ ცხრილი არსებობს)
            if (method_exists($ok_db, 'query')) {
                 $ok_db->query("INSERT INTO ok_appointment_logs 
                    (original_app_id, customer_id, service_id, appointment_date, appointment_time, reason) 
                    VALUES ('$app->id', '$app->customer_id', '$app->service_id', '$app->appointment_date', '$app->appointment_time', '$reason')");

                // წაშლა
                $ok_db->query("DELETE FROM ok_appointments WHERE id = '$app->id'");
            }
        }
    }
});