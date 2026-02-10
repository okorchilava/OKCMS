<?php
/**
 * Dr. Paata - Theme Options & Setup
 * Landing Page Data Management
 */

// =============================================================================
// 1) BASIC SETUP
// =============================================================================
if (function_exists('ok_register_sidebar')) {
    ok_register_sidebar(['id' => 'sidebar-main', 'name' => 'მთავარი საიდბარი']);
    ok_register_sidebar(['id' => 'footer-1', 'name' => 'Footer 1']);
}

if (function_exists('ok_register_theme_menus')) {
    ok_register_theme_menus([
        'header-menu' => 'მთავარი მენიუ (Header)',
        'footer-menu' => 'ფუთერის მენიუ (Footer)',
    ]);
}

// =============================================================================
// 2) THEME OPTIONS PAGE
// =============================================================================
if (function_exists('add_ok_action')) {
    add_ok_action('admin_menu', 'drpaata_register_options_page');
}

function drpaata_register_options_page(): void {
    if (!function_exists('add_menu_page')) return;

    add_menu_page(
        'Dr. Paata - პარამეტრები',
        'თემის პარამეტრები',
        'manage_options',
        'drpaata-options',
        'drpaata_render_options',
        'bi bi-sliders'
    );
}

function drpaata_render_options(): void {

    // -------------------------------------------------------------------------
    // A) SAVE DATA
    // -------------------------------------------------------------------------
    if (isset($_POST['save_drpaata'])) {

        // Hero Section
        update_ok_option('dr_hero_title',  (string)($_POST['dr_hero_title'] ?? ''));
        update_ok_option('dr_hero_desc',   (string)($_POST['dr_hero_desc']  ?? ''));
        update_ok_option('site_logo',      (string)($_POST['site_logo']     ?? ''));
        update_ok_option('dr_btn1_text',   (string)($_POST['dr_btn1_text']  ?? ''));
        update_ok_option('dr_btn1_url',    (string)($_POST['dr_btn1_url']   ?? ''));
        update_ok_option('hero_bg_image',  (string)($_POST['hero_bg_image'] ?? ''));

        // Philosophy Section
        update_ok_option('philo_scroll_text', (string)($_POST['philo_scroll_text'] ?? ''));
        update_ok_option('philo_title',       (string)($_POST['philo_title']       ?? ''));
        update_ok_option('philo_desc',        (string)($_POST['philo_desc']        ?? ''));

        // About Section
        update_ok_option('about_label',       (string)($_POST['about_label']       ?? ''));
        update_ok_option('about_title',       (string)($_POST['about_title']       ?? ''));
        update_ok_option('about_desc',        (string)($_POST['about_desc']        ?? ''));
        update_ok_option('about_section_img', (string)($_POST['about_section_img'] ?? ''));

        // Stats
        update_ok_option('stat1_num',  (string)($_POST['stat1_num']  ?? ''));
        update_ok_option('stat1_text', (string)($_POST['stat1_text'] ?? ''));
        update_ok_option('stat2_num',  (string)($_POST['stat2_num']  ?? ''));
        update_ok_option('stat2_text', (string)($_POST['stat2_text'] ?? ''));

        // Accordion (Education)
        update_ok_option('acc1_title',   (string)($_POST['acc1_title']   ?? ''));
        update_ok_option('acc1_content', (string)($_POST['acc1_content'] ?? ''));
        update_ok_option('acc2_title',   (string)($_POST['acc2_title']   ?? ''));
        update_ok_option('acc2_content', (string)($_POST['acc2_content'] ?? ''));

        // Safety (Video)
        update_ok_option('video_bg_id',     (string)($_POST['video_bg_id']     ?? ''));
        update_ok_option('safety_title',    (string)($_POST['safety_title']    ?? ''));
        update_ok_option('safety_subtitle', (string)($_POST['safety_subtitle'] ?? ''));
        update_ok_option('safety_desc',     (string)($_POST['safety_desc']     ?? ''));
        update_ok_option('safe_list_1',     (string)($_POST['safe_list_1']     ?? ''));
        update_ok_option('safe_list_2',     (string)($_POST['safe_list_2']     ?? ''));
        update_ok_option('safe_list_3',     (string)($_POST['safe_list_3']     ?? ''));

        // Contact Section & Socials
        update_ok_option('contact_label', (string)($_POST['contact_label'] ?? ''));
        update_ok_option('contact_title', (string)($_POST['contact_title'] ?? ''));
        
        update_ok_option('lbl_phone',   (string)($_POST['lbl_phone']   ?? ''));
        update_ok_option('dr_phone',    (string)($_POST['dr_phone']    ?? ''));
        update_ok_option('lbl_email',   (string)($_POST['lbl_email']   ?? ''));
        update_ok_option('dr_email',    (string)($_POST['dr_email']    ?? ''));
        update_ok_option('lbl_address', (string)($_POST['lbl_address'] ?? ''));
        update_ok_option('dr_address',  (string)($_POST['dr_address']  ?? ''));

        // Social Networks (NEW)
        update_ok_option('dr_social_fb',       (string)($_POST['dr_social_fb']       ?? ''));
        update_ok_option('dr_social_insta',    (string)($_POST['dr_social_insta']    ?? ''));
        update_ok_option('dr_social_linkedin', (string)($_POST['dr_social_linkedin'] ?? ''));

        // Form Placeholders
        update_ok_option('form_ph_name',  (string)($_POST['form_ph_name']  ?? ''));
        update_ok_option('form_ph_phone', (string)($_POST['form_ph_phone'] ?? ''));
        update_ok_option('form_ph_msg',   (string)($_POST['form_ph_msg']   ?? ''));
        update_ok_option('form_btn_text', (string)($_POST['form_btn_text'] ?? ''));

        echo '<div class="alert alert-success m-4 border-0 shadow-sm"><i class="bi bi-check-circle me-2"></i> მონაცემები შენახულია!</div>';
    }

    // -------------------------------------------------------------------------
    // B) LOAD DATA FOR FORM
    // -------------------------------------------------------------------------
    ?>
    <div class="container-fluid p-4">
        <h2 class="mb-4 fw-bold text-dark">ლენდინგის მართვა</h2>

        <form method="post" class="row g-4">

            <div class="col-12">
                <div class="bg-white p-4 shadow-sm rounded-4 border">
                    <h5 class="text-primary fw-bold border-bottom pb-2 mb-3">1. მთავარი (Hero)</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold">მთავარი სათაური</label>
                            <input type="text" name="dr_hero_title" class="form-control" value="<?php echo htmlspecialchars(get_ok_option('dr_hero_title', 'პაატა<br>ჟორჟოლიანი')); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold">პროფესია / აღწერა</label>
                            <input type="text" name="dr_hero_desc" class="form-control" value="<?php echo htmlspecialchars(get_ok_option('dr_hero_desc', 'მეან-გინეკოლოგი')); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold">ლოგოს URL</label>
                            <input type="text" name="site_logo" class="form-control" value="<?php echo htmlspecialchars(get_ok_option('site_logo', '')); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold">ფონის სურათის URL (Hero BG)</label>
                            <input type="text" name="hero_bg_image" class="form-control" value="<?php echo htmlspecialchars(get_ok_option('hero_bg_image', '')); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold">ღილაკის ტექსტი</label>
                            <input type="text" name="dr_btn1_text" class="form-control" value="<?php echo htmlspecialchars(get_ok_option('dr_btn1_text', 'კონსულტაცია')); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold">ღილაკის ლინკი</label>
                            <input type="text" name="dr_btn1_url" class="form-control" value="<?php echo htmlspecialchars(get_ok_option('dr_btn1_url', '#contact')); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="bg-white p-4 shadow-sm rounded-4 border">
                    <h5 class="text-primary fw-bold border-bottom pb-2 mb-3">2. ფილოსოფია (მოძრავი ტექსტი)</h5>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="small text-muted fw-bold">მოძრავი ტექსტი (Scroll)</label>
                            <input type="text" name="philo_scroll_text" class="form-control" value="<?php echo htmlspecialchars(get_ok_option('philo_scroll_text', 'NEW LIFE — HARMONY...')); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="small text-muted fw-bold">სათაური</label>
                            <input type="text" name="philo_title" class="form-control" value="<?php echo htmlspecialchars(get_ok_option('philo_title', 'ზრუნვა და ნდობა')); ?>">
                        </div>
                        <div class="col-md-8">
                            <label class="small text-muted fw-bold">აღწერა</label>
                            <textarea name="philo_desc" class="form-control" rows="2"><?php echo htmlspecialchars(get_ok_option('philo_desc', '')); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="bg-white p-4 shadow-sm rounded-4 border">
                    <h5 class="text-primary fw-bold border-bottom pb-2 mb-3">3. ჩემ შესახებ და სტატისტიკა</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="small text-muted fw-bold">ლეიბლი</label>
                            <input type="text" name="about_label" class="form-control" value="<?php echo htmlspecialchars(get_ok_option('about_label', 'ჩემ შესახებ')); ?>">
                        </div>
                        <div class="col-md-8">
                            <label class="small text-muted fw-bold">სათაური</label>
                            <input type="text" name="about_title" class="form-control" value="<?php echo htmlspecialchars(get_ok_option('about_title', 'გამოცდილება...')); ?>">
                        </div>
                        <div class="col-12">
                            <label class="small text-muted fw-bold">აღწერა</label>
                            <textarea name="about_desc" class="form-control" rows="2"><?php echo htmlspecialchars(get_ok_option('about_desc', '')); ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="small text-muted fw-bold">სურათის URL (About Section)</label>
                            <input type="text" name="about_section_img" class="form-control" value="<?php echo htmlspecialchars(get_ok_option('about_section_img', '')); ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label class="small text-muted fw-bold">სტატისტიკა 1 (რიცხვი)</label>
                            <input type="text" name="stat1_num" class="form-control" value="<?php echo htmlspecialchars(get_ok_option('stat1_num', '15')); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="small text-muted fw-bold">სტატისტიკა 1 (ტექსტი)</label>
                            <input type="text" name="stat1_text" class="form-control" value="<?php echo htmlspecialchars(get_ok_option('stat1_text', 'წლიანი გამოცდილება')); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="small text-muted fw-bold">სტატისტიკა 2 (რიცხვი)</label>
                            <input type="text" name="stat2_num" class="form-control" value="<?php echo htmlspecialchars(get_ok_option('stat2_num', '4000')); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="small text-muted fw-bold">სტატისტიკა 2 (ტექსტი)</label>
                            <input type="text" name="stat2_text" class="form-control" value="<?php echo htmlspecialchars(get_ok_option('stat2_text', 'ოპერაცია')); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="bg-white p-4 shadow-sm rounded-4 border">
                    <h5 class="text-primary fw-bold border-bottom pb-2 mb-3">4. განათლება (Accordion)</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold">ბლოკი 1 სათაური</label>
                            <input type="text" name="acc1_title" class="form-control" value="<?php echo htmlspecialchars(get_ok_option('acc1_title', 'განათლება')); ?>">
                            <label class="small text-muted fw-bold mt-2">ბლოკი 1 ტექსტი</label>
                            <textarea name="acc1_content" class="form-control" rows="3"><?php echo htmlspecialchars(get_ok_option('acc1_content', '')); ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold">ბლოკი 2 სათაური</label>
                            <input type="text" name="acc2_title" class="form-control" value="<?php echo htmlspecialchars(get_ok_option('acc2_title', 'ასოციაციები')); ?>">
                            <label class="small text-muted fw-bold mt-2">ბლოკი 2 ტექსტი</label>
                            <textarea name="acc2_content" class="form-control" rows="3"><?php echo htmlspecialchars(get_ok_option('acc2_content', '')); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="bg-white p-4 shadow-sm rounded-4 border">
                    <h5 class="text-primary fw-bold border-bottom pb-2 mb-3">5. უსაფრთხოება და ვიდეო</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="small text-muted fw-bold">YouTube Video ID</label>
                            <input type="text" name="video_bg_id" class="form-control" value="<?php echo htmlspecialchars(get_ok_option('video_bg_id', '9xwazD5SyVg')); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="small text-muted fw-bold">სათაური</label>
                            <input type="text" name="safety_title" class="form-control" value="<?php echo htmlspecialchars(get_ok_option('safety_title', 'უსაფრთხოება')); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="small text-muted fw-bold">ქვესათაური</label>
                            <input type="text" name="safety_subtitle" class="form-control" value="<?php echo htmlspecialchars(get_ok_option('safety_subtitle', '')); ?>">
                        </div>
                        <div class="col-12">
                            <label class="small text-muted fw-bold">აღწერა</label>
                            <textarea name="safety_desc" class="form-control" rows="2"><?php echo htmlspecialchars(get_ok_option('safety_desc', '')); ?></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="small text-muted fw-bold">სია 1</label>
                            <input type="text" name="safe_list_1" class="form-control" value="<?php echo htmlspecialchars(get_ok_option('safe_list_1', 'საერთაშორისო სტანდარტები')); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="small text-muted fw-bold">სია 2</label>
                            <input type="text" name="safe_list_2" class="form-control" value="<?php echo htmlspecialchars(get_ok_option('safe_list_2', 'უახლესი აპარატურა')); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="small text-muted fw-bold">სია 3</label>
                            <input type="text" name="safe_list_3" class="form-control" value="<?php echo htmlspecialchars(get_ok_option('safe_list_3', 'ემპათია და ზრუნვა')); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="bg-white p-4 shadow-sm rounded-4 border">
                    <h5 class="text-primary fw-bold border-bottom pb-2 mb-3">6. კონტაქტი და ფორმა</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold">ლეიბლი</label>
                            <input type="text" name="contact_label" class="form-control" value="<?php echo htmlspecialchars(get_ok_option('contact_label', 'კონტაქტი')); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold">სათაური</label>
                            <input type="text" name="contact_title" class="form-control" value="<?php echo htmlspecialchars(get_ok_option('contact_title', 'დაჯავშნეთ ვიზიტი')); ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="small text-muted fw-bold">ტელეფონის ლეიბლი</label>
                            <input type="text" name="lbl_phone" class="form-control mb-1" value="<?php echo htmlspecialchars(get_ok_option('lbl_phone', 'ტელეფონი')); ?>">
                            <input type="text" name="dr_phone" class="form-control" placeholder="მნიშვნელობა" value="<?php echo htmlspecialchars(get_ok_option('dr_phone', '')); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="small text-muted fw-bold">ელ-ფოსტის ლეიბლი</label>
                            <input type="text" name="lbl_email" class="form-control mb-1" value="<?php echo htmlspecialchars(get_ok_option('lbl_email', 'ელ-ფოსტა')); ?>">
                            <input type="text" name="dr_email" class="form-control" placeholder="მნიშვნელობა" value="<?php echo htmlspecialchars(get_ok_option('dr_email', '')); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="small text-muted fw-bold">მისამართის ლეიბლი</label>
                            <input type="text" name="lbl_address" class="form-control mb-1" value="<?php echo htmlspecialchars(get_ok_option('lbl_address', 'მისამართი')); ?>">
                            <input type="text" name="dr_address" class="form-control" placeholder="მნიშვნელობა" value="<?php echo htmlspecialchars(get_ok_option('dr_address', '')); ?>">
                        </div>

                        <div class="col-12 mt-4"><h6 class="fw-bold border-bottom pb-2 text-primary">სოციალური ქსელები</h6></div>
                        <div class="col-md-4">
                            <label class="small text-muted fw-bold"><i class="bi bi-facebook me-1"></i> Facebook ლინკი</label>
                            <input type="text" name="dr_social_fb" class="form-control" value="<?php echo htmlspecialchars(get_ok_option('dr_social_fb', '')); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="small text-muted fw-bold"><i class="bi bi-instagram me-1"></i> Instagram ლინკი</label>
                            <input type="text" name="dr_social_insta" class="form-control" value="<?php echo htmlspecialchars(get_ok_option('dr_social_insta', '')); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="small text-muted fw-bold"><i class="bi bi-linkedin me-1"></i> LinkedIn ლინკი</label>
                            <input type="text" name="dr_social_linkedin" class="form-control" value="<?php echo htmlspecialchars(get_ok_option('dr_social_linkedin', '')); ?>">
                        </div>

                        <div class="col-12 mt-4"><h6 class="fw-bold border-bottom pb-2">ფორმის ტექსტები</h6></div>
                        <div class="col-md-3">
                            <label class="small text-muted fw-bold">სახელი (Placeholder)</label>
                            <input type="text" name="form_ph_name" class="form-control" value="<?php echo htmlspecialchars(get_ok_option('form_ph_name', '')); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="small text-muted fw-bold">ტელეფონი (Placeholder)</label>
                            <input type="text" name="form_ph_phone" class="form-control" value="<?php echo htmlspecialchars(get_ok_option('form_ph_phone', '')); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="small text-muted fw-bold">შეტყობინება (Placeholder)</label>
                            <input type="text" name="form_ph_msg" class="form-control" value="<?php echo htmlspecialchars(get_ok_option('form_ph_msg', '')); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="small text-muted fw-bold">ღილაკის ტექსტი</label>
                            <input type="text" name="form_btn_text" class="form-control" value="<?php echo htmlspecialchars(get_ok_option('form_btn_text', 'გაგზავნა')); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 mt-4 mb-5">
                <button type="submit" name="save_drpaata" class="btn btn-primary btn-lg px-5 shadow fw-bold">
                    <i class="bi bi-save me-2"></i> შენახვა
                </button>
            </div>

        </form>
    </div>
    <?php
}