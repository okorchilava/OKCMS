<?php include 'header.php'; ?>

<div class="error-page d-flex align-items-center justify-content-center py-5" style="min-height: 70vh;">
    <div class="container text-center">
        
        <div class="mb-4 position-relative d-inline-block">
            <h1 class="fw-bold text-light" style="font-size: 10rem; line-height: 1; user-select: none; color: #e9ecef !important;">404</h1>
            
            <div class="position-absolute top-50 start-50 translate-middle">
                <i class="bi bi-bandaid-fill text-primary" style="font-size: 5rem;"></i>
            </div>
        </div>

        <h2 class="display-6 fw-bold text-dark mb-3">უი! გვერდი ვერ მოიძებნა.</h2>
        <p class="lead text-muted mb-5 mx-auto" style="max-width: 500px;">
            სამწუხაროდ, გვერდი რომელსაც ეძებთ, წაშლილია ან მისამართი არასწორად აკრიფეთ.
        </p>

        <div class="d-flex justify-content-center gap-3">
            <a href="/" class="btn btn-primary rounded-pill px-4 py-2 shadow-sm">
                <i class="bi bi-house-door me-2"></i> მთავარი
            </a>
            <a href="javascript:history.back()" class="btn btn-outline-secondary rounded-pill px-4 py-2">
                <i class="bi bi-arrow-left me-2"></i> უკან
            </a>
        </div>

    </div>
</div>

<style>
    /* 404 სპეციფიური სტილი */
    .error-page {
        background: radial-gradient(circle, rgba(232,244,255,1) 0%, rgba(255,255,255,1) 100%);
    }
</style>

<?php include 'footer.php'; ?>