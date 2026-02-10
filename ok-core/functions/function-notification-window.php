<?php
/**
 * OK Engine — Notifications UI Component
 */
if (!defined('OK_LOADED')) exit;

function ok_render_notification_dropdown() {
    global $ok_db;

    // მონაცემების მომზადება (notifications.php-დან)
    $notif_count = function_exists('ok_count_unread_notifications') ? ok_count_unread_notifications() : 0;
    $notifications = function_exists('ok_get_notifications') ? ok_get_notifications(8) : [];
    ?>
    <div class="dropdown me-3">
        <a href="#" class="text-white position-relative d-flex align-items-center justify-content-center" 
           id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="width: 40px; height: 40px;">
            <i class="bi bi-bell fs-5"></i>
            
            <?php if ($notif_count > 0): ?>
                <span class="badge rounded-pill bg-danger notif-badge-anim ok-badge-adjusted" id="ok-notif-badge">
                    <?php echo $notif_count > 99 ? '99+' : $notif_count; ?>
                </span>
            <?php endif; ?>
        </a>

        <div class="dropdown-menu dropdown-menu-end shadow ok-notif-dropdown" aria-labelledby="notifDropdown">
            <div class="ok-notif-header">
                <span>შეტყობინებები</span>
                <small style="cursor: pointer; color: #0d6efd;" onclick="okMarkAllRead(event)">ყველას წაკითხვა</small>
            </div>
            <div class="ok-notif-list" id="ok-header-notif-list">
                <?php if (empty($notifications)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-bell-slash fs-4 d-block mb-2"></i>
                        <small>ახალი შეტყობინებები არ არის</small>
                    </div>
                <?php else: ?>
                    <?php foreach($notifications as $notif): 
                        // Icon & Color Logic (დამატებულია info ტიპი)
                        $n_icon = 'bi-bell'; $n_color = 'bg-secondary';

                        if($notif->type === 'success') { $n_icon = 'bi-check-lg'; $n_color = 'bg-n-success'; }
                        elseif($notif->type === 'info')    { $n_icon = 'bi-info-lg'; $n_color = 'bg-n-info'; }
                        elseif($notif->type === 'warning') { $n_icon = 'bi-exclamation-lg'; $n_color = 'bg-n-warning'; }
                        elseif($notif->type === 'danger')  { $n_icon = 'bi-x-lg'; $n_color = 'bg-n-danger'; }
                        
                        $n_link = !empty($notif->link) ? $notif->link : '#';
                        $is_unread = isset($notif->is_read_by_me) ? ($notif->is_read_by_me == 0) : true;
                    ?>
                    <a href="<?php echo htmlspecialchars($n_link); ?>" 
                       class="ok-notif-item <?php echo $is_unread ? 'unread' : ''; ?>" 
                       id="notif-item-<?php echo $notif->id; ?>">
                        <div class="ok-notif-icon-box <?php echo $n_color; ?>">
                            <i class="bi <?php echo $n_icon; ?>"></i>
                        </div>
                        <div class="ok-notif-content">
                            <div><?php echo htmlspecialchars($notif->message); ?></div>
                            <span class="ok-notif-time"><?php echo date('d M, H:i', strtotime($notif->created_at)); ?></span>
                        </div>
                        <div class="ok-read-toggler" 
                             onclick="okToggleRead(event, <?php echo $notif->id; ?>)">
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="p-2 text-center border-top bg-light">
                <a href="index.php?page=ok-notifications" class="text-decoration-none small fw-bold text-secondary">ყველას ნახვა</a>
            </div>
        </div>
    </div>
    <?php
}