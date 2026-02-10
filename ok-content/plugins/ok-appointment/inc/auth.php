<?php
if (!defined('OK_LOADED')) exit;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

add_ok_action('init', function() {
    global $ok_db;

    // --- 1. რეგისტრაცია ---
    if (isset($_POST['ok_do_register'])) {
        // Nonce შემოწმება უსაფრთხოებისთვის
        if (!isset($_POST['_ok_nonce']) || !ok_verify_nonce($_POST['_ok_nonce'], 'ok_register_action')) {
            $_SESSION['ok_app_flash_msg'] = ['type' => 'danger', 'text' => 'უსაფრთხოების შეცდომა (Nonce).'];
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }

        // ველების მიღება და გასუფთავება
        $name     = strip_tags($_POST['reg_name']);
        $username = trim(strip_tags($_POST['reg_username'])); // ახალი ველი
        $email    = filter_var($_POST['reg_email'], FILTER_SANITIZE_EMAIL);
        $pass     = $_POST['reg_pass'];

        if (empty($name) || empty($username) || empty($email) || empty($pass)) {
            $_SESSION['ok_app_flash_msg'] = ['type' => 'danger', 'text' => 'შეავსეთ ყველა ველი.'];
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }

        // ვამოწმებთ არსებობს თუ არა მომხმარებელი (ელ-ფოსტა ან იუზერნეიმი)
        $exists = $ok_db->get_row("SELECT id FROM ok_users WHERE email = ? OR username = ?", [$email, $username]);

        if ($exists) {
            $_SESSION['ok_app_flash_msg'] = ['type' => 'danger', 'text' => 'მომხმარებელი ამ ელ-ფოსტით ან სახელით უკვე არსებობს.'];
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            // ვწერთ username-საც ბაზაში
            $insert = $ok_db->query(
                "INSERT INTO ok_users (display_name, username, email, password, created_at) VALUES (?, ?, ?, ?, NOW())", 
                [$name, $username, $email, $hash]
            );
            
            if ($insert) {
                $_SESSION['ok_app_flash_msg'] = ['type' => 'success', 'text' => 'რეგისტრაცია წარმატებულია! გთხოვთ გაიაროთ ავტორიზაცია.'];
            } else {
                $_SESSION['ok_app_flash_msg'] = ['type' => 'danger', 'text' => 'სისტემური შეცდომა რეგისტრაციისას.'];
            }
        }
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }

    // --- 2. ავტორიზაცია (Login) ---
    if (isset($_POST['ok_do_login'])) {
        if (!isset($_POST['_ok_nonce']) || !ok_verify_nonce($_POST['_ok_nonce'], 'ok_login_action')) {
            $_SESSION['ok_app_flash_msg'] = ['type' => 'danger', 'text' => 'უსაფრთხოების შეცდომა (Session Expired).'];
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }

        // ფორმა აგზავნის login_username-ს (და არა login_email-ს)
        $username = trim($_POST['login_username']);
        $pass = $_POST['login_pass'];

        // ვეძებთ მომხმარებელს username-ით
        $user = $ok_db->get_row("SELECT * FROM ok_users WHERE username = ?", [$username]);

        if ($user && password_verify($pass, $user->password)) {
            
            // --- SESSION LOGIC ---
            // 1. მთავარი იდენტიფიკატორი
            $_SESSION['user_id'] = (int)$user->id;
            
            // 2. ოპტიმიზაციისთვის (რომ კაბინეტში ბაზიდან აღარ მოითხოვოს სახელი)
            $_SESSION['display_name'] = $user->display_name;
            
            // 3. დამატებითი ინფო (სურვილისამებრ)
            $_SESSION['user_email'] = $user->email;
            $_SESSION['username']   = $user->username;

            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $_SESSION['ok_app_flash_msg'] = ['type' => 'danger', 'text' => 'მომხმარებელი ან პაროლი არასწორია.'];
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }
    }

    // --- 3. გასვლა (Logout) ---
    if (isset($_GET['ok_action']) && $_GET['ok_action'] == 'logout') {
        
        // ვშლით სესიის მონაცემებს
        unset($_SESSION['user_id']);
        unset($_SESSION['display_name']); // ესეც უნდა წაიშალოს
        unset($_SESSION['user_email']);
        unset($_SESSION['username']);
        
        // ძველი ნარჩენებისგან გასუფთავება
        if(isset($_SESSION['ok_cab_user'])) unset($_SESSION['ok_cab_user']);

        // გადამისამართება სუფთა URL-ზე
        header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
        exit;
    }
});