<?php
/*
Plugin Name: ნელი ჩატვირთვა v29.0
Description: 30 ანიმაცია, ფერის შეცვლა და ვიზუალური არჩევა ადმინ პანელში.
Version: 29.0
Author: OK Engine Team
*/

if (!defined('OK_LOADED')) exit;

// ------------------------------------------------------------------
// 1. დამხმარე ფუნქციები (HTML და CSS გენერაცია)
// ------------------------------------------------------------------

// აბრუნებს კონკრეტული სტილის HTML სტრუქტურას
function ok_get_loader_html($style) {
    if (in_array($style, ['ok-s1','ok-s2','ok-s3','ok-s4','ok-s5','ok-p1','ok-p2','ok-p3','ok-sq1','ok-sq2','ok-cube','ok-hg'])) {
        return "<div class='$style'></div>";
    }
    elseif (in_array($style, ['ok-d1','ok-d2','ok-d3'])) {
        return "<div class='$style'><span></span><span></span><span></span></div>";
    }
    elseif (in_array($style, ['ok-b1','ok-b2'])) {
        return "<div class='$style'></div>";
    }
    elseif ($style == 'ok-r1') return "<div class='ok-r1'><div></div><div></div><div></div><div></div></div>";
    elseif (in_array($style, ['ok-r2','ok-r3'])) return "<div class='$style'></div>";
    elseif ($style == 'ok-ht') return "<div class='ok-ht'><i class='bi bi-heart-fill'></i></div>";
    elseif ($style == 'ok-wifi') return "<div class='ok-wifi'><i class='bi bi-wifi'></i></div>";
    elseif ($style == 'ok-gear') return "<div class='ok-gear'><i class='bi bi-gear-fill'></i></div>";
    elseif ($style == 'ok-dna') return "<div class='ok-dna'><div></div><div></div><div></div><div></div><div></div></div>";
    elseif ($style == 'ok-atom') return "<div class='ok-atom'><div></div><div></div></div>";
    elseif ($style == 'ok-orbit') return "<div class='ok-orbit'></div>";
    elseif ($style == 'ok-grid') return "<div class='ok-grid'><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>";
    elseif ($style == 'ok-ripple') return "<div class='ok-ripple'><div></div><div></div></div>";
    elseif ($style == 'ok-bounce') return "<div class='ok-bounce'><div></div><div></div><div></div></div>";
    elseif ($style == 'ok-minimal') return "<div class='ok-minimal'>LOADING</div>";
    return "<div class='ok-s1'></div>";
}

// აბრუნებს CSS-ს დინამიური ფერით
function ok_get_loader_css($color_hex) {
    return "
    <style>
        :root { --ok-loader-color: {$color_hex}; }
        
        /* --- KEYFRAMES --- */
        @keyframes ok-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        @keyframes ok-pulse { 0% { transform: scale(0.8); opacity: 0.5; } 100% { transform: scale(1.2); opacity: 1; } }
        @keyframes ok-pulse-fade { 0% { transform: scale(0); opacity: 1; } 100% { transform: scale(1); opacity: 0; } }
        @keyframes ok-dots { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        @keyframes ok-bar { 0% { left: -50%; } 100% { left: 100%; } }
        @keyframes ok-dna { 0%, 100% { transform: scaleY(0.4); } 50% { transform: scaleY(1.2); } }
        @keyframes ok-bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-20px); } }
        @keyframes ok-flip { 0% { transform: perspective(120px) rotateX(0deg) rotateY(0deg); } 50% { transform: perspective(120px) rotateX(-180.1deg) rotateY(0deg); } 100% { transform: perspective(120px) rotateX(-180.1deg) rotateY(-179.9deg); } }
        @keyframes ok-orbit { 0% { transform: rotate(0deg) translateX(25px) rotate(0deg); } 100% { transform: rotate(360deg) translateX(25px) rotate(-360deg); } }
        @keyframes ok-grid { 0%, 70%, 100% { transform: scale3D(1, 1, 1); } 35% { transform: scale3D(0, 0, 1); } }
        @keyframes ok-ripple { 0% { top: 36px; left: 36px; width: 0; height: 0; opacity: 1; } 100% { top: 0px; left: 0px; width: 72px; height: 72px; opacity: 0; } }
        @keyframes ok-ring-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        /* --- STYLES USING VAR --- */
        .ok-s1 { width: 45px; height: 45px; border: 5px solid #f3f3f3; border-top: 5px solid var(--ok-loader-color); border-radius: 50%; animation: ok-spin 1s linear infinite; }
        .ok-s2 { width: 45px; height: 45px; border: 3px solid transparent; border-top: 3px solid var(--ok-loader-color); border-left: 3px solid var(--ok-loader-color); border-radius: 50%; animation: ok-spin 0.8s ease infinite; }
        .ok-s3 { width: 45px; height: 45px; border: 5px double var(--ok-loader-color); border-top: 5px solid transparent; border-radius: 50%; animation: ok-spin 1s linear infinite; }
        .ok-s4 { width: 45px; height: 45px; border-radius: 50%; background: conic-gradient(#0000 10%, var(--ok-loader-color)); -webkit-mask: radial-gradient(farthest-side,#0000 calc(100% - 8px),#000 0); animation: ok-spin 1s infinite linear; }
        .ok-s5 { width: 50px; height: 50px; border-radius: 50%; border: 4px solid var(--ok-loader-color); border-right-color: transparent; animation: ok-spin 0.8s linear infinite; }

        .ok-p1 { width: 50px; height: 50px; background: var(--ok-loader-color); border-radius: 50%; animation: ok-pulse 1s infinite alternate; }
        .ok-p2 { width: 50px; height: 50px; border: 4px solid var(--ok-loader-color); border-radius: 50%; animation: ok-pulse 1.2s infinite; }
        .ok-p3 { width: 50px; height: 50px; background: var(--ok-loader-color); border-radius: 50%; opacity: 0.5; animation: ok-pulse-fade 1.5s infinite; }

        .ok-d1 span, .ok-d2 span, .ok-d3 span { display: inline-block; width: 12px; height: 12px; margin: 0 3px; background: var(--ok-loader-color); border-radius: 50%; }
        .ok-d1 span { animation: ok-dots 0.8s infinite alternate; }
        .ok-d1 span:nth-child(2) { animation-delay: 0.2s; } .ok-d1 span:nth-child(3) { animation-delay: 0.4s; }
        .ok-d2 span { animation: ok-pulse 0.8s infinite alternate; }
        .ok-d2 span:nth-child(2) { animation-delay: 0.2s; } .ok-d2 span:nth-child(3) { animation-delay: 0.4s; }
        .ok-d3 span { border-radius: 2px; animation: ok-spin 1s infinite; }

        .ok-b1 { width: 150px; height: 4px; background: #eee; border-radius: 10px; overflow: hidden; position: relative; }
        .ok-b1::after { content: ''; position: absolute; left: -50%; width: 50%; height: 100%; background: var(--ok-loader-color); animation: ok-bar 1s infinite linear; }
        .ok-b2 { width: 150px; height: 10px; border: 1px solid var(--ok-loader-color); border-radius: 10px; position: relative; overflow: hidden; }
        .ok-b2::after { content: ''; position: absolute; top:0; left:0; width:0; height:100%; background:var(--ok-loader-color); animation: ok-b2-fill 2s infinite ease-in-out; }
        @keyframes ok-b2-fill { 0% { width: 0; } 100% { width: 100%; } }

        .ok-r1 { display: inline-block; position: relative; width: 60px; height: 60px; }
        .ok-r1 div { box-sizing: border-box; display: block; position: absolute; width: 48px; height: 48px; margin: 6px; border: 6px solid var(--ok-loader-color); border-radius: 50%; animation: ok-spin 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite; border-color: var(--ok-loader-color) transparent transparent transparent; }
        .ok-r1 div:nth-child(1) { animation-delay: -0.45s; } .ok-r1 div:nth-child(2) { animation-delay: -0.3s; } .ok-r1 div:nth-child(3) { animation-delay: -0.15s; }
        .ok-r2 { width: 40px; height: 40px; border: 4px dashed var(--ok-loader-color); border-radius: 50%; animation: ok-spin 4s linear infinite; }
        .ok-r3 { width: 40px; height: 40px; position: relative; }
        .ok-r3::before, .ok-r3::after { content: ''; position: absolute; width: 100%; height: 100%; border-radius: 50%; background-color: var(--ok-loader-color); opacity: 0.6; animation: ok-pulse-fade 2s infinite ease-in-out; }
        .ok-r3::after { animation-delay: -1s; }

        .ok-sq1 { width: 40px; height: 40px; background-color: var(--ok-loader-color); animation: ok-flip 1.2s infinite ease-in-out; }
        .ok-sq2 { width: 40px; height: 40px; border: 4px solid var(--ok-loader-color); animation: ok-spin 2s infinite ease; }

        .ok-hg { width: 40px; height: 40px; border: 4px solid #333; border-radius: 50%; position: relative; }
        .ok-hg::after { content: ''; position: absolute; width: 14px; height: 2px; background: #333; top: 50%; left: 50%; transform-origin: 0% 50%; animation: ok-spin 2s linear infinite; }
        .ok-ht { font-size: 40px; color: var(--ok-loader-color); animation: ok-pulse 0.8s infinite alternate; }

        .ok-dna { display: flex; gap: 5px; height: 40px; align-items: center; }
        .ok-dna div { width: 5px; height: 100%; background: var(--ok-loader-color); animation: ok-dna 1s infinite; }
        .ok-dna div:nth-child(2) { animation-delay: 0.1s; } .ok-dna div:nth-child(3) { animation-delay: 0.2s; } .ok-dna div:nth-child(4) { animation-delay: 0.3s; }
        .ok-wifi { font-size: 40px; color: var(--ok-loader-color); animation: ok-pulse 1s infinite; }
        .ok-gear { font-size: 40px; color: #333; animation: ok-spin 4s linear infinite; }
        .ok-atom { width: 60px; height: 60px; position: relative; display: flex; align-items: center; justify-content: center; }
        .ok-atom::before { content: ''; width: 10px; height: 10px; background: var(--ok-loader-color); border-radius: 50%; }
        .ok-atom div { position: absolute; width: 100%; height: 100%; border: 2px solid var(--ok-loader-color); border-radius: 50%; }
        .ok-atom div:nth-child(1) { transform: rotateX(70deg); animation: ok-ring-spin 1s linear infinite; }
        .ok-atom div:nth-child(2) { transform: rotateY(70deg); animation: ok-ring-spin 1.5s linear infinite; }
        .ok-orbit { width: 50px; height: 50px; border-radius: 50%; border: 1px solid #ddd; position: relative; }
        .ok-orbit::before { content: ''; position: absolute; top: 50%; left: 50%; width: 10px; height: 10px; background: var(--ok-loader-color); border-radius: 50%; margin: -5px 0 0 -5px; }
        .ok-orbit::after { content: ''; position: absolute; top: 50%; left: 50%; width: 8px; height: 8px; background: var(--ok-loader-color); border-radius: 50%; margin: -4px 0 0 -4px; animation: ok-orbit 1.5s linear infinite; }

        .ok-cube { width: 40px; height: 40px; background: var(--ok-loader-color); animation: ok-flip 1.2s infinite ease-in-out; }
        
        .ok-grid { display: inline-block; position: relative; width: 60px; height: 60px; }
        .ok-grid div { position: absolute; width: 12px; height: 12px; border-radius: 50%; background: var(--ok-loader-color); animation: ok-grid 1.2s linear infinite; }
        .ok-grid div:nth-child(1) { top: 6px; left: 6px; animation-delay: 0s; } .ok-grid div:nth-child(2) { top: 6px; left: 24px; animation-delay: -0.4s; } .ok-grid div:nth-child(3) { top: 6px; left: 42px; animation-delay: -0.8s; }
        .ok-grid div:nth-child(4) { top: 24px; left: 6px; animation-delay: -0.4s; } .ok-grid div:nth-child(5) { top: 24px; left: 24px; animation-delay: -0.8s; } .ok-grid div:nth-child(6) { top: 24px; left: 42px; animation-delay: -1.2s; }
        .ok-grid div:nth-child(7) { top: 42px; left: 6px; animation-delay: -0.8s; } .ok-grid div:nth-child(8) { top: 42px; left: 24px; animation-delay: -1.2s; } .ok-grid div:nth-child(9) { top: 42px; left: 42px; animation-delay: -1.6s; }

        .ok-ripple { display: inline-block; position: relative; width: 80px; height: 80px; }
        .ok-ripple div { position: absolute; border: 4px solid var(--ok-loader-color); opacity: 1; border-radius: 50%; animation: ok-ripple 1s cubic-bezier(0, 0.2, 0.8, 1) infinite; }
        .ok-ripple div:nth-child(2) { animation-delay: -0.5s; }

        .ok-bounce { display: flex; gap: 5px; }
        .ok-bounce div { width: 15px; height: 15px; background: var(--ok-loader-color); border-radius: 50%; animation: ok-bounce 0.6s infinite alternate; }
        .ok-bounce div:nth-child(2) { animation-delay: 0.1s; } .ok-bounce div:nth-child(3) { animation-delay: 0.2s; }

        .ok-minimal { font-size: 24px; font-weight: bold; animation: ok-pulse 1s infinite; color: var(--ok-loader-color); }
    </style>
    ";
}

// ------------------------------------------------------------------
// 2. მენიუ
// ------------------------------------------------------------------
add_ok_action('admin_menu', function() {
    add_menu_page('ნელი ჩატვირთვა', 'ნელი ჩატვირთვა', 'manage_options', 'ok-lazy-loader', 'ok_lazy_settings_page', 'bi bi-speedometer2', 45);
});

// ------------------------------------------------------------------
// 3. ადმინ გვერდი (განახლებული)
// ------------------------------------------------------------------
function ok_lazy_settings_page() {
    if (isset($_POST['save_lazy'])) {
        update_ok_option('lazy_load_enabled', isset($_POST['lazy_enabled']) ? 1 : 0);
        update_ok_option('lazy_load_style', $_POST['lazy_style']);
        update_ok_option('lazy_load_color', $_POST['lazy_color']); // ფერის შენახვა
        echo '<div class="alert alert-success m-3 shadow border-0">✅ პარამეტრები შენახულია!</div>';
    }
    
    $enabled = get_ok_option('lazy_load_enabled', 1);
    $current = get_ok_option('lazy_load_style', 'ok-s1');
    $color   = get_ok_option('lazy_load_color', '#0d6efd'); // დეფოლტი: ლურჯი

    // CSS-ის ჩატვირთვა ადმინში, რათა პრევიუ გამოჩნდეს
    echo ok_get_loader_css($color);

    $styles = [
        'ok-s1' => 'სპინერი 1', 'ok-s2' => 'სპინერი 2', 'ok-s3' => 'სპინერი 3', 'ok-s4' => 'სპინერი 4', 'ok-s5' => 'სპინერი 5',
        'ok-p1' => 'პულსი 1', 'ok-p2' => 'პულსი 2', 'ok-p3' => 'პულსი 3', 'ok-d1' => 'წერტილები 1', 'ok-d2' => 'წერტილები 2',
        'ok-d3' => 'წერტილები 3', 'ok-b1' => 'ბარი 1', 'ok-b2' => 'ბარი 2', 'ok-r1' => 'რგოლი 1', 'ok-r2' => 'რგოლი 2',
        'ok-r3' => 'რგოლი 3', 'ok-sq1' => 'კვადრატი 1', 'ok-sq2' => 'კვადრატი 2', 'ok-hg' => 'საათი', 'ok-ht' => 'გული',
        'ok-dna' => 'დნმ', 'ok-wifi' => 'ვაიფაი', 'ok-gear' => 'კბილანა', 'ok-atom' => 'ატომი', 'ok-orbit' => 'ორბიტა',
        'ok-cube' => 'კუბი', 'ok-grid' => 'ბადე', 'ok-ripple' => 'ტალღები', 'ok-bounce' => 'ბოუნსი', 'ok-minimal' => 'ტექსტი'
    ];
    ?>
    <style>
        /* ადმინ პანელის სპეციფიკური სტილი პრევიუსთვის */
        .ok-preview-box { height: 60px; display: flex; align-items: center; justify-content: center; overflow: hidden; transform: scale(0.6); }
    </style>

    <div class="p-4 bg-white min-vh-100">
        <h2 class="fw-bold mb-4 border-bottom pb-2">Preloader-ის მართვა (30 სტილი)</h2>
        <form method="post">
            <div class="card p-4 border-0 shadow-sm bg-light mb-4">
                
                <div class="d-flex align-items-center mb-4 gap-5">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="lazy_enabled" id="lazy_enabled" style="width: 2.5em; height: 1.25em;" <?php echo $enabled ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-bold ms-2" for="lazy_enabled">სისტემა ჩართულია</label>
                    </div>
                    <div class="d-flex align-items-center">
                        <label class="fw-bold me-2">ანიმაციის ფერი:</label>
                        <input type="color" name="lazy_color" value="<?php echo $color; ?>" class="form-control form-control-color" title="აირჩიეთ ფერი">
                    </div>
                </div>

                <div class="row g-2 text-center">
                    <?php foreach ($styles as $val => $label): ?>
                    <div class="col-md-2 mb-2">
                        <label class="card h-100 p-2 border <?php echo ($current == $val) ? 'border-primary bg-white shadow' : ''; ?>" style="cursor: pointer; transition: 0.2s;">
                            <div class="ok-preview-box">
                                <?php echo ok_get_loader_html($val); // ლაივ პრევიუ ?>
                            </div>
                            <input type="radio" name="lazy_style" value="<?php echo $val; ?>" <?php echo ($current == $val) ? 'checked' : ''; ?> class="mt-2">
                            <div class="small fw-bold mt-1" style="font-size: 11px;"><?php echo $label; ?></div>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>

                <button name="save_lazy" class="btn btn-primary fw-bold rounded-pill px-5 mt-4 py-2">შენახვა</button>
            </div>
        </form>
    </div>
    <?php
}

// ------------------------------------------------------------------
// 4. FRONT-END
// ------------------------------------------------------------------
if (get_ok_option('lazy_load_enabled', 1)) {
    
    // სტილების ჩატვირთვა (HEAD)
    add_ok_action('ok_head', function() {
        $color = get_ok_option('lazy_load_color', '#0d6efd');
        echo ok_get_loader_css($color); // ფუნქციის გამოძახება
        ?>
        <style>
            /* კონტეინერის დამატებითი სტილები (Fullscreen & Z-index) */
            #ok-page-preloader { 
                position: fixed; 
                top: 0; 
                left: 0; 
                right: 0; 
                bottom: 0;
                width: 100vw; 
                height: 100vh; 
                background: #ffffff; 
                z-index: 99999999999999999999999 !important; 
                display: flex; 
                flex-direction: column; 
                align-items: center; 
                justify-content: center; 
                transition: opacity 0.5s ease; 
                overflow: hidden;
            }
            .ok-pre-hidden { opacity: 0; visibility: hidden; pointer-events: none; }
            .ok-pre-text { margin-top: 25px; font-weight: bold; color: #333; font-family: sans-serif; letter-spacing: 1px; }
        </style>
        <?php
    });

    // HTML-ის ჩატვირთვა (FOOTER)
    add_ok_action('ok_footer', function() {
        $style = get_ok_option('lazy_load_style', 'ok-s1');
        ?>
        <div id="ok-page-preloader">
            <div class="ok-pre-content" style="display:flex; flex-direction:column; align-items:center;">
                <?php echo ok_get_loader_html($style); // ფუნქციის გამოძახება ?>
                
                <?php if ($style !== 'ok-minimal'): ?>
                    <div class="ok-pre-text">იტვირთება...</div>
                <?php endif; ?>
            </div>
        </div>
        
        <script>
            (function() {
                const pre = document.getElementById('ok-page-preloader');
                const hide = () => { if(pre) pre.classList.add('ok-pre-hidden'); };
                window.addEventListener('load', hide);
                setTimeout(hide, 4000); 
            })();
        </script>
        <?php
    });
}