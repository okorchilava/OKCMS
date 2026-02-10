<?php
/**
 * OK Admin Sidebar (Core + Dynamic menus + Dynamic submenus)
 * Badge Position: Icon Top-Right Corner + Live Update ID Fix 🟢
 */

global $ok_admin_menu, $ok_admin_submenu, $ok_dynamic_menus, $ok_dynamic_submenus;

$current_page = $_GET['page'] ?? 'ok-main';
?>

<style>
    .ok-sidebar { background-color:#212529!important; border-right:1px solid #2c3034; min-height:100vh; padding-top:20px; }
    .ok-sidebar .nav-link { color:#adb5bd; font-weight:400; font-size:0.9rem; padding:10px 15px; display:flex; align-items:center; border-left:4px solid transparent; transition:all .2s ease; }
    .ok-sidebar .nav-link:hover { background-color:rgba(255,255,255,.05); color:#fff; }
    .ok-sidebar .nav-link.active { background-color:#343a40; color:#fff; font-weight:500; border-left-color:#0d6efd; }

    /* --- ICON WRAPPER & BADGE STYLES --- */
    .icon-wrapper {
        position: relative; /* აუცილებელია ბეიჯის პოზიციონირებისთვის */
        display: flex;
        align-items: center;
        justify-content: center;
        width: 24px;        /* ფიქსირებული სიგანე, რომ აიქონი ცენტრში იყოს */
        margin-right: 10px; /* დაშორება ტექსტთან */
    }

    .ok-sidebar .nav-link i { 
        font-size:1.1rem; 
        text-align:center; 
        color:#6c757d; 
        transition:color .2s; 
    }
    
    .ok-sidebar .nav-link.active i { color:#fff; }
    .ok-sidebar .nav-link:hover i { color:#ced4da; }

    /* ახალი ბეიჯის სტილი - აიქონის კუთხეში */
    .ok-menu-badge {
        position: absolute;
        top: -5px;          /* ზემოთ აწევა */
        right: -8px;        /* მარჯვნივ გაწევა */
        font-size: 0.6em;   /* უფრო პატარა შრიფტი */
        padding: 0.25em 0.4em;
        min-width: 15px;    /* მინიმალური ზომა რომ ლამაზად გამოჩნდეს */
        height: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        border: 2px solid #212529; /* მუქი ბორდერი (Cutout effect) */
        box-shadow: 0 0 0 1px #212529;
    }
    /* ----------------------------------- */

    .sub-menu-container { background-color:rgba(0,0,0,.2); padding:5px 0; }
    .sub-menu-link { font-size:.85rem; color:#999; display:block; padding:6px 15px 6px 52px; text-decoration:none; transition:all .15s; }
    .sub-menu-link:hover { color:#fff; text-decoration:none; }
    .sub-menu-link.active { color:#fff; font-weight:500; }

    .dropdown-arrow { margin-left:auto; font-size:.75rem; transition:transform .2s; opacity:.6; }
    .nav-link[aria-expanded="true"] .dropdown-arrow { transform:rotate(180deg); }

    .theme-separator {
        border-top:1px solid #373b3e;
        margin:15px 20px 10px 20px;
        padding-top:15px;
        font-size:.7rem;
        text-transform:uppercase;
        color:#6c757d;
        font-weight:bold;
        letter-spacing:1px;
    }
</style>

<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse ok-sidebar p-0">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">

            <?php
            // ─────────────────────────────────────────────────────────────
            // 1) CORE MENUS
            // ─────────────────────────────────────────────────────────────
            if (!empty($ok_admin_menu) && is_array($ok_admin_menu)) {

                uasort($ok_admin_menu, function($a, $b) {
                    return ($a['position'] ?? 99) <=> ($b['position'] ?? 99);
                });

                foreach ($ok_admin_menu as $item) {
                    if (function_exists('current_user_can') && !current_user_can($item['capability'])) continue;

                    $menu_slug      = (string)($item['menu_slug'] ?? '');
                    if ($menu_slug === '') continue;

                    $submenu_items = $ok_admin_submenu[$menu_slug] ?? [];
                    $has_submenu   = !empty($submenu_items);

                    $is_active_parent = ($current_page === $menu_slug);
                    $is_submenu_open  = false;

                    if ($has_submenu) {
                        foreach ($submenu_items as $sub) {
                            if ($current_page === ($sub['menu_slug'] ?? '')) {
                                $is_active_parent = true;
                                $is_submenu_open  = true;
                                break;
                            }
                        }
                    }

                    $active_class  = $is_active_parent ? 'active' : '';
                    $collapse_show = $is_submenu_open ? 'show' : '';
                    $aria_expanded = $is_submenu_open ? 'true' : 'false';

                    $icon_class = !empty($item['icon_class']) ? (string)$item['icon_class'] : 'bi bi-circle';
                    $menu_id    = 'menu-' . preg_replace('/[^a-zA-Z0-9]/', '', $menu_slug);

                    // --- BADGE LOGIC & ID ---
                    $badge_html = '';
                    $wrapper_id = ''; // ID for JS targeting

                    if ($menu_slug === 'ok-notifications') {
                        // 1. ვანიჭებთ უნიკალურ ID-ს, რომ JS-მა იპოვოს
                        $wrapper_id = 'id="ok-notif-live-wrapper"'; 

                        if (function_exists('ok_count_unread_notifications')) {
                            $nCount = ok_count_unread_notifications();
                            // ბეიჯს სულ ვქმნით, ოღონდ თუ 0-ია ვმალავთ (რომ JS-მა ადვილად იპოვოს DOM-ში)
                            $style = ($nCount > 0) ? '' : 'display:none;';
                            $badge_html = '<span class="badge bg-danger rounded-pill ok-menu-badge" style="'.$style.'">' . $nCount . '</span>';
                        }
                    }
                    // -------------------

                    ?>

                    <li class="nav-item">
                        <?php if ($has_submenu): ?>
                            <a class="nav-link <?php echo $active_class; ?>"
                               href="#"
                               data-bs-toggle="collapse"
                               data-bs-target="#<?php echo $menu_id; ?>"
                               aria-expanded="<?php echo $aria_expanded; ?>">
                                
                                <div class="icon-wrapper" <?php echo $wrapper_id; ?>>
                                    <i class="<?php echo htmlspecialchars($icon_class); ?>"></i>
                                    <?php echo $badge_html; ?>
                                </div>
                                
                                <span><?php echo htmlspecialchars((string)$item['menu_title']); ?></span>
                                
                                <i class="bi bi-chevron-down dropdown-arrow"></i>
                            </a>

                            <div class="collapse <?php echo $collapse_show; ?>" id="<?php echo $menu_id; ?>">
                                <div class="sub-menu-container">
                                    <?php foreach ($submenu_items as $sub):
                                        if (function_exists('current_user_can') && !current_user_can($sub['capability'])) continue;
                                        $sub_slug   = (string)$sub['menu_slug'];
                                        $sub_active = ($current_page === $sub_slug) ? 'active' : '';
                                    ?>
                                        <a class="sub-menu-link <?php echo $sub_active; ?>" href="index.php?page=<?php echo htmlspecialchars($sub_slug); ?>">
                                            <?php echo htmlspecialchars((string)$sub['menu_title']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <a class="nav-link <?php echo $active_class; ?>" href="index.php?page=<?php echo htmlspecialchars($menu_slug); ?>">
                                
                                <div class="icon-wrapper" <?php echo $wrapper_id; ?>>
                                    <i class="<?php echo htmlspecialchars($icon_class); ?>"></i>
                                    <?php echo $badge_html; ?>
                                </div>

                                <span><?php echo htmlspecialchars((string)$item['menu_title']); ?></span>
                            </a>
                        <?php endif; ?>
                    </li>

                    <?php
                }
            }
            ?>

            <?php
            // ─────────────────────────────────────────────────────────────
            // 2) DYNAMIC MENUS (Theme/Plugins)
            // ─────────────────────────────────────────────────────────────
            if (!empty($ok_dynamic_menus) && is_array($ok_dynamic_menus)):
            ?>
                <?php
                $dyn = $ok_dynamic_menus;
                uasort($dyn, function($a, $b) {
                    return ($a['position'] ?? 99) <=> ($b['position'] ?? 99);
                });

                foreach ($dyn as $slug => $menu):
                    if (function_exists('current_user_can') && !current_user_can($menu['capability'])) continue;

                    $slug = (string)$slug;
                    $submenu_items = $ok_dynamic_submenus[$slug] ?? [];
                    $has_submenu   = !empty($submenu_items);

                    $is_active_parent = ($current_page === $slug);
                    $is_submenu_open  = false;

                    if ($has_submenu) {
                        foreach ($submenu_items as $sub) {
                            if ($current_page === ($sub['menu_slug'] ?? '')) {
                                $is_active_parent = true;
                                $is_submenu_open  = true;
                                break;
                            }
                        }
                    }

                    $active_class  = $is_active_parent ? 'active' : '';
                    $collapse_show = $is_submenu_open ? 'show' : '';
                    $aria_expanded = $is_submenu_open ? 'true' : 'false';

                    $icon = (string)($menu['icon_class'] ?? '');
                    if (strpos($icon, 'dashicons') !== false) $icon = 'bi bi-palette2';
                    if ($icon === '') $icon = 'bi bi-gear';

                    $menu_id = 'dyn-menu-' . preg_replace('/[^a-zA-Z0-9]/', '', $slug);

                    // --- BADGE LOGIC & ID (Dynamic Menus) ---
                    $badge_html = '';
                    $wrapper_id = '';

                    if ($slug === 'ok-notifications') {
                        // 1. ვანიჭებთ უნიკალურ ID-ს
                        $wrapper_id = 'id="ok-notif-live-wrapper"';

                        if (function_exists('ok_count_unread_notifications')) {
                             $nCount = ok_count_unread_notifications();
                             // ბეიჯს სულ ვქმნით, თუ 0-ია ვმალავთ
                             $style = ($nCount > 0) ? '' : 'display:none;';
                             $badge_html = '<span class="badge bg-danger rounded-pill ok-menu-badge" style="'.$style.'">' . $nCount . '</span>';
                        }
                    }
                    // -----------------------------------
                ?>

                    <li class="nav-item">
                        <?php if ($has_submenu): ?>
                            <a class="nav-link <?php echo $active_class; ?>"
                               href="#"
                               data-bs-toggle="collapse"
                               data-bs-target="#<?php echo $menu_id; ?>"
                               aria-expanded="<?php echo $aria_expanded; ?>">
                                
                                <div class="icon-wrapper" <?php echo $wrapper_id; ?>>
                                    <i class="<?php echo htmlspecialchars($icon); ?>"></i>
                                    <?php echo $badge_html; ?>
                                </div>

                                <span><?php echo htmlspecialchars((string)$menu['menu_title']); ?></span>
                                
                                <i class="bi bi-chevron-down dropdown-arrow"></i>
                            </a>

                            <div class="collapse <?php echo $collapse_show; ?>" id="<?php echo $menu_id; ?>">
                                <div class="sub-menu-container">
                                    <?php foreach ($submenu_items as $sub):
                                        if (function_exists('current_user_can') && !current_user_can($sub['capability'])) continue;
                                        $sub_slug   = (string)$sub['menu_slug'];
                                        $sub_active = ($current_page === $sub_slug) ? 'active' : '';
                                    ?>
                                        <a class="sub-menu-link <?php echo $sub_active; ?>" href="index.php?page=<?php echo htmlspecialchars($sub_slug); ?>">
                                            <?php echo htmlspecialchars((string)$sub['menu_title']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <a class="nav-link <?php echo $active_class; ?>" href="index.php?page=<?php echo htmlspecialchars($slug); ?>">
                                
                                <div class="icon-wrapper" <?php echo $wrapper_id; ?>>
                                    <i class="<?php echo htmlspecialchars($icon); ?>"></i>
                                    <?php echo $badge_html; ?>
                                </div>

                                <span><?php echo htmlspecialchars((string)$menu['menu_title']); ?></span>
                            </a>
                        <?php endif; ?>
                    </li>

                <?php endforeach; ?>
            <?php endif; ?>

        </ul>
    </div>
</nav>