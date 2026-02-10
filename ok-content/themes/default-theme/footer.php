<footer class="site-footer">
    <div class="container">
        <p class="text-center mb-0">
            <?php
                global $ok_hooks;
                $footer_text = '&copy; ' . date('Y') . ' ' . get_ok_option('site_title');
                echo $ok_hooks->apply_filters('footer_credit', $footer_text);
            ?>
        </p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

</body>
</html>