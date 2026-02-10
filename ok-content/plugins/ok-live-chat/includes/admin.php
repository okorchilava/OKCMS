<?php
if (!defined('OK_LOADED')) exit;

add_ok_action('admin_menu', function() {
    add_menu_page('Live Chat', 'Live Chat', 'manage_options', 'ok-live-chat', 'ok_render_admin_interface', 'bi bi-chat-dots', 55);
});

function ok_render_admin_interface() {
    // პარამეტრების შენახვა
    if (isset($_POST['ok_save_settings'])) {
        ok_chat_save_opt('tg_token', $_POST['tg_token']);
        ok_chat_save_opt('tg_chat_id', $_POST['tg_chat_id']);
        ok_chat_save_opt('openai_key', $_POST['openai_key']);
        echo '<div class="alert alert-success">შენახულია</div>';
    }
    
    // Webhook
    if (isset($_POST['ok_set_webhook'])) {
        echo OK_Chat_Telegram::set_webhook();
    }

    $active_tab = $_GET['tab'] ?? 'chat';
    ?>
    <div class="wrap">
        <h2>OK Live Chat</h2>
        <ul class="nav nav-tabs">
            <li class="nav-item"><a class="nav-link <?php echo $active_tab=='chat'?'active':''; ?>" href="?page=ok-live-chat&tab=chat">💬 მიმდინარე ჩატები</a></li>
            <li class="nav-item"><a class="nav-link <?php echo $active_tab=='settings'?'active':''; ?>" href="?page=ok-live-chat&tab=settings">⚙️ პარამეტრები</a></li>
        </ul>

        <?php if ($active_tab == 'settings'): ?>
            <form method="post" class="mt-4 p-3 bg-white border">
                <label>Telegram Token:</label>
                <input type="text" name="tg_token" class="form-control mb-2" value="<?php echo ok_chat_get_opt('tg_token'); ?>">
                <label>Chat ID:</label>
                <input type="text" name="tg_chat_id" class="form-control mb-2" value="<?php echo ok_chat_get_opt('tg_chat_id'); ?>">
                <label>OpenAI Key:</label>
                <input type="text" name="openai_key" class="form-control mb-2" value="<?php echo ok_chat_get_opt('openai_key'); ?>">
                <input type="hidden" name="ok_save_settings" value="1">
                <button class="btn btn-primary">შენახვა</button>
            </form>
            <form method="post" class="mt-2"><input type="hidden" name="ok_set_webhook" value="1"><button class="btn btn-warning">Webhook Update</button></form>

        <?php else: ?>
            <div style="display:flex; height:500px; border:1px solid #ccc; margin-top:20px; background:white;">
                <div style="width:30%; border-right:1px solid #ccc; overflow-y:auto;" id="chat-list">
                    <div style="padding:10px; text-align:center;">იტვირთება...</div>
                </div>
                
                <div style="width:70%; display:flex; flex-direction:column;">
                    <div id="admin-chat-view" style="flex:1; padding:15px; overflow-y:auto; background:#f9f9f9;">
                        აირჩიეთ ჩატი მარცხნივ
                    </div>
                    <div style="padding:10px; border-top:1px solid #ddd; display:flex;">
                        <input type="hidden" id="active-session-id">
                        <input type="text" id="admin-msg-input" class="form-control" placeholder="მიწერე პასუხი..." disabled>
                        <button class="btn btn-primary ms-2" onclick="sendAdminMsg()" id="admin-send-btn" disabled>გაგზავნა</button>
                    </div>
                </div>
            </div>

            <script>
                // მარტივი JS ადმინ ჩატისთვის
                let currentSess = '';
                
                function loadSessions() {
                    // აქ იდეაში ცალკე API მოთხოვნა უნდა, მაგრამ სიმარტივისთვის პირდაპირ ჩავსვათ PHP
                    // რადგან ეს Admin ფაილია, შეგვიძლია პირდაპირ PHP-დან გამოვიტანოთ
                    // მაგრამ ლაივ რეჟიმისთვის ჯობია AJAX
                }
                
                // დროებითი AJAX სკრიპტი სესიების სიისთვის (ცალკე ფაილი რომ არ დაგჭირდეთ)
                // რეალურ პროექტში ეს API-ში უნდა იყოს
                setInterval(() => {
                    // სესიების და მესიჯების განახლება
                    if(currentSess) loadChat(currentSess);
                    
                    // სიის განახლება (შეგიძლიათ დაამატოთ logic)
                }, 3000);

                // ეს ნაწილი მოითხოვს API-ში `get_sessions` მეთოდის დამატებას
                // ან პირდაპირ PHP-ს გამოყენებას აქ.
                // მოდით, აქ PHP-თ გამოვიტანოთ სია პირველ ჯერზე:
                
                <?php 
                global $ok_db;
                $sessions = $ok_db->get_results("SELECT * FROM ok_chat_sessions ORDER BY last_activity DESC LIMIT 20");
                ?>
                const sessions = <?php echo json_encode($sessions); ?>;
                let listHtml = '';
                sessions.forEach(s => {
                    listHtml += `<div onclick="openChat('${s.session_id}')" style="padding:10px; border-bottom:1px solid #eee; cursor:pointer; background:${s.mode=='ai'?'#eef':'#fff'}">
                        <b>${s.user_name}</b> <small>(${s.mode})</small><br>
                        <small>${s.last_activity}</small>
                    </div>`;
                });
                document.getElementById('chat-list').innerHTML = listHtml;

                function openChat(sess) {
                    currentSess = sess;
                    document.getElementById('active-session-id').value = sess;
                    document.getElementById('admin-msg-input').disabled = false;
                    document.getElementById('admin-send-btn').disabled = false;
                    loadChat(sess);
                }

                function loadChat(sess) {
                    let fd = new FormData();
                    fd.append('ok_chat_action', 'get_messages');
                    fd.append('session_id', sess);
                    fetch(window.location.href, {method:'POST', body:fd}) // მიმართავს იგივე URL-ს სადაც API უსმენს (ან სწორი გზა მიუთითეთ)
                    .then(r=>r.json()).then(d=>{
                        if(d.status==='success') {
                            let h = '';
                            d.data.forEach(m => {
                                let bg = m.type==='user' ? '#fff' : (m.type==='bot'?'#eef':'#dcf8c6');
                                let align = m.type==='user' ? 'left' : 'right';
                                h += `<div style="text-align:${align}; margin:5px;"><span style="background:${bg}; padding:5px 10px; border-radius:5px; display:inline-block; border:1px solid #ddd;"><b>${m.sender}:</b> ${m.message}</span></div>`;
                            });
                            document.getElementById('admin-chat-view').innerHTML = h;
                        }
                    });
                }

                function sendAdminMsg() {
                    let txt = document.getElementById('admin-msg-input').value;
                    if(!txt) return;
                    
                    let fd = new FormData();
                    fd.append('ok_chat_action', 'send_message');
                    fd.append('session_id', currentSess);
                    fd.append('message', txt);
                    fd.append('sender_type', 'admin'); // მონიშვნა რომ ადმინია
                    
                    fetch(window.location.href, {method:'POST', body:fd}).then(()=>{
                        document.getElementById('admin-msg-input').value='';
                        loadChat(currentSess);
                    });
                }
            </script>
        <?php endif; ?>
    </div>
    <?php
}