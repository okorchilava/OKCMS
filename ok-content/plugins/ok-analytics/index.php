<?php
/*
Plugin Name: OK Analytics Masterpiece v7.5 (Deep Acquisition)
Description: ანალიტიკა: შემოსვლის წერტილების ზუსტი აღრიცხვა URL პარამეტრებით (FB, Google, Instagram), წარმადობა და ივენთები.
Version: 7.5.0
Author: OK Engine Team
*/

if (!defined('OK_LOADED')) exit;

/**
 * 1. მონაცემთა ბაზის სტრუქტურა
 */
function ok_analytics_master_install() {
    global $ok_db;
    
    $ok_db->query("CREATE TABLE IF NOT EXISTS ok_analytics_master (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        session_id VARCHAR(64),
        visitor_hash VARCHAR(64),
        ip_address VARCHAR(45),
        page_url TEXT,
        referer TEXT,
        source_platform VARCHAR(50) DEFAULT 'პირდაპირი',
        load_time FLOAT DEFAULT 0,
        device ENUM('desktop', 'mobile', 'tablet'),
        os VARCHAR(30),
        browser VARCHAR(30),
        visit_date DATE,
        visit_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (visit_date), INDEX (session_id), INDEX (visitor_hash), INDEX (source_platform)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $ok_db->query("CREATE TABLE IF NOT EXISTS ok_analytics_events_master (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        session_id VARCHAR(64),
        event_category VARCHAR(50),
        event_action VARCHAR(50),
        event_label TEXT,
        event_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

/**
 * 2. შემოსვლის წყაროს ზუსტი დეტექტორი (URL პარამეტრების მიხედვით)
 */
function ok_get_traffic_source_info() {
    // ვამოწმებთ როგორც REQUEST_URI-ს, ისე REFERER-ს
    $full_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    
    // Facebook: fbclid პარამეტრი ან facebook referer
    if (strpos($full_url, 'fbclid=') !== false || strpos($referer, 'facebook.com') !== false || strpos($referer, 'fb.com') !== false) {
        return 'Facebook';
    } 
    // Google: gclid (Ads) პარამეტრი ან google referer (Organic)
    elseif (strpos($full_url, 'gclid=') !== false || strpos($referer, 'google.') !== false) {
        return 'Google';
    } 
    // Instagram: utm_source ან instagram referer
    elseif (strpos($full_url, 'utm_source=instagram') !== false || strpos($referer, 'instagram.com') !== false) {
        return 'Instagram';
    }
    // TikTok: ttclid ან utm_source
    elseif (strpos($full_url, 'ttclid=') !== false || strpos($full_url, 'utm_source=tiktok') !== false || strpos($referer, 'tiktok.com') !== false) {
        return 'TikTok';
    }
    // სხვა გარე საიტები
    elseif (!empty($referer) && strpos($referer, $_SERVER['HTTP_HOST']) === false) {
        $parsed = parse_url($referer);
        return $parsed['host'] ?? 'სხვა საიტი';
    }
    
    return 'პირდაპირი';
}

/**
 * 3. სისტემური ინფორმაციის დეტექტორი
 */
function ok_get_master_sys_info($ua) {
    $d = 'desktop';
    if (preg_match('/mobile|iphone|android|phone/i', $ua)) $d = 'mobile';
    elseif (preg_match('/ipad|tablet/i', $ua)) $d = 'tablet';
    
    $os = 'სხვა';
    if (preg_match('/windows/i', $ua)) $os = 'Windows';
    elseif (preg_match('/mac/i', $ua)) $os = 'Mac OS';
    elseif (preg_match('/android/i', $ua)) $os = 'Android';
    elseif (preg_match('/iphone|ipad/i', $ua)) $os = 'iOS';

    $br = 'სხვა';
    if (preg_match('/chrome/i', $ua)) $br = 'Chrome';
    elseif (preg_match('/firefox/i', $ua)) $br = 'Firefox';
    elseif (preg_match('/safari/i', $ua)) $br = 'Safari';

    return ['device' => $d, 'os' => $os, 'browser' => $br];
}

/**
 * 4. ტრეკერი
 */
add_ok_action('init', function() {
    global $ok_db;
    if (strpos($_SERVER['REQUEST_URI'] ?? '', 'admin') !== false) return;

    $v_hash = hash('sha256', $_SERVER['REMOTE_ADDR'] . ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    if (!isset($_SESSION['ok_session_id'])) {
        $_SESSION['ok_session_id'] = hash('md5', uniqid($v_hash, true));
    }

    $source = ok_get_traffic_source_info();
    $load_time = isset($_POST['load_time']) ? floatval($_POST['load_time']) : 0;
    $info = ok_get_master_sys_info($_SERVER['HTTP_USER_AGENT'] ?? '');

    $ok_db->query("INSERT INTO ok_analytics_master (session_id, visitor_hash, ip_address, page_url, referer, source_platform, load_time, device, os, browser, visit_date) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", 
                   [$_SESSION['ok_session_id'], $v_hash, $_SERVER['REMOTE_ADDR'], $_SERVER['REQUEST_URI'], $_SERVER['HTTP_REFERER'] ?? 'პირდაპირი', $source, $load_time, $info['device'], $info['os'], $info['browser'], date('Y-m-d')]);
});

// Event API Receiver
add_ok_action('init', function() {
    if (isset($_GET['ok_track_event_master'])) {
        global $ok_db;
        $ok_db->query("INSERT INTO ok_analytics_events_master (session_id, event_category, event_action, event_label) VALUES (?, ?, ?, ?)", 
        [$_SESSION['ok_session_id'] ?? 'უცნობი', $_POST['cat'] ?? 'ზოგადი', $_POST['act'] ?? 'კლიკი', $_POST['lab'] ?? 'არ არის']);
        exit;
    }
});

/**
 * 5. ადმინ პანელი
 */
add_ok_action('admin_menu', function() {
    ok_analytics_master_install();
    add_menu_page('ანალიტიკა', 'ანალიტიკა', 'manage_options', 'ok-analytics', 'ok_analytics_master_gui', 'bi bi-reception-4', 37);
});

function ok_analytics_master_gui() {
    global $ok_db;
    $range = isset($_GET['range']) ? (int)$_GET['range'] : 7;
    $today = date('Y-m-d');

    $online_now = $ok_db->get_var("SELECT COUNT(DISTINCT session_id) FROM ok_analytics_master WHERE visit_time > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
    $avg_speed = $ok_db->get_var("SELECT AVG(load_time) FROM ok_analytics_master WHERE visit_date = ? AND load_time > 0", [$today]);
    
    $uniques = $ok_db->get_var("SELECT COUNT(DISTINCT visitor_hash) FROM ok_analytics_master WHERE visit_date > DATE_SUB(NOW(), INTERVAL ? DAY)", [$range]);
    $returning = $ok_db->get_var("SELECT COUNT(*) FROM (SELECT visitor_hash FROM ok_analytics_master GROUP BY visitor_hash HAVING COUNT(DISTINCT visit_date) > 1) as t");
    $retention = $uniques > 0 ? round(($returning / $uniques) * 100, 1) : 0;

    // შემოსვლის წყაროების რეიტინგი
    $sources = $ok_db->get_results("SELECT source_platform, COUNT(DISTINCT session_id) as cnt FROM ok_analytics_master WHERE visit_date > DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY source_platform ORDER BY cnt DESC", [$range], PDO::FETCH_ASSOC);

    $chart_res = $ok_db->get_results("SELECT visit_date, COUNT(DISTINCT visitor_hash) as u, COUNT(id) as h FROM ok_analytics_master GROUP BY visit_date ORDER BY visit_date DESC LIMIT ?", [$range], PDO::FETCH_ASSOC);
    $chart_res = array_reverse($chart_res);
    ?>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <div class="p-4 bg-white min-vh-100" style="font-family: 'Inter', 'Noto Sans Georgian', sans-serif;">
        
        <div class="d-flex justify-content-between align-items-center mb-5 pb-3 border-bottom">
            <div>
                <h2 class="fw-black mb-0">OK ინტელექტი <span class="text-primary">Masterpiece v7.5</span></h2>
                <div class="text-success small fw-bold mt-1"><span class="spinner-grow spinner-grow-sm me-1"></span> <?php echo $online_now; ?> ონლაინ რეჟიმში</div>
            </div>
            <div class="btn-group shadow-sm">
                <a href="?page=ok-analytics&range=1" class="btn btn-light border <?php echo $range==1?'active':''; ?>">დღეს</a>
                <a href="?page=ok-analytics&range=7" class="btn btn-light border <?php echo $range==7?'active':''; ?>">7 დღე</a>
                <a href="?page=ok-analytics&range=30" class="btn btn-light border <?php echo $range==30?'active':''; ?>">30 დღე</a>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="p-4 rounded-4 shadow-sm bg-primary text-white h-100">
                    <div class="small opacity-75 fw-bold text-uppercase mb-1">უნიკალური ვიზიტორი</div>
                    <div class="h1 fw-black mb-0"><?php echo number_format($uniques); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-4 rounded-4 shadow-sm bg-white border h-100">
                    <div class="small text-muted fw-bold text-uppercase mb-2">მთავარი წყარო</div>
                    <?php if(!empty($sources)): ?>
                        <div class="h2 fw-black mb-0"><?php echo $sources[0]['source_platform']; ?></div>
                        <div class="small text-success fw-bold"><?php echo $sources[0]['cnt']; ?> ვიზიტი</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-4 rounded-4 shadow-sm bg-white border h-100">
                    <div class="small text-muted fw-bold text-uppercase mb-1">ჩატვირთვის სიჩქარე</div>
                    <div class="h2 fw-black mb-0 text-<?php echo $avg_speed > 3 ? 'danger' : 'success'; ?>"><?php echo round($avg_speed ?: 0, 2); ?>წმ</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-4 rounded-4 shadow-sm bg-dark text-white text-center h-100">
                    <div class="small opacity-75 fw-bold text-uppercase mb-2">მოწყობილობები</div>
                    <div class="d-flex justify-content-around">
                        <?php 
                        $devs = $ok_db->get_results("SELECT device, COUNT(*) as c FROM ok_analytics_master GROUP BY device", [], PDO::FETCH_ASSOC);
                        foreach($devs as $d): 
                            $icon = ($d['device'] == 'mobile' ? 'bi-phone' : ($d['device'] == 'tablet' ? 'bi-tablet' : 'bi-display')); 
                        ?>
                            <div title="<?php echo $d['device']; ?>"><i class="bi <?php echo $icon; ?> h4"></i><div class="small fw-bold"><?php echo $d['c']; ?></div></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="row g-4 mb-5">
            <div class="col-lg-12">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="p-4 bg-light border-bottom fw-bold"><i class="bi bi-box-arrow-in-right me-2"></i>შემოსვლის წერტილების რეიტინგი (URL პარამეტრებით)</div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-white small">
                                <tr>
                                    <th class="ps-4">წყარო / პლატფორმა</th>
                                    <th class="text-center">წილი ტრაფიკში</th>
                                    <th class="text-end pe-4">რაოდენობა</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($sources as $source): 
                                    $percent = ($uniques > 0) ? round(($source['cnt'] / $uniques) * 100, 1) : 0;
                                    $s_name = $source['source_platform'];
                                    $icon = "bi-globe";
                                    if($s_name == 'Facebook') $icon = "bi-facebook text-primary";
                                    elseif($s_name == 'Google') $icon = "bi-google text-danger";
                                    elseif($s_name == 'Instagram') $icon = "bi-instagram text-danger";
                                    elseif($s_name == 'TikTok') $icon = "bi-tiktok text-dark";
                                    elseif($s_name == 'პირდაპირი') $icon = "bi-lightning-fill text-warning";
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <i class="bi <?php echo $icon; ?> me-2"></i><strong><?php echo $s_name; ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <div class="progress" style="height: 6px; width: 120px; margin: 0 auto;">
                                            <div class="progress-bar bg-primary" style="width: <?php echo $percent; ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?php echo $percent; ?>%</small>
                                    </td>
                                    <td class="text-end pe-4 fw-bold text-dark"><?php echo number_format($source['cnt']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h6 class="fw-bold mb-4">ტრაფიკის დინამიკა (უნიკალურები vs ნახვები)</h6>
                    <canvas id="masterLineChart" style="height: 300px;"></canvas>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100 text-center">
                    <h6 class="fw-bold mb-4">საიტის მდგომარეობა (Radar)</h6>
                    <canvas id="healthRadarChart"></canvas>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="p-4 bg-white border-bottom fw-bold">პოპულარული კონტენტი</div>
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small"><tr><th class="ps-4">გვერდის URL</th><th class="text-end pe-4">ნახვები</th></tr></thead>
                        <tbody>
                            <?php 
                            $top_pages = $ok_db->get_results("SELECT page_url, COUNT(*) as cnt FROM ok_analytics_master GROUP BY page_url ORDER BY cnt DESC LIMIT 5", [], PDO::FETCH_ASSOC);
                            foreach($top_pages as $p): ?>
                            <tr>
                                <td class="ps-4 small fw-medium">
                                    <a href="<?php echo htmlspecialchars($p['page_url']); ?>" target="_blank" class="text-decoration-none text-primary">
                                        <?php echo htmlspecialchars($p['page_url']); ?>
                                    </a>
                                </td>
                                <td class="text-end pe-4 fw-bold"><?php echo $p['cnt']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="p-4 bg-white border-bottom fw-bold">ბოლო მოქმედებები (Live API)</div>
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small"><tr><th class="ps-4">ქმედება</th><th class="text-end pe-4">ნიშნული</th></tr></thead>
                        <tbody>
                            <?php 
                            $evs = $ok_db->get_results("SELECT * FROM ok_analytics_events_master ORDER BY id DESC LIMIT 5", [], PDO::FETCH_ASSOC);
                            foreach($evs as $e): ?>
                            <tr><td class="ps-4 small fw-bold text-dark"><?php echo $e['event_action']; ?></td><td class="text-end pe-4 small text-muted"><?php echo $e['event_label']; ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
    new Chart(document.getElementById('masterLineChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($chart_res, 'visit_date')); ?>,
            datasets: [
                { label: 'ნახვები', data: <?php echo json_encode(array_column($chart_res, 'h')); ?>, borderColor: '#eee', borderDash: [5, 5], fill: false, tension: 0.3 },
                { label: 'უნიკალური', data: <?php echo json_encode(array_column($chart_res, 'u')); ?>, borderColor: '#0d6efd', backgroundColor: 'rgba(13, 110, 253, 0.05)', fill: true, tension: 0.4, borderWidth: 4 }
            ]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { display: false } }, x: { grid: { display: false } } } }
    });

    new Chart(document.getElementById('healthRadarChart'), {
        type: 'radar',
        data: {
            labels: ['დაბრუნება', 'სიჩქარე', 'პირდაპირი', 'სოც. ქსელი', 'კომპიუტერი'],
            datasets: [{
                data: [<?php echo $retention; ?>, 85, 70, 60, 90],
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                borderColor: '#0d6efd',
                borderWidth: 2
            }]
        },
        options: { plugins: { legend: { display: false } }, scales: { r: { suggestMin: 0, suggestMax: 100, ticks: { display: false } } } }
    });
    </script>
    <style>
        .fw-black { font-weight: 900; }
        .rounded-4 { border-radius: 1.25rem !important; }
        .active { background-color: #0d6efd !important; color: #fff !important; }
    </style>
    <?php
}

add_ok_action('ok_footer', function() {
    ?>
    <script>
    window.addEventListener('load', () => {
        const perf = window.performance.timing;
        const loadTime = (perf.loadEventEnd - perf.navigationStart) / 1000;
        if (loadTime > 0) navigator.sendBeacon(window.location.href, new URLSearchParams({load_time: loadTime}));
    });

    window.okAnalytics = {
        log: (cat, act, lab) => {
            fetch('?ok_track_event_master=1', {
                method: 'POST',
                body: new URLSearchParams({cat, act, lab})
            });
        }
    };
    </script>
    <?php
});