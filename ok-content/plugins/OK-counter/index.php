<?php
/*
Plugin Name: OK Smart Counter (Clean Design)
Description: ანიმირებული მთვლელი ვიჯეტი (სრულიად გამჭვირვალე).
Version: 3.2
Author: OK Engine Team
*/

// =============================================================================
// 1. მენიუს რეგისტრაცია (ADMIN MENU)
// =============================================================================

if (function_exists('ok_add_action')) {
    ok_add_action('admin_menu', 'ok_counter_register_menu');
}

function ok_counter_register_menu() {
    if (function_exists('add_menu_page')) {
        add_menu_page(
            'Counter პარამეტრები',   
            'Counter პლაგინი',       
            'manage_options',        
            'ok-counter-settings',   
            'ok_counter_render_page',
            'bi bi-stopwatch'        
        );
    }
}

// =============================================================================
// 2. ადმინ გვერდის რენდერი (RENDER PAGE)
// =============================================================================

function ok_counter_render_page() {
    if (isset($_POST['save_counter_global'])) {
        update_ok_option('ok_counter_default_color', $_POST['default_color']);
        echo '<div class="alert alert-success m-4 shadow-sm border-0"><i class="bi bi-check-circle me-2"></i> პარამეტრები შენახულია!</div>';
    }

    $default_color = get_ok_option('ok_counter_default_color', '#0d6efd');
    ?>
    <div class="container-fluid p-4">
        <h2 class="mb-4 fw-bold text-dark"><i class="bi bi-stopwatch me-2 text-primary"></i> Counter პლაგინის მართვა</h2>
        
        <div class="row g-4">
            <div class="col-md-6">
                <form method="post" class="bg-white p-4 shadow-sm rounded-4 border">
                    <h5 class="border-bottom pb-3 mb-4 text-primary fw-bold">გლობალური პარამეტრები</h5>
                    <div class="mb-4">
                        <label class="form-label fw-bold text-muted small text-uppercase">ნაგულისხმევი ფერი</label>
                        <div class="d-flex align-items-center bg-light p-2 rounded border">
                            <input type="color" name="default_color" class="form-control form-control-color border-0 me-3" value="<?php echo htmlspecialchars($default_color); ?>">
                            <span class="text-muted small">ეს ფერი გამოიყენება, თუ ვიჯეტში ფერი არ აირჩიეთ.</span>
                        </div>
                    </div>
                    <button type="submit" name="save_counter_global" class="btn btn-primary px-4 fw-bold shadow-sm">
                        <i class="bi bi-save me-2"></i> შენახვა
                    </button>
                </form>
            </div>
            <div class="col-md-6">
                <div class="alert alert-light border shadow-sm rounded-4 p-4">
                    <h5 class="alert-heading fw-bold mb-3"><i class="bi bi-info-circle-fill me-2 text-info"></i> ინსტრუქცია</h5>
                    <p class="text-secondary">ეს პლაგინი ამატებს ახალ ვიჯეტს: <strong>"OK - მთვლელი"</strong>.</p>
                    <hr>
                    <p class="mb-0 text-muted small">გადადით: <strong>ვიჯეტები -> OK Counter</strong> და ჩააგდეთ ზონაში.</p>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// =============================================================================
// 3. ვიჯეტის რეგისტრაცია (NO STYLE / CLEAN)
// =============================================================================

if (function_exists('ok_register_widget')) {

    ok_register_widget('ok_counter', [
        'name' => 'OK - მთვლელი (Counter)',
        'icon' => 'bi-stopwatch',
        'desc' => 'ანიმირებული რიცხვები აიკონით (სუფთა დიზაინი).',
        
        // --- ADMIN FORM ---
        'admin_template' => '
            <div class="mb-3">
                <label class="small fw-bold text-muted">სათაური (ზემოთ):</label>
                <input type="text" name="__NAME_PREFIX__[title]" class="form-control form-control-sm" value="__TITLE__" placeholder="სტატისტიკა">
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="small fw-bold text-muted">რიცხვი:</label>
                    <input type="number" name="__NAME_PREFIX__[number]" class="form-control form-control-sm" value="__NUMBER__" placeholder="100">
                </div>
                <div class="col-6">
                    <label class="small fw-bold text-muted">სიჩქარე (ms):</label>
                    <input type="number" name="__NAME_PREFIX__[duration]" class="form-control form-control-sm" value="__DURATION__" placeholder="2000">
                </div>
            </div>

            <div class="mb-3">
                <label class="small fw-bold text-muted">აღწერა (ქვემოთ):</label>
                <input type="text" name="__NAME_PREFIX__[label]" class="form-control form-control-sm" value="__LABEL__" placeholder="პროექტი">
            </div>

            <div class="row g-2 align-items-end mb-3">
                <div class="col-8">
                    <label class="small fw-bold text-muted">აიკონი (class):</label>
                    <input type="text" name="__NAME_PREFIX__[icon]" class="form-control form-control-sm" value="__ICON__" placeholder="bi-check-circle">
                </div>
                <div class="col-4">
                    <label class="small fw-bold text-muted">ფერი:</label>
                    <input type="color" name="__NAME_PREFIX__[color]" class="form-control form-control-color w-100" value="__COLOR__">
                </div>
            </div>
        ',

        // --- RENDER CALLBACK (CLEAN HTML) ---
        'render_callback' => function($data) {
            
            $title    = !empty($data['title']) ? $data['title'] : '';
            $number   = !empty($data['number']) ? (int)$data['number'] : 100;
            $label    = !empty($data['label']) ? $data['label'] : 'პროექტი';
            $icon     = !empty($data['icon']) ? $data['icon'] : 'bi-check-circle';
            $duration = !empty($data['duration']) ? (int)$data['duration'] : 2000;
            
            $global_color = get_ok_option('ok_counter_default_color', '#0d6efd');
            $color = (!empty($data['color']) && $data['color'] !== '#000000') ? $data['color'] : $global_color;

            // 🛑 სრულიად სუფთა კონტეინერი (არანაირი card, shadow, border)
            echo '<div class="ok-counter-item text-center mb-4">';
            
            // აიკონი (ფონის გარეშე, პირდაპირ აიკონი)
            echo '<div class="mb-2">';
            echo '<i class="bi '.htmlspecialchars($icon).'" style="font-size: 3rem; color: '.$color.';"></i>';
            echo '</div>';

            // რიცხვი
            echo '<div class="counter-value fw-bold" style="font-size: 3rem; color: #2c3e50; line-height: 1;" data-target="'.$number.'" data-duration="'.$duration.'">0</div>';

            // სათაური
            if ($title) {
                echo '<div class="fw-bold text-uppercase small text-muted mt-2 ls-1">'.htmlspecialchars($title).'</div>';
            }

            // აღწერა
            echo '<div class="text-secondary small">'.htmlspecialchars($label).'</div>';

            echo '</div>'; // end wrapper

            // JS (იგივე რჩება)
            if (!defined('OK_COUNTER_JS_LOADED')) {
                define('OK_COUNTER_JS_LOADED', true);
                ?>
                <script>
                document.addEventListener("DOMContentLoaded", () => {
                    const counters = document.querySelectorAll('.counter-value');
                    const observer = new IntersectionObserver((entries, observer) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                const el = entry.target;
                                const target = parseInt(el.getAttribute('data-target'));
                                const duration = parseInt(el.getAttribute('data-duration'));
                                const step = target / (duration / 16); 
                                let current = 0;
                                const updateCounter = () => {
                                    current += step;
                                    if (current < target) {
                                        el.innerText = Math.ceil(current);
                                        requestAnimationFrame(updateCounter);
                                    } else {
                                        el.innerText = target;
                                    }
                                };
                                updateCounter();
                                observer.unobserve(el);
                            }
                        });
                    }, { threshold: 0.5 });
                    counters.forEach(counter => observer.observe(counter));
                });
                </script>
                <?php
            }
        }
    ]);
}