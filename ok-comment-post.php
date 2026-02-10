<?php
declare(strict_types=1);

/**
 * OK ძრავის კომენტარების დამმუშავებელი (Handler)
 *
 * @package OK_Engine
 * @version 1.2 (Secured & Anti-Spam)
 */

// 1. ვტვირთავთ ძრავის ბირთვს
require_once __DIR__ . '/ok-core/load.php';

// 2. მეთოდის შემოწმება
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    die('Error: Method not allowed.');
}

// 3. უსაფრთხოება: CSRF დაცვა (Nonce)
// ფორმაში უნდა იყოს ველი: <?php ok_nonce_field('post_comment'); ? >

if (!isset($_POST['_ok_nonce']) || !ok_verify_nonce($_POST['_ok_nonce'], 'post_comment')) {
    http_response_code(403);
    die('Error: Security check failed (Nonce).');
}

// 4. უსაფრთხოება: Spam Honeypot
// ფორმაში უნდა იყოს დამალული ველი name="ok_website_field"
if (!empty($_POST['ok_website_field'])) {
    // თუ ბოტმა შეავსო ეს ველი, ვაიგნორებთ მოთხოვნას
    die('Error: Spam detected.');
}

// 5. მონაცემების მიღება და გასუფთავება
global $ok_db;

$post_id = isset($_POST['comment_post_id']) ? (int)$_POST['comment_post_id'] : 0;
$comment = isset($_POST['comment']) ? trim(strip_tags($_POST['comment'])) : '';

// 6. ავტორის იდენტიფიკაცია
$user_id = 0;
$author  = '';
$email   = '';
$url     = '';

// თუ მომხმარებელი ავტორიზებულია, ვიღებთ მის მონაცემებს (უფრო სანდოა)
if (isset($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
    $user    = $ok_db->get_row("SELECT display_name, email FROM users WHERE id = ?", [$user_id]);
    if ($user) {
        $author = $user->display_name;
        $email  = $user->email;
        // URL შეიძლება მომხმარებლის პროფილის ველიდან წამოვიღოთ, თუ გაქვს
    }
} else {
    // თუ სტუმარია
    $author = isset($_POST['author']) ? trim(strip_tags($_POST['author'])) : '';
    $email  = isset($_POST['email']) ? trim($_POST['email']) : '';
    $url    = isset($_POST['url']) ? trim(strip_tags($_POST['url'])) : '';
}

// 7. ვალიდაცია
$post_exists = $ok_db->get_var("SELECT id FROM posts WHERE id = ? AND post_status = 'published'", [$post_id]);

if (!$post_exists) {
    die('Error: Post not found.');
}

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