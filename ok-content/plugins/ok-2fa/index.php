<?php
/*
Plugin Name: OK Ultimate 2FA (Admin Menu)
Description: 2FA სისტემა ინტეგრირებული ადმინ მენიუში.
Version: 6.0
Author: OK Engine Team
*/

if (!defined('OK_LOADED')) { die('Access Denied.'); }

define('OK_2FA_TBL', 'ok_users_2fa_secrets');

// ჰუკების ინიციალიზაცია
if (function_exists('ok_add_action')) {
    ok_add_action('init', 'ok_2fa_install_db');   // ბაზა
    ok_add_action('init', 'ok_2fa_handle_post');  // სეთინგების შენახვა
    ok_add_action('ok_head', 'ok_2fa_shortcode'); // შორტკოდი (სურვილისამებრ)
    
    // 🔥 მთავარი ცვლილება: მენიუს ჰუკი
    ok_add_action('admin_menu', 'ok_2fa_add_menu_item'); 
}

/**
 * 1. მენიუს რეგისტრაცია
 */
function ok_2fa_add_menu_item() {
    // add_menu_page(PageTitle, MenuTitle, Capability, Slug, Callback, Icon, Position)
    // 'read' - ნიშნავს, რომ ყველა დარეგისტრირებულმა უნდა ნახოს (არა მხოლოდ ადმინმა)
    if (function_exists('add_menu_page')) {
        add_menu_page(
            'უსაფრთხოება',        // გვერდის სათაური
            '2FA დაცვა',          // მენიუს სათაური
            'read',               // უფლება (ყველასთვის რომ იყოს)
            'ok-2fa',             // URL Slug
            'ok_2fa_render_page', // ფუნქცია, რომელიც html-ს დახატავს
            'bi bi-shield-lock',  // იკონკა
            99                    // პოზიცია (ბოლოში)
        );
    }
}

/**
 * 2. გვერდის ვიზუალი (Callback ფუნქცია)
 */
function ok_2fa_render_page() {
    echo '<div class="wrap" style="padding:20px;">';
    echo '<h2>უსაფრთხოების პარამეტრები</h2>';
    echo '<hr>';
    // ვიძახებთ მთავარ ვიჯეტს
    echo ok_get_2fa_widget_html();
    echo '</div>';
}

/**
 * 3. ბაზის ინსტალაცია
 */
function ok_2fa_install_db() {
    global $ok_db;
    $ok_db->query("CREATE TABLE IF NOT EXISTS " . OK_2FA_TBL . " (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL UNIQUE,
        secret VARCHAR(255) NOT NULL,
        is_enabled TINYINT(1) DEFAULT 0,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

/**
 * 4. TOTP მათემატიკური კლასი
 */
if (!class_exists('OK_TOTP_Helper')) {
    class OK_TOTP_Helper {
        protected $_base32Map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        public function generateSecret($l = 16) { $s = ''; for ($i = 0; $i < $l; $i++) $s .= $this->_base32Map[rand(0, 31)]; return $s; }
        public function getCode($s, $ts = null) {
            if ($ts === null) $ts = floor(time() / 30);
            $sk = $this->_base32Decode($s);
            $hash = hash_hmac('SHA1', pack('N*', 0) . pack('N*', $ts), $sk, true);
            $off = ord(substr($hash, -1)) & 0x0F;
            $v = unpack('N', substr($hash, $off, 4));
            return str_pad(($v[1] & 0x7FFFFFFF) % 1000000, 6, '0', STR_PAD_LEFT);
        }
        public function verifyCode($s, $c) {
            $sl = floor(time() / 30);
            for ($i = -1; $i <= 1; $i++) if ($this->getCode($s, $sl + $i) == $c) return true;
            return false;
        }
        protected function _base32Decode($s) {
            if (empty($s)) return '';
            $map = array_flip(str_split($this->_base32Map));
            $s = str_replace('=', '', strtoupper($s));
            $bin = '';
            foreach (str_split($s) as $c) $bin .= str_pad(base_convert($map[$c], 10, 2), 5, '0', STR_PAD_LEFT);
            $res = '';
            foreach (str_split($bin, 8) as $b) if(strlen($b)==8) $res .= chr(base_convert($b, 2, 10));
            return $res;
        }
    }
}

/**
 * 5. POST მოთხოვნების დამუშავება (Save/Update)
 */
function ok_2fa_handle_post() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) return;

    global $ok_db, $ok_2fa_msg;
    $uid = (int)$_SESSION['user_id'];
    $ok_2fa_msg = '';

    if (isset($_POST['ok_enable_2fa'])) {
        $sec = $_POST['secret_key'] ?? '';
        $cod = preg_replace('/[^0-9]/', '', $_POST['verify_code'] ?? '');
        
        $totp = new OK_TOTP_Helper();
        if ($totp->verifyCode($sec, $cod)) {
            $ex = $ok_db->get_row("SELECT id FROM ".OK_2FA_TBL." WHERE user_id='$uid'");
            if ($ex) $ok_db->query("UPDATE ".OK_2FA_TBL." SET secret='$sec', is_enabled=1 WHERE user_id='$uid'");
            else $ok_db->query("INSERT INTO ".OK_2FA_TBL." (user_id, secret, is_enabled) VALUES ('$uid', '$sec', 1)");
            $ok_2fa_msg = '<div class="alert alert-success">✅ 2FA წარმატებით გააქტიურდა!</div>';
        } else {
            $ok_2fa_msg = '<div class="alert alert-danger">❌ კოდი არასწორია.</div>';
        }
    }

    if (isset($_POST['ok_disable_2fa'])) {
        $ok_db->query("UPDATE ".OK_2FA_TBL." SET is_enabled=0 WHERE user_id='$uid'");
        $ok_2fa_msg = '<div class="alert alert-warning">⚠️ 2FA გამორთულია.</div>';
    }
}

/**
 * 6. HTML ვიჯეტის გენერატორი
 */
function ok_get_2fa_widget_html() {
    global $ok_db, $ok_2fa_msg;
    
    if (!isset($_SESSION['user_id'])) return '<div class="alert alert-danger">გთხოვთ გაიაროთ ავტორიზაცია</div>';

    $uid = $_SESSION['user_id'];
    $totp = new OK_TOTP_Helper();
    
    $row = $ok_db->get_row("SELECT * FROM ".OK_2FA_TBL." WHERE user_id='$uid'");
    
    $enabled = ($row && $row->is_enabled);
    $secret = ($row && $row->secret) ? $row->secret : $totp->generateSecret();
    
    $qr = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=".urlencode("otpauth://totp/".$_SERVER['SERVER_NAME'].":$uid?secret=$secret");

    ob_start(); 
    ?>
    
    <div class="card shadow-sm" style="max-width: 700px;">
        <div class="card-body">
            <?php echo $ok_2fa_msg; ?>

            <?php if($enabled): ?>
                <div class="text-center py-5">
                    <h1 class="text-success display-3"><i class="bi bi-shield-check"></i></h1>
                    <h3 class="text-success">დაცულია</h3>
                    <p class="text-muted mb-4">თქვენს ანგარიშზე ჩართულია ორ-ეტაპიანი ავტორიზაცია.</p>
                    <form method="post" onsubmit="return confirm('ნამდვილად გსურთ გამორთვა?');">
                        <button type="submit" name="ok_disable_2fa" class="btn btn-danger">
                            <i class="bi bi-power"></i> გამორთვა
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="row">
                    <div class="col-md-5 text-center">
                        <div class="p-2 border rounded d-inline-block bg-white">
                            <img src="<?php echo $qr; ?>" alt="QR" class="img-fluid">
                        </div>
                        <p class="text-muted mt-2 small">დაასკანერეთ აპლიკაციით</p>
                    </div>
                    <div class="col-md-7">
                        <h4>აქტივაცია</h4>
                        <ol class="mb-4">
                            <li>გახსენით <b>Google Authenticator</b></li>
                            <li>დაამატეთ ახალი კოდი (+)</li>
                            <li>შეიყვანეთ მიღებული ციფრები:</li>
                        </ol>
                        
                        <form method="post" class="row g-2 align-items-center">
                            <input type="hidden" name="secret_key" value="<?php echo $secret; ?>">
                            <div class="col-auto">
                                <input type="text" name="verify_code" class="form-control form-control-lg text-center" placeholder="XXX XXX" style="letter-spacing: 3px;" required autocomplete="off">
                            </div>
                            <div class="col-auto">
                                <button type="submit" name="ok_enable_2fa" class="btn btn-primary btn-lg">
                                    ჩართვა
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php
    return ob_get_clean();
}

/**
 * 7. შორტკოდის მხარდაჭერა (სხვა ადგილებში გამოსაყენებლად)
 */
function ok_2fa_shortcode() {
    ob_start(function($b){ return str_replace('[ok_2fa_widget]', ok_get_2fa_widget_html(), $b); });
}