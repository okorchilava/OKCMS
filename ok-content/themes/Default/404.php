<?php include 'header.php'; ?>

<div class="container">
    <div class="row justify-content-center align-items-center" style="min-height: 60vh;">
        <div class="col-lg-8 text-center">
            
            <h1 class="error-code fw-bold text-primary opacity-25">404</h1>
            
            <h2 class="fw-bold mb-3 text-dark">გვერდი ვერ მოიძებნა</h2>
            
            <p class="text-muted lead mb-5">
                სამწუხაროდ, გვერდი, რომელსაც ეძებთ, არ არსებობს, წაშლილია ან მისი მისამართი შეიცვალა.
            </p>

            <div class="d-flex justify-content-center gap-3">
                <a href="/" class="btn btn-primary rounded-pill px-4 py-2 shadow-sm transition">
                    <i class="bi bi-house-door me-2"></i> მთავარზე დაბრუნება
                </a>
                
                <button onclick="history.back()" class="btn btn-outline-secondary rounded-pill px-4 py-2">
                    <i class="bi bi-arrow-left me-2"></i> უკან
                </button>
            </div>

        </div>
    </div>
</div>

<style>
    /* 404 სპეციფიკური სტილები */
    .error-code {
        font-size: 8rem;
        line-height: 1;
        margin-bottom: 1rem;
        letter-spacing: -5px;
        /* ტექსტი რომ არ მონიშნონ შემთხვევით */
        user-select: none; 
    }

    /* მობილურზე ზომის შემცირება */
    @media (max-width: 768px) {
        .error-code {
            font-size: 5rem;
        }
    }
</style>

</main>

<?php include 'footer.php'; ?>