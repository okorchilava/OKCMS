<?php
/**
 * Template Name: Medical Landing – Final (Clean Promo & No Gap)
 */

global $ok_query, $ok_db;

$post = $ok_query['object'] ?? null;
if (!$post) { echo "Page not found"; exit; }

include 'header.php';

// --- დინამიური მონაცემები ---
$hero_title = get_ok_option('dr_hero_title', 'იზრუნეთ თქვენს ჯანმრთელობაზე პროფესიონალებთან ერთად.');
$hero_desc  = get_ok_option('dr_hero_desc', 'თანამედროვე მიდგომები, უახლესი აპარატურა და ინდივიდუალური ზრუნვა.');
$hero_badge = get_ok_option('dr_hero_badge', '15 წლიანი გამოცდილება');

$btn1_text  = get_ok_option('dr_btn1_text', 'ვიზიტის დაჯავშნა');
$btn1_url   = get_ok_option('dr_btn1_url', '/contact');
$btn2_text  = get_ok_option('dr_btn2_text', 'სერვისები');
$btn2_url   = get_ok_option('dr_btn2_url', '#services');

$youtube_id    = get_ok_option('dr_video_id', 'OSVA1FIQDOQ');
$youtube_src   = "https://www.youtube.com/embed/{$youtube_id}?autoplay=1&mute=1&controls=0&rel=0&modestbranding=1&playsinline=1&loop=1&playlist={$youtube_id}";
$blog_title    = get_ok_option('dr_blog_title', 'ბოლო ჩანაწერები');
$blog_btn_text = get_ok_option('dr_blog_btn_text', 'ყველა სიახლე');
$blog_btn_url  = get_ok_option('dr_blog_btn_url', '/blog');

$map_lat = get_ok_option('dr_map_lat', '41.779565');
$map_lng = get_ok_option('dr_map_lng', '44.773246');

$theme_name = get_ok_option('active_theme', 'default');
$theme_base = '/ok-content/themes/' . $theme_name;
$hero_bg    = $theme_base . '/images/hero-bg.png';
$doctor_img = !empty($post->post_image) ? (string)$post->post_image : $theme_base . '/images/doctor.png';
$pin_icon   = $theme_base . '/images/pin.png';

$latest_posts = $ok_db->get_results("SELECT * FROM ok_posts WHERE post_type='post' AND post_status='published' ORDER BY post_date DESC LIMIT 4");
$date_format  = get_ok_option('date_format', 'd M, Y');
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<style>
/* =========================================================
   HERO SECTION
   ========================================================= */
.hero-section {
    --hero-h: auto;
    position: relative;
    background-image: url('<?php echo htmlspecialchars($hero_bg); ?>');
    background-size: cover;
    background-position: center top;
    background-repeat: no-repeat;
    background-attachment: fixed; 
    overflow: hidden;
    height: var(--hero-h);
    width: 100%;
}
.hero-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(180deg, rgba(5,18,35,.62) 0%, rgba(5,18,35,.72) 70%, rgba(5,18,35,.78) 100%);
    z-index: 2;
}

/* Mobile */
.hero-mobile {
    position: relative; z-index: 3; height: 100%; min-height: 260px;
    display: grid; place-items: center; padding: 20px 0;
}
.hero-mobile .hero-content { width: 100%; max-width: 820px; padding: 0 16px; text-align: center; color: #fff; }
.hero-badge {
    display: inline-flex; align-items: center; gap: 8px; padding: 8px 14px;
    border-radius: 999px; font-weight: 800; letter-spacing: .6px; text-transform: uppercase;
    background: rgba(13,110,253,.85); box-shadow: 0 10px 26px rgba(0,0,0,.20);
    backdrop-filter: blur(10px); margin-bottom: 14px; font-size: 12px;
}
.hero-title {
    margin: 0 0 12px; font-weight: 900; line-height: 1.14;
    text-shadow: 0 18px 50px rgba(0,0,0,.35); font-size: clamp(1.9rem, 5vw, 3.25rem);
}
.hero-desc {
    margin: 0 auto 18px; max-width: 720px; color: rgba(255,255,255,.92);
    text-shadow: 0 14px 40px rgba(0,0,0,.25); font-size: clamp(0.98rem, 2.6vw, 1.12rem);
    line-height: 1.55;
}
.hero-actions { display: inline-flex; flex-wrap: wrap; gap: 10px; justify-content: center; }
.hero-actions .btn { border-radius: 999px; font-weight: 800; letter-spacing: .1px; padding: .74rem 1.2rem; }
.hero-actions .btn-primary { border: 0; box-shadow: 0 12px 30px rgba(13,110,253,.28); }
.hero-actions .btn-outline-light { background: rgba(255,255,255,.06); border-color: rgba(255,255,255,.35); backdrop-filter: blur(10px); }

.hero-doctor-mobile {
    position: absolute; left: 50%; bottom: 0; transform: translateX(-50%);
    z-index: 2; width: auto; height: auto; max-width: min(560px, 92vw); max-height: 92%;
    object-fit: contain; filter: drop-shadow(0 22px 50px rgba(0,0,0,.28));
    pointer-events: none; user-select: none;
}

/* Desktop */
.hero-desktop { position: relative; z-index: 3; }
.doctor-img { filter: drop-shadow(0 22px 50px rgba(0,0,0,.22)); }

@media (min-width: 992px) {
    .hero-mobile, .hero-doctor-mobile { display: none; }
    .hero-desktop { display: block; }
}
@media (max-width: 991.98px) {
    .hero-desktop { display: none; }
    .hero-mobile { display: grid; }
    .hero-doctor-mobile { display: block; }
}

/* Video & Scroll Content */
.video-parallax-full {
    position: relative; height: 70vh; min-height: 360px; overflow: hidden; background: #000;
}
.video-parallax-full .video-bg { position: absolute; inset: 0; }
.video-parallax-full iframe {
    position: absolute; top: 50%; left: 50%; width: 120%; height: 120%;
    transform: translate(-50%, -50%); border: 0; pointer-events: none;
}
.video-overlay { position: absolute; inset: 0; background: rgba(0,0,0,.35); }

@media (max-width: 991.98px) {
    .video-parallax-full { height: 38vh; min-height: 220px; }
    .video-parallax-full iframe { width: 160%; height: 160%; }
}

.scroll-content {
    position: relative; margin-top: -18px;
    border-top-left-radius: 24px; border-top-right-radius: 24px; z-index: 10;
}

/* Blog Card */
.blog-card { border-radius: 16px; overflow: hidden; }
.blog-card .card-body { padding: 16px; }

@media (max-width: 767.98px) {
    #medicalMap { height: 300px !important; }
}
</style>

<section class="hero-section" id="heroSection">
    <img src="<?php echo htmlspecialchars($doctor_img); ?>" class="hero-doctor-mobile" id="heroDoctorMobile" alt="Dr. Paata">
    <div class="hero-overlay"></div>

    <div class="container hero-mobile">
        <div class="hero-content">
            <span class="hero-badge">
                <i class="bi bi-star-fill text-warning"></i> <?php echo htmlspecialchars($hero_badge); ?>
            </span>
            <h1 class="hero-title"><?php echo nl2br(htmlspecialchars($hero_title)); ?></h1>
            <p class="hero-desc"><?php echo nl2br(htmlspecialchars($hero_desc)); ?></p>
            <div class="hero-actions">
                <a href="<?php echo htmlspecialchars($btn1_url); ?>" class="btn btn-primary">
                    <i class="bi bi-calendar-plus me-2"></i><?php echo htmlspecialchars($btn1_text); ?>
                </a>
                <a href="<?php echo htmlspecialchars($btn2_url); ?>" class="btn btn-outline-light">
                    <?php echo htmlspecialchars($btn2_text); ?>
                </a>
            </div>
        </div>
    </div>

    <div class="container position-relative h-100 hero-desktop">
        <div class="row h-100">
            <div class="col-lg-6 d-flex flex-column justify-content-center mb-5 mb-lg-0 text-white position-relative z-2">
                <div class="hero-text-wrapper pe-lg-5">
                    <span class="badge bg-primary bg-opacity-75 text-white shadow-sm px-3 py-2 rounded-pill mb-3 fw-bold text-uppercase backdrop-blur">
                        <i class="bi bi-star-fill me-1 text-warning"></i> <?php echo htmlspecialchars($hero_badge); ?>
                    </span>
                    <h1 class="display-3 fw-bold mb-4 lh-sm text-shadow">
                        <?php echo nl2br(htmlspecialchars($hero_title)); ?>
                    </h1>
                    <p class="lead mb-5 opacity-90 text-shadow">
                        <?php echo nl2br(htmlspecialchars($hero_desc)); ?>
                    </p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="<?php echo htmlspecialchars($btn1_url); ?>" class="btn btn-primary btn-lg rounded-pill px-5 shadow fw-bold border-0">
                            <i class="bi bi-calendar-plus me-2"></i> <?php echo htmlspecialchars($btn1_text); ?>
                        </a>
                        <a href="<?php echo htmlspecialchars($btn2_url); ?>" class="btn btn-outline-light btn-lg rounded-pill px-4 fw-bold backdrop-blur">
                            <?php echo htmlspecialchars($btn2_text); ?>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 align-self-end text-center text-lg-end position-relative z-2 mt-4 mt-lg-0">
                <img src="<?php echo htmlspecialchars($doctor_img); ?>" class="img-fluid doctor-img" id="heroDoctorDesktop" alt="Dr. Paata">
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    const hero = document.getElementById('heroSection');
    const imgMobile  = document.getElementById('heroDoctorMobile');
    const imgDesktop = document.getElementById('heroDoctorDesktop');
    if (!hero) return;
    function isMobile() { return window.matchMedia && window.matchMedia('(max-width: 991.98px)').matches; }
    function pickImg() { return isMobile() ? imgMobile : imgDesktop; }
    function setHeroHeight() {
        const img = pickImg();
        if (!img) return;
        const rect = img.getBoundingClientRect();
        if (!rect.height || rect.height < 40) return;
        const factor = isMobile() ? 1.08 : 1.00;
        const h = Math.round(rect.height * factor);
        hero.style.setProperty('--hero-h', h + 'px');
    }
    const a = pickImg();
    if (a && a.complete) setHeroHeight();
    if (imgMobile)  imgMobile.addEventListener('load', setHeroHeight);
    if (imgDesktop) imgDesktop.addEventListener('load', setHeroHeight);
    window.addEventListener('resize', () => { clearTimeout(window.__okHeroT); window.__okHeroT = setTimeout(setHeroHeight, 80); }, { passive: true });
    setTimeout(setHeroHeight, 250);
})();
</script>

<div class="scroll-content bg-white position-relative shadow-lg rounded-top-5">

    <section class="py-5" id="services">
        <div class="container py-5">
            <div class="row g-4">
                <?php for($i=1; $i<=3; $i++): ?>
                    <div class="col-lg-4">
                        <div class="h-100">
                            <?php if (function_exists('ok_dynamic_sidebar')): ?>
                                <?php ok_dynamic_sidebar('landing-widget-' . $i); ?>
                            <?php else: ?>
                                <h3 class="text-muted">ვიჯეტი <?php echo (int)$i; ?></h3>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </section>

    <section class="video-parallax-full">
        <div class="video-bg">
            <iframe src="<?php echo htmlspecialchars($youtube_src); ?>" allow="autoplay; encrypted-media; picture-in-picture" loading="lazy" allowfullscreen></iframe>
        </div>
        <div class="video-overlay"></div>
    </section>

    <section class="pt-5 pb-4 bg-light">
        <div class="container pt-3">
            <div class="text-center mb-5">
                <h2 class="fw-bold display-6 text-dark"><?php echo htmlspecialchars($blog_title); ?></h2>
                <hr class="mx-auto mt-3" style="width: 50px; height: 3px; background-color: #0d6efd; opacity: 1;">
            </div>

            <div class="row g-4">
                <?php if (!empty($latest_posts)): foreach ($latest_posts as $p):
                    $p_img  = !empty($p->post_image) ? $p->post_image : $theme_base . '/images/blog-placeholder.jpg';
                    $p_link = "/?p=" . (int)$p->id;
                ?>
                    <div class="col-md-6 col-lg-3">
                        <article class="card h-100 border-0 shadow-sm blog-card">
                            <a href="<?php echo htmlspecialchars($p_link); ?>" class="overflow-hidden rounded-top-3">
                                <img src="<?php echo htmlspecialchars($p_img); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($p->post_title); ?>" style="height: 180px; object-fit: cover;">
                            </a>
                            <div class="card-body">
                                <small class="text-muted d-block mb-2">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    <?php echo function_exists('ok_date') ? ok_date($date_format, strtotime($p->post_date)) : htmlspecialchars((string)$p->post_date); ?>
                                </small>
                                <h5 class="card-title fw-bold lh-base mb-0">
                                    <a href="<?php echo htmlspecialchars($p_link); ?>" class="text-dark text-decoration-none stretched-link"><?php echo htmlspecialchars($p->post_title); ?></a>
                                </h5>
                            </div>
                        </article>
                    </div>
                <?php endforeach; else: ?>
                    <div class="col-12 text-center text-muted">სიახლეები არ არის.</div>
                <?php endif; ?>
            </div>

            <div class="text-center mt-5 mb-2">
                <a href="<?php echo htmlspecialchars($blog_btn_url); ?>" class="btn btn-outline-primary rounded-pill px-4 fw-bold">
                    <?php echo htmlspecialchars($blog_btn_text); ?>
                </a>
            </div>
        </div>
    </section>

    <section class="position-relative" style="z-index: 5;">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10 mb-0">
                    <?php if (function_exists('ok_dynamic_sidebar')): ?>
                        <div class="py-4">
                            <?php ok_dynamic_sidebar('landing-widget-4'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="map-section w-100 p-0 m-0 position-relative" style="display: block; margin-top: -1px !important;">
        <div id="medicalMap" style="height: 450px; width: 100%; z-index: 1; display: block;"></div>
    </section>

    <div class="site-footer-wrapper w-100 p-0 m-0">
        <?php include 'footer.php'; ?>
    </div>

</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    /* --- MAP --- */
    var lat = <?php echo htmlspecialchars($map_lat); ?>;
    var lng = <?php echo htmlspecialchars($map_lng); ?>;
    
    var map = L.map('medicalMap', { center: [lat, lng], zoom:17, scrollWheelZoom: false });

    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
        subdomains: 'abcd', maxZoom: 19
    }).addTo(map);

    var myIcon = L.icon({
        iconUrl: '<?php echo htmlspecialchars($pin_icon); ?>',
        iconSize: [50, 50], iconAnchor: [25, 50], popupAnchor: [0, -50]
    });
    L.marker([lat, lng], {icon: myIcon}).addTo(map)
        .bindPopup('<div style="text-align:center;"><b>აკ. ო. ღუდუშაურის კლინიკა</b><br>Tbilisi, Georgia</div>');

    /* --- MOBILE MENU FIX --- */
    var toggleBtn = document.querySelector('.navbar-toggler');
    var menuCollapse = document.querySelector('.navbar-collapse');
    if (toggleBtn && menuCollapse) {
        toggleBtn.addEventListener('click', function(e) {
            if (menuCollapse.classList.contains('show')) {
                setTimeout(function() { menuCollapse.classList.remove('show'); }, 10);
            }
        });
        document.addEventListener('click', function(event) {
            var isClickInside = toggleBtn.contains(event.target) || menuCollapse.contains(event.target);
            if (!isClickInside && menuCollapse.classList.contains('show')) {
                menuCollapse.classList.remove('show');
            }
        });
    }
});
</script>