<?php
/*
Plugin Name: OK New Year Pro (Auto-Schedule)
Description: სრული ავტომატიზაცია: თარიღების მითითება, მოქანავე სათამაშოები, თოვლის კონტროლი და მილოცვა.
Version: 12.0
Author: OK Engine
*/

if (!defined('OK_LOADED')) die('Access Denied.');

define('OK_NY_DIR', __DIR__);
define('OK_NY_JSON', OK_NY_DIR . '/data.json');
$folder_name = basename(OK_NY_DIR); 
define('OK_NY_URL', 'ok-content/plugins/' . $folder_name . '/');

// 1. მენიუს რეგისტრაცია
if (function_exists('ok_add_action')) {
    ok_add_action('admin_menu', function() {
        add_menu_page('ახალი წელი', 'ახალი წელი', 'manage_options', 'ok-newyear', 'ok_ny_admin_page', 'bi bi-calendar-event', 90);
    });
}

// 2. ადმინ პანელის გვერდი (განახლებული დიზაინით)
function ok_ny_admin_page() {
    // შენახვის ლოგიკა
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_ny'])) {
        $data = [
            // თარიღები (ავტომატური ჩართვა)
            'date_start'    => $_POST['date_start'],
            'date_end'      => $_POST['date_end'],
            
            // ვიზუალი
            'show_tree'     => isset($_POST['show_tree']) ? 1 : 0,
            'show_garland'  => isset($_POST['show_garland']) ? 1 : 0,
            
            // თოვლი
            'enable_snow'   => isset($_POST['enable_snow']) ? 1 : 0,
            'snow_start'    => (int)$_POST['snow_start'],
            'snow_end'      => (int)$_POST['snow_end'],
            'snow_duration' => (int)$_POST['snow_duration'],
            'snow_chance'   => (int)$_POST['snow_chance'],
            
            'greeting_text' => $_POST['greeting_text']
        ];
        file_put_contents(OK_NY_JSON, json_encode($data, JSON_UNESCAPED_UNICODE));
        echo '<div class="alert alert-success mt-3 mx-3 shadow-sm border-0"><i class="bi bi-check-circle-fill"></i> პარამეტრები შენახულია!</div>';
    }

    // მონაცემების წაკითხვა
    $saved_data = file_exists(OK_NY_JSON) ? json_decode(file_get_contents(OK_NY_JSON), true) : [];
    // Default მნიშვნელობები (მიმდინარე წელი)
    $year = date('Y');
    $next_year = $year + 1;
    $defaults = [
        'date_start' => "$year-12-15", 
        'date_end'   => "$next_year-01-15",
        'show_tree' => 1, 'show_garland' => 1,
        'enable_snow' => 0, 'snow_start' => 18, 'snow_end' => 23, 
        'snow_duration' => 20, 'snow_chance' => 50,
        'greeting_text' => '<h1 class="text-center" style="color:#ffd700; font-size: 5rem;">გილოცავთ!</h1><p class="text-center" style="color:white; font-size: 3rem;">ბედნიერი ახალი წელი!</p>'
    ];
    $data = array_merge($defaults, $saved_data);
    ?>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/ui/trumbowyg.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/trumbowyg.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/plugins/colors/trumbowyg.colors.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/plugins/colors/ui/trumbowyg.colors.min.css">

    <div class="wrap p-4 bg-light" style="min-height: 100vh;">
        
        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
            <div>
                <h2 class="mb-0 text-dark fw-bold"><i class="bi bi-stars text-warning me-2"></i>საახალწლო მართვა</h2>
                <small class="text-muted">ავტომატური განრიგი და ეფექტები</small>
            </div>
            <div>
                <a href="/?ny_test=1" target="_blank" class="btn btn-outline-primary px-3 me-2 rounded-pill fw-bold">
                    <i class="bi bi-play-circle"></i> ვიზუალური ტესტი
                </a>
                <button type="submit" form="nyForm" name="save_ny" class="btn btn-success px-4 shadow-sm rounded-pill fw-bold">
                    <i class="bi bi-save"></i> შენახვა
                </button>
            </div>
        </div>

        <form method="post" id="nyForm">
            <div class="row g-4">
                
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 mb-4 h-100">
                        <div class="card-header bg-white border-bottom py-3">
                            <h5 class="m-0 fw-bold text-primary"><i class="bi bi-calendar-range me-2"></i>აქტიურების პერიოდი</h5>
                        </div>
                        <div class="card-body bg-white">
                            <p class="text-muted small">პლაგინი საიტზე გამოჩნდება მხოლოდ ამ თარიღების შუალედში.</p>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">დაწყება</label>
                                    <input type="date" name="date_start" class="form-control" value="<?php echo $data['date_start']; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">დასრულება</label>
                                    <input type="date" name="date_end" class="form-control" value="<?php echo $data['date_end']; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                     <div class="card shadow-sm border-0 mb-4 h-100">
                        <div class="card-header bg-white border-bottom py-3">
                            <h5 class="m-0 fw-bold text-success"><i class="bi bi-palette me-2"></i>ელემენტები</h5>
                        </div>
                        <div class="card-body bg-white d-flex align-items-center justify-content-around">
                            <div class="form-check form-switch p-3 border rounded text-center" style="min-width: 150px;">
                                <input class="form-check-input float-none mx-auto mb-2" type="checkbox" name="show_garland" id="showGarl" <?php echo $data['show_garland']?'checked':''; ?> style="width: 3em; height: 1.5em;">
                                <label class="form-check-label fw-bold d-block" for="showGarl">გირლიანდა</label>
                                <small class="text-muted" style="font-size: 0.75rem;">+ სათამაშოები</small>
                            </div>
                            
                            <div class="form-check form-switch p-3 border rounded text-center" style="min-width: 150px;">
                                <input class="form-check-input float-none mx-auto mb-2" type="checkbox" name="show_tree" id="showTree" <?php echo $data['show_tree']?'checked':''; ?> style="width: 3em; height: 1.5em;">
                                <label class="form-check-label fw-bold d-block" for="showTree">ნაძვის ხე</label>
                                <small class="text-muted" style="font-size: 0.75rem;">მარცხენა კუთხე</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                            <h5 class="m-0 fw-bold text-info"><i class="bi bi-snow2 me-2"></i>თოვლის ეფექტი</h5>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="enable_snow" id="enableSnow" <?php echo $data['enable_snow']?'checked':''; ?> onchange="toggleSnowSettings()">
                                <label class="form-check-label fw-bold ms-2" for="enableSnow">ჩართვა</label>
                            </div>
                        </div>
                        <div class="card-body bg-white" id="snowSettings" style="display: <?php echo $data['enable_snow'] ? 'block' : 'none'; ?>;">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label text-muted">საათიდან (0-23)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                        <input type="number" name="snow_start" class="form-control" min="0" max="23" value="<?php echo $data['snow_start']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-muted">საათამდე (0-23)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-clock-history"></i></span>
                                        <input type="number" name="snow_end" class="form-control" min="0" max="23" value="<?php echo $data['snow_end']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-muted">ალბათობა (%)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-dice-5"></i></span>
                                        <input type="number" name="snow_chance" class="form-control" min="1" max="100" value="<?php echo $data['snow_chance']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-muted">ხანგრძლივობა (წამი)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-hourglass-split"></i></span>
                                        <input type="number" name="snow_duration" class="form-control" min="5" value="<?php echo $data['snow_duration']; ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="alert alert-light mt-3 mb-0 border small">
                                <i class="bi bi-info-circle me-1"></i> თოვლი წამოვა მითითებულ საათებში, მითითებული ალბათობით (მაგ: 50% ნიშნავს, რომ ყოველ მეორე ჩატვირთვაზე მოთოვს).
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white border-bottom py-3">
                            <h5 class="m-0 fw-bold text-dark"><i class="bi bi-card-text me-2"></i>31 დეკემბრის მისალოცი ტექსტი</h5>
                        </div>
                        <div class="card-body p-0">
                            <textarea name="greeting_text" id="ok_editor" class="form-control" rows="10"><?php echo htmlspecialchars($data['greeting_text']); ?></textarea>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>

    <script>
        function toggleSnowSettings() {
            const checkBox = document.getElementById('enableSnow');
            const settings = document.getElementById('snowSettings');
            if(checkBox.checked) {
                $(settings).slideDown();
            } else {
                $(settings).slideUp();
            }
        }

        $('#ok_editor').trumbowyg({
            btns: [['viewHTML'], ['formatting'], ['strong', 'em'], ['foreColor', 'backColor'], ['justifyLeft', 'justifyCenter', 'justifyRight'], ['fullscreen']],
            removeformatPasted: true
        });
    </script>
    <?php
}

// 3. ფრონტენდი (ლოგიკა + ვიზუალი)
$ny_data = file_exists(OK_NY_JSON) ? json_decode(file_get_contents(OK_NY_JSON), true) : [];

if (function_exists('add_action')) {
    add_action('ok_footer', function() use ($ny_data) { ok_ny_render_frontend($ny_data); });
} elseif (function_exists('ok_add_action')) {
     ok_add_action('ok_footer', function() use ($ny_data) { ok_ny_render_frontend($ny_data); });
}

function ok_ny_render_frontend($data) {
    if (strpos($_SERVER['REQUEST_URI'] ?? '', 'ok-admin') !== false) return;

    $is_test = isset($_GET['ny_test']) && $_GET['ny_test'] == '1';
    
    // --- თარიღის შემოწმება (ავტომატური ჩართვა/გათიშვა) ---
    if (!$is_test) {
        $today = date('Y-m-d');
        $start = $data['date_start'] ?? date('Y-12-15');
        $end   = $data['date_end']   ?? date('Y-01-15');

        // თუ დღევანდელი დღე არ ჯდება შუალედში, არაფერი ჩავტვირთოთ
        if ($today < $start || $today > $end) {
            return;
        }
    }

    // პარამეტრები
    $show_tree = !empty($data['show_tree']);
    $show_garland = !empty($data['show_garland']);
    $enable_snow = !empty($data['enable_snow']);
    
    // თოვლის JS კონფიგი
    $snow_config = [
        'enabled'  => $enable_snow ? true : false,
        'start'    => (int)($data['snow_start'] ?? 18),
        'end'      => (int)($data['snow_end'] ?? 23),
        'chance'   => (int)($data['snow_chance'] ?? 50),
        'duration' => (int)($data['snow_duration'] ?? 20) * 1000
    ];

    $text = $data['greeting_text'] ?? '<h1>გილოცავთ!</h1>';
    $garland_url = OK_NY_URL . "images/garland.png";
    $tree_url    = OK_NY_URL . "images/tree.png";
    ?>
    <style>
        .ny-wrapper { position: fixed; top: 0; left: 0; width: 100%; height: 0; z-index: 99999; pointer-events: none; }
        .ny-garland-img { position: fixed; top: 0; right: 0; width: 400px; max-width: 50vw; height: auto; z-index: 99998; pointer-events: none; }
        
        /* სათამაშოები (დავაბრუნეთ!) */
        .ny-toys-container { position: fixed; top: 0; right: 0; z-index: 99999; pointer-events: none; }
        .ny-toy { position: absolute; width: 40px; height: 40px; border-radius: 50%; transform-origin: top center; animation: nySwing 3s ease-in-out infinite alternate; }
        /* ძაფი */
        .ny-toy::before { content: ''; position: absolute; top: -100px; left: 50%; width: 1px; height: 100px; background: rgba(255,255,255,0.6); }
        
        .ny-toy-1 { right: 280px; top: 85px; background: radial-gradient(circle at 30% 30%, #ff4d4d, #990000); box-shadow: 0 0 10px #ff0000; }
        .ny-toy-2 { right: 180px; top: 120px; background: radial-gradient(circle at 30% 30%, #ffd700, #b8860b); box-shadow: 0 0 10px #ffd700; width: 50px; height: 50px; animation-delay: 0.5s; }
        .ny-toy-3 { right: 80px; top: 70px; background: radial-gradient(circle at 30% 30%, #00d2ff, #0056b3); box-shadow: 0 0 10px #00d2ff; width: 35px; height: 35px; animation-delay: 1s; }
        
        @keyframes nySwing { from { transform: rotate(-8deg); } to { transform: rotate(8deg); } }

        .ny-tree-img { position: fixed; bottom: 0; left: 0; width: 200px; height: auto; z-index: 99997; pointer-events: none; transition: all 2s; }
        .ny-tree-img.ny-active { width: 600px; max-width: 90vw; left: -150px; bottom: -30px; z-index: 100003; filter: drop-shadow(0 0 20px rgba(0,0,0,0.5)); }
        
        .ny-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15,32,39,0.95); z-index: 100001; display: flex; justify-content: center; align-items: center; opacity: 0; visibility: hidden; transition: opacity 1s; pointer-events: auto; }
        .ny-overlay.active { opacity: 1; visibility: visible; }
        .ny-card { text-align: center; color: white; animation: popIn 1s; }
        
        .ny-snowflake-global { position: fixed; top: -10px; color: #fff; pointer-events: none; z-index: 99999; animation: nyFall linear infinite; }
        @keyframes nyFall { to { transform: translateY(105vh); } }
        @keyframes popIn { from { transform: scale(0.5); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        @media (max-width: 768px) { .ny-garland-img { width: 250px; } .ny-tree-img { width: 120px; } .ny-toy-1 { right: 160px; top: 60px; } .ny-toy-2 { right: 100px; top: 80px; } .ny-toy-3 { right: 40px; top: 50px; } }
    </style>

    <div class="ny-wrapper">
        <?php if($show_garland): ?>
            <img src="<?php echo $garland_url; ?>" class="ny-garland-img" alt="Garland">
            <div class="ny-toys-container">
                <div class="ny-toy ny-toy-1"></div>
                <div class="ny-toy ny-toy-2"></div>
                <div class="ny-toy ny-toy-3"></div>
            </div>
        <?php endif; ?>

        <?php if($show_tree): ?>
            <img src="<?php echo $tree_url; ?>" class="ny-tree-img" id="nyTree" alt="Christmas Tree">
        <?php endif; ?>

        <div class="ny-overlay" id="nyOverlay"><div class="ny-card"><?php echo $text; ?></div></div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const snowConfig = <?php echo json_encode($snow_config); ?>;
        const isTest = window.location.search.includes('ny_test=1');

        // --- თოვლის ლოგიკა ---
        function tryStartSnow() {
            if (!snowConfig.enabled && !isTest) return;
            
            // ტესტის დროს სულ მოდის
            if (isTest) { startGlobalSnow(); return; }

            const now = new Date();
            const currentHour = now.getHours();
            let isTime = false;
            
            if (snowConfig.start <= snowConfig.end) {
                isTime = (currentHour >= snowConfig.start && currentHour < snowConfig.end);
            } else {
                isTime = (currentHour >= snowConfig.start || currentHour < snowConfig.end);
            }

            if (!isTime) return;

            const randomVal = Math.random() * 100;
            if (randomVal <= snowConfig.chance) startGlobalSnow();
        }

        let snowInterval;
        function startGlobalSnow() {
            snowInterval = setInterval(() => { createSnowflake(document.body); }, 300);
            setTimeout(() => { clearInterval(snowInterval); }, snowConfig.duration); 
        }

        function createSnowflake(container) {
            let f = document.createElement('div');
            f.className = 'ny-snowflake-global';
            f.innerHTML = '❄';
            f.style.left = Math.random() * 100 + 'vw';
            let size = Math.random() * 15 + 10;
            f.style.fontSize = size + 'px';
            f.style.animationDuration = (Math.random() * 3 + 3) + 's';
            f.style.opacity = Math.random();
            container.appendChild(f);
            setTimeout(() => f.remove(), 6000);
        }

        tryStartSnow();

        // --- 31 დეკემბერი ---
        function checkTime() {
            const now = new Date();
            if (now.getMonth() === 11 && now.getDate() === 31 && now.getHours() === 23 && now.getMinutes() === 59 && now.getSeconds() === 59) showGreeting();
        }
        setInterval(checkTime, 1000);

        function showGreeting() {
            const overlay = document.getElementById('nyOverlay');
            const tree = document.getElementById('nyTree');
            if(!overlay) return;
            
            overlay.classList.add('active');
            if(tree) tree.classList.add('ny-active');
            
            let intenseSnow = setInterval(() => createSnowflake(overlay), 100);
            setTimeout(() => { 
                overlay.classList.remove('active'); 
                if(tree) tree.classList.remove('ny-active'); 
                clearInterval(intenseSnow);
            }, 30000);
        }

        if (isTest) setTimeout(showGreeting, 500);
    });
    </script>
    <?php
}