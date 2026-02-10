<?php
declare(strict_types=1);

/**
 * OK ძრავის უსაფრთხოების ფუნქციები (Nonces & CSRF Protection)
 *
 * @package OK_Engine
 * @version 1.3 (Secure & Strict)
 */

if ( ! defined( 'OK_LOADED' ) ) {
    if (!headers_sent()) http_response_code(403);
    exit('Access Denied.');
}

/**
 * ქმნის უნიკალურ, დროზე დამოკიდებულ კოდს (Nonce).
 * * @param string|int $action მოქმედების უნიკალური სახელი (მაგ: 'delete-post-1').
 * @return string გენერირებული ჰეში.
 */
function ok_create_nonce($action = -1): string {
    $user_id = $_SESSION['user_id'] ?? 0;
    $token   = $_SESSION['token'] ?? ''; // სესიის უნიკალური ID
    
    // Fallback-ები ამოღებულია. ეყრდნობა მხოლოდ კონფიგურაციას.
    $key  = defined('NONCE_KEY') ? NONCE_KEY : '';
    $salt = defined('NONCE_SALT') ? NONCE_SALT : '';

    // Nonce ვალიდურია 24 საათი (2 "ტიკი" 12-12 საათიანი)
    $tick = ok_nonce_tick();

    return ok_hash_nonce($tick, (string)$action, (string)$user_id, (string)$token, $key, $salt);
}

/**
 * ამოწმებს გადმოცემული nonce-ის ვალიდურობას.
 * ამოწმებს როგორც მიმდინარე, ისე წინა "ტიკს" (12-24 საათის წინანდელს).
 * * @param string     $nonce  შესამოწმებელი კოდი.
 * @param string|int $action მოქმედება.
 * @return int|false 1 თუ ახალია, 2 თუ ძველია (მაგრამ ვალიდური), false თუ არასწორია.
 */
function ok_verify_nonce($nonce, $action = -1) {
    $nonce = (string)$nonce;
    $user_id = $_SESSION['user_id'] ?? 0;
    $token   = $_SESSION['token'] ?? '';
    
    $key  = defined('NONCE_KEY') ? NONCE_KEY : '';
    $salt = defined('NONCE_SALT') ? NONCE_SALT : '';

    $tick = ok_nonce_tick();

    // 1. შემოწმება მიმდინარე დროზე
    $expected_now = ok_hash_nonce($tick, (string)$action, (string)$user_id, (string)$token, $key, $salt);
    if (hash_equals($expected_now, $nonce)) {
        return 1;
    }

    // 2. შემოწმება წინა დროის მონაკვეთზე (Grace Period)
    $expected_prev = ok_hash_nonce($tick - 1, (string)$action, (string)$user_id, (string)$token, $key, $salt);
    if (hash_equals($expected_prev, $nonce)) {
        return 2;
    }

    return false;
}

/**
 * ბეჭდავს ფარულ ველს nonce-ით ფორმისთვის.
 * * @param string|int $action მოქმედების სახელი.
 * @param string     $name   input-ის name ატრიბუტი (Default: _ok_nonce).
 * @param bool       $referer დავამატოთ თუ არა referer ველი.
 * @param bool       $echo   დავბეჭდოთ თუ დავაბრუნოთ.
 */
function ok_nonce_field($action = -1, string $name = '_ok_nonce', bool $referer = true, bool $echo = true) {
    $name  = htmlspecialchars($name);
    $nonce = ok_create_nonce($action);
    
    $html = '<input type="hidden" name="' . $name . '" value="' . $nonce . '" />';

    if ($referer) {
        $ref = htmlspecialchars($_SERVER['REQUEST_URI'] ?? '');
        $html .= '<input type="hidden" name="_ok_http_referer" value="' . $ref . '" />';
    }

    if ($echo) {
        echo $html;
    } else {
        return $html;
    }
}

/**
 * ამოწმებს Admin Referer-ს და Nonce-ს ერთად.
 * * @param string|int $action
 * @param string     $query_arg
 * @return bool
 */
function check_admin_referer($action = -1, string $query_arg = '_ok_nonce'): bool {
    $nonce = $_REQUEST[$query_arg] ?? '';
    
    if (ok_verify_nonce($nonce, $action)) {
        return true;
    }

    if (!headers_sent()) {
        http_response_code(403);
    }
    ok_die('403 Forbidden', 'Security check failed (Nonce invalid).');
    return false; // მიუწვდომელი კოდი ok_die-ს გამო, მაგრამ სინტაქსისთვის
}

/* --------------------------------------------------------------------------
 * 🚀 ახალი ფუნქციები: ok_sec_check & ok_die
 * -------------------------------------------------------------------------- */

/**
 * ამოწმებს უსაფრთხოებას და „კლავს“ გვერდს, თუ რამე არასწორია.
 * ავტომატურად ეძებს $_POST['_ok_nonce']-ს.
 * * @param string|int $action მოქმედების სახელი
 */
function ok_sec_check($action = -1): void {
    // 1. ვამოწმებთ, არის თუ არა მოსული Nonce
    if (!isset($_POST['_ok_nonce'])) {
        ok_die('უსაფრთხოების შეცდომა', 'ფორმას აკლია დამცავი კოდი (Nonce missing).');
    }

    // 2. ვამოწმებთ სისწორეს
    if (!ok_verify_nonce($_POST['_ok_nonce'], $action)) {
        ok_die('ვადის გასვლა', 'უსაფრთხოების კოდს ვადა გაუვიდა. გთხოვთ, გადატვირთოთ გვერდი და სცადოთ თავიდან.');
    }
}

/**
 * ლამაზი შეცდომის გამოტანა (wp_die-ს ანალოგი)
 */
function ok_die(string $title, string $message): void {
    if (!headers_sent()) {
        http_response_code(403);
    }
    echo '
        <div style="font-family: system-ui, -apple-system, sans-serif; text-align: center; padding: 50px; background: #f8f9fa; height: 100vh; display: flex; align-items: center; justify-content: center;">
            <div style="max-width: 500px; background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border: 1px solid #eee;">
                <h1 style="color: #dc3545; margin-bottom: 20px; font-size: 24px;">⛔ ' . htmlspecialchars($title) . '</h1>
                <p style="font-size: 16px; color: #555; line-height: 1.6; margin-bottom: 30px;">' . htmlspecialchars($message) . '</p>
                <div>
                    <a href="javascript:history.back()" style="display: inline-block; background: #0d6efd; color: white; text-decoration: none; padding: 12px 30px; border-radius: 50px; font-weight: 600; transition: background 0.2s;">უკან დაბრუნება</a>
                </div>
            </div>
        </div>
    ';
    exit; // სკრიპტის გაჩერება აუცილებელია!
}

/* --- Internal Helpers --- */

/**
 * დროის "ტიკების" კალკულატორი.
 * Nonce ცოცხლობს 24 საათი (86400 წამი), დაყოფილი 2 ნაწილად.
 */
function ok_nonce_tick(): float {
    $nonce_life = 86400; // 1 დღე
    return ceil(time() / ($nonce_life / 2));
}

/**
 * ჰეშირების ფუნქცია (SHA-256 HMAC).
 */
function ok_hash_nonce($tick, string $action, string $user_id, string $token, string $key, string $salt): string {
    $data = $tick . '|' . $action . '|' . $user_id . '|' . $token;
    return substr(hash_hmac('sha256', $data, $key . $salt), -12, 10);
}