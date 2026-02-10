<?php
/*
Plugin Name: OK Bank of Georgia Payment (Auto-Sync & Hooks)
Description: BOG ინტეგრაცია: სრული სისტემა, ტაიმერი, მყისიერი სინქრონიზაცია დაბრუნებისას და Hooks.
Version: 47.0.0
Author: OK Engine Team
*/

if (!defined('OK_LOADED')) { die('Access Denied.'); }

define('OK_BOG_TBL', 'ok_bog_transactions');
define('OK_BOG_AUTH_URL', 'https://oauth2.bog.ge/auth/realms/bog/protocol/openid-connect/token');
define('OK_BOG_CREATE_URL', 'https://api.bog.ge/payments/v1/ecommerce/orders');
define('OK_BOG_REFUND_BASE', 'https://api.bog.ge/payments/v1/payment/refund/');
define('OK_BOG_DETAILS_BASE', 'https://api.bog.ge/payments/v1/receipt/'); 
define('OK_BOG_LOG_FILE', __DIR__ . '/ok_bog_debug.log');

/* =========================================================
   1. INSTALLATION & HOOKS
   ========================================================= */

if (function_exists('ok_add_action')) {
    ok_add_action('init', 'ok_bog_init');
    ok_add_action('init', 'ok_bog_check_virtual_page');
    ok_add_action('init', 'ok_bog_auto_cleanup');
    ok_add_action('init', 'ok_bog_ajax_expire');
    ok_add_action('admin_menu', 'ok_bog_admin_menu');
    ok_add_action('init', 'ok_bog_register_shortcodes');
} 
function ok_bog_init() {
    global $ok_db;
    
    $ok_db->query("CREATE TABLE IF NOT EXISTS " . OK_BOG_TBL . " (
        id INT(11) NOT NULL AUTO_INCREMENT,
        external_order_id VARCHAR(100) NOT NULL,
        bog_order_id VARCHAR(100) DEFAULT NULL,
        amount DECIMAL(10,2) NOT NULL,
        description TEXT DEFAULT NULL, 
        status VARCHAR(50) DEFAULT 'pending', 
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY external_order_id (external_order_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $cols = $ok_db->get_results("SHOW COLUMNS FROM " . OK_BOG_TBL . " LIKE 'description'");
    if (!empty($cols) && strpos(strtolower($cols[0]->Type), 'varchar') !== false) {
        $ok_db->query("ALTER TABLE " . OK_BOG_TBL . " MODIFY COLUMN description TEXT DEFAULT NULL");
    } elseif (empty($cols)) {
        $ok_db->query("ALTER TABLE " . OK_BOG_TBL . " ADD COLUMN description TEXT DEFAULT NULL AFTER amount");
    }

    if (isset($_GET['bog_callback'])) ok_bog_handle_callback();
}

function ok_bog_auto_cleanup() {
    global $ok_db;
    $timeout_min = (int)get_ok_option('bog_payment_timeout', 10);
    if ($timeout_min <= 0) $timeout_min = 30;
    $buffer_min = $timeout_min + 1;
    $sql = "DELETE FROM " . OK_BOG_TBL . " WHERE status = 'pending' AND created_at < (NOW() - INTERVAL $buffer_min MINUTE)";
    $ok_db->query($sql);
}

function ok_bog_ajax_expire() {
    if (isset($_POST['ok_expire_order_id'])) {
        global $ok_db;
        $oid = preg_replace('/[^a-zA-Z0-9-]/', '', $_POST['ok_expire_order_id']);
        if($oid) {
            $ok_db->query("DELETE FROM " . OK_BOG_TBL . " WHERE external_order_id = '$oid' AND status = 'pending'");
            echo "deleted";
        }
        exit;
    }
}

/* =========================================================
   2. ENCRYPTION & HELPERS
   ========================================================= */

function ok_bog_encrypt($string) {
    if (empty($string)) return '';
    $key = get_ok_option('bog_secret_key', 'default_secret_key_change_me');
    $iv_length = openssl_cipher_iv_length('aes-256-cbc');
    $iv = openssl_random_pseudo_bytes($iv_length);
    $encrypted = openssl_encrypt($string, 'aes-256-cbc', $key, 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

function ok_bog_decrypt($string) {
    if (empty($string)) return '';
    $key = get_ok_option('bog_secret_key', 'default_secret_key_change_me');
    $decoded = base64_decode($string);
    if (strpos($decoded, '::') === false) return $string; 
    list($encrypted_data, $iv) = explode('::', $decoded, 2);
    return openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 0, $iv);
}

function ok_bog_write_log($title, $data = null) {
    if (!get_ok_option('bog_enable_logs', 0)) return;
    $time = date('[Y-m-d H:i:s]');
    $msg = "$time $title";
    if ($data !== null) {
        $msg .= "\n" . (is_array($data) || is_object($data) ? json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : $data);
    }
    $msg .= "\n--------------------------------------------------\n";
    @file_put_contents(OK_BOG_LOG_FILE, $msg, FILE_APPEND);
}

function ok_bog_get_base_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . $host;
}

function ok_bog_get_link($amount, $description = '') {
    $base = ok_bog_get_base_url() . '/payments';
    $url = $base . '?cost=' . floatval($amount);
    if (!empty($description)) {
        $encrypted_desc = ok_bog_encrypt($description);
        $url .= '&desc=' . urlencode($encrypted_desc);
    }
    return $url;
}

function ok_bog_get_button($amount, $description = '', $label = 'გადახდა', $class = 'btn btn-primary') {
    $url = ok_bog_get_link($amount, $description);
    return '<a href="' . $url . '" class="' . $class . '">' . $label . '</a>';
}

/* =========================================================
   3. SHORTCODES
   ========================================================= */

function ok_bog_register_shortcodes() {
    if (function_exists('ok_add_shortcode')) {
        ok_add_shortcode('bog_pay_button', 'ok_bog_render_button');
        ok_add_shortcode('bog_direct_pay', 'ok_bog_direct_pay_handler');
        ok_add_shortcode('bog_button', 'ok_bog_fixed_button_shortcode');
    } elseif (function_exists('add_shortcode')) {
        add_shortcode('bog_pay_button', 'ok_bog_render_button');
        add_shortcode('bog_direct_pay', 'ok_bog_direct_pay_handler');
        add_shortcode('bog_button', 'ok_bog_fixed_button_shortcode');
    }
}

function ok_bog_fixed_button_shortcode($atts) {
    if (!is_array($atts)) $atts = [];
    $atts = array_change_key_case($atts, CASE_LOWER);
    $amount = isset($atts['amount']) ? floatval($atts['amount']) : 0;
    $desc   = isset($atts['desc']) ? $atts['desc'] : 'გადახდა';
    $label  = isset($atts['label']) ? $atts['label'] : 'გადახდა (' . $amount . '₾)';
    $class  = isset($atts['class']) ? $atts['class'] : 'btn btn-primary';
    if ($amount > 0) {
        return ok_bog_get_button($amount, $desc, $label, $class);
    }
    return '';
}

/* =========================================================
   4. VIRTUAL PAGE ROUTER (BRANDED UI)
   ========================================================= */

function ok_bog_check_virtual_page() {
    $request_uri = $_SERVER['REQUEST_URI'];
    $path = parse_url($request_uri, PHP_URL_PATH);

    if (trim($path, '/') === 'payments') {
        ?>
        <!DOCTYPE html>
        <html lang="ka">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>უსაფრთხო გადახდა - BOG</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@300;400;600;700&display=swap" rel="stylesheet">
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <style>
                :root { --bog-orange: #fe5000; --bog-dark: #2b2b2b; --bg-gradient: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%); }
                body { background: var(--bg-gradient); font-family: 'Noto Sans Georgian', sans-serif; height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; overflow: hidden; }
                body::before { content: ''; position: absolute; width: 600px; height: 600px; background: radial-gradient(circle, rgba(254,80,0,0.05) 0%, rgba(255,255,255,0) 70%); top: -100px; right: -100px; z-index: -1; animation: float 10s infinite ease-in-out; }
                
                .pay-card { background: rgba(255, 255, 255, 0.95); padding: 3rem 2.5rem; border-radius: 24px; box-shadow: 0 20px 60px rgba(0,0,0,0.08), 0 0 0 1px rgba(0,0,0,0.02); width: 100%; max-width: 550px; text-align: center; position: relative; backdrop-filter: blur(10px); transition: transform 0.3s ease; display: flex; flex-direction: column; justify-content: space-between; min-height: 480px; }
                .pay-card:hover { transform: translateY(-3px); }
                
                h4, h5 { font-weight: 700; color: var(--bog-dark); letter-spacing: -0.5px; }
                p.text-muted { font-size: 0.95rem; line-height: 1.6; color: #666 !important; }
                
                .btn-primary { background-color: var(--bog-orange); border-color: var(--bog-orange); font-weight: 600; padding: 12px 30px; border-radius: 12px; transition: all 0.2s; box-shadow: 0 4px 15px rgba(254, 80, 0, 0.2); }
                .btn-primary:hover { background-color: #e64a00; border-color: #e64a00; transform: scale(1.02); box-shadow: 0 6px 20px rgba(254, 80, 0, 0.3); }
                .alert-danger { border-radius: 12px; font-size: 0.9rem; border: none; background-color: #fff5f5; color: #dc3545; }
                
                /* TIMER */
                .timer-box { font-size: 0.95rem; color: #dc3545; font-weight: 600; background: #fff5f5; padding: 10px 20px; border-radius: 30px; display: inline-block; margin-bottom: 25px; border: 1px solid #ffebeb; }
                .timer-icon { margin-right: 6px; }
                
                /* HEADER */
                .brand-header { display: flex; align-items: center; justify-content: center; gap: 20px; margin-bottom: 1.5rem; width: 100%; }
                .site-identity { display: flex; align-items: center; gap: 15px; text-align: left; }
                .site-logo-img { height: 45px; width: auto; object-fit: contain; max-width: 100px; }
                .site-info { display: flex; flex-direction: column; line-height: 1.3; }
                .site-name { font-weight: 800; font-size: 1.1rem; color: var(--bog-dark); text-transform: uppercase; margin: 0; }
                .site-desc { font-size: 0.8rem; color: #777; font-weight: 500; margin: 0; }
                .header-divider { height: 45px; width: 2px; background: #e0e0e0; flex-shrink: 0; border-radius: 2px; }
                .bog-logo-img { height: 40px; width: auto; object-fit: contain; max-width: 150px; }

                /* FOOTER */
                .footer-branding { margin-top: auto; padding-top: 1.5rem; border-top: 1px solid #eee; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; }
                .footer-text { font-size: 0.75rem; color: #999; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
                .footer-logos-row { display: flex; align-items: center; gap: 12px; opacity: 0.8; }
                .footer-logo-ok { height: 18px; width: auto; object-fit: contain; } 
                .footer-logo-bog { height: 18px; width: auto; object-fit: contain; } 
                .footer-divider { width: 1px; height: 14px; background-color: #ccc; }

                @media (max-width: 576px) {
                    .pay-card { padding: 2rem 1.5rem; max-width: 90%; min-height: auto; }
                    .brand-header { flex-direction: column; gap: 15px; }
                    .header-divider { display: none; }
                    .site-identity { flex-direction: column; text-align: center; }
                    .site-name { white-space: normal; }
                    .bog-logo-img { height: 35px; }
                }
                @keyframes popIn { 0% { transform: scale(0); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
                .bi-check-circle-fill { color: #28a745; animation: popIn 0.5s; }
                .bi-x-circle-fill { color: #dc3545; animation: popIn 0.5s; }
            </style>
        </head>
        <body>
            <div class="pay-card">
                <?php echo ok_bog_direct_pay_handler([]); ?>
                <div class="footer-branding">
                    <span class="footer-text">დამზადებულია საქართველოში</span>
                    <div class="footer-logos-row">
                        <img src="images/ok.png" alt="OK" class="footer-logo-ok">
                        <div class="footer-divider"></div>
                        <img src="https://bankofgeorgia.ge/assets/logos/bank_of_georgia_ka.svg" alt="BOG" class="footer-logo-bog">
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

/* =========================================================
   5. API CLASS
   ========================================================= */

class OkBogAPI {
    private $client_id;
    private $secret_key;
    public $callback_url;
    public $success_url;
    public $fail_url;
    public $last_error = '';
    private $access_token = null;

    public function __construct() {
        $this->client_id = get_ok_option('bog_client_id', '');
        $this->secret_key = get_ok_option('bog_secret_key', '');
        $current_base = ok_bog_get_base_url() . strtok($_SERVER["REQUEST_URI"], '?');
        $this->callback_url = $current_base . '?bog_callback=1';
        $this->success_url  = $current_base . '?bog_return=success';
        $this->fail_url     = $current_base . '?bog_return=fail';
    }

    private function request($url, $method = 'GET', $data = [], $headers = []) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($method === 'POST') {
            $post_fields = is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data;
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        }
        if (!empty($headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        ok_bog_write_log("API REQUEST [$method]", ['URL' => $url, 'DATA' => $data]);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        ok_bog_write_log("API RESPONSE [$http_code]", ['BODY' => $response, 'ERROR' => $curl_error]);
        if ($curl_error) return false;
        $json = json_decode($response, true);
        if ($http_code >= 200 && $http_code < 300) return $json;
        $this->last_error = isset($json['message']) ? $json['message'] : "HTTP $http_code";
        return false;
    }

    private function getToken() {
        if ($this->access_token) return $this->access_token;
        if (empty($this->client_id) || empty($this->secret_key)) {
            $this->last_error = "API Credentials Missing";
            return false;
        }
        $auth = base64_encode($this->client_id . ':' . $this->secret_key);
        $headers = ["Authorization: Basic $auth", "Content-Type: application/x-www-form-urlencoded"];
        $res = $this->request(OK_BOG_AUTH_URL, 'POST', 'grant_type=client_credentials', $headers);
        if ($res && isset($res['access_token'])) {
            $this->access_token = $res['access_token'];
            return $res['access_token'];
        }
        return false;
    }

    public function createOrder($amount, $external_id, $timeout_min = 10) {
        $token = $this->getToken();
        if (!$token) return false;
        $expire_time = gmdate("Y-m-d\TH:i:s\Z", time() + ($timeout_min * 60) + 120);
        $payload = [
            'callback_url' => $this->callback_url,
            'external_order_id' => $external_id,
            'expire_date' => $expire_time,
            'purchase_units' => [
                'currency' => 'GEL',
                'total_amount' => (float)$amount,
                'basket' => [ ['quantity' => 1, 'unit_price' => (float)$amount, 'product_id' => 'general_payment'] ]
            ],
            'redirect_urls' => ['fail' => $this->fail_url, 'success' => $this->success_url]
        ];
        $headers = ["Authorization: Bearer $token", "Content-Type: application/json; charset=utf-8", "Accept-Language: ka"];
        return $this->request(OK_BOG_CREATE_URL, 'POST', $payload, $headers);
    }

    public function refundOrder($bog_order_id, $amount = null) {
        $token = $this->getToken();
        if (!$token) return false;
        $url = OK_BOG_REFUND_BASE . $bog_order_id; 
        $payload = [];
        if (!empty($amount) && (float)$amount > 0) {
            $payload['amount'] = number_format((float)$amount, 2, '.', '');
        }
        ok_bog_write_log("INITIATING REFUND", ['Order ID' => $bog_order_id, 'Amount' => $payload]);
        $headers = ["Authorization: Bearer $token", "Content-Type: application/json"];
        return $this->request($url, 'POST', $payload, $headers);
    }

    public function getOrderStatus($bog_order_id) {
        $token = $this->getToken();
        if (!$token) return false;
        if (empty($bog_order_id)) { $this->last_error = "BOG Order ID is empty"; return false; }
        $url = OK_BOG_DETAILS_BASE . trim($bog_order_id);
        $headers = ["Authorization: Bearer $token"];
        return $this->request($url, 'GET', [], $headers);
    }
}

/* =========================================================
   6. HANDLERS
   ========================================================= */

function ok_bog_direct_pay_handler($atts) {
    global $ok_db;
    $out = '';
    
    $timeout_min = (int)get_ok_option('bog_payment_timeout', 10);
    if($timeout_min <= 0) $timeout_min = 10;

    $site_logo = get_ok_option('site_logo', '');
    $bog_logo = 'https://bankofgeorgia.ge/assets/logos/bank_of_georgia_ka.svg'; 
    $site_name = function_exists('get_bloginfo') ? get_bloginfo('name') : 'OK CMS';
    $site_desc = function_exists('get_bloginfo') ? get_bloginfo('description') : '';

    $logos_html = '<div class="brand-header">';
    $logos_html .= '<div class="site-identity">';
    if($site_logo) { $logos_html .= '<img src="'.$site_logo.'" class="site-logo-img" alt="Logo">'; }
    $logos_html .= '<div class="site-info"><div class="site-name">'.htmlspecialchars($site_name).'</div>';
    if($site_desc) { $logos_html .= '<div class="site-desc">'.htmlspecialchars($site_desc).'</div>'; }
    $logos_html .= '</div></div><div class="header-divider"></div>';
    $logos_html .= '<img src="'.$bog_logo.'" class="bog-logo-img" alt="BOG Logo"></div>'; 

    // --- RETURN HANDLER (WITH AUTO-SYNC) ---
    if (isset($_GET['bog_return'])) {
        
        // თუ წარმატებული დაბრუნებაა და გვაქვს order_id, იძულებით შევამოწმოთ API
        if ($_GET['bog_return'] == 'success' && isset($_GET['order_id'])) {
            $check_bog_id = $_GET['order_id'];
            $api = new OkBogAPI();
            $status_data = $api->getOrderStatus($check_bog_id);
            
            // სტატუსის გარკვევა
            $real_status = '';
            if(isset($status_data['order_status']['key'])) $real_status = strtolower($status_data['order_status']['key']);
            elseif(isset($status_data['status'])) $real_status = strtolower($status_data['status']);

            if ($real_status === 'completed' || $real_status === 'success') {
                $ext_id = isset($status_data['external_order_id']) ? $status_data['external_order_id'] : '';
                if ($ext_id) {
                    $ok_db->query("UPDATE " . OK_BOG_TBL . " SET status='success', bog_order_id='$check_bog_id' WHERE external_order_id='$ext_id'");
                    // ჰუკის გაშვება მყისიერად!
                    if (function_exists('do_action')) {
                        do_action('ok_bog_status_update', $ext_id, 'success', $check_bog_id);
                    }
                }
            }
        }

        $status = $_GET['bog_return'];
        $icon = ($status == 'success') ? 'bi-check-circle-fill' : 'bi-x-circle-fill';
        $title = ($status == 'success') ? 'გადახდა წარმატებულია!' : 'გადახდა ვერ მოხერხდა';
        
        $out .= "
        $logos_html
        <div class='mb-2'><i class='bi $icon' style='font-size: 4rem;'></i></div>
        <h4 class='mb-3'>$title</h4>
        <a href='/' class='btn btn-primary mt-3 px-4'>მთავარზე დაბრუნება</a>
        <script>window.history.replaceState(null, null, window.location.pathname);</script>";
        return $out;
    }

    if (isset($_GET['cost']) && is_numeric($_GET['cost'])) {
        $amount = floatval($_GET['cost']);
        if ($amount > 0) {
            $desc_encrypted = isset($_GET['desc']) ? $_GET['desc'] : '';
            $api = new OkBogAPI();
            $external_id = 'LNK-' . time() . '-' . rand(1000, 9999);
            $res = $api->createOrder($amount, $external_id, $timeout_min);

            if ($res && isset($res['_links']['redirect']['href'])) {
                $redirect_url = $res['_links']['redirect']['href'];
                $bog_id = isset($res['id']) ? $res['id'] : '';
                $db_desc = ok_ap_db_esc($desc_encrypted);
                $ok_db->query("INSERT INTO " . OK_BOG_TBL . " 
                    (external_order_id, bog_order_id, amount, description, status) 
                    VALUES ('$external_id', '$bog_id', '$amount', '$db_desc', 'pending')");
                
                $safe_redirect = urlencode($redirect_url);
                echo "<script>window.location.href = '?pay_id=$external_id&u=$safe_redirect&amt=$amount&d=$desc_encrypted';</script>";
                exit;
            }
        }
    }

    if (isset($_GET['pay_id'])) {
        $oid = preg_replace('/[^a-zA-Z0-9-]/', '', $_GET['pay_id']);
        $row = $ok_db->get_row("SELECT * FROM " . OK_BOG_TBL . " WHERE external_order_id = '$oid'");
        
        $is_expired = false;
        $remaining_seconds = 0;
        
        if ($row) {
            $created = strtotime($row->created_at);
            $now = time();
            $diff = $now - $created;
            $limit = $timeout_min * 60;
            
            if ($diff > $limit || $row->status != 'pending') {
                if ($row->status == 'pending') {
                    $ok_db->query("DELETE FROM " . OK_BOG_TBL . " WHERE id = " . $row->id);
                    $is_expired = true;
                } elseif ($row->status == 'success') {
                    $out .= "<div class='alert alert-success'>გადახდილია!</div><a href='/' class='btn btn-primary'>მთავარი</a>";
                    return $out;
                }
            } else {
                $remaining_seconds = $limit - $diff;
            }
        } else {
            $is_expired = true;
        }

        if ($is_expired) {
            $out .= "
            $logos_html
            <div id='payment-expired-content'>
                <div class='mb-3'><i class='bi bi-hourglass-bottom text-danger' style='font-size: 4rem;'></i></div>
                <h4 class='mb-3'>ლინკს ვადა გაუვიდა!</h4>
                <p class='text-muted'>სამწუხაროდ, გადახდისთვის გამოყოფილი დრო გავიდა და შეკვეთა გაუქმდა.<br>გთხოვთ სცადოთ თავიდან.</p>
                <a href='/' class='btn btn-light mt-3'>მთავარზე დაბრუნება</a>
            </div>";
        } else {
            $redirect_url = isset($_GET['u']) ? urldecode($_GET['u']) : '#';
            $amount = $row->amount;
            $desc_display = 'Payment';
            if($row->description) {
                $dec = ok_bog_decrypt($row->description);
                if($dec) $desc_display = $dec;
            }

            $out .= "
            <div id='payment-active-content'>
                $logos_html
                
                <div class='timer-box'>
                    <i class='bi bi-stopwatch timer-icon'></i> გადახდისთვის გაქვთ <span id='countdown'>--:--</span>
                </div>

                <div class='mb-4 text-center'>
                    <div class='mb-3'>
                        <span class='badge bg-light text-dark border px-3 py-2 rounded-pill text-uppercase small fw-bold'>
                            შეკვეთა #{$oid}
                        </span>
                    </div>
                    <h1 class='display-4 fw-bold' style='color: var(--bog-orange);'>{$amount} ₾</h1>
                    <p class='text-muted lead'>" . htmlspecialchars($desc_display) . "</p>
                </div>

                <div class='d-grid gap-2'>
                    <a href='$redirect_url' class='btn btn-primary btn-lg shadow-sm'>
                        <i class='bi bi-credit-card-2-front me-2'></i> გადახდა
                    </a>
                    <a href='/' class='btn btn-light btn-sm text-muted mt-2'>გაუქმება</a>
                </div>
            </div>
            
            <div id='payment-expired-content' style='display:none;'>
                $logos_html
                <div class='mb-3'><i class='bi bi-hourglass-bottom text-danger' style='font-size: 4rem;'></i></div>
                <h4 class='mb-3'>დრო ამოიწურა!</h4>
                <p class='text-muted'>ლინკს ვადა გაუვიდა.</p>
                <a href='/' class='btn btn-light mt-3'>მთავარზე დაბრუნება</a>
            </div>

            <script>
            (function() {
                var timeLeft = $remaining_seconds;
                var elem = document.getElementById('countdown');
                var activeContent = document.getElementById('payment-active-content');
                var expiredContent = document.getElementById('payment-expired-content');
                var orderId = '$oid';
                
                var timerId = setInterval(function() {
                    if (timeLeft <= 0) {
                        clearInterval(timerId);
                        if(elem) elem.innerHTML = '00:00';
                        
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', window.location.href, true);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        xhr.send('ok_expire_order_id=' + orderId);

                        if(activeContent) activeContent.style.display = 'none';
                        if(expiredContent) expiredContent.style.display = 'block';
                    } else {
                        var minutes = Math.floor(timeLeft / 60);
                        var seconds = timeLeft % 60;
                        if(minutes < 10) minutes = '0' + minutes;
                        if(seconds < 10) seconds = '0' + seconds;
                        if(elem) elem.innerHTML = minutes + ':' + seconds;
                        timeLeft--;
                    }
                }, 1000);
            })();
            </script>
            ";
        }
        return $out;
    }

    $out .= "<h5>გადახდის სისტემა</h5><p class='text-muted'>გთხოვთ გამოიყენოთ სწორი ბმული.</p>";
    return $out;
}

function ok_bog_render_button($atts) {
    global $ok_db;
    $err_html = '';
    $timeout_min = (int)get_ok_option('bog_payment_timeout', 10);
    if($timeout_min <= 0) $timeout_min = 10;

    if (isset($_POST['ok_bog_pay'])) {
        $amount = floatval($_POST['bog_amount']);
        if ($amount > 0) {
            $external_id = 'ORD-' . time() . '-' . rand(1000, 9999);
            $api = new OkBogAPI();
            $res = $api->createOrder($amount, $external_id, $timeout_min);
            if ($res && isset($res['_links']['redirect']['href'])) {
                $redirect_url = $res['_links']['redirect']['href'];
                $bog_id = isset($res['id']) ? $res['id'] : '';
                $desc_enc = ok_bog_encrypt('ღილაკით გადახდა');
                $ok_db->query("INSERT INTO " . OK_BOG_TBL . " (external_order_id, bog_order_id, amount, description, status) VALUES ('$external_id', '$bog_id', '$amount', '$desc_enc', 'pending')");
                
                $safe_redirect = urlencode($redirect_url);
                echo "<script>window.location.href = '?pay_id=$external_id&u=$safe_redirect&amt=$amount';</script>";
                return;
            } else {
                $err_html = "<div class='alert alert-danger mt-2 small'><b>შეცდომა:</b> " . $api->last_error . "</div>";
            }
        }
    }
    ob_start();
    ?>
    <div class="card p-3 border rounded shadow-sm bg-white" style="max-width: 320px;">
        <h6 class="text-center mb-3">გადახდა (BOG)</h6>
        <form method="post">
            <div class="input-group">
                <span class="input-group-text">₾</span>
                <input type="number" step="0.01" name="bog_amount" class="form-control" placeholder="0.00" required>
                <button type="submit" name="ok_bog_pay" class="btn btn-primary fw-bold">გადახდა</button>
            </div>
        </form>
        <?php echo $err_html; ?>
    </div>
    <?php return ob_get_clean();
}

function ok_bog_handle_callback() {
    global $ok_db;
    $json = file_get_contents('php://input');
    ok_bog_write_log("WEBHOOK RECEIVED", $json);
    $data = json_decode($json, true);
    
    $oid = ''; $status = ''; $bog_id = '';
    if (isset($data['body'])) {
        $oid = $data['body']['external_order_id'] ?? '';
        $bog_id = $data['body']['order_id'] ?? '';
        $raw_status = $data['body']['order_status']['key'] ?? '';
        $status = ($raw_status === 'completed') ? 'success' : strtolower($raw_status);
    } else {
        $oid = $data['external_order_id'] ?? '';
        $status = strtolower($data['status'] ?? '');
    }

    if ($oid && $status) {
        $oid = preg_replace('/[^a-zA-Z0-9-]/', '', $oid);
        $status = preg_replace('/[^a-zA-Z0-9_]/', '', $status);
        $bog_id = preg_replace('/[^a-zA-Z0-9-]/', '', $bog_id);

        $sql = "UPDATE " . OK_BOG_TBL . " SET status='$status'";
        if(!empty($bog_id)) { $sql .= ", bog_order_id='$bog_id'"; }
        $sql .= " WHERE external_order_id='$oid'";
        $ok_db->query($sql);

        // Hook trigger
        if (function_exists('do_action')) {
            do_action('ok_bog_status_update', $oid, $status, $bog_id);
        }

        http_response_code(200);
    } else { http_response_code(400); }
    exit;
}

/* =========================================================
   7. ADMIN UI
   ========================================================= */

function ok_bog_recursive_table($data) {
    $dictionary = [
        'order_id' => 'შეკვეთის ID', 'external_order_id' => 'გარე შეკვეთის ID', 'client_id' => 'კლიენტის ID',
        'brand_ka' => 'ბრენდი (KA)', 'brand_en' => 'ბრენდი (EN)', 'url' => 'ვებ-გვერდი', 'create_date' => 'შექმნის თარიღი',
        'expire_date' => 'ვადა', 'status' => 'სტატუსი', 'buyer' => 'მყიდველი', 'redirect_links' => 'ბმულები',
        'success' => 'წარმატებული', 'fail' => 'ჩაიშალა', 'payment_detail' => 'გადახდის დეტალები', 'transfer_method' => 'გადარიცხვის მეთოდი',
        'google_pay' => 'Google Pay', 'transaction_id' => 'ტრანზაქციის ID', 'payer_identifier' => 'გადამხდელის ID',
        'payment_option' => 'გადახდის მეთოდი', 'card_expiry_date' => 'ბარათის ვადა', 'actions' => 'მოქმედებები',
        'refund' => 'თანხის დაბრუნება', 'rejected' => 'უარყოფილია', 'completed' => 'დასრულებულია', 'amount' => 'თანხა',
        'currency_code' => 'ვალუტა', 'items' => 'პროდუქტები', 'description' => 'აღწერა', 'reject_reason' => 'უარყოფის მიზეზი', 'message' => 'შეტყობინება'
    ];
    $html = '<table class="table table-bordered table-sm mb-0">';
    foreach ($data as $key => $value) {
        $clean_key = strtolower(trim($key));
        $display_key = isset($dictionary[$clean_key]) ? $dictionary[$clean_key] : ucwords(str_replace(['_', 'id'], [' ', 'ID'], $key));
        $html .= '<tr><th class="bg-light align-middle text-nowrap" style="width: 30%; font-size: 0.85rem;">' . htmlspecialchars($display_key) . '</th><td class="align-middle" style="font-size: 0.85rem;">';
        if (is_array($value)) { $html .= ok_bog_recursive_table($value); } else { $html .= htmlspecialchars((string)$value); }
        $html .= '</td></tr>';
    }
    $html .= '</table>';
    return $html;
}

function ok_bog_admin_menu() {
    add_menu_page('BOG Payments', 'BOG Payments', 'manage_options', 'ok-bog-settings', 'ok_bog_render_admin', 'bi bi-bank');
}

function ok_bog_render_admin() {
    global $ok_db;
    $msg = '';
    $current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'transactions';
    $api = new OkBogAPI();
    $logs_enabled = (int)get_ok_option('bog_enable_logs', 0);

    if (isset($_POST['save_settings'])) {
        update_ok_option('bog_client_id', trim($_POST['bog_client_id']));
        update_ok_option('bog_secret_key', trim($_POST['bog_secret_key']));
        update_ok_option('bog_refund_percent', floatval($_POST['bog_refund_percent']));
        update_ok_option('bog_payment_timeout', intval($_POST['bog_payment_timeout']));
        update_ok_option('bog_enable_logs', isset($_POST['bog_enable_logs']) ? 1 : 0);
        $msg = "<script>Swal.fire('შენახულია', 'პარამეტრები განახლდა', 'success').then(()=>{window.location.href='?page=ok-bog-settings&tab=settings'});</script>";
    }

    if (isset($_POST['clear_logs'])) {
        @file_put_contents(OK_BOG_LOG_FILE, '');
        $msg = "<script>Swal.fire('გასუფთავდა', 'ლოგები წაიშალა', 'success');</script>";
    }
    
    $gen_link_result = '';
    if (isset($_POST['generate_link_secure'])) {
        $g_amt = floatval($_POST['gen_price']);
        $g_desc = trim($_POST['gen_desc']);
        if($g_amt > 0) {
            $gen_link_result = ok_bog_get_link($g_amt, $g_desc);
        }
    }

    if (isset($_POST['do_refund'])) {
        $bog_id = $_POST['refund_bog_id']; 
        $ext_id = $_POST['refund_ext_id']; 
        $original_amt = (float)$_POST['refund_amt'];
        
        $deduction_percent = (float)get_ok_option('bog_refund_percent', 0);
        $final_refund_amount = $original_amt;
        if ($deduction_percent > 0) {
            $deduction = ($original_amt * $deduction_percent) / 100;
            $final_refund_amount = $original_amt - $deduction;
        }

        $res = $api->refundOrder($bog_id, $final_refund_amount);
        
        if ($res && (isset($res['key']) && $res['key'] == 'request_received')) {
            $ok_db->query("UPDATE " . OK_BOG_TBL . " SET status='refund_processing' WHERE external_order_id='$ext_id'");
            $msg = "<script>Swal.fire('მიღებულია', 'მოთხოვნა გაიგზავნა. დასაბრუნებელი თანხა: {$final_refund_amount} GEL', 'info');</script>";
        } elseif ($res && isset($res['status']) && strtolower($res['status']) == 'refunded') {
            $ok_db->query("UPDATE " . OK_BOG_TBL . " SET status='refunded' WHERE external_order_id='$ext_id'");
            // NEW: Refund Hook
            if (function_exists('do_action')) {
                do_action('ok_bog_payment_refunded', $ext_id, $bog_id);
            }
            $msg = "<script>Swal.fire('წარმატება', 'თანხა დაბრუნდა!', 'success');</script>";
        } else {
            $err = addslashes($api->last_error ?: 'უცნობი შეცდომა');
            $msg = "<script>Swal.fire({ title: 'შეცდომა', text: '$err', icon: 'error' });</script>";
        }
    }

    if (isset($_POST['check_statuses'])) {
        $processing_orders = $ok_db->get_results("SELECT * FROM " . OK_BOG_TBL . " WHERE status = 'refund_processing'");
        $count = 0;
        if ($processing_orders) {
            foreach ($processing_orders as $p_ord) {
                $details = $api->getOrderStatus($p_ord->bog_order_id);
                $new_st = '';
                if(isset($details['order_status']['key'])) $new_st = strtolower($details['order_status']['key']);
                elseif(isset($details['status'])) $new_st = strtolower($details['status']);

                if ($new_st) {
                    if ($new_st == 'refunded' || $new_st == 'returned') {
                        $ok_db->query("UPDATE " . OK_BOG_TBL . " SET status='refunded' WHERE id=" . $p_ord->id);
                        $count++;
                    } elseif ($new_st == 'rejected' || $new_st == 'error') {
                        $ok_db->query("UPDATE " . OK_BOG_TBL . " SET status='refund_failed' WHERE id=" . $p_ord->id);
                    }
                }
            }
            $msg = ($count > 0) ? "<script>Swal.fire('განახლდა', '$count ტრანზაქცია', 'success');</script>" : "<script>Swal.fire('ინფო', 'სტატუსები უცვლელია.', 'info');</script>";
        } else { $msg = "<script>Swal.fire('ინფო', 'გადასახედი ტრანზაქციები არ არის.', 'info');</script>"; }
    }

    if (isset($_POST['view_details'])) {
        $bog_id = trim($_POST['details_bog_id']);
        if (empty($bog_id)) { $msg = "<script>Swal.fire('შეცდომა', 'ტრანზაქციას არ აქვს ID', 'warning');</script>"; }
        else {
            $details = $api->getOrderStatus($bog_id);
            if($details && !isset($details['status_code'])) { 
                $formatted_table = ok_bog_recursive_table($details);
                $html = "<div class='text-start' style='max-height:600px; overflow-y:auto;'>" . $formatted_table . "</div>";
                $safe_html = json_encode($html);
                $msg = "<script>Swal.fire({ title: 'ქვითარი: $bog_id', html: $safe_html, width: '900px', showCloseButton: true });</script>";
            } else {
                $err_txt = addslashes($api->last_error ?: "უცნობი შეცდომა");
                $msg = "<script>Swal.fire({ title: 'შეცდომა', text: '$err_txt', icon: 'error' });</script>";
            }
        }
    }

    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
    echo $msg;
    ?>
    <div class="wrap container-fluid p-4">
        <h3 class="mb-4">Bank of Georgia Admin <span class="badge bg-secondary">v47.0</span></h3>

        <ul class="nav nav-tabs mb-4">
            <li class="nav-item"><a class="nav-link <?php echo ($current_tab == 'transactions') ? 'active fw-bold' : ''; ?>" href="?page=ok-bog-settings&tab=transactions"><i class="bi bi-list-ul"></i> ტრანზაქციები</a></li>
            <li class="nav-item"><a class="nav-link <?php echo ($current_tab == 'tools') ? 'active fw-bold' : ''; ?>" href="?page=ok-bog-settings&tab=tools"><i class="bi bi-tools"></i> ინსტრუმენტები</a></li>
            <li class="nav-item"><a class="nav-link <?php echo ($current_tab == 'settings') ? 'active fw-bold' : ''; ?>" href="?page=ok-bog-settings&tab=settings"><i class="bi bi-gear"></i> პარამეტრები</a></li>
            <li class="nav-item"><a class="nav-link <?php echo ($current_tab == 'docs') ? 'active fw-bold' : ''; ?>" href="?page=ok-bog-settings&tab=docs"><i class="bi bi-book"></i> დოკუმენტაცია</a></li>
            <?php if($logs_enabled): ?>
            <li class="nav-item"><a class="nav-link <?php echo ($current_tab == 'logs') ? 'active fw-bold' : ''; ?>" href="?page=ok-bog-settings&tab=logs"><i class="bi bi-terminal"></i> სისტემური ლოგები</a></li>
            <?php endif; ?>
        </ul>

        <?php if ($current_tab == 'settings'): ?>
            <form method="post">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card p-4 shadow-sm h-100">
                            <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-key"></i> API კონფიგურაცია</h6>
                            <div class="mb-3"><label class="form-label fw-bold">Client ID</label><input type="text" name="bog_client_id" class="form-control" value="<?php echo get_ok_option('bog_client_id', ''); ?>"></div>
                            <div class="mb-3"><label class="form-label fw-bold">Secret Key</label><input type="password" name="bog_secret_key" class="form-control" value="<?php echo get_ok_option('bog_secret_key', ''); ?>"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card p-4 shadow-sm h-100">
                            <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-arrow-counterclockwise"></i> დაბრუნების პოლიტიკა</h6>
                            <div class="mb-3">
                                <label class="form-label fw-bold">დაბრუნების საკომისიო (%)</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" name="bog_refund_percent" class="form-control" value="<?php echo get_ok_option('bog_refund_percent', 0); ?>">
                                    <span class="input-group-text">%</span>
                                </div>
                                <div class="form-text small">თანხის დაბრუნებისას ავტომატურად დაკავდება ეს პროცენტი.</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card p-4 shadow-sm h-100">
                            <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-clock-history"></i> ტაიმერი</h6>
                            <div class="mb-3">
                                <label class="form-label fw-bold">გადახდის დრო (წუთი)</label>
                                <div class="input-group">
                                    <input type="number" name="bog_payment_timeout" class="form-control" value="<?php echo get_ok_option('bog_payment_timeout', 10); ?>">
                                    <span class="input-group-text"><i class="bi bi-stopwatch"></i></span>
                                </div>
                                <div class="form-text small">რამდენი დრო აქვს მომხმარებელს გადახდისთვის.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="card p-4 shadow-sm border-warning">
                            <h6 class="fw-bold mb-3 text-warning text-dark"><i class="bi bi-bug"></i> დიაგნოსტიკა</h6>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="bog_enable_logs" id="logsSwitch" <?php echo $logs_enabled ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-bold" for="logsSwitch">ლოგების ჩაწერა (Debug Mode)</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-4 text-end"><button type="submit" name="save_settings" class="btn btn-lg btn-success px-5"><i class="bi bi-save"></i> შენახვა</button></div>
            </form>

        <?php elseif ($current_tab == 'logs'): ?>
            <div class="card p-3 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="m-0">სისტემური ლოგები</h5>
                    <form method="post"><button type="submit" name="clear_logs" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i> გასუფთავება</button></form>
                </div>
                <div class="bg-dark text-white p-3 rounded" style="height: 500px; overflow-y: scroll; font-family: monospace; font-size: 0.85rem;">
                    <?php echo file_exists(OK_BOG_LOG_FILE) ? nl2br(htmlspecialchars(file_get_contents(OK_BOG_LOG_FILE))) : "ლოგების ფაილი ცარიელია."; ?>
                </div>
            </div>

        <?php elseif ($current_tab == 'tools'): ?>
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card p-4 shadow-sm h-100 border-primary">
                        <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-link-45deg"></i> დაცული ლინკის გენერატორი</h5>
                        <p class="small text-muted">აღწერა ავტომატურად დაიშიფრება (AES-256).</p>
                        <form method="post">
                            <div class="row mb-3">
                                <div class="col-6"><label class="form-label small">თანხა (GEL)</label><input type="number" name="gen_price" class="form-control form-control-sm" placeholder="50" required></div>
                                <div class="col-6"><label class="form-label small">აღწერა</label><input type="text" name="gen_desc" class="form-control form-control-sm" placeholder="მომსახურება" required></div>
                            </div>
                            <button type="submit" name="generate_link_secure" class="btn btn-primary btn-sm w-100">გენერაცია</button>
                        </form>
                        <?php if($gen_link_result): ?>
                        <div class="mt-3 p-2 bg-light border rounded">
                            <label class="small fw-bold">თქვენი ლინკი:</label>
                            <div class="input-group">
                                <input type="text" id="gen_result_link" class="form-control form-control-sm" value="<?php echo $gen_link_result; ?>" readonly>
                                <button class="btn btn-sm btn-outline-secondary" onclick="copyToClip('gen_result_link')">Copy</button>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card p-4 shadow-sm h-100 border-success">
                        <h5 class="fw-bold mb-3 text-success"><i class="bi bi-code-square"></i> შორთკოდები</h5>
                        <div class="mb-3">
                            <label class="form-label small">1. ფიქსირებული თანხის ღილაკი</label>
                            <div class="input-group mb-2"><input type="text" class="form-control form-control-sm" value='[bog_button amount="50" desc="აღწერა"]' readonly id="sc_btn_fixed"><button class="btn btn-sm btn-outline-secondary" onclick="copyToClip('sc_btn_fixed')">Copy</button></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">2. თავისუფალი თანხის ღილაკი</label>
                            <div class="input-group"><input type="text" class="form-control form-control-sm" value="[bog_pay_button]" readonly id="sc_btn_free"><button class="btn btn-sm btn-outline-secondary" onclick="copyToClip('sc_btn_free')">Copy</button></div>
                        </div>
                    </div>
                </div>
            </div>
            <script>
            function copyToClip(id) {
                let copyText = document.getElementById(id);
                copyText.select();
                document.execCommand("copy");
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'კოპირებულია', showConfirmButton: false, timer: 1500 });
            }
            </script>

        <?php elseif ($current_tab == 'docs'): ?>
            <div class="container bg-white p-5 shadow-sm rounded border">
                <h4 class="mb-4 border-bottom pb-2">დოკუმენტაცია</h4>
                <div class="alert alert-success"><i class="bi bi-shield-lock"></i> <b>უსაფრთხოება:</b> ყველა აღწერა ავტომატურად იშიფრება AES-256 სტანდარტით. URL-ში და ბაზაში ინახება დაშიფრული სახით. ადმინ პანელში ხდება დეშიფრაცია.</div>
                <h5 class="mt-4">Hooks (სხვა პლაგინებისთვის)</h5>
                <pre class="bg-dark text-white p-3 rounded">
add_action('ok_bog_status_update', function($external_id, $status, $bog_id) {
    if ($status == 'success') { ... }
}, 10, 3);
                </pre>
            </div>

        <?php else: ?>
            <?php 
            $saved_pct = (float)get_ok_option('bog_refund_percent', 0);
            echo "<script>const BOG_REFUND_PCT = $saved_pct;</script>";
            ?>
            <div class="card p-3 mb-4 bg-light border">
                <form method="get" class="row g-3 align-items-end">
                    <input type="hidden" name="page" value="ok-bog-settings">
                    <div class="col-md-3"><label class="form-label small fw-bold">თარიღი (დან)</label><input type="date" name="f_date_start" class="form-control form-control-sm" value="<?php echo $_GET['f_date_start'] ?? ''; ?>"></div>
                    <div class="col-md-3"><label class="form-label small fw-bold">თარიღი (მდე)</label><input type="date" name="f_date_end" class="form-control form-control-sm" value="<?php echo $_GET['f_date_end'] ?? ''; ?>"></div>
                    <div class="col-md-3"><label class="form-label small fw-bold">სტატუსი</label><select name="f_status" class="form-select form-select-sm"><option value="">ყველა</option><option value="success">გადახდილია</option><option value="refunded">დაბრუნებულია</option><option value="rejected">უარყოფილია</option><option value="pending">მოლოდინში</option></select></div>
                    <div class="col-md-3"><button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-filter"></i> გაფილტვრა</button></div>
                </form>
            </div>
            <div class="card p-3 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold m-0">შეკვეთები</h6>
                    <form method="post"><button type="submit" name="check_statuses" class="btn btn-sm btn-warning text-dark"><i class="bi bi-arrow-repeat"></i> განახლება</button></form>
                </div>
                <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light"><tr><th>თარიღი</th><th>შეკვეთის ID</th><th>აღწერა (Decrypted)</th><th>თანხა</th><th>სტატუსი</th><th class="text-end">მოქმედება</th></tr></thead>
                    <tbody>
                        <?php 
                        $sql = "SELECT * FROM " . OK_BOG_TBL . " WHERE 1=1";
                        if (!empty($_GET['f_status'])) { $st = esc_sql($_GET['f_status']); $sql .= " AND status = '$st'"; }
                        if (!empty($_GET['f_date_start'])) { $ds = esc_sql($_GET['f_date_start']); $sql .= " AND DATE(created_at) >= '$ds'"; }
                        if (!empty($_GET['f_date_end'])) { $de = esc_sql($_GET['f_date_end']); $sql .= " AND DATE(created_at) <= '$de'"; }
                        $sql .= " ORDER BY created_at DESC LIMIT 50";
                        $logs = $ok_db->get_results($sql);

                        if($logs): foreach($logs as $index => $l): 
                            $st = strtolower($l->status);
                            $bg_class = 'secondary'; $label = $st;
                            switch ($st) {
                                case 'success': case 'completed': $bg_class = 'success'; $label = 'გადახდილია'; break;
                                case 'pending': $bg_class = 'secondary'; $label = 'მოლოდინში'; break;
                                case 'rejected': $bg_class = 'danger'; $label = 'უარყოფილია'; break;
                                case 'refund_processing': $bg_class = 'warning text-dark'; $label = 'ბრუნდება...'; break;
                                case 'refunded': case 'returned': $bg_class = 'primary'; $label = 'დაბრუნებულია'; break;
                                case 'refund_failed': $bg_class = 'dark'; $label = 'ვერ დაბრუნდა'; break;
                            }
                            $decrypted_desc = ok_bog_decrypt($l->description);
                        ?>
                        <tr>
                            <td class="small text-muted"><?php echo $l->created_at; ?></td>
                            <td><div class="fw-bold text-dark" style="font-size: 0.9em;"><?php echo $l->external_order_id; ?></div><div class="text-muted small" style="font-size: 0.75em;"><?php echo $l->bog_order_id ? $l->bog_order_id : '-'; ?></div></td>
                            <td class="text-muted small"><?php echo htmlspecialchars($decrypted_desc); ?></td>
                            <td><?php echo $l->amount; ?> ₾</td>
                            <td><span class="badge bg-<?php echo $bg_class; ?>"><?php echo $label; ?><?php if($st == 'refund_processing'): ?><span class="spinner-border spinner-border-sm ms-1" style="width:10px;height:10px;"></span><?php endif; ?></span></td>
                            <td class="text-end">
                                <div class="btn-group" role="group">
                                    <form method="post" style="display:inline;"><input type="hidden" name="details_bog_id" value="<?php echo $l->bog_order_id; ?>"><button type="submit" name="view_details" class="btn btn-sm btn-outline-info" title="დეტალები"><i class="bi bi-eye"></i></button></form>
                                    <?php if($st == 'success' || $st == 'completed'): ?>
                                    <form method="post" id="refund_form_<?php echo $index; ?>" style="display:inline;"><input type="hidden" name="refund_bog_id" value="<?php echo $l->bog_order_id; ?>"><input type="hidden" name="refund_ext_id" value="<?php echo $l->external_order_id; ?>"><input type="hidden" name="refund_amt" value="<?php echo $l->amount; ?>"><input type="hidden" name="do_refund" value="1"><button type="button" onclick="confirmRefund('refund_form_<?php echo $index; ?>', '<?php echo $l->amount; ?>')" class="btn btn-sm btn-outline-danger ms-1" title="თანხის დაბრუნება"><i class="bi bi-arrow-return-left"></i></button></form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; else: ?><tr><td colspan="6" class="text-center p-3">მონაცემები ვერ მოიძებნა</td></tr><?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <script>
    function confirmRefund(formId, amount) {
        let msg = 'ნამდვილად გსურს ' + amount + ' ლარის დაბრუნება?';
        let subMsg = 'თანხა სრულად დაბრუნდება.';
        if (typeof BOG_REFUND_PCT !== 'undefined' && BOG_REFUND_PCT > 0) {
            let deduction = (amount * BOG_REFUND_PCT) / 100;
            let finalAmt = (amount - deduction).toFixed(2);
            subMsg = 'დაკავდება: ' + BOG_REFUND_PCT + '% (' + deduction.toFixed(2) + ' GEL).<br><b>კლიენტს დაუბრუნდება: ' + finalAmt + ' GEL</b>';
        }
        Swal.fire({
            title: 'თანხის დაბრუნება',
            html: msg + '<br><br>' + subMsg,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'კი, დააბრუნე',
            cancelButtonText: 'გაუქმება'
        }).then((result) => { if (result.isConfirmed) document.getElementById(formId).submit(); })
    }
    </script>
    <?php
}