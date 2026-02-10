<?php
// =============================================================================
// 1) SIDEBARS
// =============================================================================
if (function_exists('ok_register_sidebar')) {

    ok_register_sidebar([
        'id'          => 'sidebar-main',
        'name'        => 'მთავარი საიდბარი',
        'description' => 'გამოჩნდება პოსტების და გვერდების მარჯვნივ.'
    ]);

    ok_register_sidebar(['id' => 'footer-1', 'name' => 'Footer სვეტი 1', 'description' => 'ფუთერის მარცხენა მხარე.']);
    ok_register_sidebar(['id' => 'footer-2', 'name' => 'Footer სვეტი 2', 'description' => 'ფუთერის შუა მხარე.']);
    ok_register_sidebar(['id' => 'footer-3', 'name' => 'Footer სვეტი 3', 'description' => 'ფუთერის შუა მხარე.']);
    ok_register_sidebar(['id' => 'footer-4', 'name' => 'Footer სვეტი 4', 'description' => 'ფუთერის მარჯვენა მხარე.']);

    ok_register_sidebar(['id' => 'landing-widget-1', 'name' => 'Landing: Sector 1']);
    ok_register_sidebar(['id' => 'landing-widget-2', 'name' => 'Landing: Sector 2']);
    ok_register_sidebar(['id' => 'landing-widget-3', 'name' => 'Landing: Sector 3']);
    ok_register_sidebar(['id' => 'landing-widget-4', 'name' => 'Landing: Full Width Area']);
}


// =============================================================================
// 2) THEME MENUS
// =============================================================================
if (function_exists('ok_register_theme_menus')) {
    ok_register_theme_menus([
        'header-menu'  => 'მთავარი მენიუ (Header)',
        'footer-menu'  => 'ფუთერის მენიუ (Footer)',
        'sidebar-menu' => 'საიდბარის მენიუ'
    ]);
}


// =============================================================================
// 3) THEME OPTIONS PAGE (Admin)
// =============================================================================
if (function_exists('add_ok_action')) {
    add_ok_action('admin_menu', 'drpaata_register_options_page');
}

function drpaata_register_options_page(): void
{
    if (!function_exists('add_menu_page')) return;

    add_menu_page(
        'Dr. Paata პარამეტრები',
        'თემის პარამეტრები',
        'manage_options',
        'drpaata-options',
        'drpaata_render_options',
        'bi bi-palette2'
    );
}

function drpaata_render_options(): void
{
    // Save
    if (isset($_POST['save_drpaata'])) {

        // Hero
        update_ok_option('dr_hero_title', (string)($_POST['dr_hero_title'] ?? ''));
        update_ok_option('dr_hero_desc',  (string)($_POST['dr_hero_desc'] ?? ''));
        update_ok_option('dr_hero_badge', (string)($_POST['dr_hero_badge'] ?? ''));

        // Buttons
        update_ok_option('dr_btn1_text', (string)($_POST['dr_btn1_text'] ?? ''));
        update_ok_option('dr_btn1_url',  (string)($_POST['dr_btn1_url'] ?? ''));
        update_ok_option('dr_btn2_text', (string)($_POST['dr_btn2_text'] ?? ''));
        update_ok_option('dr_btn2_url',  (string)($_POST['dr_btn2_url'] ?? ''));

        // Contact
        update_ok_option('dr_phone',   (string)($_POST['dr_phone'] ?? ''));
        update_ok_option('dr_email',   (string)($_POST['dr_email'] ?? ''));
        update_ok_option('dr_address', (string)($_POST['dr_address'] ?? ''));

        // Hours
        update_ok_option('dr_hours_week', (string)($_POST['dr_hours_week'] ?? ''));
        update_ok_option('dr_hours_sat',  (string)($_POST['dr_hours_sat'] ?? ''));
        update_ok_option('dr_hours_sun',  (string)($_POST['dr_hours_sun'] ?? ''));

        // Video & Blog & Map
        update_ok_option('dr_video_id',      (string)($_POST['dr_video_id'] ?? ''));
        update_ok_option('dr_blog_title',    (string)($_POST['dr_blog_title'] ?? ''));
        update_ok_option('dr_blog_btn_text', (string)($_POST['dr_blog_btn_text'] ?? ''));
        update_ok_option('dr_blog_btn_url',  (string)($_POST['dr_blog_btn_url'] ?? ''));
        update_ok_option('dr_map_lat',       (string)($_POST['dr_map_lat'] ?? ''));
        update_ok_option('dr_map_lng',       (string)($_POST['dr_map_lng'] ?? ''));

        echo '<div class="alert alert-success m-4 border-0 shadow-sm"><i class="bi bi-check-circle me-2"></i> მონაცემები განახლდა!</div>';
    }

    // Load
    $hero_title = get_ok_option('dr_hero_title', 'იზრუნეთ თქვენს ჯანმრთელობაზე...');
    $hero_desc  = get_ok_option('dr_hero_desc',  'თანამედროვე მიდგომები...');
    $hero_badge = get_ok_option('dr_hero_badge', '15 წლიანი გამოცდილება');

    $btn1_text = get_ok_option('dr_btn1_text', 'ვიზიტის დაჯავშნა');
    $btn1_url  = get_ok_option('dr_btn1_url',  '/contact');
    $btn2_text = get_ok_option('dr_btn2_text', 'სერვისები');
    $btn2_url  = get_ok_option('dr_btn2_url',  '#services');

    $phone   = get_ok_option('dr_phone',   '+995 555 00 00 00');
    $email   = get_ok_option('dr_email',   'info@drpaata.ge');
    $address = get_ok_option('dr_address', 'თბილისი, ჭავჭავაძის 1');

    $w = get_ok_option('dr_hours_week', '10:00 - 18:00');
    $s = get_ok_option('dr_hours_sat',  '10:00 - 14:00');
    $u = get_ok_option('dr_hours_sun',  'დაკეტილია');

    $video_id      = get_ok_option('dr_video_id',      'OSVA1FIQDOQ');
    $blog_title    = get_ok_option('dr_blog_title',    'ბოლო ჩანაწერები');
    $blog_btn_text = get_ok_option('dr_blog_btn_text', 'ყველა სიახლე');
    $blog_btn_url  = get_ok_option('dr_blog_btn_url',  '/blog');

    $map_lat = get_ok_option('dr_map_lat', '41.779565');
    $map_lng = get_ok_option('dr_map_lng', '44.773246');
    ?>
    <div class="container-fluid p-4">
        <h2 class="mb-4 fw-bold text-dark"><i class="bi bi-palette2 me-2 text-primary"></i> Dr. Paata - თემის პარამეტრები</h2>

        <form method="post" class="row g-4">
            <div class="col-12">
                <div class="bg-white p-4 shadow-sm rounded-4 border">
                    <h5 class="border-bottom pb-3 mb-4 fw-bold text-primary">მთავარი სექცია (Hero)</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">ბეიჯი</label>
                            <input type="text" name="dr_hero_badge" class="form-control" value="<?php echo htmlspecialchars((string)$hero_badge, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold small text-muted">მთავარი სათაური</label>
                            <input type="text" name="dr_hero_title" class="form-control" value="<?php echo htmlspecialchars((string)$hero_title, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small text-muted">აღწერა</label>
                            <textarea name="dr_hero_desc" class="form-control" rows="2"><?php echo htmlspecialchars((string)$hero_desc, ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">ღილაკი 1 ტექსტი</label>
                            <input type="text" name="dr_btn1_text" class="form-control" value="<?php echo htmlspecialchars((string)$btn1_text, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">ღილაკი 1 ლინკი</label>
                            <input type="text" name="dr_btn1_url" class="form-control" value="<?php echo htmlspecialchars((string)$btn1_url, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">ღილაკი 2 ტექსტი</label>
                            <input type="text" name="dr_btn2_text" class="form-control" value="<?php echo htmlspecialchars((string)$btn2_text, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">ღილაკი 2 ლინკი</label>
                            <input type="text" name="dr_btn2_url" class="form-control" value="<?php echo htmlspecialchars((string)$btn2_url, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="bg-white p-4 shadow-sm rounded-4 border">
                    <h5 class="border-bottom pb-3 mb-4 fw-bold text-primary">ვიდეო, ბლოგი და რუკა</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">YouTube Video ID</label>
                            <input type="text" name="dr_video_id" class="form-control" value="<?php echo htmlspecialchars((string)$video_id, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">რუკა: განედი (Lat)</label>
                            <input type="text" name="dr_map_lat" class="form-control" value="<?php echo htmlspecialchars((string)$map_lat, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">რუკა: გრძედი (Lng)</label>
                            <input type="text" name="dr_map_lng" class="form-control" value="<?php echo htmlspecialchars((string)$map_lng, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">ბლოგის სათაური</label>
                            <input type="text" name="dr_blog_title" class="form-control" value="<?php echo htmlspecialchars((string)$blog_title, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">ბლოგის ღილაკის ტექსტი</label>
                            <input type="text" name="dr_blog_btn_text" class="form-control" value="<?php echo htmlspecialchars((string)$blog_btn_text, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">ბლოგის ღილაკის ლინკი</label>
                            <input type="text" name="dr_blog_btn_url" class="form-control" value="<?php echo htmlspecialchars((string)$blog_btn_url, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="bg-white p-4 shadow-sm rounded-4 border h-100">
                    <h5 class="border-bottom pb-3 mb-4 fw-bold text-primary">საკონტაქტო ინფორმაცია</h5>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-muted">ტელეფონი</label>
                        <input type="text" name="dr_phone" class="form-control" value="<?php echo htmlspecialchars((string)$phone, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-muted">ელ-ფოსტა</label>
                        <input type="text" name="dr_email" class="form-control" value="<?php echo htmlspecialchars((string)$email, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-muted">მისამართი</label>
                        <input type="text" name="dr_address" class="form-control" value="<?php echo htmlspecialchars((string)$address, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="bg-white p-4 shadow-sm rounded-4 border h-100">
                    <h5 class="border-bottom pb-3 mb-4 fw-bold text-primary">სამუშაო საათები</h5>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-muted">ორშ-პარ</label>
                        <input type="text" name="dr_hours_week" class="form-control" value="<?php echo htmlspecialchars((string)$w, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-muted">შაბათი</label>
                        <input type="text" name="dr_hours_sat" class="form-control" value="<?php echo htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-muted">კვირა</label>
                        <input type="text" name="dr_hours_sun" class="form-control" value="<?php echo htmlspecialchars((string)$u, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>
            </div>

            <div class="col-12">
                <button type="submit" name="save_drpaata" class="btn btn-primary btn-lg px-5 shadow-sm fw-bold">
                    <i class="bi bi-save me-2"></i> შენახვა
                </button>
            </div>
        </form>
    </div>
    <?php
}

// =============================================================================
// 4) DrPaata Widgets (NO color option; theme controls colors via CSS)
// =============================================================================
if (function_exists('ok_register_widget')) {

    // 1) CONTACT WIDGET
    ok_register_widget('drpaata_contact', [
        'name' => 'Dr. Paata - კონტაქტი',
        'icon' => 'bi-telephone',
        'desc' => 'საკონტაქტო ინფორმაცია.',
        'admin_template' => '
            <div class="mb-3">
                <label class="small fw-bold text-muted">სათაური:</label>
                <input type="text" name="__NAME_PREFIX__[title]" class="form-control form-control-sm" value="__TITLE__" placeholder="კონტაქტი">
            </div>
        ',
        'render_callback' => function ($data) {
            $title = !empty($data['title']) ? (string)$data['title'] : 'კონტაქტი';

            $phone   = (string)get_ok_option('dr_phone', '+995 555 00 00 00');
            $email   = (string)get_ok_option('dr_email', 'info@drpaata.ge');
            $address = (string)get_ok_option('dr_address', 'თბილისი, ჭავჭავაძის 1');

            echo '<div class="widget-box ok-widget ok-widget-contact mb-4">';
            echo '<h5 class="mb-3 fw-bold ok-widget-title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h5>';
            echo '<ul class="list-unstyled mb-0 ok-widget-text">';

            if ($address !== '') {
                echo '<li class="mb-3 d-flex align-items-start">';
                echo '<i class="bi bi-geo-alt me-3 ok-widget-icon"></i>';
                echo '<span>' . htmlspecialchars($address, ENT_QUOTES, 'UTF-8') . '</span>';
                echo '</li>';
            }

            if ($phone !== '') {
                echo '<li class="mb-3 d-flex align-items-start">';
                echo '<i class="bi bi-telephone me-3 ok-widget-icon"></i>';
                echo '<span>' . htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') . '</span>';
                echo '</li>';
            }

            if ($email !== '') {
                echo '<li class="mb-0 d-flex align-items-start">';
                echo '<i class="bi bi-envelope me-3 ok-widget-icon"></i>';
                echo '<span>' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</span>';
                echo '</li>';
            }

            echo '</ul>';
            echo '</div>';
        }
    ]);

    // 2) HOURS WIDGET
    ok_register_widget('drpaata_hours', [
        'name' => 'Dr. Paata - სამუშაო საათები',
        'icon' => 'bi-clock-history',
        'desc' => 'სამუშაო საათების ჩვენება.',
        'admin_template' => '
            <div class="mb-3">
                <label class="small fw-bold text-muted">სათაური:</label>
                <input type="text" name="__NAME_PREFIX__[title]" class="form-control form-control-sm" value="__TITLE__" placeholder="სამუშაო საათები">
            </div>
        ',
        'render_callback' => function ($data) {
            $title = !empty($data['title']) ? (string)$data['title'] : 'სამუშაო საათები';

            $w = (string)get_ok_option('dr_hours_week', '10:00 - 18:00');
            $s = (string)get_ok_option('dr_hours_sat',  '10:00 - 14:00');
            $u = (string)get_ok_option('dr_hours_sun',  'დაკეტილია');

            echo '<div class="widget-box ok-widget ok-widget-hours mb-4">';
            echo '<h5 class="mb-3 fw-bold ok-widget-title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h5>';
            echo '<ul class="list-unstyled mb-0 small ok-widget-text">';

            echo '<li class="d-flex justify-content-between mb-2 py-2 ok-widget-row">';
            echo '<span>ორშ-პარ:</span><strong class="ok-widget-strong">' . htmlspecialchars($w, ENT_QUOTES, 'UTF-8') . '</strong>';
            echo '</li>';

            echo '<li class="d-flex justify-content-between mb-2 py-2 ok-widget-row">';
            echo '<span>შაბათი:</span><strong class="ok-widget-strong">' . htmlspecialchars($s, ENT_QUOTES, 'UTF-8') . '</strong>';
            echo '</li>';

            echo '<li class="d-flex justify-content-between py-2">';
            echo '<span>კვირა:</span><strong class="ok-widget-closed">' . htmlspecialchars($u, ENT_QUOTES, 'UTF-8') . '</strong>';
            echo '</li>';

            echo '</ul>';
            echo '</div>';
        }
    ]);
}

