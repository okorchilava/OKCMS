<?php
/*
Plugin Name: OK Advertisements PRO
Description: პროფესიონალური სარეკლამო სისტემა — როტაცია პრიორიტეტებით, ლიმიტებით და Clean UI-ით.
Version: 10.0
Author: OK Engine Team
*/

if (!defined('OK_LOADED')) exit;

/**
 * 1. ბაზის ინსტალაცია
 */
function ok_adv_db_install() {
    global $ok_db;
    if (!$ok_db) return;

    $ok_db->query("CREATE TABLE IF NOT EXISTS ok_advertisements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        adv_type ENUM('image', 'html') DEFAULT 'image',
        image_url TEXT,
        html_code TEXT,
        overlay_text TEXT,
        link_url TEXT,
        hook_name TEXT, 
        start_date DATE NULL,
        end_date DATE NULL,
        impression_limit INT DEFAULT 0,
        current_impressions INT DEFAULT 0,
        priority INT DEFAULT 0,
        is_active TINYINT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

/**
 * 2. რეკლამების გამოტანის ლოგიკა (Frontend)
 */
function ok_adv_display_engine($current_hook) {
    global $ok_db;
    if (!$ok_db) return;

    $today = date('Y-m-d');
    
    // ვფილტრავთ რეკლამებს: აქტიური, სწორი ჰუკი, თარიღის ვადაში და ლიმიტის ფარგლებში
    $sql = "SELECT * FROM ok_advertisements 
            WHERE is_active = 1 
            AND FIND_IN_SET(?, hook_name)
            AND (start_date <= ? OR start_date IS NULL OR start_date = '0000-00-00')
            AND (end_date >= ? OR end_date IS NULL OR end_date = '0000-00-00')
            AND (impression_limit = 0 OR current_impressions < impression_limit)";

    $all_ads = $ok_db->get_results($sql, [$current_hook, $today, $today], PDO::FETCH_ASSOC);

    if (!$all_ads) return;

    // შეწონილი როტაცია (Weighted Random Rotation)
    // რაც მაღალია პრიორიტეტი, მით მეტი შანსია გამოჩნდეს
    $weighted_ads = [];
    foreach ($all_ads as $ad) {
        $ad = (array)$ad;
        $weight = max(1, (int)$ad['priority']); 
        for ($i = 0; $i < $weight; $i++) {
            $weighted_ads[] = $ad;
        }
    }

    // შემთხვევითად ვირჩევთ ერთს
    $adv = $weighted_ads[array_rand($weighted_ads)];

    // ნახვის დაფიქსირება
    $ok_db->query("UPDATE ok_advertisements SET current_impressions = current_impressions + 1 WHERE id = ?", [$adv['id']]);

    // რენდერი
    if (($adv['adv_type'] ?? 'image') === 'html') {
        echo '<div class="ok-adv-pro-container my-1">' . $adv['html_code'] . '</div>';
    } elseif (!empty($adv['image_url'])) {
        ?>
        <div class="ok-adv-pro-container my-1">
            <a href="<?php echo htmlspecialchars((string)$adv['link_url']); ?>" target="_blank" class="ok-adv-anchor-link">
                <img src="<?php echo htmlspecialchars((string)$adv['image_url']); ?>" class="ok-adv-img-final" alt="Advertisement">
                
                <?php if(!empty($adv['overlay_text'])): ?>
                    <div class="ok-adv-overlay-content">
                        <div class="ok-adv-overlay-txt-main">
                            <?php echo htmlspecialchars($adv['overlay_text']); ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="ok-adv-gray-label">რეკლამა</div>
            </a>
        </div>

        <style>
            .ok-adv-pro-container { 
                width: 100%; 
                position: relative; 
                display: block; 
                border-radius: 14px; 
                overflow: hidden; 
                line-height: 0;
                border: none !important;
                background: none !important;
                box-shadow: none !important;
                margin-top: 5px !important; 
                margin-bottom: 10px !important; 
            }
            .ok-adv-anchor-link { display: block; position: relative; border: none !important; outline: none !important; }
            .ok-adv-img-final { 
                width: 100%; 
                height: auto; 
                display: block; 
                max-height: 480px; 
                object-fit: cover; 
                border-radius: 14px;
                border: none !important;
            }
            .ok-adv-gray-label { 
                position: absolute; 
                top: 15px; 
                left: 15px; 
                background: rgba(17, 17, 17, 0.65); 
                color: #fff; 
                font-size: 11px; 
                padding: 6px 14px; 
                border-radius: 6px; 
                text-transform: uppercase; 
                z-index: 5;
                backdrop-filter: blur(4px);
                letter-spacing: 0.8px;
                font-weight: 500;
                line-height: 1;
            }
            .ok-adv-overlay-content { 
                position: absolute; inset: 0; 
                display: flex; align-items: center; justify-content: center; 
                background: rgba(0,0,0,0.15); padding: 20px;
                text-align: center;
                border-radius: 14px;
            }
            .ok-adv-overlay-txt-main {
                color: #fff;
                font-weight: 800;
                text-shadow: 0 2px 10px rgba(0,0,0,0.4);
                font-size: clamp(18px, 4.5vw, 36px);
                line-height: 1.2;
                max-width: 90%;
            }
            @media (max-width: 768px) {
                .ok-adv-pro-container { margin-top: 2px !important; margin-bottom: 5px !important; }
                .ok-adv-pro-container, .ok-adv-img-final, .ok-adv-overlay-content { border-radius: 10px; }
                .ok-adv-img-final { max-height: 320px; }
                .ok-adv-overlay-txt-main { font-size: 18px; }
                .ok-adv-gray-label { padding: 5px 12px; font-size: 10px; top: 10px; left: 10px; }
            }
        </style>
        <?php
    }
}

// ჰუკებზე მიბმა
if (function_exists('add_ok_action')) {
    add_ok_action('ok_before_content', function() { ok_adv_display_engine('ok_before_content'); });
    add_ok_action('ok_after_content', function() { ok_adv_display_engine('ok_after_content'); });
    add_ok_action('admin_menu', function() {
        ok_adv_db_install();
        add_menu_page('რეკლამა', 'რეკლამა', 'manage_options', 'ok-adv', 'ok_adv_admin_gui', 'bi bi-megaphone-fill', 31);
    });
}

/**
 * 3. ადმინ პანელი (GUI)
 */
function ok_adv_admin_gui() {
    global $ok_db;
    if (!$ok_db) return;

    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

    $action = $_GET['action'] ?? 'list';
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $old_data = ($id > 0) ? $ok_db->get_row("SELECT * FROM ok_advertisements WHERE id=?", [$id], PDO::FETCH_ASSOC) : null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_adv'])) {
        $title      = strip_tags(trim($_POST['title']));
        $adv_type   = $_POST['adv_type'];
        $image_url  = $_POST['image_url'];
        $html_code  = $_POST['html_code'];
        $overlay    = $_POST['overlay_text'];
        $link_url   = $_POST['link_url'];
        $hooks_array = isset($_POST['hook_names']) ? $_POST['hook_names'] : [];
        $hook_name_str = implode(',', $hooks_array);
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $end_date   = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $imp_limit  = (int)$_POST['impression_limit'];
        $priority   = (int)$_POST['priority'];
        $is_active  = isset($_POST['is_active']) ? 1 : 0;

        if ($id > 0) {
            $ok_db->query("UPDATE ok_advertisements SET title=?, adv_type=?, image_url=?, html_code=?, overlay_text=?, link_url=?, hook_name=?, start_date=?, end_date=?, impression_limit=?, priority=?, is_active=? WHERE id=?", 
            [$title, $adv_type, $image_url, $html_code, $overlay, $link_url, $hook_name_str, $start_date, $end_date, $imp_limit, $priority, $is_active, $id]);
        } else {
            $ok_db->query("INSERT INTO ok_advertisements (title, adv_type, image_url, html_code, overlay_text, link_url, hook_name, start_date, end_date, impression_limit, priority, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", 
            [$title, $adv_type, $image_url, $html_code, $overlay, $link_url, $hook_name_str, $start_date, $end_date, $imp_limit, $priority, $is_active]);
        }
        echo "<script>window.location.href='?page=ok-adv&success=1';</script>"; exit;
    }

    if ($action === 'delete' && $id > 0) {
        $ok_db->query("DELETE FROM ok_advertisements WHERE id=?", [$id]);
        echo "<script>window.location.href='?page=ok-adv&deleted=1';</script>"; exit;
    }

    if (isset($_GET['success'])) echo "<script>Swal.fire({title:'შენახულია', icon:'success', timer:1000, showConfirmButton:false});</script>";
    if (isset($_GET['deleted'])) echo "<script>Swal.fire({title:'წაშლილია', icon:'warning', timer:1000, showConfirmButton:false});</script>";

    $row = $old_data ?: ['title'=>'','adv_type'=>'image','image_url'=>'','html_code'=>'','overlay_text'=>'','link_url'=>'','hook_name'=>'','start_date'=>'','end_date'=>'','impression_limit'=>0,'priority'=>0,'is_active'=>1];
    $active_hooks = explode(',', $row['hook_name']);
    ?>

    <div class="container-fluid mt-4 ok-adv-admin">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4 fw-bold mb-0">Advertisements PRO</h2>
            <a href="?page=ok-adv&action=<?php echo ($action == 'list' ? 'add' : ''); ?>" class="btn btn-primary px-4 shadow-sm"><?php echo ($action == 'list' ? '+ ახალი რეკლამა' : 'დაბრუნება'); ?></a>
        </div>

        <?php if($action == 'list'): 
            $list = $ok_db->get_results("SELECT * FROM ok_advertisements ORDER BY priority DESC, id DESC", [], PDO::FETCH_ASSOC);
        ?>
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light small">
                        <tr>
                            <th class="ps-4">ვიზუალი</th>
                            <th>სათაური</th>
                            <th>ნახვები / ლიმიტი</th>
                            <th>პრიორიტეტი</th>
                            <th class="text-end pe-4">მოქმედება</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($list as $item): $item = (array)$item; ?>
                        <tr>
                            <td class="ps-4">
                                <?php if($item['adv_type'] == 'image'): ?>
                                    <img src="<?php echo $item['image_url']; ?>" width="70" height="40" class="rounded object-fit-cover border">
                                <?php else: ?>
                                    <span class="badge bg-dark">HTML</span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold"><?php echo htmlspecialchars($item['title']); ?></td>
                            <td><?php echo $item['current_impressions']; ?> / <?php echo $item['impression_limit'] ?: '∞'; ?></td>
                            <td><span class="badge bg-primary px-2"><?php echo $item['priority']; ?></span></td>
                            <td class="text-end pe-4">
                                <a href="?page=ok-adv&action=edit&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-light border"><i class="bi bi-pencil-square"></i></a>
                                <button onclick="confirmDelete(<?php echo $item['id']; ?>)" class="btn btn-sm btn-light border text-danger"><i class="bi bi-trash3"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="card border-0 shadow-sm p-4 rounded-4">
                <form method="POST">
                    <div class="row g-4">
                        <div class="col-md-8"><label class="form-label fw-bold">სათაური</label><input type="text" name="title" class="form-control bg-light border-0" value="<?php echo htmlspecialchars($row['title']); ?>" required></div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">ტიპი</label>
                            <select name="adv_type" class="form-select bg-light border-0" onchange="toggleFields(this.value)">
                                <option value="image" <?php echo $row['adv_type'] == 'image' ? 'selected' : ''; ?>>სურათი</option>
                                <option value="html" <?php echo $row['adv_type'] == 'html' ? 'selected' : ''; ?>>HTML</option>
                            </select>
                        </div>
                        <div id="image_inputs" class="col-12 <?php echo $row['adv_type'] == 'html' ? 'd-none' : ''; ?>">
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label fw-bold small">სურათის URL</label><input type="text" name="image_url" class="form-control bg-light border-0" value="<?php echo htmlspecialchars($row['image_url']); ?>"></div>
                                <div class="col-md-6"><label class="form-label fw-bold small">ტექსტი ბანერზე</label><input type="text" name="overlay_text" class="form-control bg-light border-0" value="<?php echo htmlspecialchars($row['overlay_text']); ?>"></div>
                                <div class="col-12"><label class="form-label fw-bold small">ლინკი</label><input type="text" name="link_url" class="form-control bg-light border-0" value="<?php echo htmlspecialchars($row['link_url']); ?>"></div>
                            </div>
                        </div>
                        <div id="html_inputs" class="col-12 <?php echo $row['adv_type'] == 'image' ? 'd-none' : ''; ?>">
                            <label class="form-label fw-bold">HTML კოდი</label>
                            <textarea name="html_code" class="form-control bg-light border-0" rows="4"><?php echo $row['html_code']; ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold d-block">პოზიციები</label>
                            <div class="d-flex gap-4 p-3 bg-light rounded-3">
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="hook_names[]" value="ok_before_content" <?php echo in_array('ok_before_content', $active_hooks) ? 'checked' : ''; ?> id="h1"><label class="form-check-label" for="h1">წინ</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="hook_names[]" value="ok_after_content" <?php echo in_array('ok_after_content', $active_hooks) ? 'checked' : ''; ?> id="h2"><label class="form-check-label" for="h2">შემდეგ</label></div>
                            </div>
                        </div>
                        <div class="col-md-4"><label class="form-label fw-bold small">პრიორიტეტი (Weighted)</label><input type="number" name="priority" class="form-control bg-light border-0" value="<?php echo $row['priority']; ?>"></div>
                        <div class="col-md-4"><label class="form-label fw-bold small">ნახვების ლიმიტი (0=∞)</label><input type="number" name="impression_limit" class="form-control bg-light border-0" value="<?php echo $row['impression_limit']; ?>"></div>
                        <div class="col-md-4"><div class="form-check form-switch mt-4"><input class="form-check-input" type="checkbox" name="is_active" <?php echo $row['is_active'] ? 'checked' : ''; ?>><label class="form-check-label fw-bold">აქტიური</label></div></div>
                        <div class="col-12 mt-3"><button type="submit" name="submit_adv" class="btn btn-primary px-5 py-2">შენახვა</button></div>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
    <script>
    function toggleFields(v){ document.getElementById('image_inputs').classList.toggle('d-none',v==='html'); document.getElementById('html_inputs').classList.toggle('d-none',v==='image'); }
    function confirmDelete(id){ Swal.fire({title:'წავშალოთ?',icon:'warning',showCancelButton:true,confirmButtonText:'წაშლა'}).then((r)=>{if(r.isConfirmed)window.location.href=`?page=ok-adv&action=delete&id=${id}`;}); }
    </script>
    <?php
}