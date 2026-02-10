<?php
/**
 * OK CMS - Notification UI Component (AJAX Enabled)
 * File: /ok-public/user-notification.php
 */

// 1. AJAX დამმუშავებელი (სრულდება HTML-ის ჩატვირთვამდე)
if (isset($_GET['action']) && strpos($_GET['action'], 'ajax_mark_') === 0) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    
    // ვამოწმებთ არის თუ არა მომხმარებელი შესული
    if (isset($_SESSION['user_id'])) {
        global $ok_db;
        
        // თუ $ok_db არ ჩანს (მაგ: პირდაპირი გამოძახებისას), ვტვირთავთ core-ს
        if (!$ok_db && file_exists(dirname(__DIR__) . '/ok-core/load.php')) {
            require_once dirname(__DIR__) . '/ok-core/load.php';
        }

        $uid = (int)$_SESSION['user_id'];
        $response = ['success' => false];

        // --- ლოგიკა: ერთი შეტყობინების წაკითხვა ---
        if ($_GET['action'] === 'ajax_mark_read' && isset($_GET['nid'])) {
            $nid = (int)$_GET['nid'];
            // ამატებს ID-ს მძიმით გამოყოფილ სტრიქონში, თუ უკვე არ არის
            $sql = "UPDATE ok_notifications 
                    SET read_by_users = IF(read_by_users IS NULL OR read_by_users = '', '$uid', CONCAT(read_by_users, ',$uid')) 
                    WHERE id = '$nid' 
                    AND FIND_IN_SET('$uid', for_user_id) 
                    AND NOT FIND_IN_SET('$uid', IFNULL(read_by_users, ''))";
            
            if ($ok_db->query($sql)) {
                $response['success'] = true;
            }
        } 
        // --- ლოგიკა: ყველას წაკითხვა ---
        elseif ($_GET['action'] === 'ajax_mark_all_read') {
            $sql = "UPDATE ok_notifications 
                    SET read_by_users = IF(read_by_users IS NULL OR read_by_users = '', '$uid', CONCAT(read_by_users, ',$uid')) 
                    WHERE FIND_IN_SET('$uid', for_user_id) 
                    AND NOT FIND_IN_SET('$uid', IFNULL(read_by_users, ''))";
            
            if ($ok_db->query($sql)) {
                $response['success'] = true;
            }
        }

        // ვითვლით განახლებულ რაოდენობას
        $new_count = (int)$ok_db->get_var("
            SELECT COUNT(*) FROM ok_notifications 
            WHERE FIND_IN_SET('$uid', for_user_id) 
            AND NOT FIND_IN_SET('$uid', IFNULL(read_by_users, ''))
        ");
        $response['new_count'] = $new_count;

        // ვაბრუნებთ JSON-ს და ვწყვეტთ მუშაობას (exit), რომ HTML არ ჩაერიოს
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// 2. ვიზუალიზაციის ფუნქცია
function ok_render_profile_notifications($uid) {
    global $ok_db;

    // სტატისტიკა
    $notif_count = (int)$ok_db->get_var("
        SELECT COUNT(*) FROM ok_notifications 
        WHERE FIND_IN_SET('$uid', for_user_id) 
        AND NOT FIND_IN_SET('$uid', IFNULL(read_by_users, ''))
    ");
    
    // ბოლო 5 ჩანაწერი
    $notifications = $ok_db->get_results("
        SELECT *, FIND_IN_SET('$uid', IFNULL(read_by_users, '')) as is_read_by_me 
        FROM ok_notifications 
        WHERE FIND_IN_SET('$uid', for_user_id) 
        ORDER BY created_at DESC LIMIT 5
    ");
    ?>
    <style>
        .ok-notif-btn-wrapper { position: relative; display: inline-block; }
        .ok-notif-btn { 
            width: 45px; height: 45px; border-radius: 50%; background: white; border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08); display: flex; align-items: center; justify-content: center; 
            font-size: 1.3rem; color: #555; position: relative; transition: all 0.2s; cursor: pointer;
        }
        .ok-notif-btn:hover { background: #f8f9fa; color: #0d6efd; transform: translateY(-2px); }
        
        .ok-badge-count { 
            position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; 
            font-size: 0.7rem; font-weight: 700; min-width: 20px; height: 20px; 
            border-radius: 50%; display: flex; align-items: center; justify-content: center; 
            border: 2px solid white; z-index: 10;
        }

        .ok-notif-dropdown {
            position: absolute; top: 55px; right: 0; width: 360px;
            background: white; border-radius: 12px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.15); border: 1px solid #f0f0f0;
            z-index: 1000; display: none; overflow: hidden;
            animation: okFadeIn 0.2s ease-out;
        }
        .ok-notif-dropdown.show { display: block; }

        .ok-dd-header { padding: 15px; background: #fff; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; }
        .ok-dd-list { max-height: 350px; overflow-y: auto; }
        
        .ok-dd-item { display: flex; gap: 12px; padding: 15px; border-bottom: 1px solid #f9f9f9; cursor: default; transition: 0.2s; position: relative; }
        .ok-dd-item:hover { background: #fbfbfb; }
        .ok-dd-item.unread { background: #f0f7ff; }
        .ok-dd-item.unread:hover { background: #e6f2ff; }

        .ok-read-dot {
            width: 8px; height: 8px; background-color: #0d6efd; border-radius: 50%;
            margin-top: 8px; flex-shrink: 0; cursor: pointer; display: none;
        }
        .ok-dd-item.unread .ok-read-dot { display: block; }
        .ok-dd-item.unread .ok-read-dot:hover { transform: scale(1.5); }

        .ok-dd-footer { display: block; text-align: center; padding: 12px; background: #fafafa; border-top: 1px solid #eee; font-size: 0.85rem; font-weight: 600; text-decoration: none; color: #666; }
        .ok-dd-footer:hover { background: #f0f0f0; }

        @keyframes okFadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    </style>

    <div class="ok-notif-btn-wrapper">
        <button type="button" class="ok-notif-btn" id="okNotifToggle">
            <i class="bi bi-bell"></i>
            <?php if($notif_count > 0): ?>
                <span class="ok-badge-count" id="ok-main-badge"><?php echo $notif_count > 99 ? '99+' : $notif_count; ?></span>
            <?php endif; ?>
        </button>

        <div class="ok-notif-dropdown" id="okNotifDropdown">
            <div class="ok-dd-header">
                <span class="fw-bold small text-uppercase text-muted">შეტყობინებები</span>
                <span onclick="okMarkAllReadAjax(event)" class="small text-primary fw-bold" style="cursor: pointer;">ყველას წაკითხვა</span>
            </div>
            
            <div class="ok-dd-list" id="ok-notif-list-container">
                <?php if($notifications): ?>
                    <?php foreach($notifications as $note): 
                        $is_unread = ($note->is_read_by_me == 0) ? 'unread' : '';
                        $icon = 'bi-info-circle'; $col = '#0d6efd'; // Default Info
                        if(($note->type ?? '') == 'success') { $icon = 'bi-check-circle'; $col = '#198754'; }
                        if(($note->type ?? '') == 'warning') { $icon = 'bi-exclamation-triangle'; $col = '#fd7e14'; }
                        if(($note->type ?? '') == 'danger')  { $icon = 'bi-x-circle'; $col = '#dc3545'; }
                    ?>
                        <div class="ok-dd-item <?php echo $is_unread; ?>" id="notif-row-<?php echo $note->id; ?>">
                            <i class="bi <?php echo $icon; ?> fs-5 mt-1" style="color: <?php echo $col; ?>"></i>
                            <div style="flex: 1;" onclick="okHandleNotifClick(event, <?php echo $note->id; ?>, '<?php echo $note->link; ?>')">
                                <div class="small text-dark mb-1" style="line-height: 1.4;"><?php echo htmlspecialchars($note->message); ?></div>
                                <div class="text-muted" style="font-size: 0.7rem;"><?php echo date('d M, H:i', strtotime($note->created_at)); ?></div>
                            </div>
                            <div class="ok-read-dot" title="მონიშნე წაკითხულად" onclick="okMarkSingleReadAjax(event, <?php echo $note->id; ?>)"></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-4 text-center text-muted small">შეტყობინებები არ არის</div>
                <?php endif; ?>
            </div>
            <a href="/notifications" class="ok-dd-footer">ყველა შეტყობინების ნახვა</a>
        </div>
    </div>

    <script>
        // Dropdown Toggle
        document.addEventListener('DOMContentLoaded', () => {
            const btn = document.getElementById('okNotifToggle');
            const dd = document.getElementById('okNotifDropdown');
            if(btn && dd) {
                btn.addEventListener('click', (e) => { e.stopPropagation(); dd.classList.toggle('show'); });
                document.addEventListener('click', (e) => { 
                    if(!dd.contains(e.target) && !btn.contains(e.target)) dd.classList.remove('show'); 
                });
            }
        });

        // AJAX: Mark Single Read
        async function okMarkSingleReadAjax(e, nid) {
            e.preventDefault(); e.stopPropagation();
            try {
                // ვიძახებთ მიმდინარე გვერდს, მაგრამ action პარამეტრით
                const res = await fetch('?action=ajax_mark_read&nid=' + nid);
                const data = await res.json();
                if(data.success) {
                    const row = document.getElementById('notif-row-' + nid);
                    if(row) row.classList.remove('unread');
                    updateBadge(data.new_count);
                }
            } catch(err) { console.error('Notif Error:', err); }
        }

        // AJAX: Mark All Read
        async function okMarkAllReadAjax(e) {
            e.preventDefault(); e.stopPropagation();
            try {
                const res = await fetch('?action=ajax_mark_all_read');
                const data = await res.json();
                if(data.success) {
                    document.querySelectorAll('.ok-dd-item').forEach(el => el.classList.remove('unread'));
                    updateBadge(0);
                }
            } catch(err) { console.error('Notif Error:', err); }
        }

        // Click Handler (Read & Redirect)
        async function okHandleNotifClick(e, nid, link) {
            const row = document.getElementById('notif-row-' + nid);
            if(row && row.classList.contains('unread')) {
                // არ ველოდებით პასუხს (await-ის გარეშე), რომ სწრაფად გადავიდეს
                fetch('?action=ajax_mark_read&nid=' + nid); 
            }
            if(link && link !== '#') window.location.href = link;
        }

        function updateBadge(count) {
            const badge = document.getElementById('ok-main-badge');
            if(count > 0) {
                if(badge) badge.innerText = count > 99 ? '99+' : count;
                else {
                    // თუ ბეჯი არ იყო და გაჩნდა (იშვიათია, მაგრამ შესაძლებელი)
                    const btn = document.getElementById('okNotifToggle');
                    btn.innerHTML += `<span class="ok-badge-count" id="ok-main-badge">${count}</span>`;
                }
            } else if(badge) {
                badge.remove();
            }
        }
    </script>
    <?php
}
?>