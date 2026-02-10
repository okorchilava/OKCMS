<?php
declare(strict_types=1);

/**
 * OK ძრავის კომენტარების დამმუშავებელი (Handler)
 */

require_once __DIR__ . '/ok-core/load.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Error: Method not allowed.');
}

if (!isset($_POST['_ok_nonce']) || !ok_verify_nonce((string)$_POST['_ok_nonce'], 'post_comment')) {
    http_response_code(403);
    exit('Error: Security check failed (Nonce).');
}

if (!ok_verify_same_origin()) {
    http_response_code(403);
    exit('Error: Security check failed (Origin).');
}

if (!empty($_POST['ok_website_field'])) {
    exit('Error: Spam detected.');
}

global $ok_db;

$post_id = isset($_POST['comment_post_id']) ? (int)$_POST['comment_post_id'] : 0;
$comment = isset($_POST['comment']) ? trim((string)$_POST['comment']) : '';
$comment = mb_substr(strip_tags($comment), 0, 2000);

$user_id = 0;
$author  = '';
$email   = '';
$url     = '';

if (isset($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
    $user = $ok_db->get_row('SELECT display_name, email FROM ok_users WHERE id = ? LIMIT 1', [$user_id]);
    if ($user) {
        $author = trim((string)$user->display_name);
        $email  = trim((string)$user->email);
    }
} else {
    $author = isset($_POST['author']) ? trim(strip_tags((string)$_POST['author'])) : '';
    $email  = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
    $url    = isset($_POST['url']) ? trim((string)$_POST['url']) : '';
    $url    = filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
}

$post_exists = $ok_db->get_row('SELECT id, post_name FROM ok_posts WHERE id = ? AND post_status = ? LIMIT 1', [$post_id, 'published']);
if (!$post_exists) {
    exit('Error: Post not found.');
}

if ($author === '' || $email === '' || $comment === '') {
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/') . '?comment_error=empty_fields#respond');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/') . '?comment_error=invalid_email#respond');
    exit;
}

$comment_approved = 'pending';
$moderation_on = (string)get_ok_option('comment_moderation', '1');
if ($moderation_on === '0' || (function_exists('ok_current_user_role') && ok_current_user_role() === 'administrator')) {
    $comment_approved = 'approved';
}

$ip_address = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
$user_agent = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 254);

$sql = 'INSERT INTO comments (comment_post_id, comment_author, comment_author_email, comment_author_url, comment_IP, comment_agent, comment_content, comment_approved, user_id, comment_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())';
$result = $ok_db->query($sql, [$post_id, $author, $email, $url, $ip_address, $user_agent, $comment, $comment_approved, $user_id]);

if ($result) {
    ok_handle_notification([
        'type' => 'info',
        'message' => "{$author} დატოვა კომენტარი პოსტზე.",
        'link' => 'index.php?page=ok-notifications',
        'targets' => []
    ]);

    if (function_exists('do_ok_action')) {
        do_ok_action('comment_post', $ok_db->last_insert_id(), $comment_approved);
    }
}

$post_slug = (string)($post_exists->post_name ?? '');
$redirect_anchor = $result ? '#comments' : '#respond';
$status_param = $comment_approved === 'approved' ? 'comment_approved=1' : 'comment_waiting=1';
$target = '/' . ltrim($post_slug, '/') . '/?' . $status_param . $redirect_anchor;
header('Location: ' . $target);
exit;

if (empty($author) || empty($email) || empty($comment)) {
    // უკან დაბრუნება შეცდომით
    header('Location: ' . $_SERVER['HTTP_REFERER'] . '?comment_error=empty_fields#respond');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ' . $_SERVER['HTTP_REFERER'] . '?comment_error=invalid_email#respond');
    exit;
}

// 8. სტატუსის განსაზღვრა (Auto-approve თუ მოდერაცია გამორთულია)
$comment_approved = 'pending';
$moderation_on    = get_ok_option('comment_moderation', '1'); // 1 = ჩართულია

if ($moderation_on === '0' || ok_get_user_role($user_id) === 'administrator') {
    $comment_approved = 'approved';
}

// 9. ტექნიკური მონაცემები (IP, Agent)
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
$user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 254);

// 10. ბაზაში ჩაწერა
$sql = "INSERT INTO comments 
        (comment_post_id, comment_author, comment_author_email, comment_author_url, comment_IP, comment_agent, comment_content, comment_approved, user_id, comment_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$result = $ok_db->query($sql, [
    $post_id, 
    $author, 
    $email, 
    $url, 
    $ip_address, 
    $user_agent, 
    $comment, 
    $comment_approved, 
    $user_id
]);

if ($result) {
    // ნოტიფიკაციის გაგზავნა ადმინისტრატორთან
    if (function_exists('ok_handle_notification')) {
        $post_title = get_the_title(); // ეს ვერ იმუშავებს ლუპის გარეშე, ამიტომ სჯობს:
        $post_title_db = $ok_db->get_var("SELECT post_title FROM posts WHERE id = ?", [$post_id]);
        
        ok_handle_notification([
            'event_type' => 'info',
            'title'      => 'ახალი კომენტარი',
            'message'    => "მომხმარებელმა {$author} დატოვა კომენტარი პოსტზე: {$post_title_db}",
            'link'       => 'comments.php', // ადმინ პანელის ბმული
            'send_email' => true // ვაგზავნით მეილს
        ]);
    }

    // ჰუკი
    if (function_exists('do_action')) {
        do_action('comment_post', $ok_db->last_insert_id(), $comment_approved);
    }
}

// 11. რედირექტი
$post_slug = $ok_db->get_var("SELECT post_slug FROM posts WHERE id = ?", [$post_id]);
$redirect_anchor = $result ? '#comments' : '#respond';
$status_param    = $comment_approved === 'approved' ? 'comment_approved=1' : 'comment_waiting=1';

header('Location: /' . $post_slug . '/?' . $status_param . $redirect_anchor);
exit;