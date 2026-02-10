<?php
/**
 * პლაგინის ატვირთვა (Upload ZIP)
 */

// 1. რეგისტრაცია (ქვემენიუ პლაგინებში)
add_ok_action('admin_menu', function() {
        add_submenu_page(
            'ok-plugins',         // მშობელი (ok-plugins)
            'პლაგინის ატვირთვა',  // სათაური
            'ატვირთვა',           // მენიუ
            'manage_options',     // უფლება
            'ok-upload-plugin',   // Slug
            'ok_render_upload_plugin'
        );
    });

// 2. ვიზუალი და ლოგიკა
function ok_render_upload_plugin() {
    
    // ატვირთვის ლოგიკა
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['plugin_zip'])) {
        
        $file = $_FILES['plugin_zip'];
        $upload_dir = dirname(__DIR__) . '/ok-content/plugins/';
        
        // შემოწმება
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = "ფაილის ატვირთვა ვერ მოხერხდა (Error Code: {$file['error']})";
        } elseif (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'zip') {
            $error = "გთხოვთ ატვირთოთ მხოლოდ .zip ფორმატის ფაილი.";
        } else {
            // ZipArchive-ის გამოყენება
            $zip = new ZipArchive;
            if ($zip->open($file['tmp_name']) === TRUE) {
                
                // ვშლით საქაღალდეში
                $zip->extractTo($upload_dir);
                $zip->close();
                
                echo '<div class="alert alert-success m-4 shadow-sm border-0"><i class="bi bi-check-circle me-2"></i> პლაგინი წარმატებით დაინსტალირდა! <a href="index.php?page=ok-plugins" class="fw-bold">გადასვლა სიაზე</a></div>';
                return; // აღარ ვაჩვენოთ ფორმა
                
            } else {
                $error = "ვერ მოხერხდა ZIP ფაილის გახსნა.";
            }
        }
    }

    if (isset($error)) {
        echo '<div class="alert alert-danger m-4 shadow-sm border-0"><i class="bi bi-exclamation-triangle me-2"></i> ' . $error . '</div>';
    }
    ?>

    <div class="container-fluid" style="max-width: 800px;">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 fw-bold">პლაგინის ატვირთვა</h1>
            <a href="index.php?page=ok-plugins" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
                <i class="bi bi-arrow-left me-1"></i> უკან
            </a>
        </div>

        <div class="card border-0 shadow-sm text-center py-5">
            <div class="card-body">
                <div class="mb-4">
                    <i class="bi bi-cloud-arrow-up display-1 text-primary opacity-25"></i>
                </div>
                <h4 class="mb-3">ატვირთეთ პლაგინი (.zip)</h4>
                <p class="text-muted mb-4">თუ გაქვთ პლაგინი ZIP ფორმატში, შეგიძლიათ აქედან დააინსტალიროთ.</p>

                <form method="post" enctype="multipart/form-data" class="d-inline-block text-start" style="max-width: 400px; width: 100%;">
                    <div class="mb-3">
                        <input type="file" name="plugin_zip" class="form-control" accept=".zip" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-box-seam me-2"></i> დაინსტალირება
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
    <?php
}