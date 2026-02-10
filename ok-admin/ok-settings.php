<?php
/**
 * სისტემის პარამეტრები (Updated: With Notifications 🔔)
 */

// 1. მენიუს რეგისტრაცია
add_ok_action('admin_menu', function() {
    add_menu_page('პარამეტრები', 'პარამეტრები', 'manage_options', 'ok-settings', 'ok_render_settings', 'bi bi-sliders', 80);
    add_submenu_page('ok-settings', 'ზოგადი', 'ზოგადი', 'manage_options', 'ok-settings', 'ok_render_settings');
    add_submenu_page('ok-settings', 'კითხვა', 'კითხვა', 'manage_options', 'ok-settings-reading', 'ok_render_settings');
    add_submenu_page('ok-settings', 'ბმულები', 'ბმულები', 'manage_options', 'ok-settings-permalinks', 'ok_render_settings');
    add_submenu_page('ok-settings', 'უსაფრთხოება', 'უსაფრთხოება', 'manage_options', 'ok-settings-security', 'ok_render_settings');
    add_submenu_page('ok-settings', 'ფუნქციონალი', 'ფუნქციონალი', 'manage_options', 'ok-settings-functional', 'ok_render_settings');
});


function ok_settings_update_config_nonce_keys(string $nonceKey, string $nonceSalt): bool {
    $configPath = dirname(__DIR__) . '/ok-config.php';
    if (!is_file($configPath) || !is_readable($configPath) || !is_writable($configPath)) {
        return false;
    }

    $config = file_get_contents($configPath);
    if ($config === false) {
        return false;
    }

    $nonceKeyExport = var_export($nonceKey, true);
    $nonceSaltExport = var_export($nonceSalt, true);

    $keyPattern = '/define\s*\(\s*["\']NONCE_KEY["\']\s*,\s*.*?\)\s*;/';
    $saltPattern = '/define\s*\(\s*["\']NONCE_SALT["\']\s*,\s*.*?\)\s*;/';

    if (preg_match($keyPattern, $config)) {
        $config = preg_replace($keyPattern, "define('NONCE_KEY', " . $nonceKeyExport . ");", $config, 1);
    } else {
        $config .= "\n" . "define('NONCE_KEY', " . $nonceKeyExport . ");";
    }

    if (preg_match($saltPattern, $config)) {
        $config = preg_replace($saltPattern, "define('NONCE_SALT', " . $nonceSaltExport . ");", $config, 1);
    } else {
        $config .= "\n" . "define('NONCE_SALT', " . $nonceSaltExport . ");";
    }

    return file_put_contents($configPath, $config) !== false;
}

function ok_render_settings() {
    global $ok_db;

    // აქტიური ტაბის განსაზღვრა
    $current_page = $_GET['page'] ?? 'ok-settings';
    $active_tab = 'general';
    if ($current_page === 'ok-settings-reading')    $active_tab = 'reading';
    if ($current_page === 'ok-settings-permalinks') $active_tab = 'permalinks';
    if ($current_page === 'ok-settings-security')   $active_tab = 'security';
    if ($current_page === 'ok-settings-functional') $active_tab = 'functional';

    // --- SAVE ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
        ok_sec_check('ok_settings_save');
        
        $notif_message = ""; // ცვლადი შეტყობინებისთვის

        if ($active_tab === 'general') {
            update_ok_option('site_title', trim($_POST['site_title']));
            update_ok_option('site_tagline', trim($_POST['site_tagline']));
            update_ok_option('admin_email', trim($_POST['admin_email']));
            update_ok_option('footer_text', trim($_POST['footer_text']));
            update_ok_option('site_logo', trim($_POST['site_logo']));
            update_ok_option('site_favicon', trim($_POST['site_favicon']));
            update_ok_option('timezone_string', $_POST['timezone_string']);
            
            $df = ($_POST['date_format'] === 'custom') ? trim($_POST['date_format_custom']) : $_POST['date_format'];
            update_ok_option('date_format', $df);
            
            $tf = ($_POST['time_format'] === 'custom') ? trim($_POST['time_format_custom']) : $_POST['time_format'];
            update_ok_option('time_format', $tf);

            $notif_message = "ზოგადი პარამეტრები განახლდა.";
        }

        if ($active_tab === 'reading') {
            $display_mode = $_POST['front_page_display'] ?? 'latest';

            if ($display_mode === 'latest') {
                update_ok_option('front_page_id', 0);
                update_ok_option('blog_page_id', 0);
            } else {
                update_ok_option('front_page_id', (int)$_POST['front_page_id']);
                update_ok_option('blog_page_id', (int)$_POST['blog_page_id']);
            }

            update_ok_option('posts_per_page', (int)$_POST['posts_per_page']);

            $notif_message = "კითხვის პარამეტრები შეიცვალა.";
        }

        if ($active_tab === 'permalinks') {
            $perm_selection = $_POST['permalink_selection'];
            $struct = ($perm_selection === 'custom') ? trim($_POST['permalink_structure_custom']) : $perm_selection;
            update_ok_option('permalink_structure', $struct);

            $notif_message = "მუდმივი ბმულების სტრუქტურა განახლდა.";
        }

        if ($active_tab === 'security') {
            $new_nonce_key = trim((string)($_POST['nonce_key'] ?? ''));
            $new_nonce_salt = trim((string)($_POST['nonce_salt'] ?? ''));

            update_ok_option('nonce_key', $new_nonce_key);
            update_ok_option('nonce_salt', $new_nonce_salt);

            $cfg_ok = ok_settings_update_config_nonce_keys($new_nonce_key, $new_nonce_salt);
            if (!$cfg_ok) {
                echo '<div class="alert alert-warning shadow-sm border-0 mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> ok-config.php ვერ განახლდა ავტომატურად. შეამოწმეთ ფაილის write უფლება.</div>';
            }

            $notif_message = "უსაფრთხოების გასაღებები (Keys) შეიცვალა.";
        }

        // 🛑 Functional Tab Save
        if ($active_tab === 'functional') {
            // Sidebar
            $global_sidebar = isset($_POST['enable_global_sidebar']) ? 1 : 0;
            update_ok_option('enable_global_sidebar', $global_sidebar);

            // Dev Mode
            $dev_mode = isset($_POST['dev_mode']) ? 1 : 0;
            update_ok_option('dev_mode', $dev_mode);

            // Notification Mail
            $mail_notifications_enabled = isset($_POST['mail_notifications_enabled']) ? 1 : 0;
            update_ok_option('mail_notifications_enabled', $mail_notifications_enabled);

            // Notification polling rate limit (requests/min per session)
            $poll_limit = isset($_POST['notif_poll_rate_limit']) ? (int)$_POST['notif_poll_rate_limit'] : 60;
            if ($poll_limit < 10) $poll_limit = 10;
            if ($poll_limit > 300) $poll_limit = 300;
            update_ok_option('notif_poll_rate_limit', $poll_limit);

            // Plugin allowlist (one plugin entry per line: slug/file.php)
            $allowlist_raw = trim((string)($_POST['plugin_allowlist'] ?? ''));
            $allowlist = [];
            if ($allowlist_raw !== '') {
                $lines = preg_split('/\\r\\n|\\r|\\n/', $allowlist_raw);
                if (is_array($lines)) {
                    foreach ($lines as $line) {
                        $entry = trim($line);
                        if ($entry !== '' && preg_match('/^[a-zA-Z0-9_\/-]+\.php$/', $entry) && strpos($entry, '..') === false) {
                            $allowlist[] = $entry;
                        }
                    }
                }
            }
            update_ok_option('plugin_allowlist', array_values(array_unique($allowlist)));

            $notif_message = "ფუნქციონალური პარამეტრები შეიცვალა.";
        }

        // 🔔 1. ნოთიფიკაციის დამატება ბაზაში (თუ ფუნქცია არსებობს)
        if (!empty($notif_message) && function_exists('ok_add_notification')) {
            // ლინკი მივუთითოთ იმ გვერდზე, სადაც ვიმყოფებით
            $current_url = "index.php?page=" . htmlspecialchars($current_page);
            ok_add_notification($notif_message, 'info', [], $current_url);
        }

        echo '<div class="alert alert-success shadow-sm border-0 mb-4 fade show"><i class="bi bi-check-circle-fill me-2"></i> პარამეტრები შენახულია.</div>';
    }

    // --- DATA FETCHING ---
    
    // General
    $site_title   = get_ok_option('site_title', 'OK Engine');
    $site_tagline = get_ok_option('site_tagline', '');
    $admin_email  = get_ok_option('admin_email', '');
    $footer_text  = get_ok_option('footer_text', '© 2023 OK Engine. All rights reserved.');
    $site_logo    = get_ok_option('site_logo', '');
    $site_favicon = get_ok_option('site_favicon', '');
    $current_tz   = get_ok_option('timezone_string', 'Asia/Tbilisi');
    $date_format  = get_ok_option('date_format', 'F j, Y');
    $time_format  = get_ok_option('time_format', 'H:i');

    // Reading
    $front_page_id  = (int)get_ok_option('front_page_id', 0);
    $blog_page_id   = (int)get_ok_option('blog_page_id', 0);
    $posts_per_page = (int)get_ok_option('posts_per_page', 10);
    
    $pages = $ok_db->get_results("SELECT id, post_title FROM ok_posts WHERE post_type='page' AND post_status='published'");

    // Permalinks
    $perm_struct      = get_ok_option('permalink_structure', 'plain');
    $perm_radio       = ($perm_struct == 'plain' || $perm_struct == 'post_name') ? $perm_struct : 'custom';
    $perm_custom_val  = ($perm_radio === 'custom') ? $perm_struct : '/%postname%/';

    // Security
    $nonce_key  = get_ok_option('nonce_key', '');
    $nonce_salt = get_ok_option('nonce_salt', '');

    // 🛑 Functional Data
    $enable_global_sidebar = (int)get_ok_option('enable_global_sidebar', 1);
    $dev_mode              = (int)get_ok_option('dev_mode', 0);
    $mail_notifications_enabled = (int)get_ok_option('mail_notifications_enabled', 0);
    $notif_poll_rate_limit = (int)get_ok_option('notif_poll_rate_limit', 60);
    if ($notif_poll_rate_limit < 10) $notif_poll_rate_limit = 10;
    if ($notif_poll_rate_limit > 300) $notif_poll_rate_limit = 300;
    $plugin_allowlist_arr = get_ok_option('plugin_allowlist', []);
    if (!is_array($plugin_allowlist_arr)) $plugin_allowlist_arr = [];
    $plugin_allowlist_text = implode("\n", $plugin_allowlist_arr);

    // Helpers
    $timezones = DateTimeZone::listIdentifiers();
    $date_formats_sample = ['F j, Y' => date('F j, Y'), 'Y-m-d' => date('Y-m-d'), 'm/d/Y' => date('m/d/Y'), 'd/m/Y' => date('d/m/Y')];
    $time_formats_sample = ['H:i' => date('H:i'), 'g:i a' => date('g:i a'), 'g:i A' => date('g:i A')];
    ?>

    <style>
        .settings-nav .nav-link { 
            border-radius: 50rem; 
            padding: 10px 25px; 
            font-weight: 500;
            color: #6c757d;
            transition: all 0.2s;
        }
        .settings-nav .nav-link:hover { background-color: #f8f9fa; color: #0d6efd; }
        .settings-nav .nav-link.active { background-color: #0d6efd; color: white !important; box-shadow: 0 4px 6px rgba(13,110,253,0.2); }
        
        .code-preview { font-family: 'Courier New', monospace; background: #f8f9fa; padding: 2px 6px; border-radius: 4px; color: #d63384; border: 1px solid #e9ecef; }
        .card-header { border-bottom: 1px solid #f0f0f0; }
        .form-label { font-weight: 600; color: #495057; font-size: 0.95rem; }
        .form-text { font-size: 0.85rem; color: #adb5bd; }
        
        /* ლოგოს პრევიუ */
        .img-preview-box {
            width: 100%; height: 100px; 
            border: 2px dashed #dee2e6; 
            border-radius: 8px; 
            display: flex; align-items: center; justify-content: center;
            background: #f8f9fa; overflow: hidden; position: relative;
            cursor: pointer; transition: all 0.2s;
        }
        .img-preview-box:hover { border-color: #0d6efd; background: #fff; }
        .img-preview-box img { max-height: 100%; max-width: 100%; object-fit: contain; }
        .preview-placeholder { color: #adb5bd; text-align: center; font-size: 0.85rem; }
        .btn-remove-img { position: absolute; top: 5px; right: 5px; z-index: 10; }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-4 sticky-top bg-light py-3 border-bottom shadow-sm" style="margin-top: -20px; padding-left: 20px; padding-right: 20px; z-index: 100;">
        <h1 class="h3 mb-0 fw-bold text-dark"><i class="bi bi-gear-fill me-2 text-secondary"></i>პარამეტრები</h1>
        <button type="submit" form="settings_form" name="save_settings" class="btn btn-primary px-4 rounded-pill shadow">
            <i class="bi bi-save me-2"></i> შენახვა
        </button>
    </div>

    <form method="post" id="settings_form" class="pb-5">
        <?php ok_nonce_field('ok_settings_save'); ?>
        
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-2">
                <ul class="nav nav-pills nav-fill settings-nav">
                    <li class="nav-item"><a class="nav-link <?php echo ($active_tab === 'general') ? 'active' : ''; ?>" href="index.php?page=ok-settings"><i class="bi bi-sliders me-2"></i>ზოგადი</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($active_tab === 'reading') ? 'active' : ''; ?>" href="index.php?page=ok-settings-reading"><i class="bi bi-book me-2"></i>კითხვა</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($active_tab === 'permalinks') ? 'active' : ''; ?>" href="index.php?page=ok-settings-permalinks"><i class="bi bi-link-45deg me-2"></i>ბმულები</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($active_tab === 'security') ? 'active' : ''; ?>" href="index.php?page=ok-settings-security"><i class="bi bi-shield-lock me-2"></i>უსაფრთხოება</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($active_tab === 'functional') ? 'active' : ''; ?>" href="index.php?page=ok-settings-functional"><i class="bi bi-tools me-2"></i>ფუნქციონალი</a></li>
                </ul>
            </div>
        </div>

        <div class="tab-content">
            
            <?php if ($active_tab === 'general'): ?>
            <div class="tab-pane fade show active">
                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">საიტის იდენტობა</h5></div>
                            <div class="card-body p-4">
                                <div class="mb-3">
                                    <label class="form-label">საიტის სათაური</label>
                                    <input type="text" name="site_title" class="form-control form-control-lg" value="<?php echo htmlspecialchars($site_title); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">აღწერა (Tagline)</label>
                                    <input type="text" name="site_tagline" class="form-control" value="<?php echo htmlspecialchars($site_tagline); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">ადმინისტრატორის ელ.ფოსტა</label>
                                    <input type="email" name="admin_email" class="form-control" value="<?php echo htmlspecialchars($admin_email); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Footer ტექსტი</label>
                                    <input type="text" name="footer_text" class="form-control" value="<?php echo htmlspecialchars($footer_text); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">თარიღი და დრო</h5></div>
                            <div class="card-body p-4">
                                <div class="mb-4">
                                    <label class="form-label">დროის სარტყელი</label>
                                    <select name="timezone_string" class="form-select">
                                        <?php foreach ($timezones as $tz) echo "<option value='$tz' " . ($current_tz == $tz ? 'selected' : '') . ">$tz</option>"; ?>
                                    </select>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label mb-2">თარიღის ფორმატი</label>
                                        <?php foreach ($date_formats_sample as $fmt => $sample): ?>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input" type="radio" name="date_format" value="<?php echo htmlspecialchars($fmt); ?>" <?php echo ($date_format == $fmt) ? 'checked' : ''; ?>>
                                                <label class="form-check-label"><?php echo $sample; ?> <span class="badge bg-light text-dark border ms-1"><?php echo $fmt; ?></span></label>
                                            </div>
                                        <?php endforeach; ?>
                                        <div class="form-check d-flex align-items-center mt-2">
                                            <input class="form-check-input" type="radio" name="date_format" value="custom" <?php echo (!array_key_exists($date_format, $date_formats_sample)) ? 'checked' : ''; ?>>
                                            <label class="form-check-label me-2 ms-2">სხვა:</label>
                                            <input type="text" name="date_format_custom" class="form-control form-control-sm w-auto" value="<?php echo htmlspecialchars($date_format); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label mb-2">დროის ფორმატი</label>
                                        <?php foreach ($time_formats_sample as $fmt => $sample): ?>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input" type="radio" name="time_format" value="<?php echo htmlspecialchars($fmt); ?>" <?php echo ($time_format == $fmt) ? 'checked' : ''; ?>>
                                                <label class="form-check-label"><?php echo $sample; ?> <span class="badge bg-light text-dark border ms-1"><?php echo $fmt; ?></span></label>
                                            </div>
                                        <?php endforeach; ?>
                                        <div class="form-check d-flex align-items-center mt-2">
                                            <input class="form-check-input" type="radio" name="time_format" value="custom" <?php echo (!array_key_exists($time_format, $time_formats_sample)) ? 'checked' : ''; ?>>
                                            <label class="form-check-label me-2 ms-2">სხვა:</label>
                                            <input type="text" name="time_format_custom" class="form-control form-control-sm w-auto" value="<?php echo htmlspecialchars($time_format); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">ლოგო & Favicon</h5></div>
                            <div class="card-body p-4 text-center">
                                <label class="form-label d-block text-start mb-2">საიტის ლოგო</label>
                                <div class="img-preview-box mb-2" onclick="selectMedia('site_logo')">
                                    <?php if($site_logo): ?>
                                        <img src="<?php echo htmlspecialchars($site_logo); ?>" id="preview_site_logo">
                                        <button type="button" class="btn btn-sm btn-danger btn-remove-img" onclick="removeMedia(event, 'site_logo')"><i class="bi bi-x"></i></button>
                                    <?php else: ?>
                                        <div class="preview-placeholder" id="ph_site_logo"><i class="bi bi-image fs-3 d-block"></i> აირჩიეთ ლოგო</div>
                                        <img src="" id="preview_site_logo" style="display:none;">
                                        <button type="button" class="btn btn-sm btn-danger btn-remove-img" id="btn_rm_site_logo" style="display:none;" onclick="removeMedia(event, 'site_logo')"><i class="bi bi-x"></i></button>
                                    <?php endif; ?>
                                </div>
                                <input type="hidden" name="site_logo" id="input_site_logo" value="<?php echo htmlspecialchars($site_logo); ?>">

                                <hr class="my-4">

                                <label class="form-label d-block text-start mb-2">Favicon (აიკონი)</label>
                                <div class="img-preview-box" style="height: 64px; width: 64px; margin: 0 auto;" onclick="selectMedia('site_favicon')">
                                    <?php if($site_favicon): ?>
                                        <img src="<?php echo htmlspecialchars($site_favicon); ?>" id="preview_site_favicon">
                                        <button type="button" class="btn btn-sm btn-danger btn-remove-img" onclick="removeMedia(event, 'site_favicon')"><i class="bi bi-x"></i></button>
                                    <?php else: ?>
                                        <div class="preview-placeholder" id="ph_site_favicon"><i class="bi bi-app"></i></div>
                                        <img src="" id="preview_site_favicon" style="display:none;">
                                        <button type="button" class="btn btn-sm btn-danger btn-remove-img" id="btn_rm_site_favicon" style="display:none;" onclick="removeMedia(event, 'site_favicon')"><i class="bi bi-x"></i></button>
                                    <?php endif; ?>
                                </div>
                                <input type="hidden" name="site_favicon" id="input_site_favicon" value="<?php echo htmlspecialchars($site_favicon); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($active_tab === 'reading'): ?>
            <div class="tab-pane fade show active">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">კითხვის პარამეტრები</h5></div>
                        <div class="card-body p-4">
                            <div class="row mb-4">
                                <label class="col-sm-3 col-form-label fw-bold">მთავარი გვერდი აჩვენებს</label>
                                <div class="col-sm-9">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="front_page_display" id="show_latest" value="latest" <?php echo ($front_page_id == 0) ? 'checked' : ''; ?> onclick="toggleStaticPage(false)">
                                        <label class="form-check-label" for="show_latest">თქვენს უახლეს პოსტებს</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="front_page_display" id="show_static" value="static" <?php echo ($front_page_id > 0) ? 'checked' : ''; ?> onclick="toggleStaticPage(true)">
                                        <label class="form-check-label" for="show_static">სტატიკურ გვერდს</label>
                                    </div>
                                </div>
                            </div>

                            <div id="static_page_selection" style="display: <?php echo ($front_page_id > 0) ? 'block' : 'none'; ?>;">
                                <div class="row mb-3">
                                    <label class="col-sm-3 col-form-label ps-5">მთავარი გვერდი:</label>
                                    <div class="col-sm-4">
                                        <select name="front_page_id" class="form-select">
                                            <option value="0">-- აირჩიეთ --</option>
                                            <?php foreach ($pages as $p) echo "<option value='{$p->id}' " . ($front_page_id == $p->id ? 'selected' : '') . ">{$p->post_title}</option>"; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-4">
                                    <label class="col-sm-3 col-form-label ps-5">პოსტების გვერდი:</label>
                                    <div class="col-sm-4">
                                        <select name="blog_page_id" class="form-select">
                                            <option value="0">-- აირჩიეთ --</option>
                                            <?php foreach ($pages as $p) echo "<option value='{$p->id}' " . ($blog_page_id == $p->id ? 'selected' : '') . ">{$p->post_title}</option>"; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label class="col-sm-3 col-form-label fw-bold">პოსტების რაოდენობა</label>
                                <div class="col-sm-2">
                                    <input type="number" name="posts_per_page" class="form-control" value="<?php echo $posts_per_page; ?>" min="1">
                                </div>
                                <div class="col-sm-7 col-form-label text-muted">პოსტი თითო გვერდზე</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                function toggleStaticPage(show) {
                    document.getElementById('static_page_selection').style.display = show ? 'block' : 'none';
                }
            </script>
            <?php endif; ?>

            <?php if ($active_tab === 'permalinks'): ?>
            <div class="tab-pane fade show active">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">მუდმივი ბმულები</h5></div>
                        <div class="card-body p-4">
                            <div class="d-grid gap-2">
                                <label class="card p-3 border cursor-pointer hover-bg-light <?php echo ($perm_radio == 'plain') ? 'border-primary bg-light' : ''; ?>">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="permalink_selection" value="plain" <?php echo ($perm_radio == 'plain') ? 'checked' : ''; ?>>
                                        <span class="fw-bold mx-2">მარტივი</span> <code class="code-preview">?p=123</code>
                                    </div>
                                </label>
                                <label class="card p-3 border cursor-pointer hover-bg-light <?php echo ($perm_radio == 'post_name') ? 'border-primary bg-light' : ''; ?>">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="permalink_selection" value="post_name" <?php echo ($perm_radio == 'post_name') ? 'checked' : ''; ?>>
                                        <span class="fw-bold mx-2">სახელით</span> <code class="code-preview">/sample-post/</code>
                                    </div>
                                </label>
                                <label class="card p-3 border cursor-pointer hover-bg-light <?php echo ($perm_radio == 'custom') ? 'border-primary bg-light' : ''; ?>">
                                    <div class="form-check d-flex align-items-center">
                                        <input class="form-check-input" type="radio" name="permalink_selection" id="perm_custom" value="custom" <?php echo ($perm_radio == 'custom') ? 'checked' : ''; ?>>
                                        <span class="fw-bold mx-2">ინდივიდუალური</span>
                                        <div class="input-group input-group-sm w-50 ms-2">
                                            <span class="input-group-text bg-white text-muted">/</span>
                                            <input type="text" name="permalink_structure_custom" id="custom_structure_input" class="form-control" value="<?php echo htmlspecialchars($perm_custom_val); ?>">
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($active_tab === 'security'): ?>
            <div class="tab-pane fade show active">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold">Authentication Keys</h5>
                            <button type="button" class="btn btn-warning btn-sm text-dark" id="generate-keys"><i class="bi bi-arrow-repeat me-1"></i> გენერაცია</button>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-4"><label class="form-label fw-bold">NONCE_KEY</label><input type="text" name="nonce_key" id="nonce_key" class="form-control font-monospace bg-light" value="<?php echo htmlspecialchars($nonce_key); ?>"></div>
                            <div class="mb-3"><label class="form-label fw-bold">NONCE_SALT</label><input type="text" name="nonce_salt" id="nonce_salt" class="form-control font-monospace bg-light" value="<?php echo htmlspecialchars($nonce_salt); ?>"></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($active_tab === 'functional'): ?>
            <div class="tab-pane fade show active">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">ფუნქციონალური პარამეტრები</h5></div>
                        <div class="card-body p-4">
                            
                            <div class="d-flex align-items-center justify-content-between p-3 border rounded bg-light mb-3">
                                <div>
                                    <h6 class="fw-bold mb-1">გლობალური საიდბარი</h6>
                                    <small class="text-muted">ჩართეთ ან გამორთეთ საიდბარი მთელ საიტზე. თუ ეს გამორთულია, ინდივიდუალური პარამეტრები არ იმუშავებს.</small>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input fs-4" type="checkbox" name="enable_global_sidebar" id="enable_global_sidebar" value="1" <?php echo ($enable_global_sidebar == 1) ? 'checked' : ''; ?>>
                                </div>
                            </div>


                            <div class="d-flex align-items-center justify-content-between p-3 border rounded bg-light mb-3">
                                <div>
                                    <h6 class="fw-bold mb-1"><i class="bi bi-envelope-check me-2"></i>ელფოსტის ნოთიფიკაციები</h6>
                                    <small class="text-muted">ჩართავს სისტემური ნოთიფიკაციების ელფოსტით გაგზავნას.</small>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input fs-4" type="checkbox" name="mail_notifications_enabled" id="mail_notifications_enabled" value="1" <?php echo ($mail_notifications_enabled == 1) ? 'checked' : ''; ?>>
                                </div>
                            </div>

                            <div class="p-3 border rounded bg-light mb-3">
                                <label class="form-label fw-bold"><i class="bi bi-speedometer2 me-2"></i>Notification Poll Rate Limit (10-300 / წუთში)</label>
                                <input type="number" min="10" max="300" class="form-control" name="notif_poll_rate_limit" value="<?php echo (int)$notif_poll_rate_limit; ?>">
                                <small class="text-muted">გამოიყენება unread endpoint-ზე სესიის მიხედვით.</small>
                            </div>

                            <div class="p-3 border rounded bg-light mb-3">
                                <label class="form-label fw-bold"><i class="bi bi-shield-lock me-2"></i>Plugin Allowlist</label>
                                <textarea class="form-control font-monospace" rows="6" name="plugin_allowlist" placeholder="ok-quiz/index.php
ok-social/ok-social.php"><?php echo htmlspecialchars($plugin_allowlist_text, ENT_QUOTES, 'UTF-8'); ?></textarea>
                                <small class="text-muted">თითო plugin ფაილი ახალ ხაზზე. თუ ცარიელია, allowlist enforcement გამორთულია.</small>
                            </div>

                            <div class="d-flex align-items-center justify-content-between p-3 border rounded border-warning bg-warning bg-opacity-10 mb-3">
                                <div>
                                    <h6 class="fw-bold mb-1 text-dark"><i class="bi bi-bug me-2"></i>დეველოპერ რეჟიმი (Debug Mode)</h6>
                                    <small class="text-dark opacity-75">
                                        რრთავს PHP-ს შეცდომების ჩვენებას ეკრანზე (`display_errors`).<br>
                                        <span class="text-danger fw-bold">ყურადღება:</span> არ დატოვოთ ჩართული საჯარო (Production) საიტზე!
                                    </small>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input fs-4" type="checkbox" name="dev_mode" id="dev_mode" value="1" <?php echo ($dev_mode == 1) ? 'checked' : ''; ?>>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </form>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // --- Security Keys ---
        const btnGen = document.getElementById('generate-keys');
        if (btnGen) {
            btnGen.addEventListener('click', function() {
                if (confirm('ყურადღება: გასაღებების შეცვლა გააუქმებს ყველა აქტიურ სესიას.')) {
                    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+[]{}|;:,.<>?';
                    const gen = () => { let res = ''; const vals = new Uint32Array(64); window.crypto.getRandomValues(vals); for (let i = 0; i < 64; i++) res += chars[vals[i] % chars.length]; return res; };
                    document.getElementById('nonce_key').value = gen(); document.getElementById('nonce_salt').value = gen();
                }
            });
        }

        // --- Custom Permalink ---
        const customInput = document.getElementById('custom_structure_input');
        const customRadio = document.getElementById('perm_custom');
        if(customInput) customInput.addEventListener('focus', () => customRadio.checked = true);

        // --- Media Selector (Logo/Favicon) ---
        window.selectMedia = function(targetKey) {
            Swal.fire({
                title: 'აირჩიეთ ფაილი', width: '800px',
                html: '<div id="swal-gallery-grid" class="d-flex flex-wrap gap-2 justify-content-center p-2" style="max-height: 400px; overflow-y: auto;"><i>იტვირთება...</i></div>',
                showConfirmButton: false, showCancelButton: true, cancelButtonText: 'დახურვა',
                didOpen: () => {
                    fetch('index.php?page=ok-gallery&action=get_images_ajax').then(r => r.json()).then(res => {
                        const grid = document.getElementById('swal-gallery-grid');
                        if(res.success && res.images.length > 0) {
                            grid.innerHTML = '';
                            res.images.forEach(img => {
                                const div = document.createElement('div');
                                div.style.cssText = 'width: 100px; height: 100px; cursor: pointer; border: 2px solid #eee; overflow: hidden; border-radius: 4px; transition: 0.2s;';
                                div.innerHTML = `<img src="${img.url}" style="width: 100%; height: 100%; object-fit: cover;" title="${img.name}">`;
                                div.addEventListener('click', () => {
                                    document.getElementById('input_' + targetKey).value = img.url;
                                    const preview = document.getElementById('preview_' + targetKey);
                                    preview.src = img.url; preview.style.display = 'block';
                                    const ph = document.getElementById('ph_' + targetKey); if(ph) ph.style.display = 'none';
                                    const rmBtn = document.getElementById('btn_rm_' + targetKey); if(rmBtn) rmBtn.style.display = 'block'; else preview.parentElement.querySelector('.btn-remove-img').style.display = 'block';
                                    Swal.close();
                                });
                                div.onmouseover = () => div.style.borderColor = '#0d6efd'; div.onmouseout = () => div.style.borderColor = '#eee';
                                grid.appendChild(div);
                            });
                        } else { grid.innerHTML = 'გალერეა ცარიელია.'; }
                    });
                }
            });
        };

        window.removeMedia = function(e, targetKey) {
            e.stopPropagation();
            document.getElementById('input_' + targetKey).value = '';
            document.getElementById('preview_' + targetKey).style.display = 'none';
            document.getElementById('ph_' + targetKey).style.display = 'block';
            if(e.target.tagName === 'I') e.target.parentElement.style.display = 'none'; else e.target.style.display = 'none';
        }
    });
    </script>
    <?php
}
