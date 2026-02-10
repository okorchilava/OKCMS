<?php
/*
Plugin Name: OK Social Meta, API & Sharing PRO v14.0
Description: Facebook Fix, Meta Tags (OG & Desc), Sticky Buttons & Analytics.
Version: 14.0.0
Author: OK Engine Team
*/

if (!defined('OK_LOADED')) exit;

/* ---------------------------------------------------------
   1. ადმინ პანელი და პარამეტრების მართვა
--------------------------------------------------------- */
add_ok_action('admin_menu', function() {
    add_menu_page('სოციალური', 'სოციალური', 'manage_options', 'ok-social', 'ok_render_social_admin_page', 'bi bi-share', 31);
});

function ok_render_social_admin_page() {
    // ყველა ველი, რომელსაც ვინახავთ
    $fields = [
        'fb_app_id', 'fb_verify_tag', 'og_default_img', 
        'site_description', // OG Description
        'show_fb', 'show_wa', 'show_vi', 'show_tg', 'show_tw', 'show_li', 'show_pi', 
        'show_count', 'show_bottom_like'
    ];
    
    // შენახვის პროცესი
    if (isset($_POST['ok_save_social'])) {
        foreach ($fields as $field) {
            // Checkbox ლოგიკა: თუ მონიშნულია - მნიშვნელობა, თუ არა - 0
            // Text ლოგიკა: პირდაპირ მნიშვნელობა
            $val = isset($_POST[$field]) ? $_POST[$field] : (strpos($field, 'show_') === 0 ? 0 : '');
            
            // სლეშების გასუფთავება (Verification Tag-ისთვის)
            if($field == 'fb_verify_tag') $val = stripslashes($val);
            
            update_ok_option($field, $val);
        }
        echo '<div class="alert alert-success m-3 shadow-sm border-0">პარამეტრები წარმატებით შენახულია!</div>';
    }
    
    // მონაცემების წაკითხვა ბაზიდან (რომ ფორმაში ჩავსვათ)
    $opt = [];
    foreach ($fields as $field) { 
        $opt[$field] = get_ok_option($field, ''); 
    }

    ?>
    <div class="p-4 bg-light min-vh-100">
        <h2 class="mb-4 fw-bold text-dark"><i class="bi bi-share-fill me-2"></i>სოციალური ქსელების მართვა</h2>
        <form method="post" class="p-4 bg-white border rounded shadow-sm">
            <div class="row">
                <div class="col-md-7 border-end">
                    <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary">Facebook & Meta</h5>
                    
                    <label class="form-label small fw-bold">Facebook App ID</label>
                    <input type="text" name="fb_app_id" value="<?php echo htmlspecialchars((string)$opt['fb_app_id']); ?>" class="form-control mb-3" placeholder="მაგ: 123456789...">
                    
                    <label class="form-label small fw-bold">Domain Verification Meta Tag</label>
                    <textarea name="fb_verify_tag" class="form-control mb-3" rows="3" placeholder='<meta name="facebook-domain-verification" content="..." />'><?php echo htmlspecialchars((string)$opt['fb_verify_tag']); ?></textarea>
                    
                    <label class="form-label small fw-bold">ნაგულისხმევი OG სურათი (URL)</label>
                    <input type="text" name="og_default_img" value="<?php echo htmlspecialchars((string)$opt['og_default_img']); ?>" class="form-control mb-3">
                    
                    <label class="form-label small fw-bold">საიტის ზოგადი აღწერა (OG Description)</label>
                    <textarea name="site_description" class="form-control" rows="3"><?php echo htmlspecialchars((string)$opt['site_description']); ?></textarea>
                    <div class="form-text text-muted">გამოჩნდება მაშინ, როცა კონკრეტულ გვერდს ტექსტი არ აქვს.</div>
                </div>

                <div class="col-md-5 ps-4">
                    <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary">Sticky ღილაკები (მარჯვნივ)</h5>
                    
                    <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="show_fb" value="1" <?php echo $opt['show_fb']?'checked':''; ?>> <label>Facebook Share</label></div>
                    <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="show_wa" value="1" <?php echo $opt['show_wa']?'checked':''; ?>> <label>WhatsApp</label></div>
                    <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="show_vi" value="1" <?php echo $opt['show_vi']?'checked':''; ?>> <label>Viber</label></div>
                    <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="show_tg" value="1" <?php echo $opt['show_tg']?'checked':''; ?>> <label>Telegram</label></div>
                    <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="show_tw" value="1" <?php echo $opt['show_tw']?'checked':''; ?>> <label>Twitter (X)</label></div>
                    <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="show_li" value="1" <?php echo $opt['show_li']?'checked':''; ?>> <label>LinkedIn</label></div>
                    <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="show_pi" value="1" <?php echo $opt['show_pi']?'checked':''; ?>> <label>Pinterest</label></div>
                    
                    <hr>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="show_count" value="1" <?php echo $opt['show_count']?'checked':''; ?>> 
                        <label class="fw-bold text-danger">ნახვების იმიტაცია (Counter)</label>
                    </div>
                    
                    <h5 class="fw-bold mb-3 mt-4 border-bottom pb-2 text-primary">კონტენტის ბოლოს</h5>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="show_bottom_like" value="1" <?php echo $opt['show_bottom_like']?'checked':''; ?>> 
                        <label class="fw-bold text-success">Facebook Like ღილაკი</label>
                    </div>
                </div>
            </div>
            <div class="text-end mt-4">
                <button type="submit" name="ok_save_social" class="btn btn-primary px-5 fw-bold shadow rounded-pill">პარამეტრების შენახვა</button>
            </div>
        </form>
    </div>
    <?php
}

/* ---------------------------------------------------------
   2. მეტა ტეგების და SDK-ს ჩასმა HEAD-ში
--------------------------------------------------------- */
add_ok_action('ok_head', function() {
    global $post, $ok_query;
    
    // ნაგულისხმევი მონაცემები
    $site_title = get_ok_option('site_title', 'OK CMS');
    $default_img = get_ok_option('og_default_img', '');
    $default_desc = get_ok_option('site_description', '');
    
    // URL-ის გენერაცია
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    $og_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    
    // მიმდინარე ობიექტი (პოსტი ან გვერდი)
    $current_obj = $post ?? ($ok_query['object'] ?? null);
    
    // 1. Title
    $og_title = (!empty($current_obj->post_title)) ? (string)$current_obj->post_title : $site_title;
    
    // 2. Image
    $og_img = (!empty($current_obj->post_image)) ? $current_obj->post_image : $default_img;

    // 3. Description (ავტომატური შეკვეცით)
    $og_desc = $default_desc;
    if (!empty($current_obj->post_content)) {
        // ვასუფთავებთ HTML-სგან და ზედმეტი სიცარიელეებისგან
        $clean_text = strip_tags($current_obj->post_content);
        $clean_text = preg_replace('/\s+/', ' ', $clean_text);
        // ვჭრით 160 სიმბოლოზე
        $og_desc = mb_substr($clean_text, 0, 160, 'UTF-8') . (mb_strlen($clean_text) > 160 ? '...' : '');
    } elseif (!empty($current_obj->post_description)) {
        $og_desc = $current_obj->post_description;
    }

    // --- Meta Tags Output ---
    echo '<meta property="og:type" content="article" />' . "\n";
    echo '<meta property="og:title" content="' . htmlspecialchars((string)$og_title) . '" />' . "\n";
    echo '<meta property="og:description" content="' . htmlspecialchars((string)$og_desc) . '" />' . "\n";
    echo '<meta property="og:url" content="' . htmlspecialchars((string)$og_url) . '" />' . "\n";
    if ($og_img) echo '<meta property="og:image" content="' . htmlspecialchars((string)$og_img) . '" />' . "\n";

    // Facebook Specific
    $fb_id = get_ok_option('fb_app_id', '');
    if (!empty($fb_id)) {
        echo '<meta property="fb:app_id" content="' . htmlspecialchars($fb_id) . '" />' . "\n";
    }

    $verify_tag = get_ok_option('fb_verify_tag', '');
    if (!empty($verify_tag)) {
        echo html_entity_decode($verify_tag) . "\n";
    }
    
    // Facebook SDK Script
    if ($fb_id) {
        echo '<div id="fb-root"></div>
              <script async defer crossorigin="anonymous" src="https://connect.facebook.net/ka_GE/sdk.js#xfbml=1&version=v18.0&appId='.$fb_id.'&autoLogAppEvents=1"></script>' . "\n";
    }

}, 1);

/* ---------------------------------------------------------
   3. Sticky Buttons (მარჯვენა მხარეს)
--------------------------------------------------------- */
function ok_display_social_buttons() {
    $url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $enc_url = urlencode($url);

    echo '<div class="ok-social-sticky-v">';
    
    // Counter
    if(get_ok_option('show_count')) {
        $fake_count = (abs(crc32($url) % 280) + 40);
        echo '<div class="ok-count-v shadow-sm mb-2"><i class="bi bi-graph-up-arrow text-danger"></i><div>'.$fake_count.'</div></div>';
    }

    $nets = [
        'fb' => ['bg' => '#1877f2', 'icon' => 'facebook', 'u' => 'https://www.facebook.com/sharer/sharer.php?u='],
        'wa' => ['bg' => '#25D366', 'icon' => 'whatsapp', 'u' => 'https://api.whatsapp.com/send?text='],
        'vi' => ['bg' => '#7360f2', 'icon' => 'chat-dots-fill', 'u' => 'viber://forward?text='],
        'tg' => ['bg' => '#0088cc', 'icon' => 'telegram', 'u' => 'https://t.me/share/url?url='],
        'tw' => ['bg' => '#000000', 'icon' => 'twitter-x', 'u' => 'https://twitter.com/intent/tweet?url='],
        'li' => ['bg' => '#0077b5', 'icon' => 'linkedin', 'u' => 'https://www.linkedin.com/sharing/share-offsite/?url='],
        'pi' => ['bg' => '#bd081c', 'icon' => 'pinterest', 'u' => 'https://pinterest.com/pin/create/button/?url=']
    ];

    foreach($nets as $key => $net) {
        if(get_ok_option('show_'.$key)) {
            $ico = ($net['icon'] == 'twitter-x') ? 'twitter' : $net['icon'];
            echo '<a href="'.$net['u'].$enc_url.'" target="_blank" class="ok-s-btn-v shadow" style="background:'.$net['bg'].'" title="გაზიარება">
                    <i class="bi bi-'.$ico.'"></i>
                  </a>';
        }
    }
    echo '</div>';
    
    echo '<style>
        .ok-social-sticky-v { position: fixed; right: 20px; bottom: 80px; z-index: 9990; display: flex; flex-direction: column; gap: 10px; }
        .ok-s-btn-v { width:48px; height:48px; display:flex; align-items:center; justify-content:center; border-radius:50%; color:#fff !important; text-decoration:none; font-size:22px; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .ok-s-btn-v:hover { transform: scale(1.15) translateX(-3px); filter: brightness(1.1); box-shadow: 0 5px 15px rgba(0,0,0,0.2) !important; }
        .ok-count-v { background:#fff; border-radius:12px; padding:8px 0; text-align:center; font-weight:900; color:#333; font-size:12px; animation: okFadeIn 1s; }
        @keyframes okFadeIn { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
        @media (max-width: 768px) { .ok-social-sticky-v { right: 10px; bottom: 90px; } .ok-s-btn-v { width:40px; height:40px; font-size:18px; } }
    </style>';
}
add_ok_action('ok_before_content', 'ok_display_social_buttons');

/* ---------------------------------------------------------
   4. Like ღილაკი კონტენტის ბოლოს (After Content)
--------------------------------------------------------- */
function ok_display_bottom_like_button() {
    // თუ გამორთულია ადმინში, არაფერს ვბეჭდავთ
    if (!get_ok_option('show_bottom_like')) return;

    $url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    
    echo '<div class="ok-fb-like-wrapper mt-4 mb-3 p-3 bg-light rounded border d-flex align-items-center justify-content-between shadow-sm">';
    echo '  <span class="fw-bold text-secondary me-3"><i class="bi bi-hand-thumbs-up-fill text-primary"></i> მოგეწონათ სტატია?</span>';
    
    // Facebook Like Button (Share გამორთულია: data-share="false")
    echo '  <div class="fb-like" 
                 data-href="'.$url.'" 
                 data-width="" 
                 data-layout="button_count" 
                 data-action="like" 
                 data-size="large" 
                 data-share="false">
            </div>';
    echo '</div>';
}
add_ok_action('ok_after_content', 'ok_display_bottom_like_button');