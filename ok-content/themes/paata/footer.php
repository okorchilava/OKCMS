<?php
/**
 * Theme Footer — copyright + links + social icons
 * Footer widget links always visible (inherit footer text color)
 */
?>
<footer class="site-footer bg-dark text-light">
  <div class="container-fluid px-0">
    <div class="px-3 px-md-4 px-lg-5 pt-3 pb-0">
      <?php if (isset($ok_hooks)) { $ok_hooks->do_action('footer_widgets_before'); } ?>

      <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3 g-lg-4 mx-0 align-items-start">
        <div class="col">
          <div class="footer-stack d-flex flex-column gap-3">
            <?php if (function_exists('dynamic_footer')) { dynamic_footer('footer-area1'); } ?>
          </div>
        </div>
        <div class="col">
          <div class="footer-stack d-flex flex-column gap-3">
            <?php if (function_exists('dynamic_footer')) { dynamic_footer('footer-area2'); } ?>
          </div>
        </div>
        <div class="col">
          <div class="footer-stack d-flex flex-column gap-3">
            <?php if (function_exists('dynamic_footer')) { dynamic_footer('footer-area3'); } ?>
          </div>
        </div>
        <div class="col">
          <div class="footer-stack d-flex flex-column gap-3">
            <?php if (function_exists('dynamic_footer')) { dynamic_footer('footer-area4'); } ?>
          </div>
        </div>
      </div>

      <?php if (isset($ok_hooks)) { $ok_hooks->do_action('footer_widgets_after'); } ?>

      <hr class="border-secondary opacity-25 my-2">

      <div class="d-flex justify-content-between align-items-center mt-2 footer-bottom flex-wrap gap-2">
        <p class="mb-0 small">
          <?php
            global $ok_hooks;
            $footer_text = '&copy; ' . date('Y') . ' ' . htmlspecialchars(get_ok_option('site_title', 'OK Engine'));
            echo isset($ok_hooks) ? $ok_hooks->apply_filters('footer_credit', $footer_text) : $footer_text;
          ?>
        </p>
        <div class="footer-links d-flex gap-3 small">
          <a href="/terms">მომსახურების პირობები</a>
          <a href="/privacy">კონფიდენციალობა</a>
          <a href="/docs">დოკუმენტები</a>
        </div>
        <div class="footer-social d-flex gap-3">
          <a href="#" aria-label="Facebook"><i class="bi bi-facebook fs-5"></i></a>
          <a href="#" aria-label="Twitter"><i class="bi bi-twitter fs-5"></i></a>
          <a href="#" aria-label="Instagram"><i class="bi bi-instagram fs-5"></i></a>
          <a href="#" aria-label="LinkedIn"><i class="bi bi-linkedin fs-5"></i></a>
        </div>
      </div>
    </div>
  </div>
</footer>

<style>
  .site-footer { background-color:#0f172a; color:#cbd5e1; margin-bottom:0; padding-bottom:0 !important; }
  .site-footer a { color: inherit !important; text-decoration: none; }
  .site-footer a:hover { color:#0dcaf0 !important; text-decoration: underline; }
  .site-footer .footer-stack .widget{ width:100%; }
  .site-footer .widget-title, .site-footer h5, .site-footer h6{ font-size:1rem; margin-bottom:.5rem; }
  .site-footer .widget p, .site-footer .widget li, .site-footer .widget a{ font-size:.9375rem; }
  .footer-bottom { margin-bottom:0; }
  @media (min-width: 992px){ .site-footer .footer-stack{ gap: .75rem !important; } }
</style>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<script>
/*
 * Responsive navbar collapse control (pure JS, no data-API conflicts).
 * - Toggle via #navToggleBtn
 * - Close on nav-link click (<992px), outside click, Esc, and on resize to >=992px
 * - Sync aria-expanded for burger animation
 */
document.addEventListener('DOMContentLoaded', function () {
  var menuEl  = document.getElementById('mainNav');
  var toggler = document.getElementById('navToggleBtn');
  if (!menuEl || !toggler) return;

  var collapse = bootstrap.Collapse.getOrCreateInstance(menuEl, { toggle: false });

  // Programmatic toggle
  toggler.addEventListener('click', function (e) {
    e.preventDefault();
    if (menuEl.classList.contains('show')) { collapse.hide(); } else { collapse.show(); }
  });

  // Close on nav-link click (mobile)
  menuEl.addEventListener('click', function (e) {
    var link = e.target.closest('.nav-link');
    if (link && window.innerWidth < 992 && menuEl.classList.contains('show')) { collapse.hide(); }
  });

  // Click outside → close (mobile)
  document.addEventListener('click', function (e) {
    var inside = menuEl.contains(e.target) || toggler.contains(e.target);
    if (!inside && window.innerWidth < 992 && menuEl.classList.contains('show')) { collapse.hide(); }
  });

  // Esc → close
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && menuEl.classList.contains('show')) { collapse.hide(); }
  });

  // On resize to desktop, ensure closed
  window.addEventListener('resize', function () {
    if (window.innerWidth >= 992 && menuEl.classList.contains('show')) { collapse.hide(); }
  });

  // aria-expanded sync
  menuEl.addEventListener('shown.bs.collapse',  function(){ toggler.setAttribute('aria-expanded','true');  });
  menuEl.addEventListener('hidden.bs.collapse', function(){ toggler.setAttribute('aria-expanded','false'); });
});
</script>
<?php 
// --- შესწორება: ვამატებთ ჰუკს ---
// ეს ხაზი აუცილებელია პლაგინების გამართული მუშაობისთვის
do_action('ok_footer'); 
?>
</body>
</html>
