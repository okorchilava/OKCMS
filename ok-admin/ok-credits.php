<?php
/**
 * OK Engine - Credits / About Page
 */

// მენიუში დამატება (პარამეტრების ქვემენიუდ)
add_ok_action('admin_menu', function() {
        add_submenu_page('ok-settings', 'სისტემის შესახებ', 'სისტემის შესახებ', 'read', 'ok-credits', 'ok_render_credits');
    });

function ok_render_credits() {
    global $ok_db;
    ?>
    
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
            
            <div class="text-center py-5 mb-4">
                <div class="d-inline-flex align-items-center justify-content-center bg-white shadow-sm rounded-circle mb-4" style="width: 80px; height: 80px;">
                    <i class="bi bi-speedometer2 text-primary display-4"></i>
                </div>
                <h1 class="fw-bold display-6 mb-2">OK Engine</h1>
                <p class="text-muted lead">მძლავრი, სწრაფი და მოქნილი მართვის სისტემა.</p>
                <div class="mt-3">
                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill border border-primary border-opacity-10">
                        ვერსია 1.5
                    </span>
                </div>
            </div>

            <div class="row g-4">
                
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 fw-bold border-bottom-0">
                            <i class="bi bi-code-square me-2 text-info"></i> გამოყენებული ტექნოლოგიები
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-bootstrap-fill text-purple me-3 fs-4" style="color: #6f42c1;"></i>
                                        <div>
                                            <h6 class="mb-0">Bootstrap 5</h6>
                                            <small class="text-muted">UI Framework</small>
                                        </div>
                                    </div>
                                    <span class="badge bg-light text-dark border">v5.3</span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-pie-chart-fill text-danger me-3 fs-4"></i>
                                        <div>
                                            <h6 class="mb-0">Chart.js</h6>
                                            <small class="text-muted">Data Visualization</small>
                                        </div>
                                    </div>
                                    <span class="badge bg-light text-dark border">v4.4</span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-arrows-move text-success me-3 fs-4"></i>
                                        <div>
                                            <h6 class="mb-0">SortableJS</h6>
                                            <small class="text-muted">Drag & Drop</small>
                                        </div>
                                    </div>
                                    <span class="badge bg-light text-dark border">Latest</span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-fonts text-warning me-3 fs-4"></i>
                                        <div>
                                            <h6 class="mb-0">Bootstrap Icons</h6>
                                            <small class="text-muted">Iconography</small>
                                        </div>
                                    </div>
                                    <span class="badge bg-light text-dark border">v1.11</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-3 fw-bold border-bottom-0">
                            <i class="bi bi-hdd-rack me-2 text-secondary"></i> სერვერის გარემო
                        </div>
                        <div class="card-body">
                            <div class="row g-3 text-center">
                                <div class="col-6">
                                    <div class="p-3 bg-light rounded border">
                                        <div class="text-muted small text-uppercase fw-bold mb-1">PHP ვერსია</div>
                                        <div class="h5 mb-0 font-monospace"><?php echo phpversion(); ?></div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-3 bg-light rounded border">
                                        <div class="text-muted small text-uppercase fw-bold mb-1">MySQL</div>
                                        <div class="h5 mb-0 font-monospace"><?php echo $ok_db->get_var("SELECT VERSION()"); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm bg-primary text-white">
                        <div class="card-body p-4 text-center">
                            <h5 class="fw-bold mb-3">შექმნილია სიყვარულით <i class="bi bi-heart-fill text-danger mx-1 heartbeat"></i></h5>
                            <p class="mb-4 opacity-75 small">OK Engine არის მსუბუქი და სწრაფი გადაწყვეტილება თქვენი ვებ-გვერდებისთვის.</p>
                            
                            <div class="d-flex justify-content-center">
                                <div class="bg-white bg-opacity-25 rounded-pill px-4 py-2 d-flex align-items-center">
                                    <i class="bi bi-code-slash me-2"></i> Created by <strong>&nbsp;OK Team</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="text-center mt-5 text-muted small">
                &copy; <?php echo date('Y'); ?> ყველა უფლება დაცულია.
            </div>

        </div>
    </div>

    <style>
        .heartbeat { animation: beat 1s infinite alternate; display: inline-block; }
        @keyframes beat { from { transform: scale(1); } to { transform: scale(1.2); } }
    </style>
    <?php
}