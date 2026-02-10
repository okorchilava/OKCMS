<?php 
// ვიძახებთ საიტის დიზაინის თავს
get_header(); 
?>

<main class="container py-5 text-center">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h1 class="display-1 fw-bold">404</h1>
            <p class="fs-3"> <span class="text-danger">Oops!</span> გვერდი ვერ მოიძებნა.</p>
            <p class="lead">
                ბოდიშს გიხდით, თქვენ მიერ მოთხოვნილი გვერდი არ არსებობს ან გადატანილია.
            </p>
            <a href="/" class="btn btn-primary mt-3">მთავარ გვერდზე დაბრუნება</a>
        </div>
    </div>
</main>

<?php 
// ვიძახებთ საიტის დიზაინის ბოლოს
get_footer(); 
?>