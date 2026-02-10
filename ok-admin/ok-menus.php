<?php
/**
 * მენიუების მართვა (Fixed Sorting + UI Update)
 */

    add_ok_action('admin_menu', function() {
        add_menu_page('მენიუები', 'მენიუები', 'manage_options', 'ok-menus', 'ok_render_menus', 'bi bi-list', 29);
    });


function ok_render_menus() {
    global $ok_db, $ok_registered_nav_menus;

    // 1. მონაცემების წამოღება
    $pages = $ok_db->get_results("SELECT id, post_title FROM ok_posts WHERE post_type='page' AND post_status='published' ORDER BY post_title ASC");
    $categories = $ok_db->get_results("SELECT id, name FROM ok_categories ORDER BY name ASC");

    // 2. შენახვის ლოგიკა (🛑 გასწორებული სორტირება)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_menus'])) {
        $data_to_save = [];
        
        if (!empty($_POST['menu'])) {
            foreach ($_POST['menu'] as $loc => $items) {
                if (is_array($items)) {
                    
                    $sort_index = 0; // 🛑 მთვლელი 0-იდან იწყება
                    
                    foreach ($items as $id => $item) {
                        $data_to_save[$loc][$id] = [
                            'type'      => $item['type'],
                            'label'     => trim($item['label']),
                            'url'       => $item['url'] ?? '',
                            'object_id' => $item['object_id'] ?? 0,
                            'order'     => $sort_index // 🛑 ვანიჭებთ მიმდინარე ინდექსს
                        ];
                        
                        $sort_index++; // 🛑 ვიზრდებით
                    }
                }
            }
        }
        
        update_ok_option('ok_nav_menus', json_encode($data_to_save, JSON_UNESCAPED_UNICODE));
        echo '<div class="alert alert-success shadow-sm mb-4 border-0 rounded-3"><i class="bi bi-check-circle-fill me-2"></i> მენიუ წარმატებით შეინახა.</div>';
    }

    $saved_json = get_ok_option('ok_nav_menus', '');
    $saved_menus = !empty($saved_json) ? json_decode($saved_json, true) : [];

    // 3. ავტომატური "მთავარი გვერდი"
    if (empty($saved_menus['header-menu'])) {
        $saved_menus['header-menu'] = [
            'home_default' => ['type' => 'custom', 'label' => 'მთავარი', 'url' => '/', 'order' => 0]
        ];
    }
    ?>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <div>
            <h1 class="h3 fw-bold text-dark mb-0"><i class="bi bi-list me-2 text-primary"></i>მენიუების მართვა</h1>
            <small class="text-muted">გადაათრიეთ ელემენტები მარცხნიდან მარჯვნივ</small>
        </div>
        <button type="submit" form="menu_form" name="save_menus" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">
            <i class="bi bi-save me-2"></i> შენახვა
        </button>
    </div>

    <form method="post" id="menu_form">
        <div class="row g-4">
            
            <div class="col-lg-4 col-md-5">
                
                <div class="card border-0 shadow-sm mb-3 overflow-hidden rounded-3">
                    <div class="card-header bg-white py-3 fw-bold text-uppercase small text-muted border-bottom">
                        <i class="bi bi-gear me-2"></i>სისტემური
                    </div>
                    <div class="card-body p-2 source-list" id="source-system">
                        <div class="menu-item border rounded p-2 mb-2 bg-light cursor-grab shadow-sm" data-type="custom" data-label="მთავარი" data-url="/">
                            <div class="d-flex align-items-center">
                                <span class="icon-box bg-primary text-white rounded me-2"><i class="bi bi-house"></i></span>
                                <span class="fw-medium">მთავარი გვერდი</span>
                                <i class="bi bi-grip-vertical ms-auto text-muted opacity-50"></i>
                            </div>
                        </div>
                        <div class="menu-item border rounded p-2 mb-2 bg-light cursor-grab shadow-sm" data-type="custom" data-label="ლინკი" data-url="#">
                            <div class="d-flex align-items-center">
                                <span class="icon-box bg-secondary text-white rounded me-2"><i class="bi bi-link-45deg"></i></span>
                                <span class="fw-medium">საკუთარი ლინკი</span>
                                <i class="bi bi-grip-vertical ms-auto text-muted opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-3 overflow-hidden rounded-3">
                    <div class="card-header bg-white py-3 fw-bold text-uppercase small text-muted border-bottom">
                        <i class="bi bi-file-earmark-text me-2"></i>გვერდები
                    </div>
                    <div class="card-body p-2 source-list" id="source-pages" style="max-height: 250px; overflow-y: auto;">
                        <?php if($pages): foreach($pages as $p): ?>
                            <div class="menu-item border rounded p-2 mb-2 bg-white cursor-grab hover-shadow" 
                                 data-type="page" data-label="<?php echo htmlspecialchars($p->post_title); ?>" data-object-id="<?php echo $p->id; ?>">
                                <div class="d-flex align-items-center">
                                    <span class="text-truncate flex-grow-1" style="font-size: 0.9rem;"><?php echo htmlspecialchars($p->post_title); ?></span>
                                    <i class="bi bi-plus-circle text-primary opacity-50"></i>
                                </div>
                            </div>
                        <?php endforeach; else: ?>
                            <div class="text-muted small text-center py-2">გვერდები არ არის</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-3 overflow-hidden rounded-3">
                    <div class="card-header bg-white py-3 fw-bold text-uppercase small text-muted border-bottom">
                        <i class="bi bi-tags me-2"></i>კატეგორიები
                    </div>
                    <div class="card-body p-2 source-list" id="source-cats" style="max-height: 250px; overflow-y: auto;">
                        <?php if($categories): foreach($categories as $c): ?>
                            <div class="menu-item border rounded p-2 mb-2 bg-white cursor-grab hover-shadow" 
                                 data-type="category" data-label="<?php echo htmlspecialchars($c->name); ?>" data-object-id="<?php echo $c->id; ?>">
                                <div class="d-flex align-items-center">
                                    <span class="text-truncate flex-grow-1" style="font-size: 0.9rem;"><?php echo htmlspecialchars($c->name); ?></span>
                                    <i class="bi bi-plus-circle text-warning opacity-50"></i>
                                </div>
                            </div>
                        <?php endforeach; else: ?>
                            <div class="text-muted small text-center py-2">კატეგორიები არ არის</div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <div class="col-lg-8 col-md-7">
                <div class="accordion" id="menusAccordion">
                    <?php 
                    $i=0; foreach($ok_registered_nav_menus as $loc => $name): 
                        $i++; $is_open = ($i===1);
                        $current_items = $saved_menus[$loc] ?? [];
                        
                        // 🛑 სორტირება ჩვენებისთვის
                        uasort($current_items, function($a, $b) { 
                            return (int)($a['order']??0) <=> (int)($b['order']??0); 
                        });
                    ?>
                        <div class="accordion-item border-0 shadow-sm mb-3 rounded-3 overflow-hidden">
                            <h2 class="accordion-header">
                                <button class="accordion-button <?php echo $is_open?'':'collapsed'; ?> bg-white fw-bold py-3" type="button" data-bs-toggle="collapse" data-bs-target="#col_<?php echo $loc; ?>">
                                    <span class="badge bg-primary bg-opacity-10 text-primary me-2"><?php echo $loc; ?></span> <?php echo $name; ?>
                                </button>
                            </h2>
                            <div id="col_<?php echo $loc; ?>" class="accordion-collapse collapse <?php echo $is_open?'show':''; ?>" data-bs-parent="#menusAccordion">
                                <div class="accordion-body bg-light-subtle p-3 menu-target-zone border-top" data-location="<?php echo $loc; ?>" style="min-height: 150px;">
                                    
                                    <?php if(empty($current_items)): ?>
                                        <div class="empty-placeholder text-center text-muted py-5 border border-dashed rounded-3 bg-white opacity-75">
                                            <i class="bi bi-arrow-left-circle fs-1 text-secondary mb-2 d-block opacity-25"></i>
                                            <span class="small">გადმოათრიეთ მენიუს ელემენტები აქ</span>
                                        </div>
                                    <?php endif; ?>

                                    <?php foreach($current_items as $uid => $item): ?>
                                        <div class="card mb-2 menu-item-card border shadow-sm rounded-3">
                                            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center cursor-move handle border-bottom-0">
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-grip-vertical text-muted me-2 handle-icon"></i>
                                                    <span class="fw-bold text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($item['label']); ?></span>
                                                    <span class="badge bg-light text-secondary border ms-2" style="font-size: 0.65rem;"><?php echo ucfirst($item['type']); ?></span>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-link text-danger p-0 opacity-50 hover-opacity-100" onclick="removeItem(this)" title="წაშლა"><i class="bi bi-trash"></i></button>
                                            </div>
                                            <div class="card-body p-2 bg-light bg-opacity-25 border-top">
                                                <div class="row g-2">
                                                    <input type="hidden" name="menu[<?php echo $loc; ?>][<?php echo $uid; ?>][type]" value="<?php echo $item['type']; ?>">
                                                    <input type="hidden" name="menu[<?php echo $loc; ?>][<?php echo $uid; ?>][object_id]" value="<?php echo $item['object_id'] ?? 0; ?>">
                                                    
                                                    <div class="col-6">
                                                        <label class="form-label small text-muted mb-0" style="font-size: 0.7rem;">სათაური</label>
                                                        <input type="text" class="form-control form-control-sm" name="menu[<?php echo $loc; ?>][<?php echo $uid; ?>][label]" value="<?php echo htmlspecialchars($item['label']); ?>">
                                                    </div>
                                                    
                                                    <?php if($item['type'] === 'custom'): ?>
                                                    <div class="col-6">
                                                        <label class="form-label small text-muted mb-0" style="font-size: 0.7rem;">URL</label>
                                                        <input type="text" class="form-control form-control-sm font-monospace" name="menu[<?php echo $loc; ?>][<?php echo $uid; ?>][url]" value="<?php echo htmlspecialchars($item['url']); ?>">
                                                    </div>
                                                    <?php else: ?>
                                                    <div class="col-6 d-flex align-items-end pb-1">
                                                        <span class="text-muted small fst-italic"><i class="bi bi-link-45deg"></i> ორიგინალი ბმული</span>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>

                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </form>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const sources = document.querySelectorAll('.source-list');
        sources.forEach(src => {
            new Sortable(src, { group: { name: 'menu', pull: 'clone', put: false }, sort: false, animation: 150, cursor: 'grabbing' });
        });

        const targets = document.querySelectorAll('.menu-target-zone');
        targets.forEach(tgt => {
            new Sortable(tgt, {
                group: 'menu', animation: 150, handle: '.handle', ghostClass: 'bg-primary-subtle',
                onAdd: function (evt) {
                    const item = evt.item;
                    const type = item.getAttribute('data-type');
                    const label = item.getAttribute('data-label');
                    const objId = item.getAttribute('data-object-id') || 0;
                    const url   = item.getAttribute('data-url') || '';
                    const loc = tgt.getAttribute('data-location');
                    const uid = Date.now() + '_' + Math.floor(Math.random()*1000);
                    const ph = tgt.querySelector('.empty-placeholder');
                    if(ph) ph.remove();

                    const newEl = document.createElement('div');
                    newEl.className = 'card mb-2 menu-item-card border shadow-sm rounded-3';
                    
                    let extraField = '';
                    let badge = type.charAt(0).toUpperCase() + type.slice(1);

                    if(type === 'custom') {
                        extraField = `<div class="col-6"><label class="form-label small text-muted mb-0" style="font-size: 0.7rem;">URL</label><input type="text" class="form-control form-control-sm font-monospace" name="menu[${loc}][${uid}][url]" value="${url}"></div>`;
                    } else {
                        extraField = `<div class="col-6 d-flex align-items-end pb-1"><span class="text-muted small fst-italic"><i class="bi bi-link-45deg"></i> ორიგინალი ბმული</span></div>`;
                    }

                    newEl.innerHTML = `
                        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center cursor-move handle border-bottom-0">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-grip-vertical text-muted me-2 handle-icon"></i>
                                <span class="fw-bold text-dark" style="font-size: 0.9rem;">${label}</span>
                                <span class="badge bg-light text-secondary border ms-2" style="font-size: 0.65rem;">${badge}</span>
                            </div>
                            <button type="button" class="btn btn-sm btn-link text-danger p-0 opacity-50 hover-opacity-100" onclick="removeItem(this)" title="წაშლა"><i class="bi bi-trash"></i></button>
                        </div>
                        <div class="card-body p-2 bg-light bg-opacity-25 border-top">
                            <div class="row g-2">
                                <input type="hidden" name="menu[${loc}][${uid}][type]" value="${type}">
                                <input type="hidden" name="menu[${loc}][${uid}][object_id]" value="${objId}">
                                <div class="col-6">
                                    <label class="form-label small text-muted mb-0" style="font-size: 0.7rem;">სათაური</label>
                                    <input type="text" class="form-control form-control-sm" name="menu[${loc}][${uid}][label]" value="${label}">
                                </div>
                                ${extraField}
                            </div>
                        </div>`;
                    item.replaceWith(newEl);
                }
            });
        });
    });

    function removeItem(btn) {
        Swal.fire({ title: 'წავშალოთ?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'დიახ' }).then((result) => {
            if (result.isConfirmed) btn.closest('.menu-item-card').remove();
        });
    }
    </script>

    <style>
        .cursor-grab { cursor: grab; }
        .cursor-move { cursor: move; cursor: -webkit-grabbing; }
        .handle:hover { background-color: #f8f9fa; }
        .icon-box { width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; }
        .hover-shadow:hover { box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important; color: #0d6efd; }
        .hover-opacity-100:hover { opacity: 1 !important; }
        .menu-item-card { transition: all 0.2s; }
        .menu-item-card:hover { border-color: #dee2e6 !important; }
    </style>
    <?php
}