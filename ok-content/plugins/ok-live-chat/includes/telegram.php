<?php
if (!defined('OK_LOADED')) exit;

class OK_Chat_Telegram {
    
    /**
     * 1. გაგზავნა: საიტი -> Telegram ჯგუფი
     * გამოიძახება როცა 2 წუთი გადის, ან როცა ადმინი წერს საიტიდან
     */
    public static function send($session_id, $user_name, $msg) {
        $token = ok_chat_get_opt('tg_token');
        $chat_id = ok_chat_get_opt('tg_chat_id');
        
        // თუ ტოკენი არ წერია, არაფერს ვაკეთებთ
        if (!$token || !$chat_id) return;

        // ფორმატი: აუცილებელია ეს ჰეშთეგი (#...), რომ პასუხისას ID ვიპოვოთ
        // 🆔 სიმბოლო გამოიყენება Regex-ისთვის ქვემოთ
        $text = "💬 <b>New Message</b>\n🆔 #$session_id\n👤 $user_name\n\n$msg";
        
        self::req("https://api.telegram.org/bot$token/sendMessage", [
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => 'HTML'
        ]);
    }

    /**
     * 2. მიღება: Telegram Reply -> საიტი
     * ეს ფუნქცია უსმენს Webhook-ს
     */
    public static function handle_webhook() {
        // ვიღებთ მონაცემებს Telegram-ისგან
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        // ვამოწმებთ, არის თუ არა ეს მესიჯი პასუხი (Reply)
        if (isset($data['message']['reply_to_message'])) {
            
            $reply_text = $data['message']['text']; // ოპერატორის მოწერილი ტექსტი
            $original_text = $data['message']['reply_to_message']['text']; // ორიგინალი მესიჯი (სადაც ID წერია)

            // ვეძებთ სესიის ID-ს ორიგინალ ტექსტში (Regex: 🆔 #sess_...)
            if (preg_match('/🆔 #([a-zA-Z0-9_\-]+)/', $original_text, $m)) {
                $session_id = $m[1];
                
                global $ok_db;

                // ნაბიჯი 1: მესიჯის შენახვა ბაზაში (როგორც ადმინი)
                // sender_type = 'admin' ნიშნავს, რომ მომხმარებლისთვის ეს არის ოპერატორის პასუხი
                $ok_db->query("INSERT INTO ok_chat_messages (session_id, sender, sender_type, message) VALUES (?, ?, 'admin', ?)", 
                    [$session_id, 'Operator (TG)', ok_chat_encrypt($reply_text)]);
                
                // ნაბიჯი 2: სესიის გადაყვანა HUMAN რეჟიმზე
                // ეს ძალიან მნიშვნელოვანია! ამის შემდეგ AI აღარ უპასუხებს ამ სესიაში
                $ok_db->query("UPDATE ok_chat_sessions SET mode='human', last_activity=NOW() WHERE session_id=?", 
                    [$session_id]);
            }
        }
        // აუცილებელია exit, რომ WordPress/CMS-მა არაფერი ჩატვირთოს ზედმეტად
        exit; 
    }

    /**
     * 3. Webhook-ის დაყენება
     * გამოიძახება ადმინ პანელიდან ღილაკზე დაჭერისას
     */
    public static function set_webhook() {
        $token = ok_chat_get_opt('tg_token');
        
        if (!$token) {
            return '<div class="alert alert-danger">ჯერ ჩაწერეთ Token პარამეტრებში!</div>';
        }

        // ვაგენერირებთ URL-ს, სადაც Telegram-მა უნდა გამოგზავნოს მონაცემები
        // ვარაუდი: CMS-ის მთავარ გვერდზე ვუსმენთ ?ok_tg_hook=1 პარამეტრს
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        $url = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/?ok_tg_hook=1";
        
        // ვაგზავნით მოთხოვნას Telegram API-ზე
        $api_url = "https://api.telegram.org/bot$token/setWebhook?url=" . urlencode($url);
        $res = self::req($api_url);
        
        $json = json_decode($res, true);
        
        if ($json && $json['ok']) {
            return '<div class="alert alert-success">Webhook წარმატებით განახლდა! URL: '.$url.'</div>';
        } else {
            return '<div class="alert alert-danger">შეცდომა: ' . ($json['description'] ?? 'უცნობი') . '</div>';
        }
    }

    /**
     * დამხმარე ფუნქცია cURL მოთხოვნებისთვის
     */
    private static function req($url, $data = []) {
        $ch = curl_init($url);
        if(!empty($data)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // SSL ვერიფიკაციის გამორთვა (თუ ლოკალურ სერვერზე ხართ, პრობლემა რომ არ შექმნას)
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }
}