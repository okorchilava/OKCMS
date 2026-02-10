<?php
/**
 * პოსტის რედაქტორი v3.8 (Fixed ID Storage: 1,2,3)
 * - Notifications: for_user_id ინახება როგორც "1,2,3"
 * - Gallery: attached_post_ids ინახება როგორც "1,2,3"
 */

global $ok_db;

// ─────────────────────────────────────────────────────────────────────────────
// 1. დამხმარე ფუნქციები
// ─────────────────────────────────────────────────────────────────────────────

if (!function_exists('ok_get_base_url')) {
    function ok_get_base_url() {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        return $protocol . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
    }
}

// 🔔 ნოტიფიკაციის შექმნა (Fixed: IDs as "1,2,3")
if (!function_exists('ok_create_notification')) {
    function ok_create_notification($type, $message, $link = '#', $sender_id = 0) {
        global $ok_db;
        if (!isset($ok_db)) return false;

        // 1. ვიღებთ ადმინების აიდებს
        $admins = $ok_db->get_results("SELECT id FROM ok_users WHERE user_role IN ('admin', 'administrator')");
        $admin_ids = [];
        if ($admins) {
            foreach ($admins as $admin) {
                $admin_ids[] = (int)$admin->id;
            }
        }

        // 2. ფორმატირება: "1,2,3" (ნაცვლად JSON-ისა)
        $for_user_ids_str = implode(',', $admin_ids); 
        
        // 3. ჩაწერა ბაზაში
        // შენიშვნა: დარწმუნდით, რომ ok_notifications ცხრილს აქვს for_user_id სვეტი
        $sql = "INSERT INTO ok_notifications 
                (type, message, link, user_id, for_user_id, is_read, created_at) 
                VALUES (?, ?, ?, ?, ?, 0, NOW())";

        return $ok_db->query($sql, [
            $type,                
            $message,             
            $link,                
            $sender_id,           
            $for_user_ids_str     // ინახება როგორც: "1,5,12"
        ]);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// AJAX SLUG HANDLER
// ─────────────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'update_slug') {
    if (!isset($_SESSION['user_id'])) { echo json_encode(['error' => 'Auth required']); exit; }
    
    $post_id = (int)$_POST['post_id'];
    $raw_slug = strip_tags(trim($_POST['slug']));
    $title_fallback = strip_tags(trim($_POST['title']));
    $source = !empty($raw_slug) ? $raw_slug : $title_fallback;
    
    if (function_exists('generate_slug')) {
        $slug = generate_slug($source);
        $check_sql = "SELECT id FROM ok_posts WHERE post_name = ? AND id != ?";
        if ($ok_db->get_var($check_sql, [$slug, $post_id])) {
            $slug .= '-' . time();
        }
    } else {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $source)));
    }

    if ($post_id > 0) {
        $ok_db->query("UPDATE ok_posts SET post_name = ? WHERE id = ?", [$slug, $post_id]);
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'new_slug' => $slug]);
    exit;
}

// მენიუში დამატება
if (function_exists('add_ok_action')) {
    add_ok_action('admin_menu', function() {
        if(function_exists('add_submenu_page')) {
            add_submenu_page('ok-posts', 'ახალი პოსტი', 'ახალი პოსტი', 'edit_posts', 'ok-post-editor', 'ok_render_post_editor');
        }
    });
}

// ─────────────────────────────────────────────────────────────────────────────
// რენდერი (მთავარი ფუნქცია)
// ─────────────────────────────────────────────────────────────────────────────
function ok_render_post_editor() {
    global $ok_db;
    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

    $post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $is_edit = ($post_id > 0);
    $post    = null;

    if ($is_edit) {
        $post = $ok_db->get_row("SELECT * FROM ok_posts WHERE id = ?", [$post_id]);
        if (!$post) { echo '<div class="alert alert-danger m-4">პოსტი არ მოიძებნა.</div>'; return; }
    }

    // --- SAVE LOGIC ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_post'])) {
        
        if(function_exists('check_admin_referer') && !check_admin_referer('save_post_action')) {
             die('Security Check Failed');
        }

        // მონაცემების მიღება
        $title   = strip_tags(trim($_POST['post_title']));
        $content = $_POST['post_content']; 
        $status  = in_array($_POST['post_status'], ['published', 'draft']) ? $_POST['post_status'] : 'draft';
        $author  = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1; 
        $date    = date('Y-m-d H:i:s');
        
        // კატეგორიები -> "1,2,3"
        $raw_cats = isset($_POST['post_categories']) ? $_POST['post_categories'] : [];
        if (!is_array($raw_cats)) $raw_cats = []; 
        $category_ids = array_map('intval', $raw_cats);
        $cats_string = implode(',', $category_ids);

        $has_sidebar = isset($_POST['has_sidebar']) ? 1 : 0;
        $manual_slug = strip_tags(trim($_POST['post_name'] ?? ''));
        $slug_source = !empty($manual_slug) ? $manual_slug : $title;
        
        // Slug Gen
        if (function_exists('generate_slug')) {
            $slug = generate_slug($slug_source);
            $check_sql = $is_edit ? "SELECT id FROM ok_posts WHERE post_name = ? AND id != ?" : "SELECT id FROM ok_posts WHERE post_name = ?";
            $params = $is_edit ? [$slug, $post_id] : [$slug];
            if ($ok_db->get_var($check_sql, $params)) { $slug .= '-' . time(); }
        } else {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $slug_source)));
            if (empty($slug)) $slug = 'post-' . time();
        }
        
        $image_url = filter_var($_POST['post_image_url'] ?? '', FILTER_SANITIZE_URL);
        $feat_img_id = (int)($_POST['post_image_id'] ?? 0);
        $content_img_ids = array_filter(explode(',', $_POST['content_image_ids'] ?? ''), 'is_numeric');

        // =====================================================================
        // 🔔 PREPARE NOTIFICATION MESSAGE
        // =====================================================================
        $notif_message = "";
        $u_row = $ok_db->get_row("SELECT username, display_name FROM ok_users WHERE id = ?", [$author]);
        $user_label = ($u_row) ? ($u_row->display_name ?: $u_row->username) : "User ID:".$author;

        if (!$is_edit) {
            $notif_message = "{$user_label}-მ დაამატა ახალი პოსტი: '{$title}'";
        } else {
            $changes = [];
            if ($post->post_title !== $title) $changes[] = "სათაური";
            if ($post->post_content !== $content) $changes[] = "კონტენტი";
            if ($post->post_status !== $status) $changes[] = "სტატუსი";
            $old_cats_str = (string)($post->category_id ?? '');
            if ($old_cats_str !== $cats_string) $changes[] = "კატეგორიები";
            
            if (!empty($changes)) {
                $notif_message = "{$user_label}-მ განაახლა პოსტი '{$title}'. შეიცვალა: " . implode(", ", $changes);
            } else {
                $notif_message = "{$user_label}-მ განაახლა პოსტი '{$title}' (მცირე ცვლილებები)";
            }
        }

        // =====================================================================
        // 💾 DB EXECUTION
        // =====================================================================
        if ($is_edit) {
            $sql = "UPDATE ok_posts SET post_title=?, post_content=?, post_status=?, category_id=?, post_image=?, post_name=?, has_sidebar=? WHERE id=?";
            $ok_db->query($sql, [$title, $content, $status, $cats_string, $image_url, $slug, $has_sidebar, $post_id]);
            $saved_post_id = $post_id;
        } else {
            $sql = "INSERT INTO ok_posts (post_author, post_date, post_content, post_title, post_status, category_id, post_type, post_image, post_name, has_sidebar) VALUES (?, ?, ?, ?, ?, ?, 'post', ?, ?, ?)";
            $ok_db->query($sql, [$author, $date, $content, $title, $status, $cats_string, 'post', $image_url, $slug, $has_sidebar]);
            $saved_post_id = $ok_db->last_insert_id();
        }

        if ($saved_post_id > 0) {
            
            // 🔔 SEND NOTIFICATION
            if (!empty($notif_message)) {
                $notif_type = ($is_edit) ? 'info' : 'success';
                $notif_link = "index.php?page=ok-post-editor&id=" . $saved_post_id;
                
                // ვიძახებთ ზემოთ შექმნილ ფუნქციას
                ok_create_notification($notif_type, $notif_message, $notif_link, $author);
            }

            // =================================================================
            // 🖼️ GALLERY ATTACHMENT LOGIC (Fixed: uses 1,2,3 instead of JSON)
            // =================================================================
            $all_image_ids = array_unique(array_merge([$feat_img_id], $content_img_ids));
            
            foreach ($all_image_ids as $img_id) {
                if ($img_id > 0) {
                    $curr_str = $ok_db->get_var("SELECT attached_post_ids FROM ok_gallery WHERE id = ?", [$img_id]);
                    
                    // JSON-ის მაგივრად ვიყენებთ explode-ს (თუ ცარიელია - ცარიელი მასივი)
                    $attached = !empty($curr_str) ? explode(',', $curr_str) : [];
                    
                    // დაზღვევა: ვშლით ცარიელ ელემენტებს და ვაკეთებთ integer-ებად
                    $attached = array_filter(array_map('intval', $attached));

                    if (!in_array($saved_post_id, $attached)) {
                        $attached[] = $saved_post_id;
                        // ვინახავთ ისევ როგორც "1,2,3"
                        $new_str = implode(',', $attached);
                        
                        $ok_db->query("UPDATE ok_gallery SET attached_post_ids = ? WHERE id = ?", [$new_str, $img_id]);
                    }
                }
            }

            // Redirect
            $redirect_url = "index.php?page=ok-post-editor&id=" . $saved_post_id . "&success=1";
            if (!headers_sent()) {
                header("Location: " . $redirect_url);
            } else {
                echo "<script>window.location.href = '$redirect_url';</script>";
            }
            exit;
            
        } else {
            echo '<div class="alert alert-danger">შეცდომა შენახვისას. მონაცემთა ბაზის შეცდომა.</div>';
        }
    }

    // --- VIEW VARIABLES ---
    $p_title   = $post ? $post->post_title : '';
    $p_content = $post ? $post->post_content : '';
    $p_status  = $post ? $post->post_status : 'draft';
    $p_image   = $post ? ($post->post_image ?? '') : '';
    $p_slug    = $post ? ($post->post_name ?? '') : '';
    $p_sidebar = ($post && isset($post->has_sidebar)) ? $post->has_sidebar : 1;
    $site_url  = $_SERVER['HTTP_HOST'] . '/';
    
    // Categories
    $categories = $ok_db->get_results("SELECT id, name FROM ok_categories ORDER BY name ASC");
    $current_cat_ids = [];
    if ($post && !empty($post->category_id)) {
        $current_cat_ids = explode(',', $post->category_id);
    }
    
    $p_image_id = 0;
    if ($p_image) {
        $fname = basename($p_image);
        $found = $ok_db->get_row("SELECT id FROM ok_gallery WHERE file_name = ?", [$fname]);
        if($found) $p_image_id = $found->id;
    }
    ?>

    <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'მზადაა!',
                    text: 'პოსტი წარმატებით შეინახა',
                    icon: 'success',
                    confirmButtonText: 'კარგი',
                    timer: 2000,
                    timerProgressBar: true
                }).then(() => {
                    const url = new URL(window.location);
                    url.searchParams.delete('success');
                    window.history.replaceState({}, '', url);
                });
            });
        </script>
    <?php endif; ?>

    <form method="post" id="main_form" action="index.php?page=ok-post-editor<?php echo $is_edit ? '&id='.$post_id : ''; ?>">
        <?php if(function_exists('ok_nonce_field')) ok_nonce_field('save_post_action'); ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 fw-bold text-dark"><?php echo $is_edit ? 'პოსტის რედაქტირება' : 'ახალი პოსტი'; ?></h1>
            <a href="index.php?page=ok-posts" class="btn btn-outline-secondary btn-sm px-3 rounded-pill"><i class="bi bi-arrow-left me-1"></i> უკან</a>
        </div>

        <div class="row g-4">
            <div class="col-lg-9">
                <div class="mb-3">
                    <input type="text" name="post_title" id="post_title" class="form-control form-control-lg py-3 border shadow-sm fw-bold mb-2" placeholder="სათაური" value="<?php echo htmlspecialchars($p_title); ?>" required style="font-size: 1.5rem;">
                    
                    <?php if($is_edit): ?>
                    <div class="bg-light p-2 rounded border d-flex align-items-center flex-wrap gap-2 small text-muted mt-2">
                        <i class="bi bi-link-45deg fs-5"></i>
                        <span>ბმული:</span>
                        <div class="input-group input-group-sm flex-grow-1">
                            <span class="input-group-text bg-white text-muted border-end-0"><?php echo htmlspecialchars($site_url); ?></span>
                            <input type="text" name="post_name" id="post_slug_input" class="form-control border-start-0 ps-0 bg-white" value="<?php echo htmlspecialchars($p_slug); ?>">
                            <button type="button" id="update_slug_btn" class="btn btn-outline-secondary px-3" title="განახლება">OK</button>
                        </div>
                        <a href="../<?php echo htmlspecialchars($p_slug); ?>" target="_blank" id="view_post_link" class="btn btn-sm btn-link text-decoration-none">ნახვა <i class="bi bi-box-arrow-up-right ms-1"></i></a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="card border shadow-sm">
                    <div class="card-body p-0">
                        <?php 
                        if (function_exists('ok_render_editor')) {
                            ok_render_editor('post_content', $p_content, 'main_post_editor');
                        } else {
                            echo '<div class="p-3 alert alert-warning">ფუნქცია ok_render_editor არ მოიძებნა.</div>';
                            echo '<textarea name="post_content" class="form-control p-3" rows="15">'.htmlspecialchars($p_content).'</textarea>';
                        }
                        ?>
                        <input type="hidden" name="content_image_ids" id="content_image_ids" value="">
                    </div>
                </div>
            </div>

            <div class="col-lg-3">
                <div class="card border shadow-sm mb-4">
                    <div class="card-header bg-white fw-bold py-2 small text-uppercase text-muted border-bottom">გამოქვეყნება</div>
                    <div class="card-body p-3">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">სტატუსი:</label>
                            <select name="post_status" class="form-select form-select-sm">
                                <option value="published" <?php echo ($p_status === 'published') ? 'selected' : ''; ?>>გამოქვეყნებული</option>
                                <option value="draft" <?php echo ($p_status === 'draft') ? 'selected' : ''; ?>>დრაფტი</option>
                            </select>
                        </div>

                        <div class="mb-3 bg-light p-2 rounded border">
                            <label class="form-label small fw-bold text-muted mb-1">კატეგორია:</label>
                            <select name="post_categories[]" class="form-select form-select-sm" multiple style="min-height: 120px;" title="გეჭიროთ CTRL ღილაკი რამდენიმეს მოსანიშნად">
                                <?php if($categories): foreach($categories as $cat): ?>
                                    <option value="<?php echo $cat->id; ?>" <?php echo in_array($cat->id, $current_cat_ids) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat->name); ?>
                                    </option>
                                <?php endforeach; endif; ?>
                            </select>
                            <div class="form-text" style="font-size: 11px;">
                                <i class="bi bi-info-circle"></i> დააჭირე <b>Ctrl</b> (ან Cmd) ღილაკს რამდენიმეს მოსანიშნად.
                            </div>
                        </div>
                        
                        <div class="mb-3 p-2 bg-light rounded border">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="has_sidebar" name="has_sidebar" value="1" <?php echo ($p_sidebar == 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label small fw-bold text-muted" for="has_sidebar">საიდბარის ჩვენება</label>
                            </div>
                        </div>

                        <?php if(!$is_edit): ?>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">ბმული (Slug):</label>
                            <input type="text" name="post_name" class="form-control form-control-sm" value="<?php echo htmlspecialchars($p_slug); ?>">
                        </div>
                        <?php endif; ?>

                        <div class="d-grid">
                            <button type="submit" name="save_post" class="btn btn-primary btn-sm">
                                <i class="bi bi-save me-1"></i> <?php echo $is_edit ? 'განახლება' : 'გამოქვეყნება'; ?>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card border shadow-sm mb-4">
                    <div class="card-header bg-white fw-bold py-2 small text-uppercase text-muted border-bottom">მთავარი სურათი</div>
                    <div class="card-body p-3 text-center">
                        <div id="image-preview-container" class="mb-3 border rounded overflow-hidden bg-light position-relative <?php echo empty($p_image) ? 'd-none' : ''; ?>">
                            <img id="image-preview" src="<?php echo htmlspecialchars($p_image); ?>" class="img-fluid" style="max-height: 150px; width: 100%; object-fit: cover;">
                            <button type="button" id="remove-image-btn" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 shadow-sm"><i class="bi bi-x-lg"></i></button>
                        </div>
                        <input type="hidden" name="post_image_url" id="post_image_url" value="<?php echo htmlspecialchars($p_image); ?>">
                        <input type="hidden" name="post_image_id" id="post_image_id" value="<?php echo $p_image_id; ?>">
                        <div class="d-grid">
                            <button type="button" onclick="openGalleryForFeatured()" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-images me-1"></i> აირჩიეთ ფოტო
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // Slug AJAX
        const btn = document.getElementById('update_slug_btn');
        if (btn) { 
            btn.addEventListener('click', function() { 
                const slugInput = document.getElementById('post_slug_input'); 
                const titleInput = document.getElementById('post_title'); 
                const viewLink = document.getElementById('view_post_link'); 
                const originalText = btn.innerText; 
                btn.innerText = '...'; btn.disabled = true; 
                const formData = new FormData(); 
                formData.append('ajax_action', 'update_slug'); 
                formData.append('post_id', '<?php echo $post_id; ?>'); 
                formData.append('slug', slugInput.value); 
                formData.append('title', titleInput.value); 
                fetch(window.location.href, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => { 
                    if (data.success) { 
                        slugInput.value = data.new_slug; 
                        if (viewLink) viewLink.href = '../' + data.new_slug; 
                        btn.innerHTML = '<i class="bi bi-check"></i>'; 
                        setTimeout(() => { btn.innerText = originalText; btn.disabled = false; }, 1500); 
                    } 
                }); 
            }); 
        }
        
        // Gallery Functions
        function openGalleryModal(onSelectCallback) { 
            Swal.fire({ 
                title: 'აირჩიეთ სურათი', width: '800px', 
                html: `<div class="d-flex justify-content-end mb-2"><label class="btn btn-sm btn-success" for="quick-upload"><i class="bi bi-cloud-upload me-1"></i> ახალი ფოტო</label><input type="file" id="quick-upload" style="display:none;" accept="image/*" multiple></div><div id="swal-gallery-grid" class="d-flex flex-wrap gap-2 justify-content-center p-2" style="max-height: 400px; overflow-y: auto;"><i>იტვირთება...</i></div>`, 
                showCancelButton: true, showConfirmButton: false, cancelButtonText: 'დახურვა', 
                didOpen: () => { 
                    loadImages(onSelectCallback); 
                    document.getElementById('quick-upload').addEventListener('change', function() { 
                        if(this.files.length > 0) { 
                            const formData = new FormData(); 
                            for(let i=0; i<this.files.length; i++) formData.append('gallery_image[]', this.files[i]); 
                            fetch('index.php?page=ok-gallery&action=upload_images_ajax', { method: 'POST', body: formData })
                            .then(r => r.json()).then(res => { if(res.success) { loadImages(onSelectCallback); } }); 
                        } 
                    }); 
                } 
            }); 
        }
        
        function loadImages(onSelectCallback) { 
            fetch('index.php?page=ok-gallery&action=get_images_ajax')
            .then(r => r.json())
            .then(res => { 
                const grid = document.getElementById('swal-gallery-grid'); 
                if(res.success && res.images.length > 0) { 
                    grid.innerHTML = ''; 
                    res.images.forEach(img => { 
                        const div = document.createElement('div'); 
                        div.style.cssText = 'width: 120px; height: 120px; cursor: pointer; border: 2px solid #eee; overflow: hidden; border-radius: 4px;'; 
                        div.innerHTML = `<img src="${img.url}" style="width: 100%; height: 100%; object-fit: cover;">`; 
                        div.addEventListener('click', () => { onSelectCallback(img); Swal.close(); }); 
                        grid.appendChild(div); 
                    }); 
                } else { grid.innerHTML = '<p class="text-muted">გალერეა ცარიელია.</p>'; } 
            }); 
        }
        
        window.openGalleryForFeatured = function() { 
            openGalleryModal((img) => { 
                document.getElementById('post_image_url').value = img.url; 
                document.getElementById('post_image_id').value = img.id; 
                document.getElementById('image-preview').src = img.url; 
                document.getElementById('image-preview-container').classList.remove('d-none'); 
            }); 
        };
        
        window.openGalleryForEditor = function(editorId) { 
            const targetId = editorId || 'main_post_editor'; 
            openGalleryModal((img) => { 
                if(window.okInsertImage) { window.okInsertImage(targetId, img.url); } 
                const hiddenInput = document.getElementById('content_image_ids'); 
                let currentIds = hiddenInput.value ? hiddenInput.value.split(',') : []; 
                if (!currentIds.includes(img.id.toString())) { 
                    currentIds.push(img.id); hiddenInput.value = currentIds.join(','); 
                } 
            }); 
        };
        
        const removeBtn = document.getElementById('remove-image-btn'); 
        if(removeBtn) { 
            removeBtn.addEventListener('click', () => { 
                document.getElementById('post_image_url').value = ''; 
                document.getElementById('post_image_id').value = '0'; 
                document.getElementById('image-preview').src = ''; 
                document.getElementById('image-preview-container').classList.add('d-none'); 
            }); 
        }
    });
    </script>
    <?php
}
?>