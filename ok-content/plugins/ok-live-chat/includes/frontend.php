<?php
if (!defined('OK_LOADED')) exit;

// ვიჯეტის გამოჩენა (footer-ში)
add_ok_action('ok_head', function() { // ან footer, გააჩნია CMS-ს
    // თუ ადმინია, არ ვაჩვენოთ (CMS-ის ფუნქციით)
    global $ok_user;
    if (isset($ok_user) && isset($ok_user->is_admin) && $ok_user->is_admin) return;
    ?>
    <div id="ok-chat-root">
        <div id="ok-chat-btn" onclick="okChatToggle()">💬</div>
        <div id="ok-chat-box">
            <div class="ok-head">
                Online Support <span onclick="okChatToggle()" style="cursor:pointer;float:right;">✖</span>
            </div>
            <div id="ok-body"></div>
            <div class="ok-foot">
                <input id="ok-inp" placeholder="წერილის ტექსტი..." onkeypress="if(event.key==='Enter') okSend()">
                <button onclick="okSend()">➤</button>
            </div>
        </div>
    </div>
    
    <style>
        #ok-chat-root { font-family: sans-serif; }
        #ok-chat-btn { position:fixed; bottom:20px; right:20px; width:60px; height:60px; background:#0088cc; color:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:30px; cursor:pointer; z-index:99999; box-shadow:0 5px 15px rgba(0,0,0,0.2); }
        #ok-chat-box { display:none; position:fixed; bottom:90px; right:20px; width:320px; height:450px; background:white; border-radius:10px; box-shadow:0 5px 25px rgba(0,0,0,0.2); flex-direction:column; z-index:99999; overflow:hidden; }
        .ok-head { background:#0088cc; color:white; padding:15px; font-weight:bold; }
        #ok-body { flex:1; padding:10px; overflow-y:auto; background:#f5f5f5; display:flex; flex-direction:column; gap:10px; }
        .ok-msg { padding:8px 12px; border-radius:8px; max-width:80%; font-size:14px; word-wrap:break-word; }
        .ok-msg.me { align-self:flex-end; background:#0088cc; color:white; }
        .ok-msg.adm { align-self:flex-start; background:white; border:1px solid #ddd; color:#333; }
        .ok-foot { padding:10px; background:white; border-top:1px solid #ddd; display:flex; }
        #ok-inp { flex:1; border:none; outline:none; }
        .ok-foot button { border:none; background:none; color:#0088cc; font-size:20px; cursor:pointer; }
    </style>

    <script>
        let okSess = localStorage.getItem('ok_sess') || 's_'+Math.random().toString(36).substr(2);
        localStorage.setItem('ok_sess', okSess);
        let okVis = false;

        function okChatToggle() {
            okVis = !okVis;
            document.getElementById('ok-chat-box').style.display = okVis ? 'flex' : 'none';
            if(okVis) { okScroll(); okLoad(); }
        }

        function okSend() {
            let inp = document.getElementById('ok-inp');
            let txt = inp.value.trim();
            if(!txt) return;
            
            // სწრაფი ჩვენება
            document.getElementById('ok-body').innerHTML += `<div class="ok-msg me">${txt}</div>`;
            inp.value = ''; okScroll();

            let fd = new FormData();
            fd.append('ok_chat_action', 'send_message');
            fd.append('session_id', okSess);
            fd.append('user_name', 'Guest');
            fd.append('message', txt);
            fetch(window.location.href, {method:'POST', body:fd});
        }

        function okLoad() {
            if(!okVis) return;
            let fd = new FormData();
            fd.append('ok_chat_action', 'get_messages');
            fd.append('session_id', okSess);
            
            fetch(window.location.href, {method:'POST', body:fd})
            .then(r=>r.json()).then(d=>{
                if(d.status === 'success') {
                    let h = '';
                    d.data.forEach(m => {
                        let cls = (m.sender_type==='user') ? 'me' : 'adm';
                        h += `<div class="ok-msg ${cls}">${m.message}</div>`;
                    });
                    let b = document.getElementById('ok-body');
                    if(b.innerHTML !== h) { b.innerHTML = h; okScroll(); }
                }
            });
        }
        function okScroll() { let b=document.getElementById('ok-body'); b.scrollTop=b.scrollHeight; }
        setInterval(okLoad, 3000);
    </script>
    <?php
});