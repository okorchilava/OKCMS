<?php
declare(strict_types=1);

/**
 * OK ძრავის უსაფრთხოების ფუნქციები (Nonces & CSRF Protection)
 *
 * @package OK_Engine
 * @version 1.4 (Hardened)
 */

if ( ! defined( 'OK_LOADED' ) ) {
    if (!headers_sent()) http_response_code(403);
    exit('Access Denied.');
}

if (!defined('NONCE_KEY') || !defined('NONCE_SALT') || NONCE_KEY === '' || NONCE_SALT === '') {
    throw new RuntimeException('NONCE_KEY/NONCE_SALT are required.');
}

function ok_create_nonce($action = -1): string {
    $user_id = $_SESSION['user_id'] ?? 0;
    $token   = $_SESSION['token'] ?? '';

    $key  = defined('NONCE_KEY') ? NONCE_KEY : '';
    $salt = defined('NONCE_SALT') ? NONCE_SALT : '';

    $tick = ok_nonce_tick();

    return ok_hash_nonce($tick, (string)$action, (string)$user_id, (string)$token, $key, $salt);
}

function ok_verify_nonce($nonce, $action = -1) {
    $nonce = trim((string)$nonce);
    if ($nonce === '' || strlen($nonce) !== 10 || !preg_match('/^[a-f0-9]+$/', $nonce)) {
        return false;
    }

    $user_id = $_SESSION['user_id'] ?? 0;
    $token   = $_SESSION['token'] ?? '';

    $key  = defined('NONCE_KEY') ? NONCE_KEY : '';
    $salt = defined('NONCE_SALT') ? NONCE_SALT : '';

    $tick = ok_nonce_tick();

    $expected_now = ok_hash_nonce($tick, (string)$action, (string)$user_id, (string)$token, $key, $salt);
    if (hash_equals($expected_now, $nonce)) {
        return 1;
    }

    $expected_prev = ok_hash_nonce($tick - 1, (string)$action, (string)$user_id, (string)$token, $key, $salt);
    if (hash_equals($expected_prev, $nonce)) {
        return 2;
    }

    return false;
}

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

function check_admin_referer($action = -1, string $query_arg = '_ok_nonce'): bool {
    $nonce = $_REQUEST[$query_arg] ?? '';

    if (ok_verify_nonce($nonce, $action) && ok_verify_same_origin()) {
        return true;
    }

    ok_log_debug('Security check failed in check_admin_referer.', [
        'action' => (string)$action,
        'uri' => $_SERVER['REQUEST_URI'] ?? '',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
    ], 'WARNING');

    if (!headers_sent()) {
        http_response_code(403);
    }
    ok_die('403 Forbidden', 'Security check failed (Nonce/Origin invalid).');
    return false;
}

function ok_sec_check($action = -1): void {
    if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
        ok_die('უსაფრთხოების შეცდომა', 'არასწორი მოთხოვნის მეთოდი.');
    }

    if (!isset($_POST['_ok_nonce'])) {
        ok_die('უსაფრთხოების შეცდომა', 'ფორმას აკლია დამცავი კოდი (Nonce missing).');
    }

    if (!ok_verify_nonce($_POST['_ok_nonce'], $action)) {
        ok_die('ვადის გასვლა', 'უსაფრთხოების კოდს ვადა გაუვიდა. გთხოვთ, გადატვირთოთ გვერდი და სცადოთ თავიდან.');
    }

    if (!ok_verify_same_origin()) {
        ok_die('უსაფრთხოების შეცდომა', 'მოთხოვნა ბლოკირებულია (Origin/Referer mismatch).');
    }
}

function ok_die(string $title, string $message): void {
    ok_log_debug('ok_die triggered.', [
        'title' => $title,
        'message' => $message,
        'uri' => $_SERVER['REQUEST_URI'] ?? '',
        'method' => $_SERVER['REQUEST_METHOD'] ?? '',
    ], 'ERROR');

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
    exit;
}

function ok_verify_same_origin(): bool {
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '') {
        return true;
    }

    $origin = (string)($_SERVER['HTTP_ORIGIN'] ?? '');
    if ($origin !== '') {
        $originHost = strtolower((string)parse_url($origin, PHP_URL_HOST));
        $originPort = (int)parse_url($origin, PHP_URL_PORT);
        $originHostPort = $originHost;
        if ($originPort > 0) {
            $originHostPort .= ':' . $originPort;
        }
        if ($originHost !== '' && $originHostPort !== $host && $originHost !== $host) {
            return false;
        }
    }

    $referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
    if ($referer !== '') {
        $refererHost = strtolower((string)parse_url($referer, PHP_URL_HOST));
        $refererPort = (int)parse_url($referer, PHP_URL_PORT);
        $refererHostPort = $refererHost;
        if ($refererPort > 0) {
            $refererHostPort .= ':' . $refererPort;
        }
        if ($refererHost !== '' && $refererHostPort !== $host && $refererHost !== $host) {
            return false;
        }
    }

    return true;
}

function ok_nonce_tick(): float {
    $nonce_life = 86400;
    return ceil(time() / ($nonce_life / 2));
}

function ok_hash_nonce($tick, string $action, string $user_id, string $token, string $key, string $salt): string {
    $data = $tick . '|' . $action . '|' . $user_id . '|' . $token;
    return substr(hash_hmac('sha256', $data, $key . $salt), -12, 10);
}
