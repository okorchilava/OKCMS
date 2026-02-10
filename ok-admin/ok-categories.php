<?php
declare(strict_types=1);

/**
 * კატეგორიების მენეჯერი v3.0 (Refactored)
 * ინტეგრირებულია OkNotificationManager-თან
 */

// ინიციალიზაცია
add_ok_action('admin_menu', ['OkCategoryManager', 'init']);

class OkCategoryManager
{
    private const PAGE_SLUG = 'ok-categories';
    private const TABLE = 'ok_categories';

    /**
     * მენიუს რეგისტრაცია
     */
    public static function init(): void
    {
        add_submenu_page(
            'ok-posts',
            'კატეგორიები',
            'კატეგორიები',
            'manage_options',
            self::PAGE_SLUG,
            [self::class, 'render']
        );
    }

    /**
     * მთავარი რენდერი (Controller + View)
     */
    public static function render(): void
    {
        global $ok_db;

        // 1. ლოგიკის დამუშავება (POST/GET Request Handler)
        $message = self::handleRequest();

        // 2. მონაცემების მომზადება
        $editMode = false;
        $editData = null;

        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
            $editId = (int)$_GET['id'];
            $editData = $ok_db->get_row("SELECT * FROM " . self::TABLE . " WHERE id = ?", [$editId]);
            if ($editData) {
                $editMode = true;
            }
        }

        // 3. მონაცემების წამოღება
        $categories = $ok_db->get_results("SELECT * FROM " . self::TABLE . " ORDER BY id DESC");

        // 4. View-ს გამოტანა
        ?>
        <div class="wrap p-4">
            <h1 class="h3 fw-bold mb-4">კატეგორიები</h1>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message['type']; ?> m-3 shadow-sm">
                    <?php echo $message['text']; ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 <?php echo $editMode ? 'border-warning' : ''; ?>">
                        <div class="card-header bg-white fw-bold">
                            <?php echo $editMode ? 'რედაქტირება: ' . htmlspecialchars($editData->name) : 'ახალი კატეგორიის დამატება'; ?>
                        </div>
                        <div class="card-body">
                            <form method="post" action="?page=<?php echo self::PAGE_SLUG; ?><?php echo $editMode ? '&action=edit&id='.$editData->id : ''; ?>">
                                <input type="hidden" name="ok_nonce" value="<?php echo self::createNonce('ok_cat_action'); ?>">

                                <div class="mb-3">
                                    <label class="form-label small text-muted">სახელი</label>
                                    <input type="text" name="cat_name" class="form-control" required 
                                           value="<?php echo $editMode ? htmlspecialchars($editData->name) : ''; ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label small text-muted">Slug (ბმული)</label>
                                    <input type="text" name="cat_slug" class="form-control"
                                           value="<?php echo $editMode ? htmlspecialchars($editData->slug) : ''; ?>">
                                    <div class="form-text">ცარიელი = ავტომატური გენერაცია</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small text-muted">აღწერა</label>
                                    <textarea name="cat_desc" class="form-control" rows="3"><?php echo $editMode ? htmlspecialchars($editData->description) : ''; ?></textarea>
                                </div>

                                <?php if ($editMode): ?>
                                    <button type="submit" name="update_category" class="btn btn-warning w-100 text-white">განახლება</button>
                                    <a href="?page=<?php echo self::PAGE_SLUG; ?>" class="btn btn-outline-secondary w-100 mt-2">გაუქმება</a>
                                <?php else: ?>
                                    <button type="submit" name="add_category" class="btn btn-primary w-100">დამატება</button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">სახელი</th>
                                        <th>Slug</th>
                                        <th>აღწერა</th>
                                        <th class="text-end pe-4">მოქმედება</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($categories): foreach ($categories as $cat): ?>
                                    <tr class="<?php echo ($editMode && $editData->id == $cat->id) ? 'table-warning' : ''; ?>">
                                        <td class="ps-4 fw-bold text-primary"><?php echo htmlspecialchars($cat->name); ?></td>
                                        <td><code><?php echo htmlspecialchars($cat->slug); ?></code></td>
                                        <td class="small text-muted"><?php echo htmlspecialchars($cat->description); ?></td>
                                        <td class="text-end pe-4">
                                            <a href="?page=<?php echo self::PAGE_SLUG; ?>&action=edit&id=<?php echo $cat->id; ?>" 
                                               class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                                            <a href="?page=<?php echo self::PAGE_SLUG; ?>&action=delete&id=<?php echo $cat->id; ?>&_wpnonce=<?php echo self::createNonce('delete_cat_' . $cat->id); ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('დარწმუნებული ხართ?');"><i class="bi bi-trash"></i></a>
                                        </td>
                                    </tr>
                                    <?php endforeach; else: ?>
                                    <tr><td colspan="4" class="text-center p-4 text-muted">კატეგორიები არ არის.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Request Processor logic
     * @return array|null Returns message array ['type' => '...', 'text' => '...'] or null
     */
    private static function handleRequest(): ?array
    {
        global $ok_db;

        // DELETE ACTION
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            // აქ სასურველია Nonce შემოწმებაც (იხ. self::verifyNonce)
            
            $delId = (int)$_GET['id'];
            $catName = $ok_db->get_var("SELECT name FROM " . self::TABLE . " WHERE id = ?", [$delId]);

            if ($catName) {
                $ok_db->query("DELETE FROM " . self::TABLE . " WHERE id = ?", [$delId]);
                $ok_db->query("UPDATE ok_posts SET category_id = 0 WHERE category_id = ?", [$delId]);

                // 🔥 ინტეგრაცია ახალ სისტემასთან (ერთი ხაზით)
                // OkNotificationManager უკვე იცის, რომ მეილიც უნდა გაგზავნოს (თუ კონფიგურაციაში ჩართულია)
                OkNotificationManager::add(
                    "წაიშალა კატეგორია: <b>{$catName}</b>",
                    'warning',
                    [], // ადმინებს
                    'index.php?page=' . self::PAGE_SLUG
                );

                return ['type' => 'success', 'text' => 'კატეგორია წარმატებით წაიშალა.'];
            }
        }

        // POST ACTIONS (ADD / UPDATE)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // უსაფრთხოების შემოწმება (Placeholder)
            if (!isset($_POST['ok_nonce']) /* || !self::verifyNonce(...) */) {
                return ['type' => 'danger', 'text' => 'Security Token Expired.'];
            }

            $name = trim($_POST['cat_name'] ?? '');
            $desc = trim($_POST['cat_desc'] ?? '');
            $slugInput = trim($_POST['cat_slug'] ?? '');
            
            // Slug Generation
            $slug = empty($slugInput) ? self::slugify($name) : self::slugify($slugInput);
            
            if (empty($name)) return ['type' => 'danger', 'text' => 'სახელი სავალდებულოა.'];

            // INSERT
            if (isset($_POST['add_category'])) {
                // Duplicate Check
                if ($ok_db->get_var("SELECT id FROM " . self::TABLE . " WHERE slug = ?", [$slug])) {
                    $slug .= '-' . time();
                }

                $ok_db->query("INSERT INTO " . self::TABLE . " (name, slug, description) VALUES (?, ?, ?)", [$name, $slug, $desc]);

                // 🔥 ინტეგრაცია ახალ სისტემასთან
                OkNotificationManager::add(
                    "შეიქმნა ახალი კატეგორია: <b>{$name}</b>",
                    'success',
                    [], 
                    'index.php?page=' . self::PAGE_SLUG
                );

                return ['type' => 'success', 'text' => 'კატეგორია დაემატა!'];
            }

            // UPDATE
            if (isset($_POST['update_category']) && isset($_GET['id'])) {
                $editId = (int)$_GET['id'];

                // Duplicate Check (Excluding self)
                $check = $ok_db->get_var("SELECT id FROM " . self::TABLE . " WHERE slug = ? AND id != ?", [$slug, $editId]);
                if ($check) $slug .= '-' . time();

                $ok_db->query("UPDATE " . self::TABLE . " SET name=?, slug=?, description=? WHERE id=?", [$name, $slug, $desc, $editId]);

                // 🔥 ინტეგრაცია ახალ სისტემასთან
                OkNotificationManager::add(
                    "განახლდა კატეგორია: <b>{$name}</b>",
                    'info',
                    [], 
                    'index.php?page=' . self::PAGE_SLUG . '&action=edit&id=' . $editId
                );

                return ['type' => 'info', 'text' => 'კატეგორია განახლდა.'];
            }
        }

        return null;
    }

    /**
     * Helper: Slug Generator (თუ გლობალური ფუნქცია არ გვაქვს)
     */
    private static function slugify(string $text): string
    {
        if (function_exists('generate_slug')) {
            return generate_slug($text);
        }
        // Fallback implementation
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        return strtolower($text) ?: 'n-a';
    }

    /**
     * Helper: Nonce Creation (Security Mockup)
     */
    private static function createNonce($action) {
        return substr(md5($action . 'secret_salt_' . date('Ymd')), 0, 10);
    }
}