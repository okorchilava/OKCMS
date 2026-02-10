<?php
http_response_code(403);

// მივდივართ საიტის root-ში არსებულ ok-core/errors/403.php ფაილზე
$root = $_SERVER['DOCUMENT_ROOT'];
$err_file = $root . '/ok-core/errors/403.php';

if (file_exists($err_file)) {
    require $err_file;
    exit;
}

die('403 Forbidden - Access Denied');
