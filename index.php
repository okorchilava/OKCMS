<?php
/**
 * OK Engine - Frontend Router
 * Features: Page Templates, Date Archives, Smart Sidebar, Universal Permalinks, Custom Routes
 */

// 1. იტვირთება სისტემა (და მასთან ერთად ყველა ფუნქცია, მათ შორის profile logic)
require_once 'ok-core/load.php';

// 2. 🔥 დეველოპერ რეჟიმის და ბუფერიზაციის ჩართვა
if (function_exists('ok_handle_dev_mode')) {
    ok_handle_dev_mode();
}

if (function_exists('ok_start_engine_buffering')) {
    ok_start_engine_buffering();
}

// ─────────────────────────────────────────────────────────────────────────────
// 3. URL Parsing & Cleanup
// ─────────────────────────────────────────────────────────────────────────────
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$script_name = $_SERVER['SCRIPT_NAME'];
$base_dir    = dirname($script_name);

// Windows Path Fix
$base_dir = str_replace('\\', '/', $base_dir);
if ($base_dir === '/') { $base_dir = ''; }

// Determine current path relative to base directory
$current_path = $request_uri;
if (!empty($base_dir) && strpos($request_uri, $base_dir) === 0) {
    $current_path = substr($request_uri, strlen($base_dir));
}

$current_path = urldecode($current_path); 
$current_path = trim($current_path, '/');

// ─────────────────────────────────────────────────────────────────────────────
// 🔥 CUSTOM ROUTE: LOGIN (Standalone)
// ─────────────────────────────────────────────────────────────────────────────
if ($current_path === 'login') {
    require_once __DIR__ . '/ok-admin/login.php';
    exit; 
}

// ─────────────────────────────────────────────────────────────────────────────
// PREPARE THEME DATA
// ─────────────────────────────────────────────────────────────────────────────
$ok_query = []; 
$theme_path = 'ok-content/themes/' . get_ok_option('active_theme', 'default') . '/'; 
$template = '404.php'; 

// ─────────────────────────────────────────────────────────────────────────────
// 🔥 CUSTOM ROUTE: PROFILE
// ─────────────────────────────────────────────────────────────────────────────
if ($current_path === 'profile') {
    // რადგან ლოგიკა უკვე load.php-დან მოდის, აქ მხოლოდ ვიზუალს ვტვირთავთ
    if (file_exists($theme_path . 'profile.php')) {
        include $theme_path . 'profile.php';
        exit; // ვწყვეტთ მუშაობას, რადგან გვერდი ნაპოვნია
    }
    // თუ თემას profile.php არ აქვს, გაგრძელდება ქვემოთ და ამოაგდებს 404-ს
}

// ─────────────────────────────────────────────────────────────────────────────
// 4. Routing Logic (Find Post/Page/Category/Date)
// ─────────────────────────────────────────────────────────────────────────────
$slug_found = '';
$perm_struct = get_ok_option('permalink_structure', 'plain');

// A. DATE ARCHIVE ROUTING
$archive_date = null;
if (preg_match('#^date/(\d{4}-\d{2}-\d{2})/?$#', $current_path, $matches)) {
    $archive_date = $matches[1];
} elseif (isset($_GET['date']) && !empty($_GET['date'])) {
    $archive_date = $_GET['date'];
}

if ($archive_date) {
    $safe_date = addslashes($archive_date);
    $date_posts = $ok_db->get_results("SELECT * FROM ok_posts WHERE DATE(post_date) = '$safe_date' AND post_type = 'post' AND post_status = 'published' ORDER BY post_date DESC");

    $ok_query['posts'] = $date_posts;
    $ok_query['is_archive'] = true;
    $ok_query['is_date'] = true;
    $ok_query['is_category'] = true; 
    
    $format_setting = get_ok_option('date_format', 'd M, Y');
    $formatted_date = function_exists('ok_date_ka') ? ok_date_ka($format_setting, strtotime($safe_date)) : date($format_setting, strtotime($safe_date));
        
    $ok_query['object'] = (object)[
        'name' => 'არქივი: ' . $formatted_date,
        'description' => 'ნაპოვნია ' . count($date_posts ?? []) . ' პოსტი.'
    ];
}

// B. ID-based Request (?p=123)
elseif (isset($_GET['p']) && is_numeric($_GET['p'])) {
    $post_id = (int)$_GET['p'];
    $post = $ok_db->get_row("SELECT * FROM ok_posts WHERE id = $post_id AND post_status = 'published'");
    if ($post) { $ok_query['object'] = $post; $slug_found = 'ID_MATCH'; }
}

// C. Pretty Permalinks
elseif (!empty($current_path) && $current_path !== 'index.php') {
    if ($perm_struct === 'post_name') {
        $slug_found = $current_path;
    } else {
        $pattern = preg_quote($perm_struct, '~');
        $pattern = str_replace(['%postname%', '%post_id%', '%category%'], ['(?<slug>[^/]+)', '[^/]+', '[^/]+'], $pattern);
        $regex = "~^" . trim($pattern, '/') . "$~u"; 
        if (preg_match($regex, $current_path, $matches)) $slug_found = $matches['slug'];
        else $slug_found = $current_path; 
    }
    
    if (!empty($slug_found)) {
        $slug_safe = addslashes($slug_found);
        $post = $ok_db->get_row("SELECT * FROM ok_posts WHERE post_name = '$slug_safe' AND post_status = 'published'");
        if ($post) { 
            $ok_query['object'] = $post; 
        } else {
            $cat = $ok_db->get_row("SELECT * FROM ok_categories WHERE slug = '$slug_safe'");
            if ($cat) { $ok_query['is_category'] = true; $ok_query['object'] = $cat; }
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 5. Theme & Sidebar Logic & PAGE TEMPLATES
// ─────────────────────────────────────────────────────────────────────────────
$front_page_id = (int)get_ok_option('front_page_id', 0);
$blog_page_id  = (int)get_ok_option('blog_page_id', 0);

// --- SIDEBAR CONDITIONS ---
$theme_has_file = file_exists($theme_path . 'sidebar.php');
$global_setting = (int)get_ok_option('enable_global_sidebar', 1); 
$show_sidebar   = false; 

// --- TEMPLATE SELECTION ---

// A. Date Archive Found
if (isset($ok_query['is_date'])) {
    $template = 'archive.php';
    if ($theme_has_file && $global_setting === 1) $show_sidebar = true;
}

// B. Post or Page Found
elseif (isset($ok_query['object']) && !isset($ok_query['is_category'])) {
    $post = $ok_query['object'];
    
    // Sidebar logic per post
    if ($theme_has_file && $global_setting === 1) {
        $individual_setting = isset($post->has_sidebar) ? (int)$post->has_sidebar : 1;
        $individual_setting === 1 ? $show_sidebar = true : $show_sidebar = false;
    }

    // 1. Blog Page
    if ($post->post_type === 'page' && $post->id === $blog_page_id) {
        $ok_query['is_home'] = true; 
        $ok_query['is_blog_page'] = true; 
        $template = 'home.php';
        if ($theme_has_file && $global_setting === 1) $show_sidebar = true;
    } 
    // 2. Standard Page (With Template Support)
    elseif ($post->post_type === 'page') {
        $ok_query['is_page'] = true; 
        
        $custom_template = function_exists('get_post_meta') ? get_post_meta($post->id, '_ok_page_template', 'default') : 'default';
        
        if ($custom_template !== 'default' && file_exists($theme_path . $custom_template)) {
            $template = $custom_template; 
        } else {
            $template = 'page.php'; 
        }
    } 
    // 3. Single Post
    else {
        $ok_query['is_single'] = true; 
        $template = 'single.php';
    }

} 

// C. Category Archive
elseif (isset($ok_query['is_category'])) {
    $template = 'archive.php';
    if ($theme_has_file && $global_setting === 1) $show_sidebar = true;
} 

// D. Homepage (Root)
elseif (empty($current_path) || $current_path === 'index.php') {
    
    // Static Front Page
    if ($front_page_id > 0) {
        $ok_query['object'] = $ok_db->get_row("SELECT * FROM ok_posts WHERE id = $front_page_id AND post_status='published'");
        
        if($ok_query['object']) { 
            $ok_query['is_page'] = true; 
            $ok_query['is_front_page'] = true; 
            
            $custom_template = function_exists('get_post_meta') ? get_post_meta($ok_query['object']->id, '_ok_page_template', 'default') : 'default';
            
            if ($custom_template !== 'default' && file_exists($theme_path . $custom_template)) {
                $template = $custom_template;
            } else {
                $template = 'page.php';
            }
            
            // Sidebar logic
            if ($theme_has_file && $global_setting === 1) {
                $individual = isset($ok_query['object']->has_sidebar) ? (int)$ok_query['object']->has_sidebar : 1;
                if ($individual === 1) {
                    $show_sidebar = true;
                }
            }
        }
        else { 
            $ok_query['is_home'] = true; 
            $template = 'home.php'; 
            if ($theme_has_file && $global_setting === 1) $show_sidebar = true;
        }
    } else {
        $ok_query['is_home'] = true; 
        $template = 'home.php';
        if ($theme_has_file && $global_setting === 1) $show_sidebar = true;
    }

} 

// E. 404 Not Found
else {
    $ok_query['is_404'] = true; 
    $template = '404.php';
    $show_sidebar = false;
}

$ok_query['has_sidebar'] = $show_sidebar;


// ─────────────────────────────────────────────────────────────────────────────
// 6. File Loading
// ─────────────────────────────────────────────────────────────────────────────
if (file_exists($theme_path . $template)) {
    include $theme_path . $template;
} else {
    // Fallback logic
    if (($template == 'page.php' || $template == 'single.php' || $template == 'archive.php') && file_exists($theme_path . 'index.php')) {
        include $theme_path . 'index.php';
    } else {
        http_response_code(404);
        
        $err_favicon = get_ok_option('site_favicon', '');
        ?>
        <!DOCTYPE html>
        <html lang="ka">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>404 - გვერდი ვერ მოიძებნა</title>
            <?php if(!empty($err_favicon)): ?>
                <link rel="icon" href="<?php echo htmlspecialchars($err_favicon); ?>">
            <?php endif; ?>
            <style>
                body { margin: 0; padding: 0; font-family: sans-serif; background-color: #f8f9fa; color: #343a40; display: flex; align-items: center; justify-content: center; height: 100vh; text-align: center; }
                .container { max-width: 600px; padding: 40px; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
                h1 { font-size: 8rem; font-weight: 900; margin: 0; color: #e9ecef; line-height: 1; letter-spacing: -5px; }
                h2 { font-size: 1.5rem; font-weight: 700; margin: 20px 0 10px; color: #212529; }
                p { font-size: 1rem; color: #6c757d; margin-bottom: 30px; }
                .btn { display: inline-block; text-decoration: none; background-color: #0d6efd; color: white; padding: 12px 30px; border-radius: 50px; font-weight: 600; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>404</h1>
                <h2>გვერდი ვერ მოიძებნა</h2>
                <p>სამწუხაროდ, გვერდი, რომელსაც ეძებთ, არ არსებობს.</p>
                <a href="./" class="btn">მთავარზე დაბრუნება</a>
            </div>
        </body>
        </html>
        <?php
    }
}