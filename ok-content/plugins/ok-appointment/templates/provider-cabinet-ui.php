<?php 
// ბუფერიზაციის ჩართვა, რომ header-ების გადამისამართებამ იმუშაოს შეცდომის გარეშე
ob_start();

if (!defined('OK_LOADED')) exit;
global $ok_db;

// --- 1. SESSION FIX: სინქრონიზაცია ძირითად სისტემასთან ---
$user_id = 0;
if (isset($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
}

// თუ მომხმარებელი არ არის ავტორიზებული
if ($user_id == 0) {
    echo '<div class="alert alert-warning m-3">გთხოვთ გაიაროთ ავტორიზაცია კაბინეტის სანახავად.</div>';
    return; // სკრიპტის გაჩერება
}
// -----------------------------------------------------------

$days_map = [1=>'ორშაბათი', 2=>'სამშაბათი', 3=>'ოთხშაბათი', 4=>'ხუთშაბათი', 5=>'პარასკევი', 6=>'შაბათი', 7=>'კვირა'];

// მიმდინარე URL-ის გასუფთავება (query string-ის მოშორება გადამისამართებისთვის)
// თუ თქვენ საიტზე ?page=provider სტილს იყენებთ, strtok-ის მაგივრად $_SERVER['REQUEST_URI'] გამოიყენეთ.
// აქ ვიყენებთ უსაფრთხო ვარიანტს:
$current_url = strtok($_SERVER["REQUEST_URI"], '?');


// --- PHP LOGIC: POST ACTIONS (ზევით ატანილი) ---

// 1. სერვისის წაშლა
if (isset($_POST['del_srv'])) {
    $ok_db->query("DELETE FROM ok_services WHERE id=? AND provider_id=?", [$_POST['srv_id'], $user_id]);
    $ok_db->query("DELETE FROM ok_service_hours WHERE service_id=?", [$_POST['srv_id']]);
    
    header("Location: " . $current_url);
    exit;
}

// 2. სერვისის დამატება ან განახლება
if (isset($_POST['save_srv'])) {
    $title = strip_tags($_POST['title']);
    $price = (float)$_POST['price'];
    $dur   = (int)$_POST['dur'];

    if (!empty($_POST['edit_id'])) {
        // განახლება
        $ok_db->query("UPDATE ok_services SET title=?, price=?, duration=? WHERE id=? AND provider_id=?", 
            [$title, $price, $dur, $_POST['edit_id'], $user_id]);
    } else {
        // ახლის დამატება
        $ok_db->query("INSERT INTO ok_services (provider_id, title, price, duration, is_active) VALUES (?,?,?,?,1)", 
            [$user_id, $title, $price, $dur]);
    }
    
    // გადამისამართება სუფთა URL-ზე (edit_srv პარამეტრის გარეშე)
    header("Location: " . $current_url . "?tab=services"); // შეგვიძლია ტაბიც მივუთითოთ თუ JS მხარდაჭერა გვაქვს
    exit;
}

// 3. გრაფიკის შენახვა (Bulk Save)
if (isset($_POST['save_bulk_hours'])) {
    $sid = intval($_POST['sid']);
    // ვშლით ძველს
    $ok_db->query("DELETE FROM ok_service_hours WHERE service_id = ?", [$sid]);

    if (isset($_POST['days']) && is_array($_POST['days'])) {
        foreach ($_POST['days'] as $day_num => $data) {
            if (isset($data['active'])) {
                $ok_db->query("INSERT INTO ok_service_hours (service_id, day_of_week, start_time, end_time) VALUES (?,?,?,?)", 
                    [$sid, $day_num, $data['start'], $data['end']]);
            }
        }
    }
    header("Location: " . $current_url);
    exit;
}

// 4. გრაფიკიდან კონკრეტული დღის წაშლა
if (isset($_POST['del_hour'])) {
    $ok_db->query("DELETE FROM ok_service_hours WHERE id=?", [$_POST['hour_id']]);
    header("Location: " . $current_url);
    exit;
}

// 5. შვებულების დამატება
if (isset($_POST['add_vac'])) {
    $ok_db->query("INSERT INTO ok_vacations (provider_id, start_date, end_date) VALUES (?,?,?)", [$user_id, $_POST['s'], $_POST['e']]);
    header("Location: " . $current_url);
    exit;
}

// 6. შვებულების წაშლა
if (isset($_POST['del_vac'])) {
    $ok_db->query("DELETE FROM ok_vacations WHERE id=? AND provider_id=?", [$_POST['vac_id'], $user_id]);
    header("Location: " . $current_url);
    exit;
}
// ----------------------------------------------------


// --- DATA FETCHING ---
$edit_srv = null;
if (isset($_GET['edit_srv'])) {
    $edit_srv = $ok_db->get_row("SELECT * FROM ok_services WHERE id=? AND provider_id=?", [$_GET['edit_srv'], $user_id]);
}

$services = $ok_db->get_results("SELECT * FROM ok_services WHERE provider_id=?", [$user_id]);
$hours    = $ok_db->get_results("SELECT h.*, s.title FROM ok_service_hours h JOIN ok_services s ON h.service_id=s.id WHERE s.provider_id=? ORDER BY s.title, h.day_of_week", [$user_id]);
$apps     = $ok_db->get_results("SELECT a.*, s.title, u.display_name, u.email FROM ok_appointments a JOIN ok_services s ON a.service_id=s.id JOIN ok_users u ON a.customer_id=u.id WHERE s.provider_id=? ORDER BY a.appointment_date DESC", [$user_id]);
$vacations= $ok_db->get_results("SELECT * FROM ok_vacations WHERE provider_id=? ORDER BY start_date", [$user_id]);
?>

<style>
    /* სვიჩერის სტილები */
    .day-switch .form-check-input {
        background-color: #dc3545; border-color: #dc3545;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='%23fff'/%3e%3c/svg%3e");
    }
    .day-switch .form-check-input:checked {
        background-color: #198754; border-color: #198754;
    }
    .day-row { border-bottom: 1px solid #f0f0f0; padding-bottom: 8px; margin-bottom: 8px; }
    
    /* ტაბების სტილი */
    .nav-tabs .nav-link { color: #555; font-weight: 600; }
    .nav-tabs .nav-link.active { color: #0d6efd; border-bottom: 3px solid #0d6efd; }
    .tab-content { background: #fff; padding: 20px; border: 1px solid #dee2e6; border-top: none; border-radius: 0 0 5px 5px; }
</style>

<ul class="nav nav-tabs" id="providerTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" id="tab-visits" data-bs-toggle="tab" data-bs-target="#content-visits" type="button">ვიზიტები</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" id="tab-services" data-bs-toggle="tab" data-bs-target="#content-services" type="button">ჩემი სერვისები</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" id="tab-schedule" data-bs-toggle="tab" data-bs-target="#content-schedule" type="button">სამუშაო გრაფიკი</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" id="tab-vacation" data-bs-toggle="tab" data-bs-target="#content-vacation" type="button">შვებულება</button>
    </li>
</ul>

<div class="tab-content" id="providerTabsContent">
    
    <div class="tab-pane fade show active" id="content-visits">
        <h5 class="mb-3">მომავალი და მიმდინარე ვიზიტები</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light"><tr><th>პაციენტი</th><th>სერვისი</th><th>დრო</th><th>სტატუსი</th></tr></thead>
                <tbody>
                    <?php if($apps): foreach($apps as $a): ?>
                    <tr>
                        <td><strong><?php echo $a->display_name; ?></strong><br><small class="text-muted"><?php echo $a->email; ?></small></td>
                        <td><?php echo $a->title; ?></td>
                        <td><?php echo $a->appointment_date.' <span class="badge bg-light text-dark">'.$a->appointment_time.'</span>'; ?></td>
                        <td>
                            <?php 
                                if($a->status == 'paid') echo '<span class="badge bg-success">გადახდილი</span>';
                                elseif($a->status == 'on-hold') echo '<span class="badge bg-warning text-dark">ელოდება</span>';
                                else echo '<span class="badge bg-secondary">'.$a->status.'</span>';
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; else: echo "<tr><td colspan='4' class='text-center text-muted'>ვიზიტები არ არის</td></tr>"; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="tab-pane fade" id="content-services">
        <div class="row">
            <div class="col-md-4 border-end">
                <h6 class="fw-bold mb-3"><?php echo $edit_srv ? 'სერვისის რედაქტირება' : 'ახალი სერვისი'; ?></h6>
                <form method="post">
                    <?php if($edit_srv): ?>
                        <input type="hidden" name="edit_id" value="<?php echo $edit_srv->id; ?>">
                    <?php endif; ?>
                    
                    <label class="small text-muted">დასახელება</label>
                    <input type="text" name="title" class="form-control mb-2" required value="<?php echo $edit_srv->title ?? ''; ?>">
                    
                    <label class="small text-muted">ფასი (GEL)</label>
                    <input type="number" name="price" class="form-control mb-2" step="0.01" value="<?php echo $edit_srv->price ?? ''; ?>">
                    
                    <label class="small text-muted">ხანგრძლივობა (წთ)</label>
                    <input type="number" name="dur" class="form-control mb-3" value="<?php echo $edit_srv->duration ?? '30'; ?>">
                    
                    <button name="save_srv" class="btn btn-<?php echo $edit_srv ? 'warning' : 'success'; ?> w-100">
                        <?php echo $edit_srv ? 'განახლება' : 'დამატება'; ?>
                    </button>
                    <?php if($edit_srv): ?>
                        <a href="<?php echo strtok($_SERVER["REQUEST_URI"], '?'); ?>" class="btn btn-outline-secondary w-100 mt-2 btn-sm">გაუქმება</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="col-md-8">
                <h6 class="fw-bold mb-3">სერვისების სია</h6>
                <table class="table table-sm table-bordered">
                    <thead class="table-light"><tr><th>დასახელება</th><th>ფასი</th><th>დრო</th><th>მოქმედება</th></tr></thead>
                    <tbody>
                        <?php foreach($services as $s): ?>
                        <tr>
                            <td><?php echo $s->title; ?></td>
                            <td><?php echo $s->price; ?> ₾</td>
                            <td><?php echo $s->duration; ?> წთ</td>
                            <td style="width: 150px;">
                                <a href="?edit_srv=<?php echo $s->id; ?>" class="btn btn-sm btn-outline-primary me-1">Edit</a>
                                <form method="post" class="d-inline" onsubmit="return confirm('წავშალოთ სერვისი?');">
                                    <input type="hidden" name="srv_id" value="<?php echo $s->id; ?>">
                                    <button name="del_srv" class="btn btn-sm btn-outline-danger">Del</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="content-schedule">
        <div class="row">
            <div class="col-lg-5 mb-4">
                <div class="card p-3 shadow-sm border-0 bg-light">
                    <h6 class="fw-bold">გრაფიკის განახლება</h6>
                    <p class="text-muted small mb-3">აირჩიეთ სერვისი და მონიშნეთ დღეები. ეს წაშლის ძველ საათებს ამ სერვისისთვის და ჩაწერს ახალს.</p>
                    
                    <form method="post">
                        <select name="sid" class="form-select mb-3 fw-bold border-primary" required>
                            <option value="">-- აირჩიეთ სერვისი --</option>
                            <?php foreach($services as $s) echo "<option value='{$s->id}'>{$s->title} ({$s->duration} წთ)</option>"; ?>
                        </select>

                        <div class="days-container bg-white p-2 border rounded">
                            <?php foreach($days_map as $num => $name): ?>
                            <div class="row align-items-center day-row mx-0">
                                <div class="col-5 d-flex align-items-center day-switch ps-0">
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" role="switch" 
                                               id="d_<?php echo $num; ?>" 
                                               name="days[<?php echo $num; ?>][active]" 
                                               onchange="toggleTimes(<?php echo $num; ?>)">
                                        <label class="form-check-label ms-2 small fw-bold" for="d_<?php echo $num; ?>"><?php echo $name; ?></label>
                                    </div>
                                </div>
                                <div class="col-7 pe-0">
                                    <div class="input-group input-group-sm">
                                        <input type="time" name="days[<?php echo $num; ?>][start]" id="s_<?php echo $num; ?>" class="form-control" value="09:00" disabled>
                                        <span class="input-group-text">-</span>
                                        <input type="time" name="days[<?php echo $num; ?>][end]" id="e_<?php echo $num; ?>" class="form-control" value="18:00" disabled>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button name="save_bulk_hours" class="btn btn-primary w-100 mt-3">გრაფიკის შენახვა</button>
                    </form>
                </div>
            </div>

            <div class="col-lg-7">
                <h6 class="fw-bold mb-3">აქტიური საათები (დეტალურად)</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-striped border">
                        <thead class="table-dark"><tr><th>სერვისი</th><th>დღე</th><th>საათები</th><th></th></tr></thead>
                        <tbody>
                        <?php if($hours): foreach($hours as $h): ?>
                            <tr>
                                <td><?php echo $h->title; ?></td>
                                <td><?php echo $days_map[$h->day_of_week]; ?></td>
                                <td><?php echo substr($h->start_time,0,5).' - '.substr($h->end_time,0,5); ?></td>
                                <td class="text-end">
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="hour_id" value="<?php echo $h->id; ?>">
                                        <button name="del_hour" class="btn btn-link text-danger p-0 btn-sm text-decoration-none" title="წაშლა">✖</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; else: echo "<tr><td colspan='4' class='text-center'>გრაფიკი ცარიელია</td></tr>"; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="content-vacation">
        <div class="row">
            <div class="col-md-4">
                <div class="card p-3 bg-light border-0 mb-3">
                    <h6 class="fw-bold">ახალი შვებულება</h6>
                    <form method="post">
                        <label class="small">დასაწყისი</label>
                        <input type="date" name="s" class="form-control mb-2" required>
                        <label class="small">დასასრული</label>
                        <input type="date" name="e" class="form-control mb-3" required>
                        <button name="add_vac" class="btn btn-dark w-100">დამატება</button>
                    </form>
                </div>
            </div>
            <div class="col-md-8">
                <h6 class="fw-bold">ჩემი შვებულებები</h6>
                <table class="table table-bordered">
                    <thead><tr><th>დან</th><th>მდე</th><th>მოქმედება</th></tr></thead>
                    <tbody>
                        <?php if($vacations): foreach($vacations as $v): ?>
                        <tr>
                            <td><?php echo $v->start_date; ?></td>
                            <td><?php echo $v->end_date; ?></td>
                            <td>
                                <form method="post" onsubmit="return confirm('წავშალოთ?');">
                                    <input type="hidden" name="vac_id" value="<?php echo $v->id; ?>">
                                    <button name="del_vac" class="btn btn-sm btn-outline-danger">წაშლა</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; else: echo "<tr><td colspan='3' class='text-muted'>შვებულება არ ფიქსირდება</td></tr>"; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // 1. Switcher Logic
    function toggleTimes(id) {
        var check = document.getElementById('d_' + id);
        var start = document.getElementById('s_' + id);
        var end   = document.getElementById('e_' + id);
        
        if (check.checked) {
            start.disabled = false; end.disabled = false;
        } else {
            start.disabled = true; end.disabled = true;
        }
    }

    // 2. Keep Tab Active Logic (Simple localStorage)
    document.addEventListener("DOMContentLoaded", function(){
        // თუ გვერდი გადაიტვირთა, შევინარჩუნოთ ტაბი
        var activeTab = localStorage.getItem('activeProviderTab');
        if(activeTab){
            var tabEl = document.querySelector('#' + activeTab);
            if(tabEl) {
                var tabTrigger = new bootstrap.Tab(tabEl);
                tabTrigger.show();
            }
        } else {
             // Default Active Tab
             var firstTab = document.querySelector('#tab-visits');
             if(firstTab) new bootstrap.Tab(firstTab).show();
        }

        // ტაბზე დაჭერისას დავიმახსოვროთ ID
        var tabEls = document.querySelectorAll('button[data-bs-toggle="tab"]');
        tabEls.forEach(function(el){
            el.addEventListener('shown.bs.tab', function (event) {
                localStorage.setItem('activeProviderTab', event.target.id);
            });
        });

        // თუ ედიტირებაზე ვართ (URL-ში edit_srv არის), ავტომატურად გადავიდეთ სერვისების ტაბზე
        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.has('edit_srv')){
            var srvTab = document.querySelector('#tab-services');
            if(srvTab) new bootstrap.Tab(srvTab).show();
        }
    });
</script>
<?php 
// ბუფერიზაციის გასუფთავება (თუ საჭიროა, მაგრამ როგორც წესი PHP თავად შვრება)
// ob_end_flush(); 
?>