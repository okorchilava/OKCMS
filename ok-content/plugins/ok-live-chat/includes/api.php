<?php
if (!defined('OK_LOADED')) exit;

function ok_chat_handle_api() {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');

    global $ok_db;
    $act = $_POST['ok_chat_action'];
    $sess = $_POST['session_id'] ?? '';

    // --- 1. მესიჯის გაგზავნა ---
    if ($act === 'send_message') {
        $msg = trim($_POST['message']);
        $name = $_POST['user_name'] ?? 'Guest';
        $type = $_POST['sender_type'] ?? 'user'; // user ან admin (საიტის ადმინიდან)

        if ($msg) {
            // სესიის შემოწმება/შექმნა
            $session = $ok_db->get_results("SELECT * FROM ok_chat_sessions WHERE session_id=?", [$sess]);
            
            if (!$session) {
                $ok_db->query("INSERT INTO ok_chat_sessions (session_id, user_name, mode, created_at) VALUES (?, ?, 'ai', NOW())", [$sess, $name]);
                $mode = 'ai';
            } else {
                $session = is_object($session[0]) ? $session[0] : (object)$session[0];
                $mode = $session->mode;
                
                // თუ ადმინი წერს, რეჟიმი გადადის HUMAN-ზე
                if ($type === 'admin') {
                    $mode = 'human';
                    $ok_db->query("UPDATE ok_chat_sessions SET mode='human', last_activity=NOW() WHERE session_id=?", [$sess]);
                } else {
                    $ok_db->query("UPDATE ok_chat_sessions SET last_activity=NOW() WHERE session_id=?", [$sess]);
                }
            }

            // მესიჯის შენახვა
            $ok_db->query("INSERT INTO ok_chat_messages (session_id, sender, sender_type, message) VALUES (?, ?, ?, ?)", 
                [$sess, ($type=='admin'?'Operator':$name), $type, ok_chat_encrypt($msg)]);

            // თუ ადმინმა მოიწერა, ტელეგრამზე გავუშვათ მაინც (რომ იქაც ჩანდეს ისტორია)
            // თუ იუზერმა მოიწერა და რეჟიმი HUMAN არის -> ეგრევე ტელეგრამზე
            if ($type === 'admin' || $mode === 'human') {
                OK_Chat_Telegram::send($sess, $name, ($type=='admin' ? "👨‍💻 პასუხი: $msg" : $msg));
            }

            // AI ლოგიკა (მხოლოდ თუ რეჟიმი AI-ა და იუზერი წერს)
            if ($mode === 'ai' && $type === 'user') {
                $ai_key = ok_chat_get_opt('openai_key');
                if ($ai_key) {
                    ok_chat_trigger_ai($sess, $msg, $ai_key);
                }
            }
        }
        echo json_encode(['status'=>'success']); exit;
    }

    // --- 2. მესიჯების მიღება (Refresh) + 2 წუთიანი შემოწმება ---
    if ($act === 'get_messages') {
        // აქვე ვამოწმებთ დროის გასვლას!
        ok_check_timeout_alert($sess);

        $msgs = $ok_db->get_results("SELECT * FROM ok_chat_messages WHERE session_id=? ORDER BY created_at ASC", [$sess]);
        
        $output = [];
        if ($msgs) {
            foreach($msgs as $m) {
                $m = is_object($m) ? $m : (object)$m;
                $output[] = [
                    'sender' => $m->sender,
                    'type'   => $m->sender_type,
                    'message'=> htmlspecialchars(ok_chat_decrypt($m->message)),
                    'time'   => $m->created_at
                ];
            }
        }
        echo json_encode(['status'=>'success', 'data'=>$output]); exit;
    }
}

// 2 წუთიანი შემოწმება
function ok_check_timeout_alert($sess) {
    global $ok_db;
    $s = $ok_db->get_results("SELECT * FROM ok_chat_sessions WHERE session_id=?", [$sess]);
    if(!$s) return;
    $s = is_object($s[0]) ? $s[0] : (object)$s[0];

    // თუ უკვე გაგზავნილია ან რეჟიმი უკვე ადამიანზეა, არაფერი ვქნათ
    if ($s->alert_sent == 1 || $s->mode == 'human') return;

    // დროის შემოწმება (120 წამი = 2 წუთი)
    $start_time = strtotime($s->created_at);
    $now = time();

    if (($now - $start_time) > 120) {
        // 2 წუთი გავიდა! ვაგზავნით ტელეგრამზე
        $last_msg = $ok_db->get_results("SELECT message FROM ok_chat_messages WHERE session_id=? AND sender_type='user' ORDER BY id DESC LIMIT 1", [$sess]);
        $txt = $last_msg ? ok_chat_decrypt(is_object($last_msg[0]) ? $last_msg[0]->message : $last_msg[0]['message']) : 'New Chat';

        OK_Chat_Telegram::send($sess, $s->user_name, "⚠️ <b>ALARM: 2 წუთი გავიდა!</b>\nკლიენტი ელოდება.\n\nბოლო მესიჯი: $txt");
        
        // აღვნიშნოთ რომ გავაგზავნეთ
        $ok_db->query("UPDATE ok_chat_sessions SET alert_sent=1 WHERE session_id=?", [$sess]);
    }
}

function ok_chat_trigger_ai($sess, $msg, $key) {
    global $ok_db;
    $prompt = ok_chat_get_opt('ai_prompt', 'შენ ხარ დამხმარე ბოტი. უპასუხე მოკლედ და ქართულად.');
    
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', "Authorization: Bearer $key"]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => 'gpt-3.5-turbo',
        'messages' => [['role'=>'system','content'=>$prompt], ['role'=>'user','content'=>$msg]]
    ]));
    $res = curl_exec($ch); curl_close($ch);
    $arr = json_decode($res, true);
    
    if (isset($arr['choices'][0]['message']['content'])) {
        $bot_msg = $arr['choices'][0]['message']['content'];
        $ok_db->query("INSERT INTO ok_chat_messages (session_id, sender, sender_type, message) VALUES (?, ?, 'bot', ?)", 
            [$sess, 'AI Bot', ok_chat_encrypt($bot_msg)]);
    }
}