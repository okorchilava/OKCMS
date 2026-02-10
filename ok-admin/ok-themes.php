<?php
/**
 * დიზაინების (თემების) მართვა
 * Folder: ok-content/themes/
 */

// 1. მენიუს რეგისტრაცია
add_ok_action('admin_menu', function() {
        
        // A. მშობელი მენიუ ("თემები")
        add_menu_page(
            'თემები',           // გვერდის სათაური
            'თემები',           // მენიუს სათაური
            'manage_options',   // უფლება
            'ok-themes',        // Slug
            'ok_render_themes', // ფუნქცია
            'bi bi-palette',    // აიკონი
            59                  // პოზიცია
        );

        // B. ქვემენიუ ("ყველა თემა")
        add_submenu_page(
            'ok-themes',        // მშობლის Slug
            'ყველა თემა',       // გვერდის სათაური
            'ყველა თემა',       // მენიუს სათაური
            'manage_options',   // უფლება
            'ok-themes',        // Slug (იგივე, რომ დეფოლტად გაიხსნას)
            'ok_render_themes'  // ფუნქცია
        );
    });

// დამხმარე: ინფორმაციის წაკითხვა style.css-დან
function ok_get_theme_data($theme_folder) {
    // root = ერთი დონით ზემოთ ok-admin-იდან
    $root_dir   = dirname(__DIR__); 
    // ზუსტი გზა: root/ok-content/themes/folder/style.css
    $style_path = $root_dir . '/ok-content/themes/' . $theme_folder . '/style.css';
    
    $data = [
        'Name'        => $theme_folder,
        'Version'     => '1.0',
        'Author'      => 'Unknown',
        'Description' => ''
    ];

    if (file_exists($style_path)) {
        // ვკითხულობთ მთელ ფაილს, რომ Header-მეტა არ ჩაჭრილ იქნას
        $content = file_get_contents($style_path); 
        
        if ($content !== false) {
            // ჯემინის რეკომენდირებული, არახარბი regex-ები
            if (preg_match('/Theme Name:\s*([^\r\n]*)/i', $content, $m)) {
                $data['Name'] = trim($m[1]);
            }
            if (preg_match('/Version:\s*([^\r\n]*)/i', $content, $m)) {
                $data['Version'] = trim($m[1]);
            }
            if (preg_match('/Author:\s*([^\r\n]*)/i', $content, $m)) {
                $data['Author'] = trim($m[1]);
            }
            if (preg_match('/Description:\s*([^\r\n]*)/i', $content, $m)) {
                $data['Description'] = trim($m[1]);
            }
        }
    }
    return $data;
}

// 2. ვიზუალი
function ok_render_themes() {
    
    // --- აქტივაცია ---
    if (isset($_GET['action']) && $_GET['action'] === 'activate' && isset($_GET['theme'])) {
        $new_theme = strip_tags(trim($_GET['theme']));
        update_ok_option('active_theme', $new_theme);
        echo '<div class="alert alert-success border-0 shadow-sm mb-4"><i class="bi bi-check-circle me-2"></i> თემა <strong>'.htmlspecialchars($new_theme).'</strong> გააქტიურდა.</div>';
    }

    // --- თემების წაკითხვა ---
    $root_dir   = dirname(__DIR__);
    $themes_dir = $root_dir . '/ok-content/themes/';
    $active_theme = get_ok_option('active_theme', 'default');
    
    $themes = [];
    if (is_dir($themes_dir)) {
        $dirs = array_filter(glob($themes_dir . '*'), 'is_dir');
        foreach ($dirs as $dir) {
            $folder_name = basename($dir);
            $themes[$folder_name] = ok_get_theme_data($folder_name);
            
            // სურათი (png ან jpg)
            $img_url = '../ok-content/themes/' . $folder_name . '/screenshot.png';
            if (!file_exists($dir . '/screenshot.png')) {
                if (file_exists($dir . '/screenshot.jpg')) {
                    $img_url = '../ok-content/themes/' . $folder_name . '/screenshot.jpg';
                } else {
                    $img_url = null;
                }
            }
            $themes[$folder_name]['screenshot'] = $img_url;
        }
    }
    ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">დიზაინები</h3>
            <p class="text-muted small mb-0">მართეთ საიტის ვიზუალური მხარე.</p>
        </div>
        <div class="d-flex align_items-center gap-3">
            <span class="badge bg-white text-dark shadow-sm border px-3 py-2">სულ: <?php echo count($themes); ?></span>
            <a href="index.php?page=ok-theme-upload" class="btn btn-primary btn-sm px-4 rounded-pill shadow-sm">
                <i class="bi bi-cloud-upload me-2"></i>ატვირთვა
            </a>
        </div>
    </div>

    <div class="row g-4">
        <?php if (empty($themes)): ?>
            <div class="col-12">
                <div class="alert alert-warning border-0 shadow-sm">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    თემები ვერ მოიძებნა საქაღალდეში: <code>ok-content/themes/</code>
                </div>
            </div>
        <?php else: foreach ($themes as $slug => $info): 
            $isActive = ($slug === $active_theme);
        ?>
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="card h-100 border-0 shadow-sm theme-card <?php echo $isActive ? 'active-theme-border' : ''; ?>">
                    
                    <div class="theme-thumb-wrapper ratio ratio-16x9 bg-light position-relative">
                        <?php if ($info['screenshot']): ?>
                            <img src="<?php echo $info['screenshot']; ?>" class="object-fit-cover" alt="<?php echo htmlspecialchars($info['Name']); ?>">
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center text-muted h-100 flex-column">
                                <i class="bi bi-image fs-1 opacity-25"></i>
                                <span class="small mt-2 opacity-50">No Preview</span>
                            </div>
                        <?php endif; ?>

                        <?php if ($isActive): ?>
                            <div class="active-overlay d-flex align-items-center justify-content-center">
                                <span class="badge bg-success px-3 py-2 shadow border border-white">
                                    <i class="bi bi-check-circle-fill me-2"></i>აქტიური
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title fw-bold mb-0 text-truncate" title="<?php echo htmlspecialchars($info['Name']); ?>">
                                <?php echo htmlspecialchars($info['Name']); ?>
                            </h5>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border">v<?php echo htmlspecialchars($info['Version']); ?></span>
                        </div>
                        
                        <p class="card-text text-muted small flex-grow-1 theme-desc">
                            <?php echo htmlspecialchars(mb_substr($info['Description'], 0, 80)) . (strlen($info['Description']) > 80 ? '...' : ''); ?>
                        </p>
                        
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                            <small class="text-muted">By: <strong><?php echo htmlspecialchars($info['Author']); ?></strong></small>
                            
                            <?php if (!$isActive): ?>
                                <a href="index.php?page=ok-themes&action=activate&theme=<?php echo $slug; ?>" class="btn btn-sm btn-outline-primary px-3 rounded-pill activate-btn">
                                    გააქტიურება
                                </a>
                            <?php else: ?>
                                <button class="btn btn-sm btn-success px-3 rounded-pill disabled" disabled>არჩეულია</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>

    <style>
        .theme-card { transition: transform 0.2s, box-shadow 0.2s; overflow: hidden; border-radius: 12px; }
        .theme-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important; }
        .active-theme-border { border: 2px solid #198754 !important; }
        .theme-thumb-wrapper { border-bottom: 1px solid #f0f0f0; overflow: hidden; }
        .active-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.85); backdrop-filter: blur(2px); }
        .activate-btn { transition: 0.2s; }
        .activate-btn:hover { background-color: #0d6efd; color: white; }
        .theme-desc { min-height: 40px; line-height: 1.4; }
    </style>
    <?php
}
