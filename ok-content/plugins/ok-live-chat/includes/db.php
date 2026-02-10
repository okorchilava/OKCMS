<?php
if (!defined('OK_LOADED')) exit;

function ok_chat_install_db() {
    global $ok_db;
    
    // მესიჯები
    $ok_db->query("CREATE TABLE IF NOT EXISTS ok_chat_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_id VARCHAR(64) NOT NULL,
        sender VARCHAR(100), 
        sender_type ENUM('user', 'admin', 'bot') NOT NULL, 
        message TEXT NOT NULL, 
        is_read TINYINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (session_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // სესიები (განახლებული ველებით)
    $ok_db->query("CREATE TABLE IF NOT EXISTS ok_chat_sessions (
        session_id VARCHAR(64) PRIMARY KEY,
        user_name VARCHAR(100) DEFAULT 'სტუმარი',
        mode ENUM('ai', 'human') DEFAULT 'ai', 
        alert_sent TINYINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // პარამეტრები
    $ok_db->query("CREATE TABLE IF NOT EXISTS ok_chat_settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

function ok_chat_get_opt($key, $def='') {
    global $ok_db;
    $res = $ok_db->get_results("SELECT setting_value FROM ok_chat_settings WHERE setting_key = ?", [$key]);
    return ($res && isset($res[0])) ? (is_object($res[0]) ? $res[0]->setting_value : $res[0]['setting_value']) : $def;
}

function ok_chat_save_opt($key, $val) {
    global $ok_db;
    $exists = $ok_db->get_results("SELECT setting_key FROM ok_chat_settings WHERE setting_key = ?", [$key]);
    if ($exists) {
        $ok_db->query("UPDATE ok_chat_settings SET setting_value = ? WHERE setting_key = ?", [$val, $key]);
    } else {
        $ok_db->query("INSERT INTO ok_chat_settings (setting_key, setting_value) VALUES (?, ?)", [$key, $val]);
    }
}

// შიფრაცია იგივე რჩება (ადგილის დასაზოგად არ ვწერ თავიდან, წინა კოდიდან აიღეთ)
function ok_chat_encrypt($data) {
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(OK_CIPHER));
    return base64_encode($iv . openssl_encrypt($data, OK_CIPHER, OK_CHAT_KEY, 0, $iv));
}
function ok_chat_decrypt($data) {
    if (empty($data)) return '';
    $c = base64_decode($data); $ivlen = openssl_cipher_iv_length(OK_CIPHER);
    if (strlen($c) < $ivlen) return $data;
    return openssl_decrypt(substr($c, $ivlen), OK_CIPHER, OK_CHAT_KEY, 0, substr($c, 0, $ivlen));
}