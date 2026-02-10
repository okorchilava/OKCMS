<?php
/**
* Template Name: Landing Page (OK Paata Theme)
* Description: ჰერო სრული სიგანის (overlay გარეშე), მარჯვენა მხარეს hero.png; ჰეროში ანიმირებული სათაური (თითო სიტყვა ახალ ხაზზე);
* ჰეროს ქვემოთ 3 მთვლელი (count-up); მთვლელების შემდეგ განიერი CTA ბანერი „ჩაწერა ექიმთან“; სერვისები/გუნდი/FAQ/კონტაქტი.
* შენიშვნა: Breadcrumb ამოღებულია. Bootstrap 5 + SweetAlert 11 გამოყენებულია.
*/

if (!function_exists('get_ok_option')) { die('OK Engine core not loaded.'); }

// Options — საიტის ძირითადი პარამეტრები
$site_title   = get_ok_option('site_title', 'OK Clinic');
$site_tagline = get_ok_option('site_tagline', 'დახვეწილი სამედიცინო სერვისები');
$site_logo    = get_ok_option('site_logo', '');
$phone        = get_ok_option('clinic_phone', '+995 (000) 00-00-00');
$email        = get_ok_option('clinic_email', 'info@example.ge');
$address      = get_ok_option('clinic_address', 'თბილისი, Example ქუჩა 10');
$ad_slot_1    = get_ok_option('ad_slot_1_html', '');
$ad_slot_2    = get_ok_option('ad_slot_2_html', '');

// ჰეროს ფონის ძირითადი სურათი — თემა-ლოკალური bg.png
$hero_bg = '/ok-content/themes/paata/images/bg.png';

?><!DOCTYPE html>
<html lang="ka">
<head>
    <!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-HMPMFJLWD6"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-HMPMFJLWD6');
</script>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title><?php echo htmlspecialchars($site_title.' — '.$site_tagline); ?></title>

 <?php
    $favicon_url = get_ok_option('site_favicon_url');
    if ($favicon_url) {
        echo '<link rel="icon" href="' . htmlspecialchars($favicon_url) . '">';
    }
    ?>

 <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
 <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
 <link rel="preconnect" href="https://fonts.googleapis.com">
 <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
 <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;600;700;800&display=swap" rel="stylesheet">
 <link rel="stylesheet" href="/ok-content/themes/paata/style.css?v=2.20">
 <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

 <style>
  /* ===================== HERO ===================== */
  .landing-hero {
   position: relative;
   min-height: clamp(60vh, 54vh + 8vw, 88vh);
   width: 100%;
   display: block;
   background-image: url('/ok-content/themes/paata/images/hero.png'), url('<?php echo htmlspecialchars($hero_bg); ?>');
   background-repeat: no-repeat, no-repeat;
   background-size: contain, cover;
   background-position: 65% bottom, center center;
  }

  /* ===================== HERO TITLE (Animated) ===================== */
  .hero-content {
   position: relative;
   z-index: 1;
   min-height: inherit;
   display: flex;
   align-items: center;
   padding-left: 30%;
   padding-right: clamp(16px, 28vw, 40vw);
  }
  .hero-title {
   margin: 0;
   font-weight: 600;
   font-style: italic;
   font-size: clamp(1.6rem, 1.4rem + 2.5vw, 3rem);
   line-height: 1.08;
   color: #fff;
   text-indent: 18px;
   text-shadow: 0 2px 6px rgba(0,0,0,.35), 0 0 1px rgba(0,0,0,.25);
  }
  .hero-title .word {
   display: block;
   opacity: 0;
   transform: translateY(16px);
   animation: riseIn .70s ease-out forwards;
   animation-delay: calc(var(--i) * 0.12s);
   will-change: transform, opacity;
  }
  @keyframes riseIn { from { opacity: 0; transform: translateY(16px);} to { opacity: 1; transform: translateY(0);} }

  /* ===================== STATS (Counters) ===================== */
  #stats .section-caption{ font-size: 1rem; color: var(--ok-color-muted); letter-spacing: .02em; margin: 0 0 .5rem; font-weight: 500; text-transform: none; }
  #stats .stat-card { border-radius: 1rem; background: #fff; box-shadow: var(--ok-shadow-md); }
  #stats .stat-icon { font-size: 2rem; color: #114D4D; }
  #stats .stat-number { font-variant-numeric: tabular-nums; letter-spacing: .5px; }
  #stats .stat-card:hover { transform: translateY(-2px); box-shadow: 0 .75rem 1.5rem rgba(0,0,0,.08); transition: transform .18ს ease, box-shadow .18s ease; }

  /* ===================== CTA Banner ===================== */
  #cta-banner .cta-wrap{
   border-radius: 1rem;
   min-height: clamp(220px, 24vh, 320px);
   background: linear-gradient(135deg, rgba(17,77,77,0.95), rgba(17,77,77,0.85)), url('/ok-content/themes/paata/images/bg.png') center/cover no-repeat;
   box-shadow: var(--ok-shadow-md);
  }
  #cta-banner .btn{ white-space: nowrap; }
  #cta-banner{ padding-top: 1rem; }
  #stats{ padding-bottom: 2rem; }

  /* ===================== Latest Posts ===================== */
  #latest-posts .post-card { border-radius: 1rem; background: #fff; box-shadow: var(--ok-shadow-md); }
  #latest-posts .post-card:hover { transform: translateY(-2px); box-shadow: 0 .75rem 1.5rem rgba(0,0,0,.08); transition: transform .18s ease, box-shadow .18s ease; }
  #latest-posts .post-card a { text-decoration: none; }
  #latest-posts .post-meta { color: var(--ok-color-muted); font-size: .875rem; }

  /* ===================== Full-width Map ===================== */
  .site-footer { margin-top: 0 !important; }
  #map{ padding: 0; margin-bottom: 0; }
  .map-embed{ width: 100%; height: clamp(56vh, 62vh, 80vh); }
  .map-embed iframe{ width: 100%; height: 100%; border: 0; display: block; }
  #gmap{ width: 100%; height: clamp(56vh, 62vh, 80vh); }

  /* ===================== Testimonials ===================== */
  #testimonials { background: #f8f9fa; }
  #testimonials .testimonial-card { border-radius: 1rem; background: #fff; box-shadow: var(--ok-shadow-md); }
  #testimonials .testimonial-card:hover { transform: translateY(-2px); box-shadow: 0 .75rem 1.5rem rgba(0,0,0,.08); transition: transform .18s ease, box-shadow .18s ease; }
  #testimonials .testimonial-text { font-size: 1rem; line-height: 1.6; }
  #testimonials .stars .bi { color: #FFC107; }

  /* ===================== Ad Slots — FULL WIDTH ===================== */
  #ad-slot-1, #ad-slot-2 { padding-top: 1rem; padding-bottom: 1rem; }
  .ad-box, .ok-ad-slot, .ok-ad-slot .ok-ad-block, .ok-ad-slot > a, .ok-ad-slot > a.ok-ad-click { display: block !important; width: 100% !important; max-width: 100% !important; box-sizing: border-box; text-align: center; }
  .ok-ad-slot img, .ok-ad-slot video, .ok-ad-slot iframe, .ad-box img, .ad-box video, .ad-box iframe { display: block !entlich!; width: 100% !important; max-width: 100% !important; height: auto !important; border: 0; }
  .ok-ad-slot [style*="width:"], .ad-box [style*="width:"] { width: 100% !important; max-width: 100% !important; box-sizing: border-box; }

  /* ===== Burger styles ===== */
  .navbar-toggler { border: none; padding: 0; width: 24px; height: 20px; position: relative; background: transparent; cursor: pointer; }
  .navbar-toggler:focus { box-shadow: none; }
  .toggler-icon { display: block; position: absolute; height: 3px; width: 100%; background: #fff; border-radius: 3px; left: 0; transition: all .25s ease-in-out; }
  .toggler-icon.top-bar { top: 0px; } .toggler-icon.middle-bar { top: 8px; } .toggler-icon.bottom-bar { top: 16px; }
  .navbar-toggler[aria-expanded="true"] .top-bar { transform: rotate(45deg); top: 8px; }
  .navbar-toggler[aria-expanded="true"] .middle-bar { opacity: 0; }
  .navbar-toggler[aria-expanded="true"] .bottom-bar { transform: rotate(-45deg); top: 8px; }

  @media (max-width: 991.98px) {
   .site-header { position: sticky; z-index: 1030; }
   #mainNav.collapse.show, #mainNav.collapsing {
    position: absolute; top: 100%; left: 0; right: 0;
    background: rgba(17, 77, 77, 0.95); backdrop-filter: blur(5px); -webkit-backdrop-filter: blur(5px);
    padding: 1rem; border-bottom-left-radius: 1rem; border-bottom-right-radius: 1rem;
    box-shadow: 0 8px 16px rgba(0,0,0,.15);
   }
   #mainNav .nav { flex-direction: column !important; }
   .main-navigation .nav-link {
    width: 100%; text-align: center; color: #fff !important; font-size: 1.1rem;
    padding: .25rem 1rem !important; border-radius: .5rem; transition: background-color .2s ease;
   }
   .main-navigation .nav-link:hover { background-color: rgba(255, 255, 255, 0.1); }
  }

  /* ===== Testimonials-ის სწორი ცენტრირება ===== */
  #testimonials .container > div { display: flex; flex-wrap: wrap; justify-content: center; gap: 1.5rem; }
 </style>
</head>
<body>

<header class="site-header sticky-top">
 <div class="container d-flex justify-content-between align-items-center">
  <div class="site-branding d-flex align-items-center">
   <?php if (!empty($site_logo)): ?>
    <a href="/" class="site-logo d-inline-flex align-items-center" aria-label="<?php echo htmlspecialchars($site_title); ?>">
     <img src="<?php echo htmlspecialchars($site_logo); ?>" alt="<?php echo htmlspecialchars($site_title); ?> Logo">
    </a>
   <?php endif; ?>
   <div class="ms-2">
    <div class="site-title"><a href="/"> <?php echo htmlspecialchars($site_title); ?></a></div>
    <div class="site-description"><?php echo htmlspecialchars($site_tagline); ?></div>
   </div>
  </div>

  <!-- ბურგერის ღილაკი — ID და data-* გარეშე (Data API კონფლიქტის ასარიდებლად) -->
  <button id="navToggleBtn" class="navbar-toggler d-lg-none ms-auto" type="button"
      aria-controls="mainNav" aria-expanded="false" aria-label="მენიუ">
    <span class="toggler-icon top-bar"></span>
    <span class="toggler-icon middle-bar"></span>
    <span class="toggler-icon bottom-bar"></span>
  </button>

  <div class="collapse d-lg-flex ms-lg-auto" id="mainNav">
   <nav class="main-navigation">
    <?php if (function_exists('ok_nav_menu')) ok_nav_menu(['theme_location' => 'primary_menu', 'menu_class' => 'nav']); ?>
   </nav>
  </div>
 </div>
</header>

<main>
 <section class="landing-hero rounded-0">
  <div class="hero-content">
   <?php
    $title_text = trim((string)$site_title);
    $words = $title_text !== '' ? preg_split('/\s+/', $title_text) : ['OK','Clinic'];
   ?>
   <h1 class="hero-title">
    <?php foreach ($words as $i => $w): ?>
     <span class="word" style="--i: <?php echo (int)$i; ?>;"><?php echo htmlspecialchars($w); ?></span>
    <?php endforeach; ?>
   </h1>
  </div>
 </section>

 <section class="py-5" id="stats">
  <div class="container">
   <div class="text-center">
    <div class="section-caption">საიტის ჩართვიდან</div>
   </div>
   <div class="row g-4 text-center">
    <div class="col-md-4"><div class="card-body py-4"><?php echo do_shortcode('[ok_kpi key="success_operation" layout="stack" align="center" show_label="1" show_today="1" show_goal="0" label_size="25" value_size="70" sub_size="12" text_color="#0f172a" bg_color="#f5f5f5" border_color="#1c5f48" radius="12" pad="12 16" gap="6" anim="count" anim_duration="2400" anim_ease="easeOutCubic" anim_delay="0"]'); ?></div></div>
    <div class="col-md-4"><div class="card-body py-4"><?php echo do_shortcode('[ok_kpi key="success_treat" layout="stack" align="center" show_label="1" show_today="1" show_goal="0" label_size="25" value_size="70" sub_size="12" text_color="#0f172a" bg_color="#f5f5f5" border_color="#1c5f48" radius="12" pad="12 16" gap="6" anim="count" anim_duration="2400" anim_ease="easeOutCubic" anim_delay="0"]'); ?></div></div>
    <div class="col-md-4"><div class="card-body py-4"><?php echo do_shortcode('[ok_kpi key="baby-born" layout="stack" align="center" show_label="1" show_today="1" show_goal="0" label_size="25" value_size="70" sub_size="12" text_color="#0f172a" bg_color="#f5f5f5" border_color="#1c5f48" radius="12" pad="12 16" gap="6" anim="count" anim_duration="2400" anim_ease="easeOutCubic" anim_delay="0"]'); ?></div></div>
   </div>
  </div>
 </section>

 <section class="pt-3 pb-5" id="cta-banner">
  <div class="container">
   <div class="cta-wrap p-4 p-md-5 d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
    <div class="cta-text text-white">
     <h2 class="h3 fw-bold mb-1">დაიბრუნეთ სიმშვიდე — ჩაეწერეთ ექიმთან</h2>
     <p class="mb-0 opacity-90">ჩვენი გუნდი მზადაა დაგეხმაროთ — სწრაფი, კომფორტული და სანდო მომსახურება.</p>
    </div>
    <div class="cta-action">
     <a href="/book-appointment" class="btn btn-light btn-lg" id="bookNowBtn"><i class="bi bi-calendar2-check me-2"></i>ექიმთან ჩაწერა</a>
    </div>
   </div>
  </div>
 </section>

 <section id="ad-slot-1" class="py-3">
  <div class="container"><div class="ad-box"><div class="ok-ad-slot">
    <?php echo do_shortcode('[ok_ad slot="default" limit="1" order="random"]'); ?>
  </div></div></div>
 </section>

 <!-- ======= ბოლო ჩანაწერები — xs=1, md=2, lg=4; LIMIT 4 ======= -->
 <section id="latest-posts" class="py-5">
  <div class="container">
   <div class="text-center mb-4">
    <h2 class="fw-bold">ბოლო ჩანაწერები</h2>
    <p class="text-muted mb-0">ჩვენი სიახლეები და რჩევები</p>
   </div>
   <div class="row g-4 row-cols-1 row-cols-md-2 row-cols-lg-4">
    <?php
     global $ok_db;
     $latest = $ok_db->get_results("
       SELECT id, post_title, post_slug, post_content, post_date
       FROM posts
       WHERE post_type='post' AND post_status='published'
       ORDER BY post_date DESC
       LIMIT 4
     ");
     if ($latest) : foreach ($latest as $p) :
      $permalink = '/'.ltrim($p->post_slug, '/');
      $title = $p->post_title ?: 'Untitled';
      $raw = strip_tags($p->post_content);
      $excerpt = mb_substr($raw, 0, 140, 'UTF-8') . (mb_strlen($raw, 'UTF-8') > 140 ? '…' : '');
      $date = date('Y-m-d', strtotime($p->post_date));
    ?>
    <div class="col">
     <article class="post-card h-100 p-4">
      <h3 class="h5 mb-2"><a href="<?php echo htmlspecialchars($permalink); ?>"><?php echo htmlspecialchars($title); ?></a></h3>
      <div class="post-meta mb-2"><i class="bi bi-calendar-event me-1"></i><?php echo htmlspecialchars($date); ?></div>
      <p class="mb-3"><?php echo htmlspecialchars($excerpt); ?></p>
      <a class="btn btn-sm btn-primary" href="<?php echo htmlspecialchars($permalink); ?>">გაიგე მეტი</a>
     </article>
    </div>
    <?php endforeach; else: ?>
    <div class="col-12"><div class="alert alert-info mb-0">სიახლეები დროებით არ არის.</div></div>
    <?php endif; ?>
   </div>
  </div>
 </section>

 <section id="ad-slot-2" class="py-3">
  <div class="container"><div class="ad-box"><div class="ok-ad-slot">
    <?php echo do_shortcode('[ok_ad slot="default" limit="1" order="random"]'); ?>
  </div></div></div>
 </section>

 <section id="testimonials" class="py-4 py-md-5">
  <div class="container">
    <?php echo do_shortcode('[ok_testimonials limit="3"]'); ?>
  </div>
 </section>

 <section id="map" class="py-0">
  <?php $gmaps_key = get_ok_option('google_maps_api_key', ''); ?>
  <?php if (!empty($gmaps_key)): ?>
   <div id="gmap" class="map-embed"></div>
  <?php else: ?>
   <div class="map-embed">
    <iframe src="https://maps.google.com/maps?q=41.77926,44.77282&z=16&output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Gudushauri Clinic"></iframe>
   </div>
  <?php endif; ?>
 </section>
</main>

<script>
// ===== Stats Counters: count-up on view =====
(function(){
 function easeOutCubic(t){ return 1 - Math.pow(1 - t, 3); }
 function animateCount(el){
  var target = parseInt(el.dataset.target, 10) || 0;
  var duration = 2400;
  var start = performance.now();
  function tick(now){
   var p = Math.min((now - start) / duration, 1);
   var val = Math.round(target * easeOutCubic(p));
   try { el.textContent = val.toLocaleString('ka-GE'); } catch(e) { el.textContent = val; }
   if(p < 1) requestAnimationFrame(tick);
  }
  requestAnimationFrame(tick);
 }
 var els = document.querySelectorAll('.ok-kpi-value');
 if ('IntersectionObserver' in window) {
  var io = new IntersectionObserver(function(entries, obs){
   entries.forEach(function(entry){
    if (entry.isIntersecting) {
     if (!entry.target.hasAttribute('data-target')) {
      entry.target.setAttribute('data-target', entry.target.textContent.replace(/\s/g, ''));
     }
     animateCount(entry.target);
     obs.unobserve(entry.target);
    }
   });
  }, { threshold: 0.4 });
  els.forEach(function(el){ io.observe(el); });
 } else {
  els.forEach(function(el) {
   if (!el.hasAttribute('data-target')) {
    el.setAttribute('data-target', el.textContent.replace(/\s/g, ''));
   }
   animateCount(el);
  });
 }
})();
</script>

<?php $gmaps_key = get_ok_option('google_maps_api_key', ''); if (!empty($gmaps_key)): ?>
<script>
// Google Maps — Custom Pin on Gudushauri Clinic
(function(){
 window.initGMap = function(){
  var el = document.getElementById('gmap');
  if(!el){ return; }
  var center = { lat: 41.77926, lng: 44.77282 };
  var map = new google.maps.Map(el, { center: center, zoom: 17, streetViewControl: false, mapTypeControl: false, fullscreenControl: false });
  var dpr = (window.devicePixelRatio||1);
  var iconUrl = dpr > 1 ? '/ok-content/themes/paata/images/pin@2x.png' : '/ok-content/themes/paata/images/pin.png';
  var marker = new google.maps.Marker({
   position: center,
   map: map,
   title: <?php echo json_encode($site_title, JSON_UNESCAPED_UNICODE); ?>,
   icon: { url: iconUrl, scaledSize: new google.maps.Size(64, 96), anchor: new google.maps.Point(32, 92) }
  });
  var info = new google.maps.InfoWindow({ content: <?php echo json_encode($site_title.' — '.$address, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?> });
  marker.addListener('click', function(){ info.open(map, marker); });
 };
})();
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo urlencode($gmaps_key); ?>&callback=initGMap" async defer></script>
<?php endif; ?>

<?php get_footer(); ?>
