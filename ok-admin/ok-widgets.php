<?php
/**
 * ვიჯეტების მართვა (Full Logic - Secure 🛡️)
 */

add_ok_action('admin_menu', function() {
    add_menu_page('ვიჯეტები', 'ვიჯეტები', 'manage_options', 'ok-widgets', 'ok_render_widgets', 'bi bi-grid-1x2', 25);
});

function ok_render_widgets() {
    // 🛑 1. ვიღებთ ყველა საჭირო გლობალურ ცვლადს
    global $ok_registered_sidebars, $ok_registered_widgets, $ok_registered_nav_menus;

    if (empty($ok_registered_widgets)) { $ok_registered_widgets = []; }
    if (empty($ok_registered_nav_menus)) { $ok_registered_nav_menus = []; }

    // 🛑 2. ვამზადებთ მენიუს SELECT-ის HTML-ს (Default ვერსია JS-ისთვის)
    $menu_select_html_default = '<select name="__NAME_PREFIX__[menu_id]" class="form-select form-select-sm">';
    if (!empty($ok_registered_nav_menus)) {
        foreach ($ok_registered_nav_menus as $loc => $name) {
            $menu_select_html_default .= '<option value="'.$loc.'">'.$name.'</option>';
        }
    } else {
        $menu_select_html_default .= '<option value="">მენიუები არ არის</option>';
    }
    $menu_select_html_default .= '</select>';


    // 3. შენახვის ლოგიკა (დაცული 🛡️)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_widgets'])) {
        
        // 🛡️ უსაფრთხოების შემოწმება
        ok_sec_check('save_widgets_action');

        $data_to_save = [];
        if (!empty($_POST['widgets']) && is_array($_POST['widgets'])) {
            foreach ($_POST['widgets'] as $area_id => $widgets) {
                if (is_array($widgets)) {
                    foreach ($widgets as $w_id => $w_data) {
                        
                        $safe_data = [
                            'type'    => $w_data['type'] ?? '',
                            'active'  => 1
                        ];
                        
                        // ყველა ველის დინამიური შენახვა
                        foreach ($w_data as $key => $val) {
                            if ($key !== 'type' && $key !== 'active') {
                                // HTML-ის დაშვება საჭიროა ტექსტური ვიჯეტებისთვის, 
                                // მაგრამ სასურველია აქ ok_sanitize_text_field გამოყენება მომავალში.
                                // ამ ეტაპზე ვტოვებთ ისე, როგორც იყო (Admin-ს ვენდობით).
                                $safe_data[$key] = $val;
                            }
                        }
                        
                        $data_to_save[$area_id][$w_id] = $safe_data;
                    }
                }
            }
        }
        update_ok_option('ok_widget_areas', json_encode($data_to_save, JSON_UNESCAPED_UNICODE));
        echo '<div class="alert alert-success shadow-sm mb-4"><i class="bi bi-check-circle me-2"></i>ცვლილებები შენახულია.</div>';
    }

    $saved_json = get_ok_option('ok_widget_areas', '');
    $saved_widgets = !empty($saved_json) ? json_decode($saved_json, true) : [];
    ?>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h1 class="h3 fw-bold text-dark"><i class="bi bi-grid-1x2 me-2"></i>ვიჯეტები</h1>
        <button type="submit" form="widgets_form" name="save_widgets" class="btn btn-primary rounded-pill px-4 shadow">
            <i class="bi bi-save me-2"></i> შენახვა
        </button>
    </div>

    <form method="post" id="widgets_form">
        
        <?php ok_nonce_field('save_widgets_action'); ?>

        <div class="row g-4">
            
            <div class="col-lg-4 col-md-5">
                <div class="card border-0 shadow-sm sticky-top" style="top: 20px; z-index: 10;">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="mb-0 fw-bold text-uppercase small text-muted">ხელმისაწვდომი ვიჯეტები</h6>
                    </div>
                    <div class="card-body p-2" id="available-widgets-list">
                        <?php foreach ($ok_registered_widgets as $type => $info): ?>
                            <div class="widget-source-item d-flex align-items-center p-3 mb-2 rounded border bg-white cursor-grab shadow-sm user-select-none" 
                                 data-type="<?php echo $type; ?>" 
                                 draggable="true">
                                <div class="icon-box bg-light text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 35px; height: 35px;">
                                    <i class="bi <?php echo $info['icon']; ?>"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark" style="font-size: 0.95rem;"><?php echo $info['name']; ?></div>
                                    <div class="text-muted small" style="font-size: 0.75rem;"><?php echo $info['desc']; ?></div>
                                </div>
                                <i class="bi bi-grip-vertical ms-auto text-muted opacity-50"></i>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="card-footer bg-light p-3 small text-muted text-center">
                        გადაათრიეთ მარჯვნივ 👉
                    </div>
                </div>
            </div>

            <div class="col-lg-8 col-md-7">
                <div class="accordion" id="widgetAreasAccordion">
                    <?php 
                    $i = 0;
                    foreach ($ok_registered_sidebars as $area_id => $area): 
                        $i++; $is_open = ($i === 0); $collapse_id = 'collapse_' . $area_id;
                    ?>
                        <div class="accordion-item border-0 shadow-sm mb-3 overflow-hidden">
                            <h2 class="accordion-header" id="heading_<?php echo $area_id; ?>">
                                <button class="accordion-button <?php echo $is_open ? '' : 'collapsed'; ?> bg-white fw-bold py-3" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $collapse_id; ?>">
                                    <div class="d-flex flex-column">
                                        <span><?php echo $area['name']; ?></span>
                                        <small class="text-muted fw-normal mt-1" style="font-size: 11px;"><?php echo $area['description']; ?></small>
                                    </div>
                                </button>
                            </h2>
                            <div id="<?php echo $collapse_id; ?>" class="accordion-collapse collapse <?php echo $is_open ? 'show' : ''; ?>" data-bs-parent="#widgetAreasAccordion">
                                <div class="accordion-body bg-light p-3 widget-drop-zone" id="area-<?php echo $area_id; ?>" data-area-id="<?php echo $area_id; ?>" style="min-height: 150px;">
                                    
                                    <?php 
                                    if (isset($saved_widgets[$area_id]) && is_array($saved_widgets[$area_id])): 
                                        foreach ($saved_widgets[$area_id] as $w_id => $w_data):
                                            $type = $w_data['type'] ?? ''; 
                                            if (!isset($ok_registered_widgets[$type])) continue;
                                            
                                            $widget_def = $ok_registered_widgets[$type];
                                            $tpl = $widget_def['admin_template'];
                                            
                                            // 1. PREFIX replacement
                                            $tpl = str_replace('__NAME_PREFIX__', "widgets[$area_id][$w_id]", $tpl);
                                            
                                            // 2. VALUES replacement (General)
                                            foreach($w_data as $k => $v) {
                                                $tpl = str_replace('__'.strtoupper($k).'__', htmlspecialchars((string)$v), $tpl);
                                            }

                                            // 🛑 3. მენიუს SELECT-ის გენერაცია
                                            if (strpos($tpl, '__MENU_SELECT__') !== false) {
                                                $current_val = $w_data['menu_id'] ?? '';
                                                
                                                // ვქმნით Select HTML-ს
                                                $my_select = '<select name="widgets['.$area_id.']['.$w_id.'][menu_id]" class="form-select form-select-sm">';
                                                
                                                // ვამოწმებთ, აქვს თუ არა თემას მენიუები
                                                if (!empty($ok_registered_nav_menus)) {
                                                    foreach ($ok_registered_nav_menus as $loc => $name) {
                                                        // ვამოწმებთ, ეს მენიუ იყო თუ არა არჩეული
                                                        $sel = ($current_val == $loc) ? 'selected' : '';
                                                        $my_select .= '<option value="'.$loc.'" '.$sel.'>'.$name.'</option>';
                                                    }
                                                } else {
                                                    $my_select .= '<option value="">მენიუები არ არის</option>';
                                                }
                                                $my_select .= '</select>';
                                                
                                                // ვანაცვლებთ შაბლონში
                                                $tpl = str_replace('__MENU_SELECT__', $my_select, $tpl);
                                            }

                                            // 4. Default Values replacement (თუ რამე დარჩა)
                                            $tpl = str_replace('__TITLE__', htmlspecialchars($w_data['title'] ?? ''), $tpl);
                                            $tpl = str_replace('__LIMIT__', htmlspecialchars((string)($w_data['limit'] ?? 5)), $tpl);
                                            $tpl = str_replace('__CONTENT__', htmlspecialchars($w_data['content'] ?? ''), $tpl);
                                            
                                            // 5. Cleanup
                                            $tpl = preg_replace('/__[A-Z0-9_]+__/', '', $tpl);
                                    ?>
                                            <div class="widget-item card mb-2 border shadow-sm" data-id="<?php echo $w_id; ?>">
                                                <div class="card-header bg-white d-flex align-items-center justify-content-between py-2 cursor-move widget-handle">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="bi bi-grip-vertical text-muted"></i>
                                                        <span class="fw-bold small"><?php echo $widget_def['name']; ?></span>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="removeWidget(this)"><i class="bi bi-x-lg"></i></button>
                                                </div>
                                                <div class="card-body p-3">
                                                    <input type="hidden" name="widgets[<?php echo $area_id; ?>][<?php echo $w_id; ?>][active]" value="1">
                                                    <input type="hidden" name="widgets[<?php echo $area_id; ?>][<?php echo $w_id; ?>][type]" value="<?php echo $type; ?>">
                                                    <?php echo $tpl; ?>
                                                </div>
                                            </div>
                                    <?php endforeach; endif; ?>
                                    
                                    <?php if(empty($saved_widgets[$area_id])): ?>
                                        <div class="empty-placeholder text-center text-muted py-4 border border-dashed rounded bg-white">
                                            <i class="bi bi-arrow-down-circle fs-3 mb-2 d-block"></i> ჩააგდეთ ვიჯეტები აქ
                                        </div>
                                    <?php endif; ?>

                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </form>

    <script>
        // 🛑 JS TEMPLATES
        const widgetTemplates = {
            <?php foreach ($ok_registered_widgets as $type => $info): 
                $js_tpl = $info['admin_template'];
                // JS-ისთვის ვსვამთ ზოგად Select-ს (selected-ის გარეშე)
                $js_tpl = str_replace('__MENU_SELECT__', $menu_select_html_default, $js_tpl);
            ?>
            '<?php echo $type; ?>': {
                name: '<?php echo $info['name']; ?>',
                html: `<?php echo str_replace(["\r", "\n"], '', addslashes($js_tpl)); ?>`
            },
            <?php endforeach; ?>
        };
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        new Sortable(document.getElementById('available-widgets-list'), { group: { name: 'shared', pull: 'clone', put: false }, sort: false, animation: 150, cursor: 'grabbing' });
        
        const areas = document.querySelectorAll('.widget-drop-zone');
        areas.forEach(area => {
            new Sortable(area, {
                group: 'shared', animation: 150, handle: '.widget-handle', ghostClass: 'bg-info-subtle',
                onAdd: function (evt) {
                    const item = evt.item;
                    const type = item.getAttribute('data-type');
                    const areaId = area.getAttribute('data-area-id');
                    const ph = area.querySelector('.empty-placeholder');
                    if(ph) ph.remove();

                    const uniqueId = type + '_' + Date.now();
                    
                    if (widgetTemplates[type]) {
                        const tplObj = widgetTemplates[type];
                        let innerHtml = tplObj.html.replace(/__NAME_PREFIX__/g, `widgets[${areaId}][${uniqueId}]`);
                        
                        innerHtml = innerHtml.replace(/__TITLE__/g, '');
                        innerHtml = innerHtml.replace(/__LIMIT__/g, '5');
                        innerHtml = innerHtml.replace(/__CONTENT__/g, '');
                        // სხვა ველების გასუფთავება
                        innerHtml = innerHtml.replace(/__[A-Z0-9_]+__/, '');

                        const newWidget = document.createElement('div');
                        newWidget.className = 'widget-item card mb-2 border shadow-sm';
                        newWidget.innerHTML = `
                            <div class="card-header bg-white d-flex align-items-center justify-content-between py-2 cursor-move widget-handle">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-grip-vertical text-muted"></i>
                                    <span class="fw-bold small">${tplObj.name}</span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:9px;">ახალი</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="removeWidget(this)"><i class="bi bi-x-lg"></i></button>
                            </div>
                            <div class="card-body p-3">
                                <input type="hidden" name="widgets[${areaId}][${uniqueId}][active]" value="1">
                                <input type="hidden" name="widgets[${areaId}][${uniqueId}][type]" value="${type}">
                                ${innerHtml}
                            </div>
                        `;
                        item.replaceWith(newWidget);
                    }
                }
            });
        });
    });

    function removeWidget(btn) {
        Swal.fire({
            title: 'წავშალოთ?', text: "ვიჯეტი ამოიშლება.", icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'დიახ'
        }).then((result) => {
            if (result.isConfirmed) btn.closest('.widget-item').remove();
        });
    }
    </script>
    <style> .cursor-grab { cursor: grab; } .cursor-move { cursor: move; } .widget-handle:hover { background-color: #f8f9fa; } .empty-placeholder { border-style: dashed !important; } </style>
    <?php
}