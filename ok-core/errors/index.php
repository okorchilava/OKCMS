<?php
// Block direct listing/access for this directory
if (!defined('OK_LOADED')) {
    http_response_code(403);
    exit; // ძრავა არაა ჩატვირთული — მშრალი 403
}

if (!function_exists('ok_abort_403')) {
    http_response_code(403);
    // ფოლბექი შაბლონზე
    $error_title = 'წვდომა აკრძალულია';
    $error_message = 'ამ საქაღალდეზე პირდაპირი წვდომა დაბლოკილია.';
    $error_home = '/';
    $tpl = __DIR__ . '/../../ok-core/errors/403.php'; // შეუფაზე გზა შენს სტრუქტურას
    if (is_file($tpl)) require $tpl; else echo '<h1>403</h1>';
    exit;
}

ok_abort_403('ამ საქაღალდეზე პირდაპირი წვდომა დაბლოკილია.');
