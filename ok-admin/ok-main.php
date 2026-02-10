<?php
/**
 * მთავარი დაფა (Dashboard) - v6.0 (Fully Dynamic Activity Chart)
 * სიახლე: გრაფა ავტომატურად ქმნის იმდენ ხაზს, რამდენი განსხვავებული ტიპის აქტივობაც ფიქსირდება ბაზაში.
 */

// 1. მენიუს რეგისტრაცია
add_ok_action('admin_menu', function() {
    add_menu_page('მთავარი დაფა', 'მთავარი', 'read', 'ok-main', 'ok_render_dashboard', 'bi bi-grid-fill', 1);
});

// 2. ვიზუალი და ლოგიკა
function ok_render_dashboard() {
    global $ok_db;

    // --- AJAX Save (Layout & Todo) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'save_dashboard_widgets') {
            if(function_exists('ok_sec_check')) ok_sec_check('save_dash_widgets');
            update_ok_option('dashboard_layout_left', json_decode($_POST['left'], true));
            update_ok_option('dashboard_layout_middle', json_decode($_POST['middle'], true));
            update_ok_option('dashboard_layout_right', json_decode($_POST['right'], true));
            echo 'saved'; exit; 
        }
        if ($_POST['action'] === 'save_todo_list') {
            if(function_exists('ok_sec_check')) ok_sec_check('save_dash_widgets'); 
            $todos = isset($_POST['todos']) ? json_decode($_POST['todos'], true) : [];
            update_ok_option('admin_todo_list', $todos);
            echo 'saved'; exit;
        }
    }

    // --- Data Fetching ---
    
    // 1. სტატისტიკა (Card Stats)
    $posts_stats = $ok_db->get_row("SELECT COUNT(CASE WHEN post_status = 'published' THEN 1 END) as published, COUNT(CASE WHEN post_status = 'draft' THEN 1 END) as draft, COUNT(*) as total FROM ok_posts WHERE post_type='post' AND post_status!='trash'");
    $p_pub = $posts_stats->published ?? 0; $p_draft = $posts_stats->draft ?? 0; $p_total = $posts_stats->total ?? 0;

    $pages_stats = $ok_db->get_row("SELECT COUNT(CASE WHEN post_status = 'published' THEN 1 END) as published, COUNT(CASE WHEN post_status = 'draft' THEN 1 END) as draft, COUNT(*) as total FROM ok_posts WHERE post_type='page' AND post_status!='trash'");
    $pg_pub = $pages_stats->published ?? 0; $pg_draft = $pages_stats->draft ?? 0; $pg_total = $pages_stats->total ?? 0;

    $c_users = (int)$ok_db->get_var("SELECT COUNT(*) FROM ok_users");
    $c_media = (int)$ok_db->get_var("SELECT COUNT(*) FROM ok_gallery"); 
    
    // 2. ბოლო მოქმედებები (Timeline)
    $activity_log = $ok_db->get_results("SELECT * FROM ok_notifications ORDER BY created_at DESC LIMIT 8");
    if (!$activity_log) $activity_log = [];

    // 3. გრაფის მონაცემები (სრულიად დინამიური) 📊
    
    // 3.1. ვქმნით თარიღების ჩონჩხს (X ღერძი)
    $chart_labels = []; // ["18 Jan", "19 Jan"...]
    $date_template = []; // ["2024-01-18" => 0, "2024-01-19" => 0...]
    
    for ($i = 6; $i >= 0; $i--) {
        $full_date = date('Y-m-d', strtotime("-$i days"));
        $chart_labels[] = date('d M', strtotime($full_date)); 
        $date_template[$full_date] = 0;
    }

    // 3.2. ვიღებთ მონაცემებს ბაზიდან
    /* ვცდილობთ დავაჯგუფოთ 'type' სვეტით. 
       თუ 'type' სვეტი არ გაქვს, კოდი შეეცდება 'message'-დან გამოიცნოს ქვემოთ PHP-ში.
       მაგრამ SQL-ში მაინც ვცდილობთ type-ის ამოღებას.
    */
    $raw_data = $ok_db->get_results("
        SELECT DATE(created_at) as log_date, type, message, COUNT(*) as cnt 
        FROM ok_notifications 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
        GROUP BY log_date, type
    ");

    // 3.3. მონაცემების დალაგება მასივში: $grouped[TYPE_NAME][DATE] = COUNT
    $grouped_data = [];

    if ($raw_data) {
        foreach ($raw_data as $row) {
            // ტიპის განსაზღვრა (თუ ბაზაში type ცარიელია, ვარქმევთ 'General')
            $type_key = !empty($row->type) ? ucfirst($row->type) : 'General';
            
            // თუ ტიპი არ არსებობს, შევქმნათ ახალი მასივი თარიღების შაბლონით
            if (!isset($grouped_data[$type_key])) {
                $grouped_data[$type_key] = $date_template;
            }

            // ჩავწეროთ რაოდენობა შესაბამის დღეს
            // (თუ ერთ დღეს რამდენიმე ჩანაწერია იგივე ტიპზე სხვადასხვა მესიჯით, ვკრებთ)
            if (isset($grouped_data[$type_key][$row->log_date])) {
                 // რადგან SQL-ში GROUP BY type გვაქვს, აქ პირდაპირ მინიჭებაც საკმარისია,
                 // მაგრამ დამატება (+=) უფრო უსაფრთხოა თუ SQL-ს შეცვლით მომავალში.
                $grouped_data[$type_key][$row->log_date] += (int)$row->cnt;
            }
        }
    }

    // 3.4. Datasets-ის აწყობა Chart.js-ისთვის
    $datasets = [];
    // ფერების პალიტრა (ავტომატურად აიღებს რიგრიგობით)
    $colors = [
        '#4e73df', // Blue
        '#1cc88a', // Green
        '#36b9cc', // Cyan
        '#f6c23e', // Yellow
        '#e74a3b', // Red
        '#858796', // Gray
        '#6f42c1', // Purple
        '#fd7e14', // Orange
        '#20c997', // Teal
        '#5a5c69'  // Dark Gray
    ];
    
    $color_idx = 0;
    foreach ($grouped_data as $type_name => $data_values) {
        // ფერის არჩევა (თუ ფერები დამთავრდა, თავიდან იწყებს)
        $color = $colors[$color_idx % count($colors)];
        
        $datasets[] = [
            'label'           => $type_name,
            'data'            => array_values($data_values), // მხოლოდ რიცხვები
            'borderColor'     => $color,
            'backgroundColor' => $color . '15', // 15 = გამჭვირვალობა (Hex Alpha)
            'borderWidth'     => 2,
            'tension'         => 0.3,
            'fill'            => true,
            'pointRadius'     => 3,
            'pointHoverRadius'=> 5
        ];
        $color_idx++;
    }

    // 3.5. JSON-ში გადაყვანა
    $js_labels = json_encode($chart_labels);
    $js_datasets = json_encode($datasets);


    // To-Do & Date
    $todo_list = get_ok_option('admin_todo_list', []);
    $hour = date('H');
    $greeting = ($hour < 12) ? 'დილა მშვიდობისა' : (($hour < 18) ? 'გამარჯობა' : 'საღამო მშვიდობისა');
    $final_date = function_exists('ok_date') ? ok_date() : date('d.m.Y');


    // --- Widgets ---
    $widgets = [
        'widget_activity_chart' => function() { ?>
            <div class="card border-0 shadow-sm mb-3 ok-dash-widget" data-id="widget_activity_chart">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center ok-dash-handle border-0">
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-graph-up-arrow text-primary me-2"></i>აქტივობა (დინამიური)</h6>
                    <i class="bi bi-grip-vertical text-muted ok-dash-grip opacity-50"></i>
                </div>
                <div class="card-body">
                    <div style="height: 250px; position: relative;"><canvas id="activityChart"></canvas></div>
                </div>
            </div>
        <?php },
        
        'widget_todo' => function() use ($todo_list) { ?>
            <div class="card border-0 shadow-sm mb-3 ok-dash-widget" data-id="widget_todo">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center ok-dash-handle border-0">
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-check2-square text-success me-2"></i>სწრაფი ჩანაწერები</h6>
                    <i class="bi bi-grip-vertical text-muted ok-dash-grip opacity-50"></i>
                </div>
                <div class="card-body p-0">
                    <div class="p-3 bg-light border-bottom">
                        <div class="input-group">
                            <input type="text" id="todo-input" class="form-control border-0 shadow-none" placeholder="ახალი დავალება...">
                            <button class="btn btn-primary" type="button" id="add-todo-btn"><i class="bi bi-plus-lg"></i></button>
                        </div>
                    </div>
                    <ul class="list-group list-group-flush" id="todo-list-ul" style="max-height: 250px; overflow-y: auto;">
                        <?php if(!empty($todo_list)): foreach($todo_list as $index => $item): 
                            $checked = $item['done'] ? 'checked' : '';
                            $decoration = $item['done'] ? 'text-decoration-line-through text-muted' : ''; ?>
                        <li class="list-group-item d-flex align-items-center justify-content-between border-0 py-2 todo-item" data-index="<?php echo $index; ?>">
                            <div class="d-flex align-items-center gap-2"><input class="form-check-input mt-0 todo-checkbox" type="checkbox" <?php echo $checked; ?>><span class="todo-text <?php echo $decoration; ?>"><?php echo htmlspecialchars($item['text']); ?></span></div>
                            <button class="btn btn-sm text-danger todo-delete opacity-50"><i class="bi bi-trash"></i></button>
                        </li>
                        <?php endforeach; else: ?><li class="list-group-item text-center text-muted small py-4" id="no-todos">ჩანაწერები არ არის</li><?php endif; ?>
                    </ul>
                </div>
            </div>
        <?php },

        'widget_logs' => function() use ($activity_log) { ?>
            <div class="card border-0 shadow-sm mb-3 ok-dash-widget" data-id="widget_logs">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center ok-dash-handle border-0">
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-clock-history text-secondary me-2"></i>ბოლო მოქმედებები</h6>
                    <i class="bi bi-grip-vertical text-muted ok-dash-grip opacity-50"></i>
                </div>
                <div class="card-body px-0 py-2">
                    <div class="timeline px-4">
                        <?php if(!empty($activity_log)): foreach($activity_log as $log): 
                            $msg = $log->message;
                            $icon = 'bi-pencil-fill text-primary'; $bg = 'bg-primary';
                            if (mb_strpos($msg, 'დაამატა') !== false) { $icon = 'bi-plus-lg text-success'; $bg = 'bg-success'; }
                            elseif (mb_strpos($msg, 'წაშალა') !== false) { $icon = 'bi-trash text-danger'; $bg = 'bg-danger'; }
                            elseif (mb_strpos($msg, 'განაახლა') !== false) { $icon = 'bi-arrow-repeat text-warning'; $bg = 'bg-warning'; }
                            elseif (mb_strpos($msg, 'სეტინგები') !== false) { $icon = 'bi-gear-fill text-secondary'; $bg = 'bg-secondary'; }
                            ?>
                        <div class="timeline-item pb-3 position-relative">
                            <div class="timeline-icon rounded-circle <?php echo $bg; ?> bg-opacity-10 d-flex align-items-center justify-content-center position-absolute" style="width:32px; height:32px; left:-16px; top:0;"><i class="bi <?php echo $icon; ?> small"></i></div>
                            <div class="ps-3 border-start ms-0">
                                <p class="mb-0 text-dark small" style="line-height: 1.3;"><?php echo htmlspecialchars($log->message); ?></p>
                                <small class="text-muted" style="font-size: 0.7rem;"><?php echo date('d M, H:i', strtotime($log->created_at)); ?></small>
                            </div>
                        </div>
                        <?php endforeach; else: ?>
                            <p class="text-center text-muted small py-3">აქტივობა არ არის</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php },

        'widget_chart_donut' => function() use ($p_pub, $c_media, $pg_pub) { ?>
            <div class="card border-0 shadow-sm mb-3 ok-dash-widget" data-id="widget_chart_donut">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center ok-dash-handle border-0">
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-pie-chart text-info me-2"></i>სტატისტიკა</h6>
                    <i class="bi bi-grip-vertical text-muted ok-dash-grip opacity-50"></i>
                </div>
                <div class="card-body">
                    <div style="height: 160px; position: relative;"><canvas id="contentChart"></canvas></div>
                </div>
            </div>
        <?php },
        
        'widget_quick_actions' => function() { ?>
            <div class="card border-0 shadow-sm mb-3 ok-dash-widget" data-id="widget_quick_actions">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center ok-dash-handle border-0">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-lightning-charge text-danger me-2"></i>მოქმედებები</h6>
                    <i class="bi bi-grip-vertical text-muted ok-dash-grip opacity-50"></i>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2">
                        <div class="col-6"><a href="index.php?page=ok-post-editor" class="btn btn-primary bg-gradient w-100 py-3 rounded-3 border-0 shadow-sm h-100 d-flex flex-column justify-content-center align-items-center gap-1 transition-hover"><i class="bi bi-file-earmark-plus fs-4"></i><span class="small fw-bold">პოსტი</span></a></div>
                        <div class="col-6"><a href="index.php?page=ok-page-editor" class="btn btn-info bg-gradient text-white w-100 py-3 rounded-3 border-0 shadow-sm h-100 d-flex flex-column justify-content-center align-items-center gap-1 transition-hover"><i class="bi bi-layout-text-window-reverse fs-4"></i><span class="small fw-bold">გვერდი</span></a></div>
                        <div class="col-6"><a href="index.php?page=ok-user-editor" class="btn btn-success bg-gradient w-100 py-3 rounded-3 border-0 shadow-sm h-100 d-flex flex-column justify-content-center align-items-center gap-1 transition-hover"><i class="bi bi-person-plus fs-4"></i><span class="small fw-bold">წევრი</span></a></div>
                        <div class="col-6"><a href="index.php?page=ok-settings" class="btn btn-secondary bg-gradient w-100 py-3 rounded-3 border-0 shadow-sm h-100 d-flex flex-column justify-content-center align-items-center gap-1 transition-hover"><i class="bi bi-sliders fs-4"></i><span class="small fw-bold">სეტინგი</span></a></div>
                    </div>
                </div>
            </div>
        <?php },

        'widget_system_info' => function() { ?>
            <div class="card border-0 shadow-sm mb-3 ok-dash-widget" data-id="widget_system_info">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center ok-dash-handle border-0">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-hdd-rack text-secondary me-2"></i>სისტემა</h6>
                    <i class="bi bi-grip-vertical text-muted ok-dash-grip opacity-50"></i>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item px-3 py-2 d-flex justify-content-between border-light"><span class="text-muted">Version</span><span class="badge bg-primary bg-opacity-10 text-primary">v5.0</span></li>
                        <li class="list-group-item px-3 py-2 d-flex justify-content-between border-light"><span class="text-muted">PHP</span><span class="fw-bold font-monospace"><?php echo phpversion(); ?></span></li>
                    </ul>
                </div>
            </div>
        <?php }
    ];

    $saved_left   = get_ok_option('dashboard_layout_left',   ['widget_activity_chart', 'widget_logs']);
    $saved_middle = get_ok_option('dashboard_layout_middle', ['widget_todo', 'widget_chart_donut']);
    $saved_right  = get_ok_option('dashboard_layout_right',  ['widget_quick_actions', 'widget_system_info']);

    $all_keys = array_keys($widgets);
    foreach(array_diff($all_keys, array_merge($saved_left, $saved_middle, $saved_right)) as $m) $saved_left[] = $m;
    ?>

    <style>
        .ok-dash-handle { cursor: move; }
        .ok-dash-grip { cursor: grab; }
        .ok-dash-widget { border-radius: 16px; overflow: hidden; }
        .ok-dash-widget:hover { box-shadow: 0 .5rem 1rem rgba(0,0,0,.08) !important; }
        .ok-dash-widget.sortable-ghost { opacity: 0.5; background: #f8f9fa; border: 1px dashed #ccc; }
        
        .stat-card { border-radius: 20px; border: none; min-height: 140px; color: #fff; position: relative; overflow: hidden; z-index: 1; }
        .stat-icon-large { position: absolute; right: -20px; bottom: -35px; font-size: 11rem; transform: rotate(-25deg); color: #fff; opacity: 0.2; z-index: 1; line-height: 0; pointer-events: none; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .stat-card:hover .stat-icon-large { font-size: 12rem; transform: rotate(-21deg); }
        
        .stat-val-block { text-align: center; position: relative; z-index: 2; flex: 1; }
        .stat-val-num { font-size: 1.6rem; font-weight: 700; line-height: 1.2; text-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-val-label { font-size: 0.7rem; opacity: 0.8; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; margin-top: 4px; }
        .stat-divider { border-right: 1px solid rgba(255,255,255,0.2); height: 35px; align-self: center; }
        .stat-header { font-size: 1rem; font-weight: 700; opacity: 0.9; margin-bottom: 15px; position: relative; z-index: 2; display: flex; align-items: center; gap: 8px; }

        .timeline { position: relative; }
        .timeline-item:last-child { padding-bottom: 0 !important; }
        .timeline-item .border-start { border-left: 2px solid #e9ecef !important; }
        .transition-hover:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1) !important; }
        .bg-gradient-indigo { background: linear-gradient(45deg, #4e54c8, #8f94fb); }
        .bg-gradient-warning { background: linear-gradient(45deg, #f093fb, #f5576c); }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

    <div class="d-flex justify-content-between align-items-end mb-4 mt-2">
        <div>
            <h4 class="fw-bold text-dark mb-1"><?php echo $greeting; ?>, <?php echo htmlspecialchars($_SESSION['display_name'] ?? 'Admin'); ?>!</h4>
            <small class="text-muted">მიმოხილვა</small>
        </div>
        <div class="text-end">
            <span class="badge bg-white text-dark shadow-sm border py-2 px-3 fw-normal">
                <i class="bi bi-calendar3 me-2 text-primary"></i> <?php echo $final_date; ?>
            </span>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card bg-gradient-indigo shadow-sm h-100">
                <div class="card-body p-4 position-relative d-flex flex-column justify-content-center">
                    <div class="stat-header"><i class="bi bi-file-earmark-text"></i> პოსტები</div>
                    <div class="d-flex justify-content-between w-100 mt-1">
                        <div class="stat-val-block"><div class="stat-val-num"><?php echo $p_pub; ?></div><div class="stat-val-label">აქტიური</div></div>
                        <div class="stat-divider"></div>
                        <div class="stat-val-block"><div class="stat-val-num"><?php echo $p_draft; ?></div><div class="stat-val-label">დრაფტი</div></div>
                        <div class="stat-divider"></div>
                        <div class="stat-val-block"><div class="stat-val-num"><?php echo $p_total; ?></div><div class="stat-val-label">სულ</div></div>
                    </div>
                    <i class="bi bi-file-earmark-text stat-icon-large"></i>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card bg-info bg-gradient shadow-sm h-100">
                <div class="card-body p-4 position-relative d-flex flex-column justify-content-center">
                    <div class="stat-header"><i class="bi bi-layout-text-window"></i> გვერდები</div>
                    <div class="d-flex justify-content-between w-100 mt-1">
                        <div class="stat-val-block"><div class="stat-val-num"><?php echo $pg_pub; ?></div><div class="stat-val-label">აქტიური</div></div>
                        <div class="stat-divider"></div>
                        <div class="stat-val-block"><div class="stat-val-num"><?php echo $pg_draft; ?></div><div class="stat-val-label">დრაფტი</div></div>
                        <div class="stat-divider"></div>
                        <div class="stat-val-block"><div class="stat-val-num"><?php echo $pg_total; ?></div><div class="stat-val-label">სულ</div></div>
                    </div>
                    <i class="bi bi-layout-text-window stat-icon-large"></i>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card bg-success bg-gradient shadow-sm h-100">
                <div class="card-body p-4 position-relative d-flex flex-column justify-content-center">
                    <div style="position: relative; z-index: 2;">
                        <div style="font-size: 0.9rem; font-weight: 600; opacity: 0.9; text-transform: uppercase;">წევრები</div>
                        <div style="font-size: 2.5rem; font-weight: 700; margin-top: 5px;"><?php echo $c_users; ?></div>
                    </div>
                    <i class="bi bi-people stat-icon-large"></i>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card bg-gradient-warning shadow-sm h-100">
                <div class="card-body p-4 position-relative d-flex flex-column justify-content-center">
                    <div style="position: relative; z-index: 2;">
                        <div style="font-size: 0.9rem; font-weight: 600; opacity: 0.9; text-transform: uppercase;">მედია ფაილები</div>
                        <div style="font-size: 2.5rem; font-weight: 700; margin-top: 5px;"><?php echo $c_media; ?></div>
                    </div>
                    <i class="bi bi-images stat-icon-large"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4" id="dashboard-col-left">
            <?php foreach ($saved_left as $widget_id) if (isset($widgets[$widget_id])) $widgets[$widget_id](); ?>
        </div>
        <div class="col-lg-4" id="dashboard-col-middle">
            <?php foreach ($saved_middle as $widget_id) if (isset($widgets[$widget_id])) $widgets[$widget_id](); ?>
        </div>
        <div class="col-lg-4" id="dashboard-col-right">
            <?php foreach ($saved_right as $widget_id) if (isset($widgets[$widget_id])) $widgets[$widget_id](); ?>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // --- Pie Chart (Stats) ---
        const ctxDonut = document.getElementById('contentChart');
        if(ctxDonut) {
            new Chart(ctxDonut, {
                type: 'doughnut',
                data: {
                    labels: ['პოსტები', 'მედია', 'გვერდები'],
                    datasets: [{
                        data: [<?php echo $p_pub; ?>, <?php echo $c_media; ?>, <?php echo $pg_pub; ?>],
                        backgroundColor: ['#4e73df', '#f6c23e', '#36b9cc'],
                        hoverBackgroundColor: ['#2e59d9', '#e0a800', '#2c9faf'],
                        borderWidth: 0, hoverOffset: 5
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, cutout: '70%' }
            });
        }

        // --- Activity Chart (Dynamic Multi-Line) 📊 ---
        const ctxActivity = document.getElementById('activityChart');
        if(ctxActivity) {
            new Chart(ctxActivity, {
                type: 'line',
                data: {
                    labels: <?php echo $js_labels; ?>, 
                    datasets: <?php echo $js_datasets; ?> // აქ მოდის ყველა ხაზი დინამიურად
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: false, 
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: { 
                        legend: { 
                            display: true, // ლეგენდა ჩართულია, რომ დააკლიკო და გათიშო ტიპები
                            position: 'top',
                            labels: { usePointStyle: true, boxWidth: 8, padding: 15, font: { size: 11 } }
                        }, 
                        tooltip: { mode: 'index', intersect: false } 
                    }, 
                    scales: { 
                        y: { beginAtZero: true, ticks: { precision: 0 }, grid: { borderDash: [2, 4], drawBorder: false } }, 
                        x: { grid: { display: false } } 
                    } 
                } 
            });
        }

        // --- Todo List Logic ---
        const todoInput = document.getElementById('todo-input');
        const todoBtn   = document.getElementById('add-todo-btn');
        const todoList  = document.getElementById('todo-list-ul');
        const noTodos   = document.getElementById('no-todos');

        function saveTodos() {
            let todos = [];
            document.querySelectorAll('.todo-item').forEach(item => {
                todos.push({ text: item.querySelector('.todo-text').innerText, done: item.querySelector('.todo-checkbox').checked });
            });
            const formData = new FormData();
            formData.append('action', 'save_todo_list');
            formData.append('_ok_nonce', '<?php echo function_exists("ok_create_nonce") ? ok_create_nonce("save_dash_widgets") : "123"; ?>');
            formData.append('todos', JSON.stringify(todos));
            fetch(window.location.href, { method: 'POST', body: formData });
        }

        function addTodo(text) {
            if(noTodos) noTodos.remove();
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex align-items-center justify-content-between border-0 py-2 todo-item';
            li.innerHTML = `<div class="d-flex align-items-center gap-2"><input class="form-check-input mt-0 todo-checkbox" type="checkbox"><span class="todo-text">${text}</span></div><button class="btn btn-sm text-danger todo-delete opacity-50"><i class="bi bi-trash"></i></button>`;
            todoList.prepend(li);
            bindTodoEvents(li);
            saveTodos();
        }

        function bindTodoEvents(li) {
            li.querySelector('.todo-delete').addEventListener('click', function() { li.remove(); saveTodos(); });
            li.querySelector('.todo-checkbox').addEventListener('change', function(e) {
                const span = li.querySelector('.todo-text');
                if(e.target.checked) span.classList.add('text-decoration-line-through', 'text-muted');
                else span.classList.remove('text-decoration-line-through', 'text-muted');
                saveTodos();
            });
        }
        document.querySelectorAll('.todo-item').forEach(bindTodoEvents);
        todoBtn.addEventListener('click', () => { if(todoInput.value.trim()) { addTodo(todoInput.value); todoInput.value = ''; } });
        todoInput.addEventListener('keypress', (e) => { if(e.key === 'Enter' && todoInput.value.trim()) { addTodo(todoInput.value); todoInput.value = ''; } });

        // --- Drag & Drop ---
        const colLeft = document.getElementById('dashboard-col-left');
        const colMiddle = document.getElementById('dashboard-col-middle');
        const colRight = document.getElementById('dashboard-col-right');

        function saveLayout() {
            let leftOrder = [], middleOrder = [], rightOrder = [];
            colLeft.querySelectorAll('.ok-dash-widget').forEach(el => leftOrder.push(el.dataset.id));
            colMiddle.querySelectorAll('.ok-dash-widget').forEach(el => middleOrder.push(el.dataset.id));
            colRight.querySelectorAll('.ok-dash-widget').forEach(el => rightOrder.push(el.dataset.id));

            const formData = new FormData();
            formData.append('action', 'save_dashboard_widgets');
            formData.append('_ok_nonce', '<?php echo function_exists("ok_create_nonce") ? ok_create_nonce("save_dash_widgets") : "123"; ?>');
            formData.append('left', JSON.stringify(leftOrder));
            formData.append('middle', JSON.stringify(middleOrder));
            formData.append('right', JSON.stringify(rightOrder));
            fetch(window.location.href, { method: 'POST', body: formData });
        }

        const sortableOptions = { group: 'dashboard', animation: 150, handle: '.ok-dash-handle', ghostClass: 'sortable-ghost', onEnd: saveLayout };
        new Sortable(colLeft, sortableOptions);
        new Sortable(colMiddle, sortableOptions);
        new Sortable(colRight, sortableOptions);
    });
    </script>
    <?php
}