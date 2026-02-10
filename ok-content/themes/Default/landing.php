<?php
/**
 * Template Name: Landing Page (Parallax)
 */

global $ok_query;
$post = isset($ok_query['object']) ? $ok_query['object'] : null;

if (!$post) { echo "Page not found"; exit; }

include 'header.php'; 

// ფონის სურათის ლოგიკა: თუ პოსტს აქვს სურათი, ვიყენებთ მას, თუ არა - გრადიენტს.
$bg_image = !empty($post->post_image) ? $post->post_image : '';
$has_bg = !empty($bg_image);
?>

<div class="landing-hero position-relative d-flex align-items-center justify-content-center" 
     style="<?php echo $has_bg ? "background-image: url('" . htmlspecialchars($bg_image) . "');" : "background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);"; ?>">
    
    <div class="hero-overlay"></div>

    <div class="container position-relative z-2 text-center text-white hero-content-box">
        <div class="row justify-content-center">
            <div class="col-lg-9 col-md-10">
                <h1 class="display-3 fw-bold mb-4 text-shadow hero-title fade-in-up">
                    <?php echo htmlspecialchars($post->post_title); ?>
                </h1>
                
                <p class="lead mb-5 opacity-90 hero-subtitle fade-in-up" style="animation-delay: 0.2s;">
                    თანამედროვე, სწრაფი და მოქნილი ძრავა თქვენი ვებ-გვერდისთვის.
                </p>

                <div class="d-flex justify-content-center gap-3 fade-in-up" style="animation-delay: 0.4s;">
                    <a href="#contact" class="btn btn-light btn-lg rounded-pill px-5 fw-bold shadow-lg text-primary btn-hover-effect">
                        დაგვიკავშირდით
                    </a>
                    <a href="#content" class="btn btn-outline-light btn-lg rounded-pill px-5 fw-bold btn-hover-effect">
                        ვრცლად
                    </a>
                </div>
            </div>
        </div>
    </div>

    <a href="#content" class="scroll-down-arrow text-white">
        <i class="bi bi-chevron-down"></i>
    </a>
</div>

<div class="container position-relative" id="content" style="z-index: 10;">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="landing-card bg-white p-5 rounded-4 shadow-lg border-0">
                <div class="post-content lh-lg">
                    <?php echo ok_do_shortcode($post->post_content); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="bg-light py-5 mt-5">
    <div class="container text-center">
        <h3 class="fw-bold mb-3">მზად ხართ დასაწყებად?</h3>
        <p class="text-muted mb-4">შექმენით თქვენი ოცნების ვებ-გვერდი დღესვე.</p>
        <button class="btn btn-primary rounded-pill px-4 py-2">შეკვეთის გაფორმება</button>
    </div>
</div>

<style>
    /* --- PARALLAX HERO --- */
    .landing-hero {
        height: 90vh; /* მთელი ეკრანი თითქმის */
        min-height: 600px;
        width: 100%;
        background-position: center;
        background-repeat: no-repeat;
        background-size: cover;
        
        /* 🛑 მთავარი პარალაქს ეფექტი */
        background-attachment: fixed;
    }

    .hero-overlay {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        background: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.7)); /* გრადიენტული დაბნელება */
        z-index: 1;
    }

    .text-shadow { text-shadow: 0 4px 15px rgba(0,0,0,0.5); }

    /* --- OVERLAPPING CARD --- */
    .landing-card {
        margin-top: -100px; /* 🛑 აცოცების ეფექტი */
        background: #fff;
        padding: 3rem 4rem !important;
        position: relative;
    }

    /* --- ANIMATIONS --- */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .fade-in-up {
        opacity: 0;
        animation: fadeInUp 0.8s ease-out forwards;
    }

    /* Scroll Arrow */
    .scroll-down-arrow {
        position: absolute;
        bottom: 30px;
        left: 50%;
        transform: translateX(-50%);
        font-size: 2rem;
        z-index: 5;
        animation: bounce 2s infinite;
        opacity: 0.8;
        transition: opacity 0.3s;
    }
    .scroll-down-arrow:hover { opacity: 1; }

    @keyframes bounce {
        0%, 20%, 50%, 80%, 100% {transform: translateX(-50%) translateY(0);}
        40% {transform: translateX(-50%) translateY(-10px);}
        60% {transform: translateX(-50%) translateY(-5px);}
    }

    /* Button Hover */
    .btn-hover-effect { transition: transform 0.2s, box-shadow 0.2s; }
    .btn-hover-effect:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.2) !important; }

    /* --- MOBILE RESPONSIVE --- */
    @media (max-width: 991px) {
        .landing-hero {
            height: 70vh;
            background-attachment: scroll; /* მობილურზე პარალაქსი ითიშება პერფორმანსისთვის */
        }
        .landing-card {
            margin-top: -50px;
            padding: 2rem !important;
        }
        .display-3 { font-size: 2.5rem; }
    }
</style>

<?php include 'footer.php'; ?>