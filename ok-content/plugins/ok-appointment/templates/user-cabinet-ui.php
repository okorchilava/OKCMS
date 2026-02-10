<?php 
if (!defined('OK_LOADED')) exit; 

// 1. ბაზის და სესიის ინიციალიზაცია
global $ok_db;
$user_id = 0;

// --- SESSION FIX: მომხმარებლის ID-ის წამოღება ახალი სისტემიდან ---
if (isset($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
}
// -----------------------------------------------------------

// 2. მონაცემების წამოღება
$my_apps = [];
if ($user_id > 0 && $ok_db) {
    // ვიღებთ ვიზიტებს და სერვისის სახელებს (უსაფრთხოების მიზნით ვიყენებთ '?' სინტაქსს)
    $my_apps = $ok_db->get_results("SELECT a.*, s.title AS s_name 
                                    FROM ok_appointments a 
                                    LEFT JOIN ok_services s ON a.service_id = s.id 
                                    WHERE a.customer_id = ? 
                                    ORDER BY a.appointment_date DESC", [$user_id]);
}
?>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">ჩემი ვიზიტები</div>
    <div class="card-body">
        <?php if($user_id == 0): ?>
             <div class="alert alert-warning">გთხოვთ გაიაროთ ავტორიზაცია ვიზიტების სანახავად.</div>
        <?php elseif(empty($my_apps)): ?>
            <p class="text-center text-muted my-3">ვიზიტები არ არის.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light"><tr><th>სერვისი</th><th>დრო</th><th>სტატუსი</th></tr></thead>
                    <tbody>
                        <?php foreach($my_apps as $a): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($a->s_name ?? 'წაშლილი სერვისი'); ?></strong>
                            </td>
                            <td>
                                <?php echo $a->appointment_date; ?> 
                                <span class="text-muted small">(<?php echo substr($a->appointment_time, 0, 5); ?>)</span>
                            </td>
                            <td>
                                <?php 
                                    // სტატუსების ფერები
                                    if($a->status == 'paid') {
                                        echo '<span class="badge bg-success">გადახდილი</span>';
                                    } elseif($a->status == 'on-hold' || $a->status == 'pending') {
                                        echo '<span class="badge bg-warning text-dark">ელოდება</span>';
                                    } elseif($a->status == 'cancelled') {
                                        echo '<span class="badge bg-danger">გაუქმებული</span>';
                                    } else {
                                        echo '<span class="badge bg-secondary">'.htmlspecialchars($a->status).'</span>';
                                    }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>