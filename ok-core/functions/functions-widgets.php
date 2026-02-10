<?php
declare(strict_types=1);

/**
 * OK Engine - Widget API (Smart Color Aware)
 * Updated: Added Context Aware Logic & Color Pickers
 */

if (!defined('OK_LOADED')) die('Access Denied.');

global $ok_registered_sidebars, $ok_registered_widgets;
$ok_registered_sidebars = [];
$ok_registered_widgets  = []; 

function ok_register_widget($type, $args) {
    global $ok_registered_widgets;
    // 🛑 ვამატებთ სტანდარტულ ფერის ამორჩეველს ყველა ვიჯეტის ადმინ ტემპლეიტში
    $color_selector_html = '
        <div class="mb-3 pt-2 border-top">
            <label class="small fw-bold text-muted">ფერის რეჟიმი (Smart):</label>
            <select name="__NAME_PREFIX__[color_mode]" class="form-select form-select-sm">
                <option value="" selected>ავტომატური (ფონის მიხედვით)</option>
                <option value="light">ღია ტექსტი (მუქი ფონისთვის)</option>
                <option value="dark">მუქი ტექსტი (ღია ფონისთვის)</option>
            </select>
        </div>';

    // ვამატებთ ველს არსებულ ტემპლეიტს ბოლოში
    if(isset($args['admin_template'])) {
        $args['admin_template'] .= $color_selector_html;
    }
    
    $ok_registered_widgets[$type] = $args;
}

function ok_register_sidebar($args) {
    global $ok_registered_sidebars;
    $defaults = [
        'id' => 'sidebar-1', 
        'name' => 'Sidebar', 
        'description' => '',
        'class' => '', // 🛑 ახალი პარამეტრი: მაგ. 'bg-dark text-white'
        'before_widget' => '<div class="widget-box mb-5">', 
        'after_widget' => '</div>',
        'before_title' => '<h5 class="widget-title fw-bold mb-4">', 
        'after_title' => '</h5>'
    ];
    $ok_registered_sidebars[$args['id']] = array_merge($defaults, $args);
}

function ok_register_core_widgets() {
    global $ok_db;

    // 1. SEARCH (Modern Input)
    ok_register_widget('search', [
        'name' => 'ძებნა', 'icon' => 'bi-search', 'desc' => 'საძიებო ფორმა',
        'admin_template' => '<div class="mb-2"><label class="small fw-bold text-muted">სათაური:</label><input type="text" name="__NAME_PREFIX__[title]" class="form-control form-control-sm" value="__TITLE__"></div>',
        'render_callback' => function($data) {
            echo '<form action="/" method="get" class="position-relative">';
            // Smart Input: გამჭვირვალე ფონი, რომ მოერგოს ნებისმიერ ფერს
            echo '<input type="text" name="s" class="form-control bg-white bg-opacity-10 border-0 py-2 ps-3 pe-5 rounded-3 widget-input-contrast" placeholder="ძიება..." required>';
            echo '<button class="btn btn-link position-absolute top-0 end-0 widget-text-muted" type="submit"><i class="bi bi-search"></i></button>';
            echo '</form>';
            echo '<style>.widget-input-contrast::placeholder { color: inherit; opacity: 0.6; } .widget-input-contrast { color: inherit; }</style>';
        }
    ]);

    // 2. RECENT POSTS (Clean List)
    ok_register_widget('recent_posts', [
        'name' => 'ბოლო პოსტები', 'icon' => 'bi-newspaper', 'desc' => 'ბოლო სიახლეები',
        'admin_template' => '<div class="mb-2"><label class="small fw-bold text-muted">სათაური:</label><input type="text" name="__NAME_PREFIX__[title]" class="form-control form-control-sm" value="__TITLE__"></div><div class="mb-2"><label class="small fw-bold text-muted">რაოდენობა:</label><input type="number" name="__NAME_PREFIX__[limit]" class="form-control form-control-sm" value="__LIMIT__" min="1" max="20"></div>',
        'render_callback' => function($data) use ($ok_db) {
            $limit = isset($data['limit']) ? (int)$data['limit'] : 5;
            if ($limit < 1) $limit = 1;
            if ($limit > 20) $limit = 20;
            $posts = $ok_db->get_results('SELECT id, post_title, post_name, post_image, post_date FROM ok_posts WHERE post_type = ? AND post_status = ? ORDER BY post_date DESC LIMIT ' . (int)$limit, ['post', 'published']);
            
            if($posts) {
                $perm = get_ok_option('permalink_structure', 'plain');
                $df   = get_ok_option('date_format', 'd M, Y');
                
                echo '<div class="d-flex flex-column gap-3">'; 
                foreach($posts as $p) {
                    if ($perm === 'plain') $link = "/?p=" . $p->id;
                    elseif ($perm === 'post_name') $link = "/" . $p->post_name;
                    else $link = '/' . ltrim(str_replace(['%postname%', '%post_id%'], [$p->post_name, $p->id], $perm), '/');

                    $img_html = '<div class="w-100 h-100 bg-white bg-opacity-10 rounded-3 d-flex align-items-center justify-content-center border-0 widget-text-muted"><i class="bi bi-image"></i></div>';
                    if(!empty($p->post_image)) $img_html = '<img src="'.htmlspecialchars($p->post_image).'" class="rounded-3 w-100 h-100 object-fit-cover" alt="img">';
                    $date = function_exists('ok_date_ka') ? ok_date_ka($df, strtotime($p->post_date)) : date($df, strtotime($p->post_date));

                    echo '<div class="d-flex align-items-start position-relative group-hover">
                            <div class="flex-shrink-0 me-3" style="width: 70px; height: 70px;">'.$img_html.'</div>
                            <div class="flex-grow-1 min-width-0 pt-1">
                                <h6 class="mb-1 lh-sm fw-bold" style="font-size: 0.95rem;">
                                    <a href="'.$link.'" class="text-decoration-none stretched-link transition-color widget-text-main">'.htmlspecialchars($p->post_title).'</a>
                                </h6>
                                <small class="widget-text-muted opacity-75" style="font-size: 0.8rem;">'.$date.'</small>
                            </div>
                          </div>';
                }
                echo '</div>'; 
                // CSS - ფერები მემკვიდრეობით მიიღება მშობლისგან
                echo '<style>.transition-color { transition: color 0.2s; } .group-hover:hover .transition-color { opacity: 0.7; }</style>';
            }
        }
    ]);

    // 3. CUSTOM HTML
    ok_register_widget('custom_html', [
        'name' => 'HTML კოდი', 'icon' => 'bi-code-slash', 'desc' => 'HTML / Script',
        'admin_template' => '<div class="mb-2"><label class="small fw-bold text-muted">სათაური:</label><input type="text" name="__NAME_PREFIX__[title]" class="form-control form-control-sm" value="__TITLE__"></div><div class="mb-2"><label class="small fw-bold text-muted">HTML:</label><textarea name="__NAME_PREFIX__[content]" class="form-control font-monospace form-control-sm" rows="4">__CONTENT__</textarea></div>',
        'render_callback' => function($data) {
            echo '<div class="widget-text-main">';
            echo $data['content'] ?? '';
            echo '</div>';
        }
    ]);

    // 4. CATEGORIES (Modern Pills)
    ok_register_widget('categories', [
        'name' => 'კატეგორიები', 'icon' => 'bi-folder', 'desc' => 'კატეგორიების სია',
        'admin_template' => '<div class="mb-2"><label class="small fw-bold text-muted">სათაური:</label><input type="text" name="__NAME_PREFIX__[title]" class="form-control form-control-sm" value="__TITLE__"></div>',
        'render_callback' => function($data) use ($ok_db) {
            $cats = $ok_db->get_results("SELECT name, slug, (SELECT COUNT(*) FROM ok_posts WHERE category_id = ok_categories.id AND post_status='published') as count FROM ok_categories ORDER BY name ASC");
            if($cats) {
                echo '<ul class="list-unstyled mb-0">';
                foreach($cats as $cat) {
                    if($cat->count == 0) continue;
                    // Smart Colors: ვიყენებთ inherit ფერებს და გამჭვირვალობას
                    echo '<li class="mb-2">
                            <a href="/'.$cat->slug.'" class="d-flex justify-content-between align-items-center text-decoration-none p-2 rounded hover-bg-contrast transition widget-text-secondary">
                                <span><i class="bi bi-folder2-open me-2 opacity-50"></i> '.htmlspecialchars($cat->name).'</span>
                                <span class="badge bg-white bg-opacity-25 text-reset rounded-pill fw-normal">'.$cat->count.'</span>
                            </a>
                          </li>';
                }
                echo '</ul>';
                echo '<style>.hover-bg-contrast:hover { background-color: rgba(128,128,128,0.1); color: inherit !important; }</style>';
            }
        }
    ]);

    // 5. MENU WIDGET
    ok_register_widget('nav_menu', [
        'name' => 'მენიუ',
        'icon' => 'bi-list-ul',
        'desc' => 'მენიუს გამოტანა',
        'admin_template' => '
            <div class="mb-2">
                <label class="small fw-bold text-muted">სათაური:</label>
                <input type="text" name="__NAME_PREFIX__[title]" class="form-control form-control-sm" value="__TITLE__">
            </div>
            <div class="mb-2">
                <label class="small fw-bold text-muted">აირჩიეთ მენიუ:</label>
                __MENU_SELECT__
            </div>',
        'render_callback' => function($data) {
            $menu_id = !empty($data['menu_id']) ? $data['menu_id'] : 'header-menu';
            if (function_exists('ok_nav_menu')) {
                echo '<div class="widget-nav-menu widget-text-main">';
                ok_nav_menu($menu_id, ['vertical' => true]);
                echo '</div>';
            }
        }
    ]);
    
    // 6. CALENDAR WIDGET (ადაპტირებული ფერები)
    ok_register_widget('calendar', [
        'name' => 'კალენდარი',
        'icon' => 'bi-calendar3',
        'desc' => 'პოსტების არქივის კალენდარი',
        'admin_template' => '<div class="mb-2"><label class="small fw-bold text-muted">სათაური:</label><input type="text" name="__NAME_PREFIX__[title]" class="form-control form-control-sm" value="__TITLE__" placeholder="არქივი"></div>',
        'render_callback' => function($data) {
            global $ok_db;
            $perm_struct = get_ok_option('permalink_structure', 'plain');
            $year  = isset($_GET['cal_y']) ? (int)$_GET['cal_y'] : (int)date('Y');
            $month = isset($_GET['cal_m']) ? (int)$_GET['cal_m'] : (int)date('n');

            if ($month < 1) { $month = 12; $year--; }
            if ($month > 12) { $month = 1;  $year++; }

            // ... (კალენდრის ლოგიკა იგივე რჩება) ...
            $first_day_ts = mktime(0, 0, 0, $month, 1, $year);
            $days_in_month = (int)date('t', $first_day_ts);
            $day_of_week = (int)date('N', $first_day_ts); 
            $results = $ok_db->get_results("SELECT DISTINCT DAY(post_date) as p_day FROM ok_posts WHERE MONTH(post_date) = $month AND YEAR(post_date) = $year AND post_status = 'published'");
            $active_days = [];
            if ($results) { foreach ($results as $row) { $active_days[] = $row->p_day; } }
            $posts_map = !empty($active_days) ? array_flip($active_days) : [];

            $geo_months = [1=>'იანვარი', 'თებერვალი', 'მარტი', 'აპრილი', 'მაისი', 'ივნისი', 'ივლისი', 'აგვისტო', 'სექტემბერი', 'ოქტომბერი', 'ნოემბერი', 'დეკემბერი'];
            $geo_week   = ['ორშ', 'სამ', 'ოთხ', 'ხუთ', 'პარ', 'შაბ', 'კვი'];

            $prev_m = $month - 1; $prev_y = $year; if ($prev_m < 1) { $prev_m = 12; $prev_y--; }
            $next_m = $month + 1; $next_y = $year; if ($next_m > 12) { $next_m = 1; $next_y++; }
            $base_url = strtok($_SERVER["REQUEST_URI"], '?');
            $prev_link = $base_url . "?cal_y=$prev_y&cal_m=$prev_m";
            $next_link = $base_url . "?cal_y=$next_y&cal_m=$next_m";

            // 🛑 ფონის ფერი: გამჭვირვალე თეთრი
            echo '<div class="calendar-widget bg-white bg-opacity-10 p-3 rounded-4 widget-text-main">';
            echo '<div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom border-secondary border-opacity-25">';
            echo '<a href="'.$prev_link.'" class="btn btn-sm btn-light bg-white bg-opacity-50 border-0 text-reset py-0 px-2 rounded-circle"><i class="bi bi-chevron-left"></i></a>';
            echo '<span class="fw-bold small text-uppercase">'.$geo_months[$month].' '.$year.'</span>';
            echo '<a href="'.$next_link.'" class="btn btn-sm btn-light bg-white bg-opacity-50 border-0 text-reset py-0 px-2 rounded-circle"><i class="bi bi-chevron-right"></i></a>';
            echo '</div>';

            echo '<table class="table table-borderless table-sm mb-0 text-center small w-100" style="table-layout: fixed;">';
            echo '<thead><tr class="widget-text-muted opacity-75" style="font-size: 0.75rem;">';
            foreach($geo_week as $wd) { echo '<th class="fw-normal pb-2 text-reset">'.$wd.'</th>'; }
            echo '</tr></thead><tbody><tr>';

            for ($i = 1; $i < $day_of_week; $i++) { echo '<td></td>'; }

            for ($day = 1; $day <= $days_in_month; $day++) {
                if (($day + $day_of_week - 2) % 7 == 0 && $day != 1) { echo '</tr><tr>'; }
                $is_today = ($day == (int)date('j') && $month == (int)date('n') && $year == (int)date('Y'));
                $has_post = isset($posts_map[$day]);

                echo '<td class="p-1 align-middle">';
                if ($has_post) {
                     $date_str = sprintf("%04d-%02d-%02d", $year, $month, $day);
                     $link = ($perm_struct === 'plain') ? '/?date=' . $date_str : '/date/' . $date_str . '/';
                     // აქტიური დღე: Primary ფერი
                     echo '<a href="'.$link.'" class="d-flex align-items-center justify-content-center mx-auto text-decoration-none fw-bold rounded-circle bg-primary text-white shadow-sm" style="width:28px; height:28px;">'.$day.'</a>';
                } else {
                     $span_class = $is_today ? 'bg-white bg-opacity-50 fw-bold' : 'opacity-50';
                     echo '<span class="d-flex align-items-center justify-content-center mx-auto rounded-circle text-reset '.$span_class.'" style="width:28px; height:28px;">'.$day.'</span>';
                }
                echo '</td>';
            }
            while (($day + $day_of_week - 2) % 7 != 0) { echo '<td></td>'; $day++; }
            echo '</tr></tbody></table></div>';
        }
    ]);

// 7. SOCIAL (გაფართოებული სია + ცენტრირება + გასწორებული ადმინი)
    $social_networks = [
        'facebook'  => ['icon' => 'bi-facebook',  'label' => 'Facebook',  'color' => '#1877F2'],
        'instagram' => ['icon' => 'bi-instagram', 'label' => 'Instagram', 'color' => '#E4405F'],
        'tiktok'    => ['icon' => 'bi-tiktok',    'label' => 'TikTok',    'color' => '#000000'],
        'linkedin'  => ['icon' => 'bi-linkedin',  'label' => 'LinkedIn',  'color' => '#0A66C2'],
        'youtube'   => ['icon' => 'bi-youtube',   'label' => 'YouTube',   'color' => '#FF0000'],
        'twitter'   => ['icon' => 'bi-twitter-x', 'label' => 'X (Twitter)', 'color' => '#000000'], // ან bi-twitter
        'pinterest' => ['icon' => 'bi-pinterest', 'label' => 'Pinterest', 'color' => '#E60023'],
        'whatsapp'  => ['icon' => 'bi-whatsapp',  'label' => 'WhatsApp',  'color' => '#25D366'],
        'telegram'  => ['icon' => 'bi-telegram',  'label' => 'Telegram',  'color' => '#0088cc'],
        'viber'     => ['icon' => 'bi-chat-text', 'label' => 'Viber',     'color' => '#7360f2'], // Viber-ს ხშირად bi-chat-text-ით ანაცვლებენ თუ spec icon არაა
        'skype'     => ['icon' => 'bi-skype',     'label' => 'Skype',     'color' => '#00aff0'],
        'email'     => ['icon' => 'bi-envelope',  'label' => 'Email',     'color' => '#6c757d']
    ];

    $social_admin_html = '<div class="mb-3"><label class="small fw-bold text-muted">სათაური:</label><input type="text" name="__NAME_PREFIX__[title]" class="form-control form-control-sm" value="__TITLE__"></div>';
    
    foreach ($social_networks as $key => $net) {
        // value ატრიბუტი ამოღებულია, რომ __KEY__ არ გამოჩნდეს
        $social_admin_html .= '<div class="input-group input-group-sm mb-2">
            <span class="input-group-text bg-white border-end-0" style="width: 40px; justify-content: center;"><i class="bi '.$net['icon'].'"></i></span>
            <input type="text" name="__NAME_PREFIX__['.$key.']" class="form-control border-start-0" placeholder="'.$net['label'].' URL">
        </div>';
    }
    
    ok_register_widget('social', [
        'name' => 'სოციალური', 'icon' => 'bi-share', 'desc' => 'სოც. ქსელების ღილაკები',
        'admin_template' => $social_admin_html,
        'render_callback' => function($data) use ($social_networks) {
            // justify-content-center უზრუნველყოფს ცენტრირებას
            echo '<div class="d-flex flex-wrap gap-2 justify-content-center">';
            foreach ($social_networks as $key => $net) {
                if (!empty($data[$key])) {
                    $url = htmlspecialchars($data[$key]);
                    // Email-ისთვის mailto:-ს დამატება, WhatsApp-ისთვის wa.me ლოგიკა თუ გინდათ, აქ შეიძლება ჩამატება, მაგრამ სტანდარტულად ლინკიც საკმარისია
                    if ($key === 'email' && strpos($url, 'mailto:') === false) $url = 'mailto:' . $url;
                    
                    echo '<a href="'.$url.'" target="_blank" class="btn btn-light btn-sm rounded-circle d-flex align-items-center justify-content-center social-btn-'.$key.'" style="width:36px; height:36px;" title="'.$net['label'].'">
                            <i class="bi '.$net['icon'].'" style="font-size: 1.1rem;"></i>
                          </a>';
                }
            }
            echo '</div>';
            
            // დინამიური ფერების გენერაცია
            echo '<style>';
            foreach($social_networks as $key => $net) {
                echo ".social-btn-$key { color: {$net['color']}; transition: all 0.2s; }";
                echo ".social-btn-$key:hover { background-color: {$net['color']} !important; color: #fff !important; transform: translateY(-2px); }";
            }
            echo '</style>';
        }
    ]);
}
ok_register_core_widgets();


/**
 * ვიჯეტების გამოტანა (Frontend) - SMART CONTEXT AWARE
 */
function ok_dynamic_sidebar(string $index): bool {
    global $ok_registered_widgets, $ok_registered_sidebars; // დაემატა sidebars
    
    $all_widgets_json = get_ok_option('ok_widget_areas', '');
    if (empty($all_widgets_json)) return false;

    $all_widgets = json_decode($all_widgets_json, true);
    if (empty($all_widgets[$index])) return false;

    // 🛑 ვიღებთ ზონის პარამეტრებს, რომ ვიცოდეთ რა ფონია (Smart Context)
    $sidebar_args = $ok_registered_sidebars[$index] ?? [];
    $sidebar_class = $sidebar_args['class'] ?? ''; // მაგ: 'text-white' ან 'bg-dark'

    // ზონის კონტეინერი (თუ საჭიროა კლასების შემოხვევა)
    // თუ ზონას აქვს კლასი text-white, ვიჯეტები ავტომატურად გათეთრდება
    if($sidebar_class) echo '<div class="'.htmlspecialchars($sidebar_class).'">';

    $area_widgets = $all_widgets[$index];
    uasort($area_widgets, function($a, $b) { return (int)($a['order']??0) <=> (int)($b['order']??0); });

    foreach ($area_widgets as $unique_id => $w) {
        $active = isset($w['active']) ? (int)$w['active'] : 0;
        if (!$active) continue;

        $type = $w['type'] ?? '';
        
        if (isset($ok_registered_widgets[$type])) {
            ok_render_single_widget($type, $w);
        }
    }

    if($sidebar_class) echo '</div>'; // End wrapper

    return true;
}

// 🛑 განახლებული რენდერი: ფერის რეჟიმების მართვა
function ok_render_single_widget($type, $data) {
    global $ok_registered_widgets;
    
    $widget_def = $ok_registered_widgets[$type];
    $title = htmlspecialchars($data['title'] ?? '');
    
    // 🛑 ფერის რეჟიმის განსაზღვრა
    $mode = $data['color_mode'] ?? ''; // '', 'light', 'dark'
    
    // კლასების გენერაცია
    $text_class = '';
    $muted_class = 'text-muted'; // Default muted
    
    if ($mode === 'light') {
        // ღია ტექსტი (მუქი ფონისთვის)
        $text_class  = 'text-white';
        $muted_class = 'text-white-50';
    } elseif ($mode === 'dark') {
        // მუქი ტექსტი (ღია ფონისთვის)
        $text_class  = 'text-dark';
        $muted_class = 'text-secondary';
    } else {
        // Auto: არაფერს ვუწერთ, inherit-ს იზამს CSS-ით
        // ამ შემთხვევაში ვიყენებთ სპეციალურ კლასებს CSS-ისთვის
    }

    // Smart Classes for inner content
    // ვიჯეტის შიგნით აღარ ვიყენებთ პირდაპირ 'text-dark'-ს, არამედ ამ ცვლადებს:
    // .widget-text-main -> სათაურები, ლინკები
    // .widget-text-secondary -> აღწერები, მეტა

    echo '<div class="widget-box mb-5 widget-'.$type.' '.$text_class.'">';
    
    // Smart Styles (Inline for simplicity, or move to CSS file)
    if ($mode === 'light') {
        echo '<style>.widget-'.$type.' .widget-text-main { color: #fff !important; } .widget-'.$type.' .widget-text-muted { color: rgba(255,255,255,0.6) !important; } .widget-'.$type.' .widget-text-secondary { color: rgba(255,255,255,0.8) !important; }</style>';
    } elseif ($mode === 'dark') {
        echo '<style>.widget-'.$type.' .widget-text-main { color: #212529 !important; } .widget-'.$type.' .widget-text-muted { color: #6c757d !important; }</style>';
    }

    // სათაური
    if (!empty($title)) {
        // text-dark ამოღებულია, ახლა იყენებს მემკვიდრეობას
        echo '<h5 class="widget-title fw-bold mb-4 widget-text-main">' . $title . '</h5>';
    }
    
    // კონტენტი
    echo '<div class="widget-content widget-text-secondary">';
    if (isset($widget_def['render_callback']) && is_callable($widget_def['render_callback'])) {
        call_user_func($widget_def['render_callback'], $data);
    }
    echo '</div>'; 
    echo '</div>'; 
}
?>