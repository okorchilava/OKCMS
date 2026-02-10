<?php include 'header.php'; 
global $ok_query;
$page = isset($ok_query['object']) ? $ok_query['object'] : null;

// უსაფრთხოება
if (!$page) { echo "Page not found"; return; }

$show_sidebar = isset($ok_query['has_sidebar']) ? $ok_query['has_sidebar'] : false;
$has_cover = !empty($page->post_image);
?>

<main class="site-main">

<?php if ($has_cover): ?>
    <div class="page-hero position-relative d-flex align-items-center justify-content-center text-center text-white mb-5" style="background-image: url('<?php echo htmlspecialchars($page->post_image); ?>');">
        <div class="hero-overlay position-absolute top-0 start-0 w-100 h-100 bg-dark opacity-50"></div>
        
        <div class="container position-relative z-2">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <h1 class="fw-bold display-8 text-shadow"><?php echo htmlspecialchars($page->post_title); ?></h1>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="container mt-5 mb-4 text-center">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <h1 class="fw-bold display-5 text-dark"><?php echo htmlspecialchars($page->post_title); ?></h1>
                <hr class="mt-4 mx-auto" style="width: 100px; height: 3px; background-color: #0d6efd; opacity: 1;">
            </div>
        </div>
    </div>
<?php endif; ?>

    <div class="container mb-5 pb-5"> 
        <div class="row <?php echo $show_sidebar ? '' : 'justify-content-center'; ?>">
            
            <div class="<?php echo $show_sidebar ? 'col-lg-8' : 'col-lg-10'; ?>">
                <div class="page-content post-content lh-lg">
                    <?php echo ok_do_shortcode($page->post_content); ?>
                </div>
            </div>

            <?php if ($show_sidebar): ?>
                <div class="col-lg-4">
                    <div class="ps-lg-4 mt-5 mt-lg-0">
                        <?php include 'sidebar.php'; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>

</main>

<style>
    /* --- 🛑 HERO PARALLAX STYLES --- */
    .page-hero {
        height: 250px; /* ოდნავ გავზარდეთ სიმაღლე ეფექტისთვის */
        width: 100%;
        
        /* ფონის პარამეტრები */
        background-size: cover;
        background-position: center center; /* ცენტრირება */
        background-repeat: no-repeat;
        
        /* 🛑 პარალაქსის ეფექტი - ფიქსირებული ფონი */
        background-attachment: fixed; 
    }

    /* მობილურზე პარალაქსი ხშირად პრობლემურია, ამიტომ ვთიშავთ */
    @media (max-width: 768px) {
        .page-hero {
            background-attachment: scroll; /* სტანდარტული ქცევა მობილურზე */
            height: 150px; /* სიმაღლის შემცირება მობილურისთვის */
        }
        .page-hero h1 {
            font-size: 2.5rem; /* შრიფტის შემცირება */
        }
    }

    /* ტექსტის ჩრდილი */
    .text-shadow { 
        text-shadow: 2px 2px 10px rgba(0,0,0,0.8); 
    }

    /* CONTENT STYLES */
    .post-content {
        font-size: 1.1rem;
        color: #333;
    }
    .post-content p { margin-bottom: 1.5rem; }
    
    .post-content img { 
        max-width: 100%; height: auto; 
        border-radius: 12px; margin: 30px 0; 
        box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
    }
    
    .post-content h2 { margin-top: 3rem; margin-bottom: 1.5rem; font-weight: 800; color: #1a1a1a; }
    .post-content ul, .post-content ol { margin-bottom: 1.5rem; padding-left: 2rem; }
    
    .post-content blockquote {
        border-left: 5px solid #0d6efd;
        padding: 25px 40px;
        margin: 40px 0;
        font-style: italic;
        background: #f8f9fa;
        color: #555;
        border-radius: 0 10px 10px 0;
    }
</style>

<?php include 'footer.php'; ?>