<footer class="site-footer bg-dark text-white pt-5 pb-4 mt-auto" style="background-color: #1a252f !important;">
    <div class="container-fluid px-4 px-lg-5">
        <div class="row g-4 justify-content-between">

            <div class="col-lg-3 col-md-6">
                <div class="footer-widget h-100">
                    <?php if (function_exists('ok_dynamic_sidebar')): ?>
                        <?php ok_dynamic_sidebar('footer-1'); ?>
                    <?php else: ?>
                        <h5 class="fw-bold text-white mb-3"><?php echo htmlspecialchars(get_ok_option('site_title', 'Dr. Paata')); ?></h5>
                        <p class="text-white-50 small mb-3">
                            თანამედროვე სამედიცინო სერვისები და პროფესიონალიზმი თქვენს სამსახურში. ჩვენ ვზრუნავთ თქვენს ჯანმრთელობაზე.
                        </p>
                        <div class="social-links">
                            <a href="#" class="text-white me-3"><i class="bi bi-facebook"></i></a>
                            <a href="#" class="text-white me-3"><i class="bi bi-instagram"></i></a>
                            <a href="#" class="text-white"><i class="bi bi-linkedin"></i></a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="footer-widget h-100">
                    <?php if (function_exists('ok_dynamic_sidebar')): ?>
                        <?php ok_dynamic_sidebar('footer-2'); ?>
                    <?php else: ?>
                        <h5 class="fw-bold text-white mb-3">ნავიგაცია</h5>
                        <ul class="list-unstyled text-white-50 small footer-links">
                            <li class="mb-2"><a href="/" class="text-white-50 text-decoration-none transition-hover"><i class="bi bi-chevron-right me-1"></i> მთავარი</a></li>
                            <li class="mb-2"><a href="#services" class="text-white-50 text-decoration-none transition-hover"><i class="bi bi-chevron-right me-1"></i> სერვისები</a></li>
                            <li class="mb-2"><a href="/about" class="text-white-50 text-decoration-none transition-hover"><i class="bi bi-chevron-right me-1"></i> ჩვენ შესახებ</a></li>
                            <li class="mb-2"><a href="/contact" class="text-white-50 text-decoration-none transition-hover"><i class="bi bi-chevron-right me-1"></i> კონტაქტი</a></li>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="footer-widget h-100">
                    <?php if (function_exists('ok_dynamic_sidebar')): ?>
                        <?php ok_dynamic_sidebar('footer-3'); ?>
                    <?php else: ?>
                        <h5 class="fw-bold text-white mb-3">კონტაქტი</h5>
                        <ul class="list-unstyled text-white-50 small">
                            <li class="mb-3 d-flex"><i class="bi bi-geo-alt me-2 text-primary"></i> თბილისი, ჭავჭავაძის 1</li>
                            <li class="mb-3 d-flex"><i class="bi bi-telephone me-2 text-primary"></i> +995 555 00 00 00</li>
                            <li class="mb-3 d-flex"><i class="bi bi-envelope me-2 text-primary"></i> info@drpaata.ge</li>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="footer-widget h-100">
                    <?php if (function_exists('ok_dynamic_sidebar')): ?>
                        <?php ok_dynamic_sidebar('footer-4'); ?>
                    <?php else: ?>
                        <h5 class="fw-bold text-white mb-3">სამუშაო საათები</h5>
                        <table class="table table-dark table-borderless table-sm small text-white-50">
                            <tbody>
                                <tr><td>ორშ - პარ:</td><td class="text-end">10:00 - 18:00</td></tr>
                                <tr><td>შაბათი:</td><td class="text-end">10:00 - 14:00</td></tr>
                                <tr><td>კვირა:</td><td class="text-end text-danger">დაკეტილია</td></tr>
                            </tbody>
                        </table>
                        <a href="/contact" class="btn btn-primary btn-sm w-100 rounded-pill fw-bold">ჩაწერა</a>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <div class="container-fluid px-4 px-lg-5 mt-5 pt-3 border-top border-secondary border-opacity-25 text-center">
        <p class="small text-white-50 mb-1">
            &copy; <?php echo date('Y'); ?> 
            <strong><?php echo htmlspecialchars(get_ok_option('site_title', 'Dr. Paata')); ?></strong>. 
            ყველა უფლება დაცულია.
        </p>
        <p class="small text-white-50 mb-0">
            POWERED BY <strong class="text-white">OK CSM</strong>
        </p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<style>
    /* Footer Specific Styles */
    .footer-links a:hover { color: #fff !important; padding-left: 5px; }
    .transition-hover { transition: all 0.3s; }
</style>

<?php 
// ✅ Hook Injection Point:
// ეს კოდი გაეშვება მას შემდეგ, რაც მთელი HTML, JS და CSS ჩაიტვირთება.
if (function_exists('add_ok_action')) {
    do_ok_action('ok_footer');
}
?>

</body>
</html>