<footer class="site-footer bg-dark text-white py-5 mt-auto border-top">
    <div class="container-fluid px-4 px-lg-5">
        
        <div class="row g-4">
            
            <div class="col-md-3">
                <?php if (function_exists('ok_dynamic_sidebar')) ok_dynamic_sidebar('footer-1'); ?>
            </div>

            <div class="col-md-3">
                <?php if (function_exists('ok_dynamic_sidebar')) ok_dynamic_sidebar('footer-2'); ?>
            </div>

            <div class="col-md-3">
                <?php if (function_exists('ok_dynamic_sidebar')) ok_dynamic_sidebar('footer-3'); ?>
            </div>
            
            <div class="col-md-3">
                <?php if (function_exists('ok_dynamic_sidebar')) ok_dynamic_sidebar('footer-4'); ?>
            </div>

        </div>

        <div class="row mt-5 pt-3 border-top border-secondary opacity-50">
            <div class="col-12 text-center small">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> <strong><?php echo htmlspecialchars(get_ok_option('site_title')); ?></strong>. ყველა უფლება დაცულია.</p>
            </div>
        </div>

    </div>
</footer>

<style>

    /* ფუთერში ვიჯეტების დიზაინის ადაპტაცია */
    .site-footer .card {
        background-color: transparent !important; /* გამჭვირვალე ფონი */
        border: none !important; /* ჩარჩოს გარეშე */
        box-shadow: none !important; /* ჩრდილის გარეშე */
    }
    
    .site-footer .card-header {
        background-color: transparent !important;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        padding-left: 0;
        padding-right: 0;
    }

    .site-footer .card-header h5 {
        color: #fff; /* სათაური თეთრად */
        text-transform: uppercase;
        font-size: 0.9rem;
        letter-spacing: 1px;
    }

    .site-footer .card-body {
        padding-left: 0 !important;
        padding-right: 0 !important;
        color: rgba(255,255,255,0.7); /* ტექსტი ოდნავ ნაცრისფრად */
    }

    /* ლინკები ფუთერში */
    .site-footer a {
        color: rgba(255,255,255,0.7) !important;
        transition: color 0.2s;
    }
    .site-footer a:hover {
        color: #fff !important;
        text-decoration: underline;
    }

    /* სიის ელემენტები */
    .site-footer .list-group-item {
        background-color: transparent !important;
        border-color: rgba(255,255,255,0.1) !important;
        color: rgba(255,255,255,0.7) !important;
        padding-left: 0;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>