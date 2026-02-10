<?php
/*
Plugin Name: OK Testimonials PRO (Full System)
Description: სრული სისტემა: 2-ქარდიანი ბადე, AJAX ფორმა, კამპანიები და სრულსიგანის ადმინ პანელი.
Version: 15.0
Author: OK Engine
*/

if (!defined('OK_LOADED')) exit;

/**
 * 1. მონაცემთა ბაზა
 */
function ok_testi_db_install() {
    global $ok_db;
    if (!$ok_db) return;
    $ok_db->query("CREATE TABLE IF NOT EXISTS ok_testimonials (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_name VARCHAR(255) NOT NULL,
        campaign_id INT DEFAULT 0,
        testimonial_text TEXT NOT NULL,
        rating TINYINT DEFAULT 5,
        is_public TINYINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $ok_db->query("CREATE TABLE IF NOT EXISTS ok_testi_campaigns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        camp_name VARCHAR(255) NOT NULL,
        camp_slug VARCHAR(100) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

function ok_testi_render_avatar($name, $size = '40px') {
    $words = explode(' ', trim($name));
    $initials = (count($words) >= 2) ? mb_substr($words[0], 0, 1, 'UTF-8') . mb_substr($words[count($words)-1], 0, 1, 'UTF-8') : mb_substr($name, 0, 2, 'UTF-8');
    $colors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#6610f2'];
    $bg = $colors[abs(crc32($name)) % count($colors)];
    return '<div style="background:'.$bg.'; width:'.$size.'; height:'.$size.'; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:bold; font-size:calc('.$size.' / 2.5);">'.mb_strtoupper($initials).'</div>';
}

/**
 * 2. ადმინ პანელი (სრულსიგანის ცხრილი და ქართული ინტერფეისი)
 */
function ok_testi_admin_gui() {
    global $ok_db;
    ok_testi_db_install();

    $tab = $_GET['tab'] ?? 'testimonials';
    $action = $_GET['action'] ?? 'list';
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['submit_camp'])) {
            $name = strip_tags($_POST['camp_name']);
            $slug = preg_replace('~[^\pL\d]+~u', '-', mb_strtolower($name));
            $ok_db->query("INSERT INTO ok_testi_campaigns (camp_name, camp_slug) VALUES (?, ?)", [$name, trim($slug, '-')]);
            echo "<script>window.location.href='?page=ok-testimonials&tab=campaigns';</script>"; exit;
        }
        if (isset($_POST['submit_testi'])) {
            $data = [strip_tags($_POST['client_name']), (int)$_POST['campaign_id'], strip_tags($_POST['testimonial_text']), (int)$_POST['rating'], isset($_POST['is_public'])?1:0];
            if ($id > 0) { $ok_db->query("UPDATE ok_testimonials SET client_name=?, campaign_id=?, testimonial_text=?, rating=?, is_public=? WHERE id=?", array_merge($data, [$id])); }
            else { $ok_db->query("INSERT INTO ok_testimonials (client_name, campaign_id, testimonial_text, rating, is_public) VALUES (?, ?, ?, ?, ?)", $data); }
            echo "<script>window.location.href='?page=ok-testimonials';</script>"; exit;
        }
    }

    if ($action === 'delete') {
        $ok_db->query("DELETE FROM ok_testimonials WHERE id=?", [$id]);
        echo "<script>window.location.href='?page=ok-testimonials';</script>"; exit;
    }
    ?>
    <style>
        .ok-admin-wrap { width: 100% !important; padding: 20px; box-sizing: border-box; }
        .ok-card { background: #fff; border-radius: 15px; border: 1px solid #edf2f9; box-shadow: 0 0.75rem 1.5rem rgba(18,38,63,0.03); width: 100%; margin-top: 20px; }
        .table { width: 100% !important; border-collapse: collapse; }
        .table thead th { background: #f9fbfd; color: #95aac9; font-size: 11px; padding: 15px; text-transform: uppercase; text-align: left; border: none; }
        .table td { padding: 15px; border-top: 1px solid #edf2f9; text-align: left; }
        .nav-tabs-custom { border-bottom: 2px solid #f1f4f8; display: flex; gap: 20px; margin-bottom: 20px; }
        .nav-tabs-custom a { text-decoration: none; color: #6e84a3; font-weight: 600; padding-bottom: 10px; border-bottom: 2px solid transparent; }
        .nav-tabs-custom a.active { color: #2c7be5; border-bottom: 2px solid #2c7be5; }
    </style>

    <div class="ok-admin-wrap">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-bold"><span style="color: #ffc107; margin-right: 10px;">⭐</span>შეფასებების მართვა</h2>
            <div class="nav-tabs-custom">
                <a href="?page=ok-testimonials&tab=testimonials" class="<?php echo $tab=='testimonials'?'active':''; ?>">შეფასებები</a>
                <a href="?page=ok-testimonials&tab=campaigns" class="<?php echo $tab=='campaigns'?'active':''; ?>">კამპანიები</a>
            </div>
        </div>

        <?php if ($tab == 'testimonials'): ?>
            <?php if ($action == 'list'): 
                $items = $ok_db->get_results("SELECT t.*, c.camp_name FROM ok_testimonials t LEFT JOIN ok_testi_campaigns c ON t.campaign_id = c.id ORDER BY t.id DESC", [], PDO::FETCH_ASSOC); ?>
                <div class="d-flex justify-content-end mb-3"><a href="?page=ok-testimonials&action=add" class="btn btn-primary">+ დამატება</a></div>
                <div class="ok-card overflow-hidden">
                    <table class="table">
                        <thead><tr><th>ავტორი</th><th>კამპანია</th><th>რეიტინგი</th><th>სტატუსი</th><th style="text-align:right">მოქმედება</th></tr></thead>
                        <tbody>
                            <?php foreach($items as $row): ?>
                            <tr>
                                <td><div class="d-flex align-items-center"><?php echo ok_testi_render_avatar($row['client_name'], '35px'); ?><div class="ms-3 fw-bold small"><?php echo $row['client_name']; ?></div></div></td>
                                <td><span class="badge bg-light text-muted border"><?php echo $row['camp_name'] ?: 'ზოგადი'; ?></span></td>
                                <td class="text-warning small"><?php echo str_repeat('★', $row['rating']); ?></td>
                                <td><?php echo $row['is_public'] ? '<span class="badge bg-success">LIVE</span>' : '<span class="badge bg-warning">Pending</span>'; ?></td>
                                <td style="text-align:right">
                                    <a href="?page=ok-testimonials&action=edit&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <a href="?page=ok-testimonials&action=delete&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('წავშალოთ?')">Del</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: 
                $edit = $id > 0 ? $ok_db->get_row("SELECT * FROM ok_testimonials WHERE id=?", [$id], PDO::FETCH_ASSOC) : ['client_name'=>'','campaign_id'=>0,'testimonial_text'=>'','rating'=>5,'is_public'=>1];
                $camps = $ok_db->get_results("SELECT * FROM ok_testi_campaigns", [], PDO::FETCH_ASSOC); ?>
                <div class="ok-card p-4">
                    <form method="POST">
                        <div class="row g-4">
                            <div class="col-md-6"><label class="form-label fw-bold">ავტორი</label><input type="text" name="client_name" class="form-control" value="<?php echo $edit['client_name']; ?>" required></div>
                            <div class="col-md-3"><label class="form-label fw-bold">კამპანია</label><select name="campaign_id" class="form-select"><?php echo '<option value="0">ზოგადი</option>'; foreach($camps as $c) echo "<option value='{$c['id']}' ".($edit['campaign_id']==$c['id']?'selected':'').">{$c['camp_name']}</option>"; ?></select></div>
                            <div class="col-md-3"><label class="form-label fw-bold">რეიტინგი</label><select name="rating" class="form-select"><?php for($i=5;$i>=1;$i--) echo "<option value='$i' ".($edit['rating']==$i?'selected':'').">$i ვარსკვლავი</option>"; ?></select></div>
                            <div class="col-12"><label class="form-label fw-bold">ტექსტი</label><textarea name="testimonial_text" class="form-control" rows="5" required><?php echo $edit['testimonial_text']; ?></textarea></div>
                            <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_public" id="pbl" <?php echo $edit['is_public']?'checked':''; ?>><label class="form-check-label fw-bold" for="pbl">გამოქვეყნება</label></div></div>
                            <div class="col-12"><button type="submit" name="submit_testi" class="btn btn-primary px-5">შენახვა</button></div>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        <?php elseif ($tab == 'campaigns'): ?>
            <div class="row">
                <div class="col-md-4"><div class="ok-card p-4"><h6 class="fw-bold mb-3">ახალი კამპანია</h6><form method="POST"><input type="text" name="camp_name" class="form-control mb-3" placeholder="სახელი" required><button type="submit" name="submit_camp" class="btn btn-primary w-100">დამატება</button></form></div></div>
                <div class="col-md-8"><div class="ok-card overflow-hidden"><table class="table"><thead><tr><th>კამპანია</th><th>შორთკოდი</th></tr></thead><tbody><?php $camps=$ok_db->get_results("SELECT * FROM ok_testi_campaigns", [], PDO::FETCH_ASSOC); foreach($camps as $c): ?><tr><td class="fw-bold small"><?php echo $c['camp_name']; ?></td><td><code>[ok_testimonials_list id="<?php echo $c['camp_slug']; ?>"]</code></td></tr><?php endforeach; ?></tbody></table></div></div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * 3. Frontend - AJAX ფორმა (ქარდის გარეშე, სრულ სიგანეზე)
 */
function ok_testi_form_render($atts) {
    global $ok_db;
    $slug = $atts['id'] ?? '';
    $camp_id = 0;
    if(!empty($slug)) $camp_id = $ok_db->get_var("SELECT id FROM ok_testi_campaigns WHERE camp_slug = ?", [$slug]);

    if (isset($_POST['ok_action']) && $_POST['ok_action'] == 'save') {
        $ok_db->query("INSERT INTO ok_testimonials (client_name, campaign_id, testimonial_text, rating, is_public) VALUES (?, ?, ?, ?, 0)", 
            [strip_tags($_POST['n']), (int)$_POST['c'], strip_tags($_POST['t']), (int)$_POST['r']]);
        echo "ok"; exit;
    }

    ob_start(); ?>
    <style>
        .ok-form-full { width: 100%; margin: 0 auto; text-align: left; }
        .ok-star-row { display: flex; flex-direction: row-reverse; justify-content: center; gap: 10px; margin: 20px 0; }
        .ok-star-row input { display: none; }
        .ok-star-row label { font-size: 40px; color: #ddd; cursor: pointer; transition: 0.2s; }
        .ok-star-row input:checked ~ label, .ok-star-row label:hover, .ok-star-row label:hover ~ label { color: #ffc107; }
        .ok-input { width: 100%; padding: 15px; border: 1px solid #eee; border-radius: 12px; margin-bottom: 15px; background: #f9f9f9; box-sizing: border-box; font-size: 16px; }
        .ok-submit { background: #2c7be5; color: #fff; border: none; padding: 18px; width: 100%; border-radius: 12px; font-weight: bold; cursor: pointer; font-size: 18px; }
    </style>
    <div class="ok-form-full">
        <form id="ok-ajax-f">
            <input type="hidden" id="c-id" value="<?php echo $camp_id; ?>">
            <input type="text" id="c-name" class="ok-input" placeholder="თქვენი სახელი" required>
            <div class="ok-star-row">
                <input type="radio" name="rate" id="s5" value="5"><label for="s5">★</label>
                <input type="radio" name="rate" id="s4" value="4"><label for="s4">★</label>
                <input type="radio" name="rate" id="s3" value="3" checked><label for="s3">★</label>
                <input type="radio" name="rate" id="s2" value="2"><label for="s2">★</label>
                <input type="radio" name="rate" id="s1" value="1"><label for="s1">★</label>
            </div>
            <textarea id="c-text" class="ok-input" rows="4" placeholder="თქვენი აზრი..." required></textarea>
            <button type="submit" class="ok-submit">გაგზავნა</button>
            <div id="ok-success" style="display:none; color: green; font-weight: bold; margin-top: 15px; text-align: center;">მადლობა, შეფასება მიღებულია!</div>
        </form>
    </div>
    <script>
        document.getElementById('ok-ajax-f')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('.ok-submit');
            btn.disabled = true; btn.innerText = 'იგზავნება...';
            const fd = new FormData();
            fd.append('ok_action', 'save');
            fd.append('n', document.getElementById('c-name').value);
            fd.append('t', document.getElementById('c-text').value);
            fd.append('r', this.querySelector('input[name="rate"]:checked').value);
            fd.append('c', document.getElementById('c-id').value);
            fetch(window.location.href, { method: 'POST', body: fd }).then(r => r.text()).then(res => {
                if(res.trim()==='ok'){ this.reset(); btn.style.display='none'; document.getElementById('ok-success').style.display='block'; }
            });
        });
    </script>
    <?php return ob_get_clean();
}

/**
 * 4. Frontend - სია (2 ქარდი, ვარსკვლავები შუაში, დანარჩენი მარცხნივ)
 */
function ok_testi_list_render($atts) {
    global $ok_db;
    $slug = $atts['id'] ?? '';
    $limit = isset($atts['limit']) ? (int)$atts['limit'] : 10;

    $q = "SELECT * FROM ok_testimonials WHERE is_public = 1 ";
    $p = [];
    if(!empty($slug)) {
        $cid = $ok_db->get_var("SELECT id FROM ok_testi_campaigns WHERE camp_slug = ?", [$slug]);
        if($cid) { $q .= " AND campaign_id = ? "; $p[] = $cid; }
    }
    $q .= " ORDER BY id DESC LIMIT $limit";
    $items = $ok_db->get_results($q, $p, PDO::FETCH_ASSOC);

    ob_start(); ?>
    <style>
        .ok-list-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; width: 100%; }
        @media (max-width: 768px) { .ok-list-grid { grid-template-columns: 1fr; } }
        .ok-list-item { 
            background: #fff; border-radius: 18px; padding: 25px; 
            border: 1px solid #f1f1f1; text-align: left; /* ტექსტი მარცხნივ */
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        }
        .ok-list-stars { 
            color: #ffc107; font-size: 26px; margin-bottom: 15px; 
            display: flex; justify-content: center; /* მხოლოდ ვარსკვლავები შუაში */
        }
        .ok-list-text { color: #444; font-size: 15px; line-height: 1.6; font-style: italic; margin-bottom: 20px; }
        .ok-list-user { display: flex; align-items: center; gap: 12px; border-top: 1px solid #f8f8f8; padding-top: 15px; }
    </style>
    <div class="ok-list-grid">
        <?php foreach($items as $i): ?>
            <div class="ok-list-item">
                <div class="ok-list-stars"><?php echo str_repeat('★', $i['rating']); ?></div>
                <div class="ok-list-text">"<?php echo nl2br(htmlspecialchars($i['testimonial_text'])); ?>"</div>
                <div class="ok-list-user">
                    <?php echo ok_testi_render_avatar($i['client_name'], '38px'); ?>
                    <div>
                        <div class="fw-bold text-dark small" style="line-height:1;"><?php echo $i['client_name']; ?></div>
                        <div class="text-muted" style="font-size:10px;"><?php echo date('d.m.Y', strtotime($i['created_at'])); ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php return ob_get_clean();
}

/**
 * 5. რეგისტრაცია
 */
if (function_exists('add_ok_action')) {
    add_ok_action('admin_menu', function() {
        add_menu_page('Reviews', 'შეფასებები', 'manage_options', 'ok-testimonials', 'ok_testi_admin_gui', 'bi bi-star-fill', 25);
    });
}
if (function_exists('add_ok_shortcode')) {
    add_ok_shortcode('ok_testimonials_list', 'ok_testi_list_render');
    add_ok_shortcode('ok_testimonial_form', 'ok_testi_form_render');
}