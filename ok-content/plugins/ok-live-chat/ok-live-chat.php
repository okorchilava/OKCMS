<?php
/*
Plugin Name: OK Live Chat (Stable Logic)
Description: ლაივ ჩატი - No Duplicates, Server-Side Rendering Source, Media & Templates.
Version: 26.0
Author: OK Engine
*/

if (!defined('OK_LOADED')) exit;

define('OK_CHAT_KEY', 'Sup3r_S3cret_K3y_CHANGE_THIS_IN_PROD!'); 
define('OK_CIPHER', 'aes-256-cbc');

// ---------------------------------------------------------
// 1. HELPERS & DB INSTALL
// ---------------------------------------------------------

function ok_chat_encrypt($data) {
    $ivlen = openssl_cipher_iv_length(OK_CIPHER);
    $iv = openssl_random_pseudo_bytes($ivlen);
    $encrypted = openssl_encrypt($data, OK_CIPHER, OK_CHAT_KEY, 0, $iv);
    return base64_encode($iv . $encrypted);
}

function ok_chat_decrypt($data) {
    if (empty($data)) return '';
    $c = base64_decode($data);
    $ivlen = openssl_cipher_iv_length(OK_CIPHER);
    if (strlen($c) < $ivlen) return $data; 
    $iv = substr($c, 0, $ivlen);
    $encrypted = substr($c, $ivlen);
    return openssl_decrypt($encrypted, OK_CIPHER, OK_CHAT_KEY, 0, $iv);
}

function ok_chat_install() {
    global $ok_db;
    
    // მესიჯები
    $ok_db->query("CREATE TABLE IF NOT EXISTS ok_chat_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_id VARCHAR(64) NOT NULL,
        sender VARCHAR(100) NOT NULL, 
        sender_type ENUM('user', 'admin') NOT NULL, 
        message TEXT NOT NULL, 
        type ENUM('text', 'sticker') DEFAULT 'text',
        attachment VARCHAR(255) DEFAULT NULL,
        is_read TINYINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (session_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $cols = $ok_db->get_results("SHOW COLUMNS FROM ok_chat_messages LIKE 'type'");
    if (empty($cols)) $ok_db->query("ALTER TABLE ok_chat_messages ADD COLUMN type ENUM('text', 'sticker') DEFAULT 'text'");
    
    $cols2 = $ok_db->get_results("SHOW COLUMNS FROM ok_chat_messages LIKE 'attachment'");
    if (empty($cols2)) $ok_db->query("ALTER TABLE ok_chat_messages ADD COLUMN attachment VARCHAR(255) DEFAULT NULL");

    // სესიები
    $ok_db->query("CREATE TABLE IF NOT EXISTS ok_chat_sessions (
        session_id VARCHAR(64) PRIMARY KEY,
        user_name VARCHAR(100) DEFAULT 'სტუმარი',
        status ENUM('active', 'closed') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        closed_at TIMESTAMP NULL DEFAULT NULL,
        user_typing TIMESTAMP NULL DEFAULT NULL,
        admin_typing TIMESTAMP NULL DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $check_ut = $ok_db->get_results("SHOW COLUMNS FROM ok_chat_sessions LIKE 'user_typing'");
    if(empty($check_ut)) $ok_db->query("ALTER TABLE ok_chat_sessions ADD COLUMN user_typing TIMESTAMP NULL DEFAULT NULL");

    $check_at = $ok_db->get_results("SHOW COLUMNS FROM ok_chat_sessions LIKE 'admin_typing'");
    if(empty($check_at)) $ok_db->query("ALTER TABLE ok_chat_sessions ADD COLUMN admin_typing TIMESTAMP NULL DEFAULT NULL");

    // სხვა ცხრილები
    $ok_db->query("CREATE TABLE IF NOT EXISTS ok_chat_quick_responses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(100) NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $ok_db->query("CREATE TABLE IF NOT EXISTS ok_chat_operators (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $ok_db->query("CREATE TABLE IF NOT EXISTS ok_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT 0,
        for_user_id TEXT,
        read_by_users TEXT,
        type VARCHAR(50) DEFAULT 'info',
        message TEXT,
        link VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

function ok_chat_is_operator($user_id) {
    global $ok_db;
    $res = $ok_db->get_results("SELECT id FROM ok_chat_operators WHERE user_id = ?", [$user_id]);
    return !empty($res);
}

function ok_chat_add_notification_internal($msg, $link = '') {
    global $ok_db;
    $ops = $ok_db->get_results("SELECT user_id FROM ok_chat_operators");
    $admins = $ok_db->get_results("SELECT id FROM ok_users WHERE user_role = 'admin'");
    $ids = [];
    if($ops) foreach($ops as $o) $ids[] = $o->user_id;
    if($admins) foreach($admins as $a) $ids[] = $a->id;
    $ids = array_unique($ids);
    if(!empty($ids)) {
        $for_users = implode(',', $ids);
        $ok_db->query("INSERT INTO ok_notifications (for_user_id, type, message, link, created_at) VALUES (?, 'chat', ?, ?, NOW())", [$for_users, $msg, $link]);
    }
}

// ---------------------------------------------------------
// 2. API & LOGIC
// ---------------------------------------------------------
add_ok_action('init', function() {
    global $ok_db;
    ok_chat_install();
    $session_id = session_id(); if(empty($session_id)) session_start(); $session_id = session_id();

    if (isset($_POST['ok_chat_action'])) {
        while (ob_get_level()) { ob_end_clean(); }
        if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');

        // --- TYPING ---
        if ($_POST['ok_chat_action'] === 'set_typing_status') {
            $sess = $_POST['chat_session_id'] ?? $session_id;
            $type = $_POST['sender_type'] ?? 'user';
            $col = ($type === 'admin') ? 'admin_typing' : 'user_typing';
            $ok_db->query("UPDATE ok_chat_sessions SET $col = NOW() WHERE session_id = ?", [$sess]);
            echo json_encode(['status' => 'success']); exit;
        }

        // --- GET MESSAGES ---
        if ($_POST['ok_chat_action'] === 'get_messages') {
            $sess = $_POST['chat_session_id'] ?? $session_id;
            
            $s_info = $ok_db->get_results("SELECT status, user_typing, admin_typing FROM ok_chat_sessions WHERE session_id=?", [$sess]);
            $status = 'active'; $opponent_typing = false;
            
            if ($s_info) {
                $row = is_object($s_info[0]) ? $s_info[0] : (object)$s_info[0];
                $status = $row->status;
                $is_admin_view = isset($_POST['is_admin_view']);
                if ($is_admin_view) {
                    if ($row->user_typing && (strtotime($row->user_typing) > time() - 4)) $opponent_typing = true;
                } else {
                    if ($row->admin_typing && (strtotime($row->admin_typing) > time() - 4)) $opponent_typing = true;
                }
            }

            $unread_res = $ok_db->get_results("SELECT COUNT(*) as cnt FROM ok_chat_messages WHERE session_id=? AND sender_type='admin' AND is_read=0", [$sess]);
            $unread_count = $unread_res ? (is_object($unread_res[0]) ? $unread_res[0]->cnt : $unread_res[0]->cnt) : 0;

            // ვიღებთ ბოლო 100 მესიჯს, მაგრამ ვალაგებთ ID-ის მიხედვით ზრდადობით
            $msgs = $ok_db->get_results("SELECT * FROM (SELECT * FROM ok_chat_messages WHERE session_id=? ORDER BY created_at DESC LIMIT 100) sub ORDER BY created_at ASC", [$sess]);
            $final_msgs = [];
            if ($msgs) {
                foreach ($msgs as $m) {
                    $obj = is_object($m) ? $m : (object)$m;
                    $obj->message = htmlspecialchars(ok_chat_decrypt($obj->message));
                    $final_msgs[] = $obj;
                }
            }

            if (isset($_POST['chat_open']) && $_POST['chat_open'] === 'true') {
                $target = isset($_POST['is_admin_view']) ? 'user' : 'admin';
                $ok_db->query("UPDATE ok_chat_messages SET is_read=1 WHERE session_id=? AND sender_type=?", [$sess, $target]);
                if (!isset($_POST['is_admin_view'])) $unread_count = 0;
            }

            echo json_encode(['status'=>'success', 'chat_status'=>$status, 'messages'=>$final_msgs, 'unread_count'=>$unread_count, 'opponent_typing'=>$opponent_typing]); exit;
        }

        // --- SEND MESSAGE ---
        if ($_POST['ok_chat_action'] === 'send_message') {
            $msg_raw = trim($_POST['message'] ?? '');
            $sender_type = $_POST['sender_type'] ?? 'user';
            $custom_sess = $_POST['chat_session_id'] ?? $session_id;
            $msg_type = $_POST['msg_type'] ?? 'text'; 
            $attachment = null;

            if ($msg_type === 'sticker') {
                $attachment = $_POST['sticker_url']; 
                if(empty($msg_raw)) $msg_raw = 'Sticker';
            }

            $sender_name = 'Guest';
            global $ok_user;
            if ($sender_type === 'admin') {
                $sender_name = 'Operator';
                if (isset($ok_user->id)) {
                    $u_data = $ok_db->get_results("SELECT display_name FROM ok_users WHERE id = ?", [$ok_user->id]);
                    if ($u_data && !empty($u_data[0]->display_name)) {
                         $parts = explode(' ', trim(is_object($u_data[0]) ? $u_data[0]->display_name : $u_data[0]['display_name']));
                         $sender_name = $parts[0];
                    } elseif (!empty($ok_user->display_name)) {
                         $parts = explode(' ', trim($ok_user->display_name));
                         $sender_name = $parts[0];
                    }
                }
            } else {
                if (isset($ok_user->id)) {
                     $dname = isset($ok_user->display_name) ? $ok_user->display_name : $ok_user->username;
                     $parts = explode(' ', trim($dname)); $sender_name = $parts[0];
                } elseif (isset($_POST['guest_name'])) {
                    $sender_name = strip_tags(trim($_POST['guest_name']));
                } else {
                    $s = $ok_db->get_results("SELECT user_name FROM ok_chat_sessions WHERE session_id=?", [$custom_sess]);
                    if($s) $sender_name = is_object($s[0]) ? $s[0]->user_name : $s[0]->user_name;
                }
            }

            if (!empty($msg_raw) || $attachment) {
                $chk = $ok_db->get_results("SELECT status FROM ok_chat_sessions WHERE session_id=?", [$custom_sess]);
                if (!$chk) $ok_db->query("INSERT INTO ok_chat_sessions (session_id, user_name, status) VALUES (?, ?, 'active')", [$custom_sess, $sender_name]);
                else {
                    $st = is_object($chk[0]) ? $chk[0]->status : $chk[0]->status;
                    if ($st === 'closed') { echo json_encode(['status'=>'error', 'message'=>'ჩატი დასრულებულია']); exit; }
                    if ($sender_type === 'user' && $sender_name !== 'Guest') {
                        $ok_db->query("UPDATE ok_chat_sessions SET user_name=? WHERE session_id=?", [$sender_name, $custom_sess]);
                    }
                }

                $enc = ok_chat_encrypt($msg_raw);
                $ok_db->query("INSERT INTO ok_chat_messages (session_id, sender, sender_type, message, type, attachment) VALUES (?, ?, ?, ?, ?, ?)", 
                    [$custom_sess, $sender_name, $sender_type, $enc, $msg_type, $attachment]);

                if ($sender_type === 'user') ok_chat_add_notification_internal("ახალი მესიჯი: $sender_name", "?page=ok-live-chat&chat_id=$custom_sess");
                echo json_encode(['status' => 'success']);
            }
            exit;
        }

        // --- GLOBAL NOTIFS ---
        if ($_POST['ok_chat_action'] === 'check_global_notifications') {
            global $ok_user;
            if(!isset($ok_user->id)) { echo json_encode(['count'=>0]); exit; }
            $uid = (int)$ok_user->id;
            $cnt = (int)$ok_db->get_var("SELECT COUNT(*) FROM ok_notifications WHERE FIND_IN_SET(?, for_user_id) AND (read_by_users IS NULL OR NOT FIND_IN_SET(?, read_by_users))", [$uid, $uid]);
            $last = ($cnt > 0) ? $ok_db->get_var("SELECT message FROM ok_notifications WHERE FIND_IN_SET(?, for_user_id) AND (read_by_users IS NULL OR NOT FIND_IN_SET(?, read_by_users)) ORDER BY id DESC LIMIT 1", [$uid, $uid]) : '';
            echo json_encode(['count' => $cnt, 'latest_message' => $last]); exit;
        }

        // --- ADMIN HELPERS ---
        if ($_POST['ok_chat_action'] === 'get_quick_responses') {
            $r = $ok_db->get_results("SELECT * FROM ok_chat_quick_responses ORDER BY id DESC");
            echo json_encode(['status'=>'success', 'data'=>$r?:[]]); exit;
        }
        if ($_POST['ok_chat_action'] === 'save_quick_response') {
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            if($id > 0) $ok_db->query("UPDATE ok_chat_quick_responses SET title=?, message=? WHERE id=?", [$_POST['title'], $_POST['message'], $id]);
            else $ok_db->query("INSERT INTO ok_chat_quick_responses (title, message) VALUES (?, ?)", [$_POST['title'], $_POST['message']]);
            echo json_encode(['status' => 'success']); exit;
        }
        if ($_POST['ok_chat_action'] === 'delete_quick_response') {
            $ok_db->query("DELETE FROM ok_chat_quick_responses WHERE id=?", [intval($_POST['id'])]);
            echo json_encode(['status' => 'success']); exit;
        }
        if ($_POST['ok_chat_action'] === 'get_chat_list') {
            $list = $ok_db->get_results("SELECT s.session_id, s.user_name, s.status, MAX(m.created_at) as last_msg_time, 
                (SELECT message FROM ok_chat_messages WHERE session_id = s.session_id ORDER BY id DESC LIMIT 1) as last_msg,
                (SELECT COUNT(*) FROM ok_chat_messages WHERE session_id = s.session_id AND sender_type='user' AND is_read=0) as unread
                FROM ok_chat_sessions s LEFT JOIN ok_chat_messages m ON s.session_id = m.session_id
                GROUP BY s.session_id ORDER BY last_msg_time DESC");
            $data = [];
            if($list) foreach($list as $l) {
                $l = (object)$l;
                $l->last_msg = mb_substr(ok_chat_decrypt($l->last_msg), 0, 40).'...';
                $data[] = $l;
            }
            echo json_encode(['status'=>'success', 'data'=>$data]); exit;
        }
        if ($_POST['ok_chat_action'] === 'end_chat') {
            $ok_db->query("UPDATE ok_chat_sessions SET status='closed', closed_at=NOW() WHERE session_id=?", [$_POST['chat_session_id']]);
            echo json_encode(['status'=>'success']); exit;
        }
        if ($_POST['ok_chat_action'] === 'add_operator') {
             $u = $ok_db->get_results("SELECT id FROM ok_users WHERE email=?", [trim($_POST['email'])]);
             if($u) {
                 $uid = is_object($u[0]) ? $u[0]->id : $u[0]->id;
                 $ok_db->query("INSERT IGNORE INTO ok_chat_operators (user_id) VALUES (?)", [$uid]);
                 echo json_encode(['status'=>'success']);
             } else echo json_encode(['status'=>'error', 'message'=>'მომხმარებელი არ მოიძებნა']); exit;
        }
        if ($_POST['ok_chat_action'] === 'delete_operator') {
            $ok_db->query("DELETE FROM ok_chat_operators WHERE id=?", [(int)$_POST['id']]);
            echo json_encode(['status'=>'success']); exit;
        }
        exit;
    }
});


// ---------------------------------------------------------
// 3. ADMIN NOTIFIER
// ---------------------------------------------------------
function ok_chat_global_notifier_script() {
    global $ok_user;
    if (!isset($ok_user->id) || (!ok_chat_is_operator($ok_user->id) && $ok_user->user_role !== 'admin')) return;
    ?>
    <script>
    (function(){
        let okNotifSound = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');
        let lastCount = -1; let audioUnlocked = false;
        function unlockAdminAudio() { if(!audioUnlocked) okNotifSound.play().then(()=>{ okNotifSound.pause(); okNotifSound.currentTime=0; audioUnlocked=true; }).catch(()=>{}); }
        document.addEventListener('click', unlockAdminAudio, {once:true});
        if (Notification.permission !== "granted") Notification.requestPermission();

        function checkGlobalNotifs() {
            let fd = new FormData(); fd.append('ok_chat_action', 'check_global_notifications');
            fetch('?ajax=1', {method:'POST', body:fd}).then(r=>r.json()).then(d=>{
                let cnt = parseInt(d.count);
                if(lastCount !== -1 && cnt > lastCount) {
                    try { okNotifSound.play(); } catch(e){}
                    if (Notification.permission === "granted" && d.latest_message) new Notification("ახალი შეტყობინება", { body: d.latest_message });
                }
                lastCount = cnt;
            }).catch(e=>{});
        }
        setInterval(checkGlobalNotifs, 5000);
    })();
    </script>
    <?php
}
if (function_exists('add_ok_action')) {
    add_ok_action('admin_footer', 'ok_chat_global_notifier_script'); 
    add_ok_action('ok_footer', 'ok_chat_global_notifier_script');   
}

// ---------------------------------------------------------
// 4. CLIENT WIDGET (FRONTEND - DUPLICATE FIX: NO OPTIMISTIC APPEND)
// ---------------------------------------------------------
function ok_render_live_chat() {
    if (strpos($_SERVER['REQUEST_URI'] ?? '', 'admin') !== false) return;
    global $ok_user;
    $is_logged_in = isset($ok_user->id);
    $user_name = $is_logged_in ? ($ok_user->display_name ?: $ok_user->username) : '';
    if ($is_logged_in && ok_chat_is_operator($ok_user->id)) return;
    ?>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <div id="ok-chat-widget">
        <div class="ok-chat-btn" onclick="toggleOkChat()">
            <i class="bi bi-chat-dots-fill"></i>
            <span id="ok-chat-badge">0</span>
        </div>
        
        <div class="ok-chat-box" id="okChatBox">
            <div class="ok-chat-header">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-headset"></i> <span>Live Chat</span>
                </div>
                <div class="ok-header-controls">
                    <button onclick="toggleOkChat()" class="btn-ctrl-chat" title="ჩაკეცვა"><i class="bi bi-dash-lg"></i></button>
                    <button onclick="confirmEndChat()" class="btn-ctrl-chat" title="დასრულება"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
            
            <div id="okChatNameSection" class="ok-chat-center-msg">
                <i class="bi bi-person-circle text-primary" style="font-size: 40px;"></i>
                <h5 class="mt-3">მოგესალმებით!</h5>
                <p class="text-muted small">გთხოვთ, გაგვეცნოთ:</p>
                <input type="text" id="guestNameInput" class="form-control mb-2 text-center" placeholder="თქვენი სახელი">
                <button class="btn btn-primary w-100" onclick="startChatAsGuest()">დაწყება</button>
            </div>

            <div id="okChatInterface">
                <div class="ok-chat-body" id="okChatBody"></div>
                <div id="okTypingIndicator" class="typing-indicator" style="display:none;"><span></span><span></span><span></span></div>
                
                <div id="okEmojiPicker" class="ok-popup-grid"></div>
                <div id="okStickerPicker" class="ok-popup-grid"></div>

                <div class="ok-chat-footer" id="okChatFooter">
                    <button onclick="toggleEmoji()" title="ემოჯი" class="btn-media"><i class="bi bi-emoji-smile"></i></button>
                    <button onclick="toggleStickers()" title="სტიკერი" class="btn-media"><i class="bi bi-stars"></i></button>
                    <input type="text" id="okChatInput" placeholder="წერილის ტექსტი..." onkeypress="handleEnter(event)" oninput="handleTyping('user')">
                    <button onclick="sendOkMessage()"><i class="bi bi-send-fill"></i></button>
                </div>
                <div class="ok-chat-closed" id="okChatClosedMsg">ჩატი დასრულებულია</div>
            </div>
        </div>
    </div>

    <style>
        #ok-chat-widget { position: fixed; bottom: 20px; right: 20px; z-index: 99990; font-family: sans-serif; }
        .ok-chat-btn { position: relative; width: 60px; height: 60px; background: #0d6efd; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; cursor: pointer; box-shadow: 0 4px 15px rgba(13, 110, 253, 0.4); transition: 0.3s; }
        .ok-chat-btn:hover { transform: scale(1.05); }
        #ok-chat-badge { display:none; position: absolute; top: -5px; right: -5px; min-width: 22px; height: 22px; background: #dc3545; color: white; border-radius: 50%; border: 2px solid white; font-size: 11px; font-weight: bold; align-items: center; justify-content: center; }
        .ok-chat-box { display: none; width: 340px; height: 450px; background: white; position: absolute; bottom: 80px; right: 0; border-radius: 12px; box-shadow: 0 5px 25px rgba(0,0,0,0.15); flex-direction: column; overflow: hidden; border: 1px solid #eee; }
        .ok-chat-header { background: #0d6efd; color: white; padding: 15px; font-weight: bold; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; }
        .ok-header-controls { display: flex; gap: 10px; }
        .btn-ctrl-chat { background: none; border: none; color: white; cursor: pointer; font-size: 16px; opacity: 0.8; padding: 0; }
        .ok-chat-center-msg { display:none; height:100%; flex-direction:column; align-items:center; justify-content:center; padding:20px; text-align:center; }
        #okChatInterface { display:none; flex-direction:column; height:100%; overflow: hidden; flex: 1; position: relative; }
        .ok-chat-body { flex: 1; padding: 15px; overflow-y: auto; background: #f9f9f9; display: flex; flex-direction: column; gap: 8px; }
        .ok-msg { padding: 8px 12px; border-radius: 12px; max-width: 80%; font-size: 14px; line-height: 1.4; word-wrap: break-word; }
        .ok-msg.user { background: #0d6efd; color: white; align-self: flex-end; border-bottom-right-radius: 2px; }
        .ok-msg.admin { background: #e9ecef; color: #333; align-self: flex-start; border-bottom-left-radius: 2px; }
        .msg-sender-name { font-size: 10px; opacity: 0.7; margin-bottom: 2px; display: block; }
        .ok-chat-footer { padding: 8px; border-top: 1px solid #eee; display: flex; gap: 5px; background: white; flex-shrink: 0; align-items: center; }
        #okChatInput { flex: 1; border: 1px solid #ddd; padding: 8px 12px; border-radius: 20px; outline: none; font-size: 13px; }
        .ok-chat-footer button { background: none; border: none; color: #0d6efd; font-size: 18px; cursor: pointer; padding: 0 5px; }
        .btn-media { color: #6c757d !important; transition: 0.2s; }
        .btn-media:hover { color: #0d6efd !important; }
        .ok-chat-closed { display:none; padding: 15px; text-align: center; background: #f8d7da; color: #721c24; font-weight: bold; font-size: 14px; border-top: 1px solid #f5c6cb; }
        .typing-indicator { padding: 5px 20px; background: #f1f1f1; font-size: 12px; color: #888; display: flex; align-items: center; gap: 3px; }
        .typing-indicator span { display: inline-block; width: 6px; height: 6px; background: #aaa; border-radius: 50%; animation: typing 1.4s infinite ease-in-out both; }
        .typing-indicator span:nth-child(1) { animation-delay: -0.32s; }
        .typing-indicator span:nth-child(2) { animation-delay: -0.16s; }
        @keyframes typing { 0%, 80%, 100% { transform: scale(0); } 40% { transform: scale(1); } }
        .ok-popup-grid { display:none; position:absolute; bottom:55px; left:10px; width:250px; height:200px; background:white; border:1px solid #ddd; box-shadow:0 -5px 15px rgba(0,0,0,0.1); border-radius:8px; overflow-y:auto; padding:10px; z-index:10; grid-template-columns: repeat(5, 1fr); gap:5px; }
        .ok-emoji-item, .ok-sticker-item { font-size:20px; cursor:pointer; text-align:center; padding:5px; border-radius:4px; }
        .ok-emoji-item:hover, .ok-sticker-item:hover { background:#f1f1f1; }
        .ok-sticker-item img { width: 40px; height: 40px; }
    </style>

    <script>
        const isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
        const loggedInName = "<?php echo htmlspecialchars($user_name); ?>";
        let chatOpen = false;
        let isChatClosed = false;

        const clientAudio = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');
        let clientAudioUnlocked = false;
        let clientLastRenderedId = 0; 
        let typingTimeout = null;

        const EMOJIS = ['😊','😂','😍','👍','👎','🔥','❤️','😭','😎','🤔','🎉','👋','🙏','💩','👻','😡','👀','✅','❌','💪'];
        const STICKERS = [
            'https://cdn-icons-png.flaticon.com/128/742/742751.png', 
            'https://cdn-icons-png.flaticon.com/128/742/742752.png',
            'https://cdn-icons-png.flaticon.com/128/742/742824.png',
            'https://cdn-icons-png.flaticon.com/128/742/742928.png',
            'https://cdn-icons-png.flaticon.com/128/166/166538.png',
            'https://cdn-icons-png.flaticon.com/128/414/414902.png'
        ];

        function unlockClientAudio() {
            if(clientAudioUnlocked) return;
            clientAudio.play().then(() => { clientAudio.pause(); clientAudio.currentTime=0; clientAudioUnlocked=true; }).catch(()=>{}); 
        }
        document.addEventListener('click', unlockClientAudio);

        // --- Typing ---
        function handleTyping(type) {
            if(isChatClosed) return;
            if(typingTimeout) return; 
            typingTimeout = setTimeout(() => {
                let fd = new FormData(); fd.append('ok_chat_action', 'set_typing_status'); fd.append('sender_type', type);
                fetch(window.location.href, {method:'POST', body:fd});
                typingTimeout = null;
            }, 1500);
        }

        function toggleOkChat() {
            const box = document.getElementById('okChatBox');
            chatOpen = !chatOpen;
            box.style.display = chatOpen ? 'flex' : 'none';
            if(chatOpen) { 
                document.getElementById('ok-chat-badge').style.display = 'none';
                unlockClientAudio(); 
                checkUserIdentity(); 
                scrollToBottom();
            }
        }
        function checkUserIdentity() {
            const nameSec = document.getElementById('okChatNameSection');
            const intSec = document.getElementById('okChatInterface');
            if(isLoggedIn || sessionStorage.getItem('ok_chat_guest_name')) {
                nameSec.style.display = 'none'; intSec.style.display = 'flex';
            } else {
                nameSec.style.display = 'flex'; intSec.style.display = 'none';
            }
        }
        function startChatAsGuest() {
            const val = document.getElementById('guestNameInput').value.trim();
            if(!val) return;
            sessionStorage.setItem('ok_chat_guest_name', val);
            unlockClientAudio(); checkUserIdentity();
        }
        function handleEnter(e) { if(e.key==='Enter') sendOkMessage(); }
        
        function sendOkMessage() {
            if(isChatClosed) return;
            const inp = document.getElementById('okChatInput');
            const msg = inp.value.trim(); if(!msg) return;
            sendMessageInternal(msg, 'text');
            inp.value = ''; 
        }

        function sendMessageInternal(content, type, attachment = null) {
            unlockClientAudio();
            let name = isLoggedIn ? loggedInName : sessionStorage.getItem('ok_chat_guest_name');
            
            // NO OPTIMISTIC APPEND - Wait for server to avoid duplicates
            // We just clear the input and send request.
            // The polling loop will pick it up in < 3s.
            
            let fd = new FormData();
            fd.append('ok_chat_action', 'send_message');
            fd.append('sender_type', 'user');
            if (type === 'sticker') {
                fd.append('msg_type', 'sticker');
                fd.append('sticker_url', content);
                fd.append('message', '');
            } else {
                fd.append('message', content);
            }

            if(!isLoggedIn && name) fd.append('guest_name', name);
            // Immediately trigger load to reduce delay
            fetch(window.location.href, {method:'POST', body:fd}).then(() => loadMessages());
        }

        function toggleEmoji() {
            const p = document.getElementById('okEmojiPicker');
            const s = document.getElementById('okStickerPicker');
            s.style.display = 'none';
            if(p.style.display === 'grid') { p.style.display = 'none'; return; }
            p.innerHTML = ''; p.style.display = 'grid';
            EMOJIS.forEach(e => {
                let s = document.createElement('div'); s.className = 'ok-emoji-item'; s.innerText = e;
                s.onclick = () => { document.getElementById('okChatInput').value += e; p.style.display = 'none'; };
                p.appendChild(s);
            });
        }
        function toggleStickers() {
            const p = document.getElementById('okStickerPicker');
            const e = document.getElementById('okEmojiPicker');
            e.style.display = 'none';
            if(p.style.display === 'grid') { p.style.display = 'none'; return; }
            p.innerHTML = ''; p.style.display = 'grid';
            STICKERS.forEach(url => {
                let d = document.createElement('div'); d.className = 'ok-sticker-item';
                d.innerHTML = `<img src="${url}">`;
                d.onclick = () => { sendMessageInternal(url, 'sticker'); p.style.display = 'none'; };
                p.appendChild(d);
            });
        }

        function confirmEndChat() {
            Swal.fire({
                title: 'დასრულება', text: "ნამდვილად გსურთ ჩატის დასრულება?", icon: 'warning',
                showCancelButton: true, confirmButtonText: 'კი', cancelButtonText: 'არა'
            }).then((res) => {
                if(res.isConfirmed) {
                    let fd = new FormData(); fd.append('ok_chat_action', 'end_chat');
                    fetch(window.location.href, {method:'POST', body:fd}).then(()=>{
                         isChatClosed = true; updateInterfaceState();
                         Swal.fire('დასრულებულია', '', 'success');
                    });
                }
            });
        }

        function loadMessages() {
            let fd = new FormData();
            fd.append('ok_chat_action', 'get_messages');
            fd.append('chat_open', chatOpen ? 'true' : 'false');
            
            fetch(window.location.href, {method:'POST', body:fd}).then(r=>r.json()).then(d=>{
                if(d.status === 'success') {
                    const badge = document.getElementById('ok-chat-badge');
                    if(d.unread_count > 0 && !chatOpen) { badge.innerText = d.unread_count; badge.style.display = 'flex'; }
                    else badge.style.display = 'none';

                    isChatClosed = (d.chat_status === 'closed');
                    updateInterfaceState();
                    
                    const typingDiv = document.getElementById('okTypingIndicator');
                    if(d.opponent_typing) typingDiv.style.display = 'flex'; else typingDiv.style.display = 'none';

                    if(chatOpen) {
                        // SMART DIFF LOGIC (APPEND ONLY NEW)
                        const newMsgs = d.messages.filter(m => parseInt(m.id) > clientLastRenderedId);
                        
                        if (clientLastRenderedId === 0 && d.messages.length > 0) {
                             // First load: Render all
                             document.getElementById('okChatBody').innerHTML = ''; 
                             d.messages.forEach(allM => {
                                 renderClientMsg(allM);
                                 clientLastRenderedId = Math.max(clientLastRenderedId, parseInt(allM.id));
                             });
                             scrollToBottom();
                             return;
                        } 
                        
                        if(newMsgs.length > 0) {
                            newMsgs.forEach(m => {
                                renderClientMsg(m);
                                clientLastRenderedId = parseInt(m.id);
                                if(m.sender_type === 'admin') { try { clientAudio.play(); } catch(e){} }
                                scrollToBottom();
                            });
                        }
                    }
                }
            }).catch(e=>{});
        }
        
        function renderClientMsg(m) {
            let content = m.message;
            if(m.type === 'sticker') content = `<img src="${m.attachment}" style="width:60px;">`;
            appendMessage(content, m.sender_type, m.sender);
        }

        function updateInterfaceState() {
            const foot = document.getElementById('okChatFooter');
            const clos = document.getElementById('okChatClosedMsg');
            if(isChatClosed) { foot.style.display='none'; clos.style.display='block'; }
            else { foot.style.display='flex'; clos.style.display='none'; }
        }

        function appendMessage(text, type, name) {
            const body = document.getElementById('okChatBody');
            const div = document.createElement('div'); div.className = 'ok-msg '+type;
            div.innerHTML = `<span class="msg-sender-name">${name}</span>${text}`;
            body.appendChild(div);
        }
        function scrollToBottom() { 
            const b = document.getElementById('okChatBody'); 
            requestAnimationFrame(() => { b.scrollTop = b.scrollHeight; });
        }
        setInterval(loadMessages, 3000);
    </script>
    <?php
}
if (function_exists('add_ok_action')) add_ok_action('ok_head', 'ok_render_live_chat');


// ---------------------------------------------------------
// 5. ADMIN CHAT PAGE (SEAMLESS UPDATE & NO DUPLICATE)
// ---------------------------------------------------------
add_ok_action('admin_menu', function() {
    add_menu_page('ლაივ ჩატი', 'ლაივ ჩატი', 'manage_options', 'ok-live-chat', 'ok_render_admin_chat_page', 'bi bi-chat-text-fill', 56);
    add_submenu_page('ok-live-chat', 'ლაივ ჩატი', 'ლაივ ჩატი', 'manage_options', 'ok-live-chat', 'ok_render_admin_chat_page');
    add_submenu_page('ok-live-chat', 'ოპერატორები', 'ოპერატორები', 'manage_options', 'ok-live-chat-ops', 'ok_render_operators_page');
    add_submenu_page('ok-live-chat', 'ოპერატორის შაბლონები', 'ოპერატორის შაბლონები', 'manage_options', 'ok-live-chat-templates', 'ok_render_templates_page');
});

function ok_render_admin_chat_page() {
    global $ok_db;
    $active_chat = $_GET['chat_id'] ?? null;
    $base_url = '?page=ok-live-chat';
    ?>
    <style>
        .chat-layout { display: flex; height: 85vh; border: 1px solid #ccc; background: white; margin-top: 10px; border-radius: 5px; overflow: hidden; }
        .chat-sidebar { width: 300px; border-right: 1px solid #eee; overflow-y: auto; background: #f8f9fa; }
        .chat-main { flex: 1; display: flex; flex-direction: column; position: relative; }
        .chat-session-item { padding: 15px; border-bottom: 1px solid #eee; text-decoration: none; display: block; color: #333; }
        .chat-session-item:hover, .chat-session-item.active { background: #e9ecef; }
        .chat-messages-area { flex: 1; padding: 20px; overflow-y: auto; background: #fff; display: flex; flex-direction: column; gap: 10px; }
        .admin-msg { padding: 10px 15px; border-radius: 15px; max-width: 70%; font-size: 14px; }
        .admin-msg.me { align-self: flex-end; background: #007bff; color: white; }
        .admin-msg.other { align-self: flex-start; background: #f1f1f1; color: black; }
        .chat-input-area { padding: 15px; border-top: 1px solid #eee; background: #f8f9fa; display: flex; gap: 10px; align-items:center; }
        .typing-bubble { margin-bottom:10px; align-self:flex-start; background:#f1f1f1; padding:8px 15px; border-radius:15px; display:none; }
        
        /* Admin Media Popups */
        .admin-popup { display:none; position:absolute; bottom:70px; left:20px; width:250px; height:200px; background:white; border:1px solid #ddd; box-shadow:0 -5px 15px rgba(0,0,0,0.1); border-radius:8px; overflow-y:auto; padding:10px; z-index:99; grid-template-columns: repeat(5, 1fr); gap:5px; }
        .admin-emoji-item, .admin-sticker-item { font-size:20px; cursor:pointer; text-align:center; padding:5px; border-radius:4px; }
        .admin-emoji-item:hover, .admin-sticker-item:hover { background:#f1f1f1; }
        .admin-sticker-item img { width: 40px; height: 40px; }
        .btn-admin-media { color: #555; background: none; border: none; font-size: 18px; cursor: pointer; }
    </style>

    <div class="wrap">
        <h2><i class="bi bi-chat-text-fill"></i> Live Chat Console</h2>
        <div class="chat-layout">
            <div class="chat-sidebar" id="adminSessionList">Loading...</div>
            <div class="chat-main">
                <?php if($active_chat): ?>
                    <div class="p-3 border-bottom d-flex justify-content-between">
                        <strong>Chat #<?php echo htmlspecialchars($active_chat); ?></strong>
                        <button onclick="endAdminChat()" class="btn btn-sm btn-danger">დასრულება</button>
                    </div>
                    <div class="chat-messages-area" id="adminMsgArea"></div>
                    <div id="adminTypingBubble" class="typing-bubble">წერს...</div>
                    
                    <div id="adminEmojiPanel" class="admin-popup"></div>
                    <div id="adminStickerPanel" class="admin-popup"></div>

                    <div class="chat-input-area" id="adminInputArea">
                         <div class="dropdown">
                           <button class="btn btn-warning dropdown-toggle" type="button" data-bs-toggle="dropdown" id="quickRespBtn"><i class="bi bi-lightning-fill"></i></button>
                           <ul class="dropdown-menu" id="quickRespList"><li><a class="dropdown-item" href="#">Loading...</a></li></ul>
                         </div>
                         
                         <button class="btn-admin-media" onclick="toggleAdminEmoji()" title="Emoji"><i class="bi bi-emoji-smile"></i></button>
                         <button class="btn-admin-media" onclick="toggleAdminStickers()" title="Sticker"><i class="bi bi-stars"></i></button>

                         <input type="text" id="adminInp" class="form-control" placeholder="Type a message..." onkeypress="if(event.key==='Enter') sendAdminMsg()" oninput="handleAdminTyping()">
                        <button class="btn btn-primary" onclick="sendAdminMsg()">Send</button>
                    </div>
                    <div id="adminClosedMsg" class="p-3 text-center bg-danger text-white" style="display:none;">ჩატი დასრულებულია</div>
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center h-100 text-muted">აირჩიეთ ჩატი მარცხნივ</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        const activeChat = '<?php echo $active_chat; ?>';
        const baseUrl = '<?php echo $base_url; ?>';
        
        // Shared Assets
        const EMOJIS = ['😊','😂','😍','👍','👎','🔥','❤️','😭','😎','🤔','🎉','👋','🙏','💩','👻','😡','👀','✅','❌','💪'];
        const STICKERS = [
            'https://cdn-icons-png.flaticon.com/128/742/742751.png', 
            'https://cdn-icons-png.flaticon.com/128/742/742752.png',
            'https://cdn-icons-png.flaticon.com/128/742/742824.png',
            'https://cdn-icons-png.flaticon.com/128/742/742928.png',
            'https://cdn-icons-png.flaticon.com/128/166/166538.png',
            'https://cdn-icons-png.flaticon.com/128/414/414902.png'
        ];

        let adminTypingTimeout = null;
        let adminLastRenderedId = 0; // ADMIN SMART DIFF

        function handleAdminTyping() {
            if(adminTypingTimeout) return;
            adminTypingTimeout = setTimeout(() => {
                let fd = new FormData(); fd.append('ok_chat_action', 'set_typing_status'); fd.append('chat_session_id', activeChat); fd.append('sender_type', 'admin');
                fetch(window.location.href, {method:'POST', body:fd}); adminTypingTimeout = null;
            }, 1500);
        }
        function loadSessions() {
            let fd = new FormData(); fd.append('ok_chat_action', 'get_chat_list');
            fetch(window.location.href, {method:'POST', body:fd}).then(r=>r.json()).then(d=>{
                const con = document.getElementById('adminSessionList');
                if(d.data.length === 0) { con.innerHTML = '<div class="p-3">ცარიელია</div>'; return; }
                let h = '';
                d.data.forEach(s => {
                    let cls = (s.session_id === activeChat) ? 'active' : '';
                    let badge = s.unread > 0 ? `<span class="badge bg-danger float-end">${s.unread}</span>` : '';
                    let status = s.status === 'closed' ? '<i class="bi bi-x text-danger"></i>' : '<i class="bi bi-circle-fill text-success" style="font-size:8px;"></i>';
                    h += `<a href="${baseUrl}&chat_id=${s.session_id}" class="chat-session-item ${cls}">
                        <div>${status} <strong>${s.user_name}</strong> ${badge}</div>
                        <small class="text-muted">${s.last_msg}</small>
                    </a>`;
                });
                con.innerHTML = h;
            });
        }
        setInterval(loadSessions, 3000); loadSessions();

        if(activeChat) {
            function loadChat() {
                let fd = new FormData(); fd.append('ok_chat_action', 'get_messages'); fd.append('chat_session_id', activeChat); fd.append('is_admin_view', '1');
                fetch(window.location.href, {method:'POST', body:fd}).then(r=>r.json()).then(d=>{
                    if(d.chat_status === 'closed') {
                        document.getElementById('adminInputArea').style.display='none';
                        document.getElementById('adminClosedMsg').style.display='block';
                    }
                    const area = document.getElementById('adminMsgArea');
                    const bub = document.getElementById('adminTypingBubble');
                    if(d.opponent_typing) bub.style.display = 'block'; else bub.style.display = 'none';

                    // ADMIN SMART DIFF
                    const newMsgs = d.messages.filter(m => parseInt(m.id) > adminLastRenderedId);
                    
                    if (adminLastRenderedId === 0 && d.messages.length > 0) {
                        area.innerHTML = '';
                        d.messages.forEach(m => {
                            renderAdminMsg(m, area);
                            adminLastRenderedId = Math.max(adminLastRenderedId, parseInt(m.id));
                        });
                        requestAnimationFrame(() => { area.scrollTop = area.scrollHeight; });
                        return;
                    }

                    if(newMsgs.length > 0) {
                        newMsgs.forEach(m => {
                            renderAdminMsg(m, area);
                            adminLastRenderedId = parseInt(m.id);
                            requestAnimationFrame(() => { area.scrollTop = area.scrollHeight; });
                        });
                    }
                });
            }
            
            function renderAdminMsg(m, container) {
                let cls = (m.sender_type === 'admin') ? 'me' : 'other';
                let content = m.message;
                if(m.type === 'sticker') content = `<img src="${m.attachment}" style="width:60px;">`;
                container.innerHTML += `<div class="admin-msg ${cls}"><div>${content}</div><small style="opacity:0.6;font-size:10px;">${m.sender}</small></div>`;
            }

            setInterval(loadChat, 3000); loadChat();

            window.sendAdminInternal = function(content, type) {
                let fd = new FormData(); 
                fd.append('ok_chat_action', 'send_message'); 
                fd.append('chat_session_id', activeChat); 
                fd.append('sender_type', 'admin'); 
                if(type === 'sticker') {
                    fd.append('msg_type', 'sticker');
                    fd.append('sticker_url', content);
                    fd.append('message', '');
                } else {
                    fd.append('message', content);
                }
                
                // NO OPTIMISTIC APPEND HERE EITHER FOR ADMIN
                fetch(window.location.href, {method:'POST', body:fd}).then(()=>{ loadChat(); });
            }

            window.sendAdminMsg = function() {
                let val = document.getElementById('adminInp').value; if(!val) return;
                sendAdminInternal(val, 'text');
                document.getElementById('adminInp').value='';
            };
            window.endAdminChat = function() {
                if(!confirm('დავასრულოთ?')) return;
                let fd = new FormData(); fd.append('ok_chat_action', 'end_chat'); fd.append('chat_session_id', activeChat);
                fetch(window.location.href, {method:'POST', body:fd}).then(()=>loadChat());
            }

            // Quick Responses Logic
            document.getElementById('quickRespBtn').addEventListener('click', function() {
                 let fd = new FormData(); fd.append('ok_chat_action', 'get_quick_responses');
                 fetch(window.location.href, {method:'POST', body:fd}).then(r=>r.json()).then(d=>{
                     const ul = document.getElementById('quickRespList'); ul.innerHTML = '';
                     if(d.data.length > 0) {
                         d.data.forEach(q => {
                             let li = document.createElement('li');
                             li.innerHTML = `<a class="dropdown-item" href="#" onclick="document.getElementById('adminInp').value='${q.message}'"><strong>${q.title}</strong><br><small>${q.message.substring(0,20)}...</small></a>`;
                             ul.appendChild(li);
                         });
                     } else ul.innerHTML = '<li><span class="dropdown-item">შაბლონები არ არის</span></li>';
                 });
            });

            // Admin Media Toggles
            window.toggleAdminEmoji = function() {
                const p = document.getElementById('adminEmojiPanel');
                const s = document.getElementById('adminStickerPanel');
                s.style.display = 'none';
                if(p.style.display === 'grid') { p.style.display = 'none'; return; }
                p.innerHTML = ''; p.style.display = 'grid';
                EMOJIS.forEach(e => {
                    let d = document.createElement('div'); d.className = 'admin-emoji-item'; d.innerText = e;
                    d.onclick = () => { document.getElementById('adminInp').value += e; p.style.display = 'none'; };
                    p.appendChild(d);
                });
            }
            window.toggleAdminStickers = function() {
                const p = document.getElementById('adminStickerPanel');
                const e = document.getElementById('adminEmojiPanel');
                e.style.display = 'none';
                if(p.style.display === 'grid') { p.style.display = 'none'; return; }
                p.innerHTML = ''; p.style.display = 'grid';
                STICKERS.forEach(url => {
                    let d = document.createElement('div'); d.className = 'admin-sticker-item';
                    d.innerHTML = `<img src="${url}">`;
                    d.onclick = () => { sendAdminInternal(url, 'sticker'); p.style.display = 'none'; };
                    p.appendChild(d);
                });
            }
        }
    </script>
    <?php
}

function ok_render_operators_page() {
    global $ok_db;
    $ops = $ok_db->get_results("SELECT o.id, u.email, u.display_name FROM ok_chat_operators o JOIN ok_users u ON o.user_id = u.id");
    ?>
    <div class="wrap"><h2>ოპერატორები</h2><form onsubmit="addOp(event)" class="d-flex gap-2 mb-4" style="max-width:400px;"><input type="email" id="newOpEmail" class="form-control" placeholder="Email" required><button class="btn btn-primary">დამატება</button></form><table class="table bg-white"><thead><tr><th>სახელი</th><th>Email</th><th>Action</th></tr></thead><tbody><?php if($ops) foreach($ops as $o): ?><tr><td><?= htmlspecialchars($o->display_name) ?></td><td><?= htmlspecialchars($o->email) ?></td><td><button class="btn btn-danger btn-sm" onclick="delOp(<?= $o->id ?>)">წაშლა</button></td></tr><?php endforeach; ?></tbody></table></div><script>function addOp(e) { e.preventDefault(); let fd = new FormData(); fd.append('ok_chat_action','add_operator'); fd.append('email', document.getElementById('newOpEmail').value); fetch(window.location.href, {method:'POST', body:fd}).then(r=>r.json()).then(d=>{ if(d.status==='success') location.reload(); else alert(d.message); }); } function delOp(id) { if(!confirm('Delete?')) return; let fd = new FormData(); fd.append('ok_chat_action','delete_operator'); fd.append('id', id); fetch(window.location.href, {method:'POST', body:fd}).then(()=>location.reload()); }</script><?php
}

function ok_render_templates_page() {
    global $ok_db;
    $res = $ok_db->get_results("SELECT * FROM ok_chat_quick_responses ORDER BY id DESC");
    ?>
    <div class="wrap"><h2>ოპერატორის შაბლონები</h2>
    <div class="card p-3 mb-3" style="max-width:500px;">
        <form onsubmit="saveTpl(event)">
            <input type="text" id="tplTitle" class="form-control mb-2" placeholder="სათაური (მაგ: ფასი)" required>
            <textarea id="tplMsg" class="form-control mb-2" placeholder="პასუხის ტექსტი..." required></textarea>
            <button class="btn btn-success w-100">შენახვა</button>
        </form>
    </div>
    <div class="list-group" style="max-width:500px;">
        <?php if($res) foreach($res as $r): ?>
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <div><strong><?= htmlspecialchars($r->title) ?></strong><br><small><?= htmlspecialchars($r->message) ?></small></div>
                <button class="btn btn-sm btn-danger" onclick="delTpl(<?= $r->id ?>)"><i class="bi bi-trash"></i></button>
            </div>
        <?php endforeach; ?>
    </div>
    </div>
    <script>
    function saveTpl(e) { e.preventDefault(); let fd = new FormData(); fd.append('ok_chat_action','save_quick_response'); fd.append('title', document.getElementById('tplTitle').value); fd.append('message', document.getElementById('tplMsg').value); fetch(window.location.href, {method:'POST', body:fd}).then(()=>location.reload()); }
    function delTpl(id) { if(!confirm('Delete?')) return; let fd = new FormData(); fd.append('ok_chat_action','delete_quick_response'); fd.append('id', id); fetch(window.location.href, {method:'POST', body:fd}).then(()=>location.reload()); }
    </script>
    <?php
}