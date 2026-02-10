<?php
/**
 * Template Name: Medical Landing – Gynecology (Final Fixed Fonts)
 */

global $ok_query, $ok_db;

$post = $ok_query['object'] ?? null;
if (!$post) { echo "Page not found"; exit; }

// =========================================================
//                  OPTION VARIABLES
// =========================================================

// --- Hero Section ---
$hero_title     = get_ok_option('dr_hero_title', 'პაატა<br>ჟორჟოლიანი');
$hero_desc      = get_ok_option('dr_hero_desc', 'მეან-გინეკოლოგი');
$site_logo      = get_ok_option('site_logo', ''); 
$btn1_text      = get_ok_option('dr_btn1_text', 'კონსულტაცია');
$btn1_url       = get_ok_option('dr_btn1_url', '#contact'); 

// --- Philosophy Section ---
$philo_scroll   = get_ok_option('philo_scroll_text', 'NEW LIFE — HARMONY — CARE — TRUST —');
$philo_title    = get_ok_option('philo_title', 'ზრუნვა და ნდობა');
$philo_desc     = get_ok_option('philo_desc', 'ქალის ჯანმრთელობა ჩვენი პრიორიტეტია. სიმშვიდე, კომფორტი და მაღალი პროფესიონალიზმი.');

// --- About Section ---
$about_label    = get_ok_option('about_label', 'ჩემ შესახებ');
$about_title    = get_ok_option('about_title', 'გამოცდილება და<br>განათლება');
$about_desc     = get_ok_option('about_desc', 'ჩვენი მიზანია შევქმნათ უსაფრთხო და კომფორტული გარემო. მრავალწლიანი გამოცდილება გვაძლევს საშუალებას, გავუმკლავდეთ ურთულეს გამოწვევებს.');

// Stats
$stat1_num      = get_ok_option('stat1_num', '15');
$stat1_text     = get_ok_option('stat1_text', 'წლიანი გამოცდილება');
$stat2_num      = get_ok_option('stat2_num', '4000');
$stat2_text     = get_ok_option('stat2_text', 'ოპერაცია');

// Accordion
$acc1_title     = get_ok_option('acc1_title', 'განათლება და კვალიფიკაცია');
$acc1_content   = get_ok_option('acc1_content', 'თბილისის სახელმწიფო სამედიცინო უნივერსიტეტი (2005-2011).<br>რეზიდენტურა (2011-2015).');
$acc2_title     = get_ok_option('acc2_title', 'ასოციაციების წევრობა');
$acc2_content   = get_ok_option('acc2_content', 'საქართველოს მეან-გინეკოლოგთა ასოციაცია.<br>ევროპის გინეკოლოგიური ენდოსკოპიის საზოგადოება (ESGE).');

// --- Safety (Video) Section ---
$video_id       = get_ok_option('video_bg_id', '9xwazD5SyVg');
$safety_title   = get_ok_option('safety_title', 'უსაფრთხოება');
$safety_sub     = get_ok_option('safety_subtitle', 'თქვენ და თქვენი პატარა - საიმედო ხელში');
$safety_desc    = get_ok_option('safety_desc', 'ჩვენთან მოსვლის პირველივე წუთიდან თქვენ ხვდებით გარემოში, სადაც დეტალებს გადამწყვეტი მნიშვნელობა აქვს.');

$safe_item1     = get_ok_option('safe_list_1', 'საერთაშორისო სტანდარტები');
$safe_item2     = get_ok_option('safe_list_2', 'უახლესი აპარატურა');
$safe_item3     = get_ok_option('safe_list_3', 'ემპათია და ზრუნვა');

// --- Contact Section ---
$contact_label  = get_ok_option('contact_label', 'კონტაქტი');
$contact_title  = get_ok_option('contact_title', 'დაჯავშნეთ<br>ვიზიტი');

$lbl_phone      = get_ok_option('lbl_phone', 'ტელეფონი');
$val_phone      = get_ok_option('dr_phone', '+995 555 12 34 56');

$lbl_email      = get_ok_option('lbl_email', 'ელ-ფოსტა');
$val_email      = get_ok_option('dr_email', 'info@doctorpaata.ge');

$lbl_addr       = get_ok_option('lbl_address', 'მისამართი');
$val_addr       = get_ok_option('dr_address', 'თბილისი, ი. ჭავჭავაძის გამზ. 5');

// --- Social Networks ---
$soc_fb         = get_ok_option('dr_social_fb', '');
$soc_insta      = get_ok_option('dr_social_insta', '');
$soc_linkedin   = get_ok_option('dr_social_linkedin', '');

// Form Placeholders
$ph_name        = get_ok_option('form_ph_name', 'თქვენი სახელი');
$ph_phone       = get_ok_option('form_ph_phone', 'ტელეფონის ნომერი');
$ph_msg         = get_ok_option('form_ph_msg', 'შეტყობინება');
$btn_submit     = get_ok_option('form_btn_text', 'გაგზავნა');

// --- Images ---
$default_doctor_img = 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?q=80&w=2940&auto=format&fit=crop';
$doctor_img     = !empty($post->post_image) ? (string)$post->post_image : $default_doctor_img;
$about_img      = get_ok_option('about_section_img', 'https://drpaata.ge/ok-content/uploads/2026/02/1770642417-6989dbf15ce2e-916.png');
$hero_bg_image  = get_ok_option('hero_bg_image', 'http://localhost/ok-content/themes/newdrpaata/images/hero-bg.webp');

?>

<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo strip_tags($hero_title); ?> | Medical Portfolio</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100;200;300;400;500;600;700;800;900&family=Noto+Serif+Georgian:wght@100;200;300;400;500;600;700;800;900&display=swap&subset=georgian" rel="stylesheet">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>

    <style>
        :root {
            --bg-color: #F9F8F6;
            --text-main: #2F4858;
            --gold: #BF9B7A;
            --border-light: rgba(47, 72, 88, 0.1);
        }

        /* STRICT FONT RESET */
        * { 
            margin: 0; padding: 0; box-sizing: border-box; cursor: default; 
            font-family: 'Montserrat', sans-serif; /* Default for EVERYTHING */
        }

        /* Specific override for Headings */
        h1, h2, h3, h4, h5, .hero-title, .contact-title, .scrolling-text { 
            font-family: 'Noto Serif Georgian', serif !important; 
        }

        /* Force small elements to respect Montserrat */
        button, input, textarea, select, span, small, p, a, div, .btn-glow, ::placeholder {
            font-family: 'Montserrat', sans-serif !important;
        }

        a, button, .btn-glow, select, input[type="submit"], .accordion-header, .social-link, .dot, .arrow-btn { cursor: pointer; }
        input[type="text"], input[type="email"], textarea { cursor: text; }

        /* SCROLL SNAP LOGIC */
        html, body {
            height: 100%;
            overflow: hidden;
        }

        .main-scroller {
            height: 100vh;
            width: 100%;
            overflow-y: scroll;
            scroll-snap-type: y mandatory;
            scroll-behavior: smooth;
            scrollbar-width: none;
        }
        .main-scroller::-webkit-scrollbar { display: none; }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
        }

        /* SECTION STYLING */
        section {
            height: 100vh;
            width: 100%;
            position: relative;
            scroll-snap-align: start;
            scroll-snap-stop: always;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 0 5vw;
            overflow: hidden;
        }

        /* --- NAVIGATION DOTS --- */
        .nav-dots {
            position: fixed; right: 30px; top: 50%; transform: translateY(-50%);
            display: flex; flex-direction: column; gap: 20px; z-index: 1000;
        }
        .dot {
            width: 10px; height: 10px; border: 1px solid var(--text-main); border-radius: 50%;
            transition: 0.3s; position: relative;
        }
        .dot.active { background: var(--gold); border-color: var(--gold); transform: scale(1.3); }
        .dot::after {
            content: attr(data-label); position: absolute; right: 25px; top: 50%;
            transform: translateY(-50%); opacity: 0; pointer-events: none;
            font-size: 0.7rem; font-family: 'Noto Serif Georgian', serif !important; text-transform: uppercase;
            transition: 0.3s; background: var(--text-main); color: #fff; padding: 4px 8px; border-radius: 2px; white-space: nowrap;
        }
        .dot:hover::after { opacity: 1; right: 20px; }

        /* --- NAVIGATION ARROWS --- */
        .nav-arrows {
            position: fixed; bottom: 40px; right: 80px; z-index: 1000; display: flex; gap: 15px;
        }
        .arrow-btn {
            width: 45px; height: 45px; border: 1px solid var(--border-light); border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            background: rgba(255, 255, 255, 0.4); backdrop-filter: blur(5px);
            transition: all 0.3s ease; color: var(--text-main);
        }
        .arrow-btn svg { width: 14px; height: 14px; fill: currentColor; }
        .arrow-btn:hover { background: var(--gold); border-color: var(--gold); color: #fff; transform: scale(1.1); }
        .arrow-btn.disabled { opacity: 0; pointer-events: none; transform: scale(0.8); }

        @media (max-width: 768px) {
            .nav-arrows { right: 30px; bottom: 30px; }
            .nav-dots { display: none; }
        }

        /* UTILS */
        .noise-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            pointer-events: none; z-index: 9999; opacity: 0.03;
            background-image: url("https://grainy-gradients.vercel.app/noise.svg");
            filter: invert(1);
        }
        .ambient-light {
            position: fixed; top: 50%; left: 50%; width: 80vw; height: 80vw;
            background: radial-gradient(circle, rgba(191, 155, 122, 0.08) 0%, rgba(255,255,255,0) 70%);
            transform: translate(-50%, -50%); z-index: -1; filter: blur(80px);
        }

        /* HEADER */
        .site-header { position: fixed; top: 0; left: 0; width: 100%; padding: 30px 5vw; display: flex; justify-content: flex-end; align-items: center; z-index: 100; pointer-events: none; }
        .nav-menu { pointer-events: auto; }
        .nav-menu ul { display: flex; list-style: none; margin: 0; padding: 0; gap: 40px; }
        .nav-menu a { 
            text-decoration: none; color: var(--text-main); 
            text-transform: uppercase; font-size: 0.9rem; letter-spacing: 1px; 
            font-weight: 600; transition: 0.3s; 
        }
        .nav-menu > a { margin-left: 40px; }
        .nav-menu a:hover { color: var(--gold); }

        /* LOGO */
        .logo-container { 
            position: fixed; top: 30px; left: 5vw; z-index: 100; 
            width: 250px; height: 100px;
            display: flex; justify-content: center; align-items: center; 
            color: var(--text-main); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px; 
            pointer-events: none; 
        }
        .logo-container img { max-width: 100%; max-height: 100%; object-fit: contain; object-position: left center; }

        /* HERO */
        .hero { padding: 0; }
        .hero-bg-absolute { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: -2; }
        .hero-bg-absolute::after { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(249, 248, 246, 0.5); z-index: 1; }
        .hero-bg-absolute img { width: 100%; height: 100%; object-fit: cover; filter: grayscale(80%); transform: scale(1.1); z-index: 0; }
        
        .hero-img-container { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; display: flex; justify-content: flex-end; align-items: flex-end; padding-right: 8vw; }
        .hero-img { height: 95vh; width: auto; object-fit: cover; filter: drop-shadow(-20px 0 30px rgba(0,0,0,0.1)); }
        
        .hero-text-container { position: absolute; bottom: 10vh; left: 5vw; text-align: left; z-index: 2; }
        .hero-title { font-size: 3.5vw; line-height: 1.1; text-transform: uppercase; color: var(--text-main); }
        
        @media (max-width: 768px) {
            .hero-img { height: auto; width: 100%; padding-right: 0; opacity: 0.5; }
            .hero-title { font-size: 8vw; }
        }

        /* BUTTON */
        .btn-glow {
            display: inline-block; text-decoration: none; padding: 15px 45px; 
            background: var(--gold); border: 2px solid var(--gold); 
            color: #fff; font-weight: 700; font-size: 1rem;
            text-transform: uppercase; letter-spacing: 2px; 
            position: relative; overflow: hidden; transition: 0.4s; z-index: 10;
            box-shadow: 0 4px 15px rgba(191, 155, 122, 0.4);
        }
        .btn-glow:hover { 
            background: var(--text-main); border-color: var(--text-main); 
            color: #fff; transform: translateY(-2px);
        }

        /* PHILOSOPHY */
        .scrolling-text { 
            white-space: nowrap; font-size: 13vw; font-weight: 700;
            color: rgba(47, 72, 88, 0.04); 
            position: absolute; top: 50%; transform: translateY(-50%); z-index: 0;
        }
        .content-block { max-width: 700px; z-index: 2; position: relative; margin: 0 auto; text-align: center; }
        .content-block h2 { color: var(--gold); font-size: 3.5rem; margin-bottom: 30px; line-height: 1.1; }
        .content-block p { font-size: 1.3rem; line-height: 1.8; color: var(--text-main); }

        /* ABOUT */
        .about-section { display: flex; flex-direction: row; align-items: center; justify-content: space-between; gap: 40px; }
        .about-content { flex: 1; min-width: 300px; }
        .about-image-wrapper { 
            flex: 0 0 40%; height: 70vh; position: relative; overflow: hidden; border-radius: 4px; 
            box-shadow: 20px 20px 0px rgba(191, 155, 122, 0.2); 
        }
        .about-image { width: 100%; height: 100%; object-fit: cover; }
        
        .section-label { color: var(--gold); font-weight: 700; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 10px; display: block; font-size: 0.8rem; }
        .about-title { font-size: 2.5rem; margin-bottom: 20px; line-height: 1.1; }
        .about-desc { font-size: 1rem; line-height: 1.6; color: var(--text-main); opacity: 0.8; margin-bottom: 30px; }
        
        .stats-container { display: flex; gap: 40px; margin-bottom: 30px; border-top: 1px solid var(--border-light); padding-top: 20px; }
        .stat-item h5 { font-size: 2.5rem; color: var(--gold); font-weight: 300; }
        .stat-item p { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; margin-top: 5px; }

        .accordion-item { border-bottom: 1px solid var(--border-light); }
        .accordion-header { padding: 15px 0; display: flex; justify-content: space-between; align-items: center; transition: 0.3s; }
        .accordion-title { font-size: 1.1rem; }
        .accordion-content { max-height: 0; overflow: hidden; transition: max-height 0.4s ease-out; opacity: 0.8; font-size: 0.9rem; line-height: 1.5; }
        .accordion-inner { padding-bottom: 15px; }

        @media (max-width: 900px) {
            .about-section { flex-direction: column; overflow-y: auto; display: block; padding-top: 100px; }
            .about-image-wrapper { height: 300px; width: 100%; margin-bottom: 30px; }
        }

        /* VIDEO */
        .video-bg-container { position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: -1; overflow: hidden; }
        .video-bg-container iframe { position: absolute; top: 50%; left: 50%; width: 100vw; height: 56.25vw; min-height: 100vh; min-width: 177.77vh; transform: translate(-50%, -50%); border: none; }
        .video-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(249, 248, 246, 0.45); z-index: 1; }
        
        .safety-content { position: absolute; bottom: 10vh; right: 5vw; text-align: right; z-index: 5; max-width: 600px; }
        .safety-content h2 { font-size: 3.5rem; color: var(--text-main); margin-bottom: 5px; }
        .safety-subtitle { color: var(--text-main); opacity: 0.9; font-size: 1.2rem; margin-bottom: 20px; font-weight: 500; }
        .safety-divider { height: 2px; width: 80px; background: var(--gold); margin-left: auto; margin-bottom: 20px; }
        .safety-desc { font-size: 0.95rem; line-height: 1.6; color: var(--text-main); margin-bottom: 20px; }
        .safety-list { display: flex; gap: 20px; justify-content: flex-end; flex-wrap: wrap; }
        .safety-list span { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; color: var(--text-main); position: relative; }
        .safety-list span:not(:last-child)::after { content: '•'; margin-left: 20px; color: var(--gold); }

        /* CONTACT */
        .contact-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; width: 100%; max-width: 1200px; margin: 0 auto; align-items: center; }
        .contact-title { font-size: 2.5rem; margin-bottom: 30px; line-height: 1.1; }
        .contact-detail-item { margin-bottom: 20px; }
        .contact-detail-item h6 { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 2px; color: var(--gold); margin-bottom: 5px; font-weight: 700; }
        .contact-detail-item p, .contact-detail-item a { font-size: 1.1rem; color: var(--text-main); text-decoration: none; font-family: 'Noto Serif Georgian', serif !important; }
        
        .social-link {
            width: 40px; height: 40px; 
            border: 1px solid var(--text-main); border-radius: 50%; 
            display: flex; align-items: center; justify-content: center; 
            text-decoration: none; color: var(--text-main);
            transition: 0.3s; font-size: 0.8rem; font-weight: bold;
        }
        .social-link:hover { background-color: var(--text-main); color: #fff; }

        .form-group { margin-bottom: 20px; }
        .form-input { width: 100%; padding: 12px 0; background: transparent; border: none; border-bottom: 1px solid var(--text-main); font-size: 0.95rem; color: var(--text-main); }
        
        .site-footer { 
            position: absolute; bottom: 10px; left: 0; width: 100%; 
            padding: 10px 5vw; border-top: 1px solid var(--border-light); 
            display: flex; justify-content: space-between; align-items: center; 
            font-size: 0.7rem; text-transform: uppercase; opacity: 0.6; letter-spacing: 1px; 
        }

        @media (max-width: 900px) {
            .contact-grid { grid-template-columns: 1fr; gap: 30px; }
            .form-group { margin-bottom: 15px; }
            .contact-section { overflow-y: auto; display: block; padding-top: 100px; padding-bottom: 50px; }
            .header-contact-info { display: none !important; }
            .logo-container { width: 160px; left: 20px; }
        }

    </style>
    <?php if (function_exists('do_ok_action')) { do_ok_action('ok_head'); } ?>
</head>
<body>

    <div class="noise-overlay"></div>
    <div class="ambient-light"></div>

    <header class="site-header">
        <div class="header-contact-info" style="margin-right: auto; padding-left: 260px; display: flex; flex-direction: column; justify-content: center; pointer-events: auto;">
            <a href="tel:<?php echo preg_replace('/[^0-9]/', '', $val_phone); ?>" style="text-decoration: none; color: var(--text-main); font-weight: 700; font-size: 0.9rem;">
                📞 <?php echo $val_phone; ?>
            </a>
            <span style="font-size: 0.75rem; opacity: 0.7; letter-spacing: 1px;">ორშ-პარ: 10:00 - 18:00</span>
        </div>

        <nav class="nav-menu">
            <?php
            if (function_exists('ok_nav_menu')) {
                ok_nav_menu('header-menu');
            } else {
                echo '<ul><li><a href="/">მთავარი</a></li></ul>';
            }
            ?>
        </nav>
    </header>

    <div class="logo-container" style="<?php echo empty($site_logo) ? 'border: 2px dashed rgba(47, 72, 88, 0.3);' : ''; ?>">
        <?php if ( !empty($site_logo) ): ?>
            <img src="<?php echo $site_logo; ?>" alt="Site Logo">
        <?php else: ?>
            <span>LOGO PLACEHOLDER</span>
        <?php endif; ?>
    </div>

    <div class="nav-dots">
        <div class="dot active" onclick="scrollToSection(0)" data-label="მთავარი"></div>
        <div class="dot" onclick="scrollToSection(1)" data-label="ფილოსოფია"></div>
        <div class="dot" onclick="scrollToSection(2)" data-label="შესახებ"></div>
        <div class="dot" onclick="scrollToSection(3)" data-label="უსაფრთხოება"></div>
        <div class="dot" onclick="scrollToSection(4)" data-label="კონტაქტი"></div>
    </div>

    <div class="nav-arrows">
        <div class="arrow-btn" id="arrow-up" onclick="scrollNext('prev')">
            <svg viewBox="0 0 24 24"><path d="M7.41 15.41L12 10.83l4.59 4.58L18 14l-6-6-6 6z"></path></svg>
        </div>
        <div class="arrow-btn" id="arrow-down" onclick="scrollNext('next')">
            <svg viewBox="0 0 24 24"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6z"></path></svg>
        </div>
    </div>

    <div class="main-scroller" id="scroller">

        <section class="hero" id="sec-0">
            <div class="hero-bg-absolute"><img src="<?php echo $hero_bg_image; ?>" alt="Hero Background"></div>
            <div class="hero-img-container">
                <img src="<?php echo $doctor_img; ?>" class="hero-img" alt="<?php echo strip_tags($hero_title); ?>">
            </div>
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 5;">
                 <a href="<?php echo $btn1_url; ?>" class="btn-glow"><?php echo $btn1_text; ?></a>
            </div>
            <div class="hero-text-container">
                <h1 class="hero-title"><?php echo $hero_title; ?></h1>
                <p style="margin-top: 20px; letter-spacing: 3px; text-transform: uppercase; font-weight: 700; color: var(--text-main);">
                    <?php echo $hero_desc; ?>
                </p>
            </div>
        </section>

        <section class="philosophy" id="sec-1">
            <div class="scrolling-text"><?php echo $philo_scroll; ?></div>
            <div class="content-block">
                <h2><?php echo $philo_title; ?></h2>
                <p><?php echo $philo_desc; ?></p>
            </div>
        </section>

        <section class="about-section" id="sec-2">
            <div class="about-image-wrapper">
                <img src="<?php echo $about_img; ?>" class="about-image" alt="Doctor Working">
            </div>
            <div class="about-content">
                <span class="section-label"><?php echo $about_label; ?></span>
                <h2 class="about-title"><?php echo $about_title; ?></h2>
                <p class="about-desc"><?php echo $about_desc; ?></p>
                <div class="stats-container">
                    <div class="stat-item">
                        <h5><span class="counter" data-target="<?php echo preg_replace('/[^0-9]/', '', $stat1_num); ?>">0</span>+</h5>
                        <p><?php echo $stat1_text; ?></p>
                    </div>
                    <div class="stat-item">
                        <h5><span class="counter" data-target="<?php echo preg_replace('/[^0-9]/', '', $stat2_num); ?>">0</span>+</h5>
                        <p><?php echo $stat2_text; ?></p>
                    </div>
                </div>
                <div class="accordion">
                    <div class="accordion-item">
                        <div class="accordion-header">
                            <span class="accordion-title"><?php echo $acc1_title; ?></span>
                            <span class="accordion-icon">+</span>
                        </div>
                        <div class="accordion-content">
                            <div class="accordion-inner"><?php echo $acc1_content; ?></div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <div class="accordion-header">
                            <span class="accordion-title"><?php echo $acc2_title; ?></span>
                            <span class="accordion-icon">+</span>
                        </div>
                        <div class="accordion-content">
                            <div class="accordion-inner"><?php echo $acc2_content; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="reveal-section" id="sec-3">
            <div class="video-bg-container">
                <div class="video-overlay"></div>
                <iframe 
                    src="https://www.youtube.com/embed/<?php echo $video_id; ?>?autoplay=1&mute=1&controls=0&loop=1&playlist=<?php echo $video_id; ?>&playsinline=1&showinfo=0&rel=0&iv_load_policy=3&disablekb=1" 
                    allow="autoplay; encrypted-media" 
                    allowfullscreen>
                </iframe>
            </div>
            <div class="safety-content">
                <h2><?php echo $safety_title; ?></h2>
                <p class="safety-subtitle"><?php echo $safety_sub; ?></p>
                <div class="safety-divider"></div>
                <p class="safety-desc"><?php echo $safety_desc; ?></p>
                <div class="safety-list">
                    <span><?php echo $safe_item1; ?></span>
                    <span><?php echo $safe_item2; ?></span>
                    <span><?php echo $safe_item3; ?></span>
                </div>
            </div>
        </section>

        <section class="contact-section" id="sec-4">
            <div class="contact-grid">
                <div>
                    <span class="section-label"><?php echo $contact_label; ?></span>
                    <h2 class="contact-title"><?php echo $contact_title; ?></h2>
                    
                    <div class="contact-detail-item">
                        <h6><?php echo $lbl_phone; ?></h6>
                        <a href="tel:<?php echo preg_replace('/[^0-9]/', '', $val_phone); ?>"><?php echo $val_phone; ?></a>
                    </div>
                    
                    <div class="contact-detail-item">
                        <h6><?php echo $lbl_email; ?></h6>
                        <a href="mailto:<?php echo $val_email; ?>"><?php echo $val_email; ?></a>
                    </div>
                    
                    <div class="contact-detail-item">
                        <h6><?php echo $lbl_addr; ?></h6>
                        <p><?php echo $val_addr; ?></p>
                    </div>

                    <?php if (!empty($soc_fb) || !empty($soc_insta) || !empty($soc_linkedin)): ?>
                    <div class="social-links" style="display: flex; gap: 15px; margin-top: 30px;">
                        <?php if(!empty($soc_fb)): ?>
                            <a href="<?php echo $soc_fb; ?>" target="_blank" class="social-link">FB</a>
                        <?php endif; ?>
                        <?php if(!empty($soc_insta)): ?>
                            <a href="<?php echo $soc_insta; ?>" target="_blank" class="social-link">IG</a>
                        <?php endif; ?>
                        <?php if(!empty($soc_linkedin)): ?>
                            <a href="<?php echo $soc_linkedin; ?>" target="_blank" class="social-link">IN</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                </div>

                <div style="display: flex; flex-direction: column; justify-content: center;">
                    <form action="#" method="POST" style="background: rgba(255,255,255,0.5); padding: 30px; border: 1px solid rgba(255,255,255,0.8); box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
                        <div class="form-group">
                            <input type="text" class="form-input" placeholder="<?php echo $ph_name; ?>" required>
                        </div>
                        <div class="form-group">
                            <input type="text" class="form-input" placeholder="<?php echo $ph_phone; ?>" required>
                        </div>
                        <div class="form-group">
                            <textarea class="form-input" rows="2" placeholder="<?php echo $ph_msg; ?>"></textarea>
                        </div>
                        <div style="margin-top: 20px;">
                            <button type="submit" class="btn-glow" style="width: 100%; border-radius: 0; padding: 12px;"><?php echo $btn_submit; ?></button>
                        </div>
                    </form>
                </div>
            </div>
            
            <footer class="site-footer">
                <div>&copy; <?php echo date('Y'); ?>  <strong><?php echo htmlspecialchars(get_ok_option('site_title', 'Dr. Paata')); ?></strong>. </div>
                <div>POWERED BY <strong class="text-white">OK CSM</strong></div>
            </footer>
        </section>

    </div>

    <?php if (function_exists('do_ok_action')) { do_ok_action('ok_footer'); } ?>

    <script>
        gsap.registerPlugin(ScrollTrigger);

        const scroller = document.getElementById('scroller');
        const dots = document.querySelectorAll('.dot');
        const arrowUp = document.getElementById('arrow-up');
        const arrowDown = document.getElementById('arrow-down');
        const totalSections = 5; 
        
        updateArrows(0);

        scroller.addEventListener('scroll', () => {
            let index = Math.round(scroller.scrollTop / window.innerHeight);
            dots.forEach(d => d.classList.remove('active'));
            if(dots[index]) dots[index].classList.add('active');
            updateArrows(index);
        });

        function updateArrows(index) {
            if (index <= 0) arrowUp.classList.add('disabled');
            else arrowUp.classList.remove('disabled');

            if (index >= totalSections - 1) arrowDown.classList.add('disabled');
            else arrowDown.classList.remove('disabled');
        }

        function scrollToSection(index) {
            if (index < 0) index = 0;
            if (index >= totalSections) index = totalSections - 1;
            const target = document.getElementById('sec-' + index);
            if(target) target.scrollIntoView({ behavior: 'smooth' });
        }

        function scrollNext(direction) {
            let index = Math.round(scroller.scrollTop / window.innerHeight);
            if (direction === 'next') scrollToSection(index + 1);
            else scrollToSection(index - 1);
        }

        window.addEventListener('keydown', (e) => {
            let index = Math.round(scroller.scrollTop / window.innerHeight);
            if (e.key === 'ArrowDown') { e.preventDefault(); scrollToSection(index + 1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); scrollToSection(index - 1); }
        });

        gsap.from(".hero-text-container", { y: 50, opacity: 0, duration: 1.2, delay: 0.5 });
        gsap.from(".logo-container", { y: -30, opacity: 0, duration: 1, delay: 0.2 });

        document.querySelectorAll('.accordion-header').forEach(header => {
            header.addEventListener('click', () => {
                const content = header.nextElementSibling;
                const icon = header.querySelector('.accordion-icon');
                if (content.style.maxHeight) {
                    content.style.maxHeight = null;
                    icon.textContent = "+";
                } else {
                    document.querySelectorAll('.accordion-content').forEach(c => c.style.maxHeight = null);
                    document.querySelectorAll('.accordion-icon').forEach(i => i.textContent = "+");
                    content.style.maxHeight = content.scrollHeight + "px";
                    icon.textContent = "−";
                }
            });
        });

        const counters = document.querySelectorAll('.counter');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const counter = entry.target;
                    const target = +counter.getAttribute('data-target');
                    gsap.to(counter, {
                        innerText: target,
                        duration: 2,
                        snap: { innerText: 1 },
                        ease: "power2.out",
                        onUpdate: function() { counter.innerText = Math.ceil(this.targets()[0].innerText); }
                    });
                    observer.unobserve(counter);
                }
            });
        }, { threshold: 0.5 });

        counters.forEach(c => observer.observe(c));
    </script>
</body>
</html>