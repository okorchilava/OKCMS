<?php
/**
 * თემის ატვირთვა (Upload & Extract Logic)
 */

// -----------------------------------------------------------------------------
// AJAX როუტერის დამმუშავებელი (PHP Execution Point)
// ეს ნაწილი მუშაობს POST მოთხოვნაზე და აბრუნებს JSON-ს.
// -----------------------------------------------------------------------------

// ვამოწმებთ, ეს არის თუ არა AJAX მოთხოვნა თემის ატვირთვისთვის.
// ეს უნდა შესრულდეს *ადრე*, სანამ რაიმე HTML დაიბეჭდება.
if (isset($_GET['page']) && $_GET['page'] === 'ok-theme-upload' &&
    isset($_GET['action']) && $_GET['action'] === 'upload_theme_ajax' &&
    $_SERVER['REQUEST_METHOD'] === 'POST')
{
    // ვიძახებთ ლოგიკას და ვაბრუნებთ JSON-ს
    header('Content-Type: application/json');
    
    // PHP ლოგიკის ფუნქცია
    function ok_handle_theme_upload_ajax_logic() {
        if (!current_user_can('manage_options')) {
            return ['success' => false, 'step' => 'permission', 'message' => 'უფლება არ გაქვთ.'];
        }
    
        $file = $_FILES['theme_zip'] ?? null;
        // სამიზნე საქაღალდე: [ROOT]/ok-content/themes/
        $target_dir = dirname(__DIR__, 2) . '/ok-content/themes/';

        // 🟢  აქ ვამატებთ: თუ საქაღალდე არ არსებობს – ვქმნით, მერე ვამოწმებთ ჩაწერადობას
        if (!is_dir($target_dir)) {
            if (!mkdir($target_dir, 0775, true)) {
                return [
                    'success' => false,
                    'step'    => 'extraction',
                    'message' => "ვერ შეიქმნა სამიზნე საქაღალდე: {$target_dir}"
                ];
            }
        }

        if (!is_writable($target_dir)) {
            return [
                'success' => false,
                'step'    => 'extraction',
                'message' => "სამიზნე საქაღალდე არ არის ჩასაწერად მზად. (Permission Denied: {$target_dir})"
            ];
        }
        
        // Step 1: ფაილის ვალიდაცია
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            // შეცდომის კოდები: 1 - დიდი ფაილი, 4 - ფაილი არ ატვირთულა
            return ['success' => false, 'step' => 'upload_validation', 'message' => "ფაილის ატვირთვის შეცდომა (Code: {$file['error']}). შეამოწმეთ ზომის ლიმიტი."];
        }
    
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (strtolower($ext) !== 'zip') {
            return ['success' => false, 'step' => 'extension_validation', 'message' => "დაშვებულია მხოლოდ .zip ფაილები."];
        }
        
        // Step 2: ამოარქივება
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive;
            if ($zip->open($file['tmp_name']) === TRUE) {
                
                $first_entry = $zip->getNameIndex(0);
                $parts = explode('/', $first_entry);
                $theme_name = reset($parts);

                if (empty($theme_name) || substr($first_entry, -1) !== '/') {
                    $theme_name = pathinfo($file['name'], PATHINFO_FILENAME);
                }
                
                if (is_dir($target_dir . $theme_name) && file_exists($target_dir . $theme_name . '/style.css')) {
                    return ['success' => false, 'step' => 'extraction', 'message' => "თემა <strong>{$theme_name}</strong> უკვე არსებობს."];
                }

                // (ეს შემოწმებაც შეიძლება დარჩეს – ზედმეტი არ აწყენს)
                if (!is_writable($target_dir)) {
                    return ['success' => false, 'step' => 'extraction', 'message' => "სამიზნე საქაღალდე არ არის ჩასაწერად მზად. (Permission Denied)"];
                }

                $zip->extractTo($target_dir);
                $zip->close();
                
                // Step 3: ინსტალაციის ვალიდაცია
                if (is_dir($target_dir . $theme_name) && file_exists($target_dir . $theme_name . '/style.css')) {
                    return [
                        'success'    => true,
                        'step'       => 'complete',
                        'message'    => "თემა <strong>{$theme_name}</strong> წარმატებით აიტვირთა და დაინსტალირდა!",
                        'theme_slug' => $theme_name
                    ];
                } else {
                    return [
                        'success' => false,
                        'step'    => 'extraction_validation',
                        'message' => "ამოარქივება დასრულდა, მაგრამ თემის საქაღალდე <strong>({$theme_name}/style.css)</strong> ვერ მოიძებნა. დარწმუნდით, რომ ZIP ფაილი შეიცავს ერთ ძირითად საქაღალდეს."
                    ];
                }

            } else {
                return ['success' => false, 'step' => 'extraction', 'message' => "ვერ მოხერხდა არქივის გახსნა. (Zip Error)"];
            }
        } else {
            return ['success' => false, 'step' => 'prerequisite', 'message' => "სერვერზე არ არის ჩართული PHP ZipArchive."];
        }
    }

    echo json_encode(ok_handle_theme_upload_ajax_logic());
    exit; // !!! კრიტიკულად მნიშვნელოვანია: აჩერებს გვერდის რენდერინგს AJAX-ის დროს
}
// -----------------------------------------------------------------------------


// 1. მენიუს რეგისტრაცია 
add_ok_action('admin_menu', function() {
        add_submenu_page(
            'ok-themes',
            'თემის ატვირთვა',
            'თემის ატვირთვა',
            'manage_options',
            'ok-theme-upload',
            'ok_render_theme_upload'
        );
    });


// -----------------------------------------------------------------------------
// ვიზუალი (ok_render_theme_upload) - HTML და Vanilla JS
// -----------------------------------------------------------------------------

function ok_render_theme_upload() {
    ?>
    <div class="row justify-content-center mt-5">
        <div class="col-md-6">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold text-dark mb-0">თემის ატვირთვა</h3>
                <a href="index.php?page=ok-themes" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i> უკან
                </a>
            </div>
            
            <div id="upload-form-wrapper" class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-5 text-center">
                    <form id="theme-upload-form" enctype="multipart/form-data">
                        
                        <div class="mb-4">
                            <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="bi bi-file-earmark-zip fs-1 text-primary"></i>
                            </div>
                            <h5 class="fw-bold">აირჩიეთ ZIP ფაილი</h5>
                            <p class="text-muted small">ატვირთეთ თემის არქივი (.zip). ის ავტომატურად გაიშლება <code>/themes/</code> საქაღალდეში.</p>
                        </div>

                        <div class="mb-4">
                            <input type="file" name="theme_zip" class="form-control form-control-lg" accept=".zip" required>
                        </div>

                        <button type="submit" class="btn btn-primary px-5 py-2 rounded-pill shadow-sm fw-bold">
                            <i class="bi bi-cloud-arrow-up me-2"></i> დაინსტალირება
                        </button>

                    </form>
                </div>
            </div>
            
            <div id="progress-wrapper" class="card border-0 shadow-sm rounded-4 d-none">
                <div class="card-body p-5">
                    <h5 class="fw-bold mb-4 text-center">ინსტალაციის პროცესი</h5>
                    <div class="list-group list-group-flush" id="progress-list">
                        
                        <div class="list-group-item d-flex align-items-center" id="step-upload">
                            <div class="spinner-border spinner-border-sm me-3 text-primary" role="status"></div>
                            <span class="step-text">მიმდინარეობს ფაილის ატვირთვა...</span>
                        </div>
                        <div class="list-group-item d-flex align-items-center text-muted" id="step-extract">
                            <i class="bi bi-circle me-3"></i>
                            <span class="step-text">ამოარქივება და ფაილების ვალიდაცია...</span>
                        </div>
                        <div class="list-group-item d-flex align-items-center text-muted" id="step-complete">
                            <i class="bi bi-circle me-3"></i>
                            <span class="step-text">ინსტალაციის დასრულება და ვალიდაცია...</span>
                        </div>
                        
                    </div>
                    
                    <div id="result-message" class="mt-4 pt-3 border-top text-center d-none"></div>
                    <div id="action-buttons" class="mt-4 pt-3 border-top text-center d-none">
                         <a href="index.php?page=ok-themes" class="btn btn-primary px-5 rounded-pill shadow-sm">თემებში დაბრუნება</a>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('theme-upload-form');
        const progressWrapper = document.getElementById('progress-wrapper');
        const formWrapper = document.getElementById('upload-form-wrapper');
        const progressList = document.getElementById('progress-list');
        const resultMessage = document.getElementById('result-message');
        const actionButtons = document.getElementById('action-buttons');
        
        // ფუნქცია: ეტაპების ვიზუალური განახლებისთვის
        function updateStep(stepId, status, message = '', isFinal = false) {
            const step = document.getElementById(stepId);
            if (!step) return;

            step.classList.remove('text-muted');
            
            let htmlContent = '';

            if (status === 'start') {
                htmlContent = `<div class="spinner-border spinner-border-sm me-3 text-primary" role="status"></div><span class="step-text">${message}</span>`;
            } else if (status === 'done') {
                htmlContent = `<i class="bi bi-check-circle-fill me-3 text-success"></i><span class="step-text fw-bold">${message}</span>`;
            } else if (status === 'error') {
                htmlContent = `<i class="bi bi-x-octagon-fill me-3 text-danger"></i><span class="step-text fw-bold text-danger">${message}</span>`;
                isFinal = true; 
            }
            
            step.innerHTML = htmlContent;

            // პროგრესის ხელით განახლება (მომდევნო ეტაპის დაწყება)
            if (stepId === 'step-upload' && status === 'done') {
                const extractStep = document.getElementById('step-extract');
                if (extractStep) extractStep.classList.remove('text-muted');
                if (extractStep) extractStep.innerHTML = '<div class="spinner-border spinner-border-sm me-3 text-primary" role="status"></div><span class="step-text">ამოარქივება და ფაილების ვალიდაცია...</span>';
            } else if (stepId === 'step-extract' && status === 'done') {
                 const completeStep = document.getElementById('step-complete');
                 if (completeStep) completeStep.classList.remove('text-muted');
                 if (completeStep) completeStep.innerHTML = '<div class="spinner-border spinner-border-sm me-3 text-primary" role="status"></div><span class="step-text">ინსტალაციის დასრულება და ვალიდაცია...</span>';
            }

            // დასრულების ლოგიკა
            if (isFinal) {
                const spinners = progressList.querySelectorAll('.spinner-border');
                spinners.forEach(s => s.remove());
                
                resultMessage.classList.remove('d-none');
                actionButtons.classList.remove('d-none');
                
                const actionLink = actionButtons.querySelector('a');

                if (status === 'done') {
                    resultMessage.innerHTML = '<h4 class="text-success fw-bold">ინსტალაცია დასრულდა!</h4><p class="small text-muted">თემა წარმატებით დაემატა.</p>';
                    actionLink.classList.remove('btn-danger');
                    actionLink.classList.add('btn-success');
                } else if (status === 'error') {
                    resultMessage.innerHTML = `<h4 class="text-danger fw-bold">ინსტალაცია ჩავარდა!</h4><p class="small text-danger">${message}</p>`;
                    actionLink.href = 'index.php?page=ok-theme-upload';
                    actionLink.textContent = 'ხელახლა ცდა';
                    actionLink.classList.remove('btn-success');
                    actionLink.classList.add('btn-danger');
                }
            }
        }
        
        // ფორმის გაგზავნა (Vanilla JS Fetch API-ით)
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // <<< აქ ვწყვეტთ სტანდარტულ (GET) გაგზავნას
            
            formWrapper.classList.add('d-none');
            progressWrapper.classList.remove('d-none');
            
            const formData = new FormData(this);
            // URL, რომელზეც PHP AJAX დამმუშავებელი ელოდება POST მოთხოვნას
            const url = 'index.php?page=ok-theme-upload&action=upload_theme_ajax';

            updateStep('step-upload', 'start', 'მიმდინარეობს ფაილის ატვირთვა...');
            
            fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(response => {
                
                if (response.success) {
                    updateStep('step-upload', 'done', 'ფაილი ატვირთულია.');
                    updateStep('step-extract', 'done', 'ამოარქივება წარმატებით დასრულდა.');
                    updateStep('step-complete', 'done', response.message, true);
                    
                } else {
                    let errorMessage = response.message || 'უცნობი შეცდომა.';
                    
                    if (response.step === 'extraction' || response.step === 'extraction_validation' || response.step === 'prerequisite') {
                        updateStep('step-upload', 'done', 'ფაილი ატვირთულია.');
                        updateStep('step-extract', 'error', errorMessage, true);
                        const completeStep = document.getElementById('step-complete');
                        if (completeStep) completeStep.remove();
                    } else {
                        updateStep('step-upload', 'error', errorMessage, true);
                        const extractStep = document.getElementById('step-extract');
                        const completeStep = document.getElementById('step-complete');
                        if (extractStep) extractStep.remove();
                        if (completeStep) completeStep.remove();
                    }
                }
            })
            .catch(error => {
                updateStep('step-upload', 'error', `კავშირის ან სერვერის შეცდომა: ${error.message}. (შეამოწმეთ Network ჩანართი)`, true);
                const extractStep = document.getElementById('step-extract');
                const completeStep = document.getElementById('step-complete');
                if (extractStep) extractStep.remove();
                if (completeStep) completeStep.remove();
            });
        });
    });
    </script>
    <?php
}
