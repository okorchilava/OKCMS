<?php
/**
 * FILE: ok-admin/ok-plugins.php
 * OK Engine - Plugin Management System (Final Stable Version)
 */

if (!defined('OK_LOADED')) exit;

// 1. მენიუს რეგისტრაცია
add_ok_action('admin_menu', function() {
    add_menu_page(
        'პლაგინები',         
        'პლაგინები',         
        'manage_options',    
        'ok-plugins',        
        'ok_render_plugins', 
        'bi bi-plug',        
        60                   
    );
});

/**
 * დამხმარე: საქაღალდის რეკურსიული წაშლა
 */
function ok_recursive_rmdir($dir) { 
   if (is_dir($dir)) { 
     $objects = scandir($dir); 
     foreach ($objects as $object) { 
       if ($object != "." && $object != "..") { 
         if (is_dir($dir. DIRECTORY_SEPARATOR .$object) && !is_link($dir."/".$object))
           ok_recursive_rmdir($dir. DIRECTORY_SEPARATOR .$object);
         else
           unlink($dir. DIRECTORY_SEPARATOR .$object); 
       } 
     }
     rmdir($dir); 
   } 
}

/**
 * დამხმარე: პლაგინის ჰედერის წაკითხვა
 */
function ok_get_plugin_data($plugin_file) {
    $default_headers = [ 
        'Name' => 'Plugin Name', 
        'Version' => 'Version', 
        'Description' => 'Description', 
        'Author' => 'Author' 
    ];
    
    if (!file_exists($plugin_file)) return false;

    // ფაილის მხოლოდ პირველ 8kb-ს ვკითხულობთ სისწრაფისთვის
    $plugin_data = file_get_contents($plugin_file, false, null, 0, 8192);
    $info = [];
    
    foreach ($default_headers as $field => $regex) {
        if (preg_match('/^[ \t\/*#@]*' . preg_quote($regex, '/') . ':(.*)$/mi', $plugin_data, $match) && $match[1])
            $info[$field] = trim($match[1]);
        else
            $info[$field] = '';
    }
    
    // Fallback: თუ სახელი არ წერია, ფაილის სახელი დავარქვათ
    if (empty($info['Name'])) {
        $info['Name'] = basename($plugin_file);
    }
    
    return $info;
}

/**
 * მთავარი ინტერფეისი
 */
function ok_render_plugins() {
    // 1. გზების განსაზღვრა (DOCUMENT_ROOT საიმედოობისთვის)
    $plugins_root = $_SERVER['DOCUMENT_ROOT'] . '/ok-content/plugins/';
    $plugins_url_base = '../ok-content/plugins/'; 

    if (!is_dir($plugins_root)) {
        mkdir($plugins_root, 0755, true);
    }

    // 2. აქტიური პლაგინების მიღება (დაცული ტიპებით)
    $val = get_ok_option('active_plugins', []);
    // [!!!] მთავარი შესწორება: გარანტიას ვაძლევთ, რომ მასივია
    $active_plugins = (is_array($val)) ? $val : [];

    // 3. Action Handler (ჩართვა/გამორთვა/წაშლა)
    if (isset($_GET['action']) && isset($_GET['plugin'])) {
        $action_plugin = $_GET['plugin']; 
        
        // ა) გააქტიურება
        if ($_GET['action'] === 'activate') {
            if (!in_array($action_plugin, $active_plugins)) {
                $active_plugins[] = $action_plugin;
                // ვიყენებთ array_unique-ს დუბლიკატების თავიდან ასაცილებლად
                update_ok_option('active_plugins', array_values(array_unique($active_plugins)));
            }
        } 
        // ბ) გამორთვა
        elseif ($_GET['action'] === 'deactivate') {
            // სიიდან ამოღება
            $active_plugins = array_diff($active_plugins, [$action_plugin]);
            update_ok_option('active_plugins', array_values($active_plugins));
        }
        // გ) ფიზიკური წაშლა
        elseif ($_GET['action'] === 'delete') {
            if (!in_array($action_plugin, $active_plugins)) {
                $full_path = $plugins_root . $action_plugin;
                // თუ ფაილია - ვშლით ფაილს, თუ საქაღალდეშია - ვშლით საქაღალდეს
                if (file_exists($full_path)) {
                    $plugin_dir = dirname($full_path);
                    if ($plugin_dir !== rtrim($plugins_root, '/')) {
                        ok_recursive_rmdir($plugin_dir);
                    } else {
                        unlink($full_path);
                    }
                }
            }
        }
        
        // გვერდის განახლება სტატუსის შესაცვლელად
        echo "<script>window.location.href='index.php?page=ok-plugins';</script>";
        exit;
    }

    // 4. სკანირება - ყველა პლაგინის პოვნა
    $all_plugins = [];
    
    // ა) საქაღალდეებში ძებნა (მაგ: ok-quiz/ok-quiz.php)
    $dirs = glob($plugins_root . '*', GLOB_ONLYDIR);
    if ($dirs) {
        foreach ($dirs as $dir) {
            $dirname = basename($dir);
            
            // ვეძებთ მთავარ ფაილს (სახელი.php ან index.php)
            $candidate_1 = $dir . '/' . $dirname . '.php';
            $candidate_2 = $dir . '/index.php';
            
            $target_file = '';
            if (file_exists($candidate_1)) $target_file = $candidate_1;
            elseif (file_exists($candidate_2)) $target_file = $candidate_2;

            if ($target_file) {
                $data = ok_get_plugin_data($target_file);
                $relative_path = $dirname . '/' . basename($target_file);
                
                $all_plugins[$relative_path] = [
                    'data' => $data, 
                    'dir'  => $dir,
                    'url'  => $plugins_url_base . $dirname . '/'
                ];
            }
        }
    }

    // ბ) პირდაპირ ფესვში არსებული ფაილები (მაგ: simple-plugin.php)
    $single_files = glob($plugins_root . '*.php');
    if ($single_files) {
        foreach ($single_files as $file_path) {
            $data = ok_get_plugin_data($file_path);
            if ($data) {
                $filename = basename($file_path);
                $all_plugins[$filename] = [
                    'data' => $data, 
                    'dir'  => $plugins_root,
                    'url'  => $plugins_url_base
                ];
            }
        }
    }
    ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-dark fw-bold">პლაგინების მართვა</h1>
            <p class="text-muted small">სულ: <?php echo count($all_plugins); ?> | აქტიური: <?php echo count($active_plugins); ?></p>
        </div>
        <a href="index.php?page=ok-upload-plugin" class="btn btn-primary rounded-pill shadow-sm">
            <i class="bi bi-upload me-1"></i> ატვირთვა
        </a>
    </div>

    <div class="row g-4">
        <?php if (!empty($all_plugins)): 
            foreach ($all_plugins as $slug => $plugin): 
                $data = $plugin['data'];
                // აქ უკვე დაცულია active_plugins შემოწმება
                $is_active = in_array($slug, $active_plugins);
                
                // აიკონის მოძიება
                $icon_url = '';
                if (file_exists($plugin['dir'] . '/icon.png')) $icon_url = $plugin['url'] . 'icon.png';
                elseif (file_exists($plugin['dir'] . '/icon.jpg')) $icon_url = $plugin['url'] . 'icon.jpg';

                $card_border = $is_active ? 'border-primary shadow' : 'border-0 shadow-sm';
        ?>
        <div class="col-xxl-3 col-xl-4 col-md-6">
            <div class="card h-100 <?php echo $card_border; ?>" style="<?php echo $is_active ? 'border-width: 2px;' : ''; ?>">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0 me-3">
                            <?php if ($icon_url): ?>
                                <img src="<?php echo $icon_url; ?>" class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center fw-bold" style="width: 50px; height: 50px;">
                                    <?php echo strtoupper(substr($data['Name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="overflow-hidden">
                            <h5 class="mb-0 fw-bold text-truncate" title="<?php echo htmlspecialchars($data['Name']); ?>">
                                <?php echo htmlspecialchars($data['Name']); ?>
                            </h5>
                            <small class="text-muted">v<?php echo htmlspecialchars($data['Version']); ?></small>
                        </div>
                    </div>
                    
                    <p class="text-secondary small mb-4 flex-grow-1">
                        <?php echo htmlspecialchars(mb_strimwidth($data['Description'], 0, 80, "...")); ?>
                    </p>

                    <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                        <?php if ($is_active): ?>
                             <span class="badge bg-success">აქტიური</span>
                             <a href="index.php?page=ok-plugins&action=deactivate&plugin=<?php echo urlencode($slug); ?>" class="btn btn-sm btn-outline-danger">გამორთვა</a>
                        <?php else: ?>
                             <span class="badge bg-secondary">გამორთული</span>
                             <div>
                                <a href="index.php?page=ok-plugins&action=delete&plugin=<?php echo urlencode($slug); ?>" class="btn btn-sm btn-light text-danger me-1" onclick="return confirm('ნამდვილად წავშალოთ?');"><i class="bi bi-trash"></i></a>
                                <a href="index.php?page=ok-plugins&action=activate&plugin=<?php echo urlencode($slug); ?>" class="btn btn-sm btn-primary">ჩართვა</a>
                             </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; else: ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-plug display-1 text-muted"></i>
                <p class="mt-3 text-muted">პლაგინები ვერ მოიძებნა.</p>
            </div>
        <?php endif; ?>
    </div>
    <?php
}