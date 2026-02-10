<?php
declare(strict_types=1);

if (!defined('OK_LOADED')) {
    exit('Access Denied.');
}

function ok_current_user_role(): string
{
    return (string)($_SESSION['user_role'] ?? '');
}

function ok_require_login(bool $json = false): void
{
    if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0) {
        return;
    }

    if ($json) {
        if (!headers_sent()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!headers_sent()) {
        header('Location: /ok-admin/login.php', true, 302);
    }
    exit;
}

function ok_require_capability(string $capability, bool $json = false): void
{
    ok_require_login($json);

    if (function_exists('current_user_can') && current_user_can($capability)) {
        return;
    }

    if ($json) {
        if (!headers_sent()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['error' => 'Forbidden'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (function_exists('ok_die')) {
        ok_die('403 Forbidden', 'წვდომა აკრძალულია.');
    }

    if (!headers_sent()) {
        http_response_code(403);
    }
    exit('Forbidden');
}
