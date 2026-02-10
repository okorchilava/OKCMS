<?php
/*
Plugin Name: OK Bank of Georgia Payment (Enterprise v80 - Hooks & UI)
Description: სრული სისტემა: ზუსტი კოდები, თანამედროვე UI და Hook-ები სხვა პლაგინებთან ინტეგრაციისთვის (წარმატება/უარყოფა).
Version: 80.0
Author: OK Engine Team
*/

if (!defined('OK_LOADED')) { die('Access Denied.'); }

// --- 1. კონფიგურაცია ---
define('OK_BOG_TBL', 'ok_bog_transactions');
define('OK_BOG_CONF_TBL', 'ok_bog_config'); 
define('OK_BOG_LOG_FILE', __DIR__ . '/ok_bog_debug.log');

// Callback URL
define('OK_BOG_CALLBACK_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/?ok_action=bog_callback");

/* =========================================================
   2. ინსტალაცია (DB)
   ========================================================= */
function ok_bog_install() {
    global $ok_db;
    
    // ტრანზაქციების ცხრილი (Module Name-ის მხარდაჭერით)
    $sql_trans = "CREATE TABLE IF NOT EXISTS ".OK_BOG_TBL." (
        id INT AUTO_INCREMENT PRIMARY KEY,
        module_name VARCHAR(50) NOT NULL DEFAULT 'custom',      
        module_record_id INT NOT NULL DEFAULT 0,         
        bog_order_id VARCHAR(100),             
        external_order_id VARCHAR(100),        
        amount DECIMAL(10,2),
        status VARCHAR(20) DEFAULT 'pending', 
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        refund_amount DECIMAL(10,2) DEFAULT 0.00,
        refund_date DATETIME NULL,
        full_log TEXT DEFAULT NULL,
        INDEX idx_bog_oid (bog_order_id),
        INDEX idx_ext_oid (external_order_id),
        INDEX idx_module (module_name, module_record_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $ok_db->query($sql_trans);

    // კონფიგურაციის ცხრილი
    $sql_conf = "CREATE TABLE IF NOT EXISTS ".OK_BOG_CONF_TBL." (
        setting_key VARCHAR(50) PRIMARY KEY,
        setting_val TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $ok_db->query($sql_conf);

    // განახლების დროს სვეტების დამატება (თუ არ არსებობს)
    try {
        $cols = $ok_db->get_results("SHOW COLUMNS FROM ".OK_BOG_TBL);
        $col_names = [];
        foreach ($cols as $c) $col_names[] = $c->Field;

        if (!in_array('full_log', $col_names)) $ok_db->query("ALTER TABLE ".OK_BOG_TBL." ADD COLUMN full_log TEXT DEFAULT NULL");
        if (!in_array('module_name', $col_names)) $ok_db->query("ALTER TABLE ".OK_BOG_TBL." ADD COLUMN module_name VARCHAR(50) NOT NULL DEFAULT 'custom'");
        if (!in_array('module_record_id', $col_names)) $ok_db->query("ALTER TABLE ".OK_BOG_TBL." ADD COLUMN module_record_id INT NOT NULL DEFAULT 0");
        if (!in_array('refund_amount', $col_names)) $ok_db->query("ALTER TABLE ".OK_BOG_TBL." ADD COLUMN refund_amount DECIMAL(10,2) DEFAULT 0.00");
    } catch (Throwable $e) {}
}
ok_bog_install();

// --- დამხმარე ფუნქციები ---
function ok_bog_get_setting($key, $default = '') {
    global $ok_db;
    $val = $ok_db->get_var("SELECT setting_val FROM ".OK_BOG_CONF_TBL." WHERE setting_key = ?", [$key]);
    return ($val !== false && $val !== null) ? $val : $default;
}

function ok_bog_update_setting($key, $val) {
    global $ok_db;
    $exists = $ok_db->get_var("SELECT count(*) FROM ".OK_BOG_CONF_TBL." WHERE setting_key = ?", [$key]);
    if ($exists > 0) {
        $ok_db->query("UPDATE ".OK_BOG_CONF_TBL." SET setting_val = ? WHERE setting_key = ?", [$val, $key]);
    } else {
        $ok_db->query("INSERT INTO ".OK_BOG_CONF_TBL." (setting_key, setting_val) VALUES (?, ?)", [$key, $val]);
    }
}

function ok_bog_write_log($title, $data = null) {
    if (!ok_bog_get_setting('bog_enable_logs', 0)) return;
    $time = date('[Y-m-d H:i:s]');
    $msg = "$time $title";
    if ($data !== null) {
        $msg .= "\n" . (is_array($data) || is_object($data) ? json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : $data);
    }
    $msg .= "\n--------------------------------------------------\n";
    @file_put_contents(OK_BOG_LOG_FILE, $msg, FILE_APPEND);
}

// --- ბანკის კოდების თარგმნა (სურათის მიხედვით) ---
function ok_bog_get_status_text($code) {
    $messages = [
        '100' => 'წარმატებული გადახდა', 
        '101' => 'გადახდა უარყოფილია, რადგან ბარათის გამოყენება შეზღუდულია. დეტალური ინფორმაციისთვის დაუკავშირდით ბარათის მომსახურე ბანკს',
        '102' => 'დამახსოვრებული ბარათი ვერ მოიძებნა',
        '103' => 'გადახდა უარყოფილია, რადგან ბარათი არ არის ვალიდური',
        '104' => 'გადახდა უარყოფილია ტრანზაქციის რაოდენობის ლიმიტის გადაჭარბების გამო',
        '105' => 'გადახდა უარყოფილია, რადგან ბარათი ვადაგასულია',
        '106' => 'გადახდა უარყოფილია თანხის ლიმიტის გადაჭარბების გამო',
        '107' => 'გადახდა უარყოფილია ანგარიშზე არასაკმარისი თანხის გამო',
        '108' => 'გადახდის ავტორიზაციის უარყოფა',
        '109' => 'დაფიქსირდა ტექნიკური ხარვეზი',
        '110' => 'ოპერაციის შესრულების დრო ამოიწურა',
        '111' => 'გადახდის ავტორიზაციის დრო ამოიწურა',
        '112' => 'საერთო შეცდომა',
        '116' => '3D Secure შემოწმება ვერ გაიარა',
        '117' => 'PIN კოდი არასწორია',
        '199' => 'უცნობი პასუხი',
        '200' => 'წარმატებული პრეავტორიზაცია',
        '904' => 'სისტემური შეცდომა'
    ];
    return $messages[$code] ?? "დაფიქსირდა უცნობი პასუხი (კოდი: $code)";
}

/* =========================================================
   3. მენიუ და Hook-ები
   ========================================================= */
if (function_exists('add_ok_action')) {
    add_ok_action('admin_menu', function() {
        add_menu_page('BOG Payments', 'BOG გადახდები', 'manage_options', 'ok-bog-logs', 'ok_bog_render_admin', 'bi bi-credit-card', 50);
    });
    
    add_ok_action('init', 'ok_bog_init_handlers');
    add_ok_action('init', 'ok_bog_check_virtual_page');
}

// გადახდის დაწყების ფუნქცია (სხვა პლაგინებისთვის)
// გამოყენება: ok_bog_start_payment('appointment', 15, 50.00);
function ok_bog_start_payment($module, $record_id, $amount) {
    global $ok_db;
    
    // 1. ვქმნით ჩანაწერს ბაზაში
    $stmt = $ok_db->query("INSERT INTO ".OK_BOG_TBL." (module_name, module_record_id, amount, status, created_at) VALUES (?, ?, ?, 'pending', NOW())", [$module, $record_id, $amount]);
    
    if (!$stmt) return ['success' => false, 'message' => 'DB Insert Failed'];

    $local_id = 0;
    if (method_exists($ok_db, 'last_insert_id')) $local_id = $ok_db->last_insert_id();
    elseif (isset($ok_db->insert_id)) $local_id = $ok_db->insert_id;
    
    if (!$local_id) $local_id = $ok_db->get_var("SELECT MAX(id) FROM ".OK_BOG_TBL);
    
    $external_id = "pay-" . $local_id . "-" . time();

    // 2. ვქმნით შეკვეთას ბანკში
    $api = new OkBogAPI();
    $res = $api->createOrder($amount, $external_id);

    if ($res && isset($res['id'])) {
        $bog_order_id = $res['id'];
        $redirect_url = $res['_links']['redirect']['href'];

        $ok_db->query("UPDATE ".OK_BOG_TBL." SET bog_order_id = ?, external_order_id = ? WHERE id = ?", [$bog_order_id, $external_id, $local_id]);
        
        // ვაბრუნებთ ლინკს
        return ['success' => true, 'redirect_url' => $redirect_url];
    } else {
        $err = $api->last_error ?: 'Unknown Error';
        $ok_db->query("UPDATE ".OK_BOG_TBL." SET status = 'api_error', full_log = ? WHERE id = ?", [json_encode(['error' => $err]), $local_id]);
        return ['success' => false, 'message' => 'Bank Error: ' . $err];
    }
}

function ok_bog_init_handlers() {
    global $ok_db;
    $tbl = OK_BOG_TBL;

    // --- CALLBACK HANDLER (Webhook) ---
    if (isset($_GET['ok_action']) && $_GET['ok_action'] == 'bog_callback') {
        $input = @file_get_contents('php://input');
        $json = @json_decode($input, true);

        $bog_order_id = $_GET['order_id'] ?? $_GET['id'] ?? null;
        $external_id  = $_GET['external_order_id'] ?? null;

        if ($json && isset($json['body'])) {
            if (empty($bog_order_id)) $bog_order_id = $json['body']['order_id'] ?? null;
            if (empty($external_id)) $external_id = $json['body']['external_order_id'] ?? null;
        }

        // თუ ბრაუზერია, გავუშვათ შედეგის გვერდზე
        if (!isset($json) || empty($json)) {  
             $host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST']; 
             header("Location: $host/?ok_action=bog_result&oid=$external_id"); 
             exit;
        }

        if (!$bog_order_id && !$external_id) { http_response_code(200); exit('OK'); }

        $trans = null;
        if ($bog_order_id) {
            $clean_id = preg_replace('/[^a-zA-Z0-9-]/', '', $bog_order_id);
            $trans = $ok_db->get_row("SELECT * FROM $tbl WHERE bog_order_id = ?", [$clean_id]);
        } 
        if (!$trans && $external_id) {
            $clean_ext = preg_replace('/[^a-zA-Z0-9-]/', '', $external_id);
            $trans = $ok_db->get_row("SELECT * FROM $tbl WHERE external_order_id = ?", [$clean_ext]);
        }

        if ($trans) {
            $api = new OkBogAPI();
            $check_id = $trans->bog_order_id ?: $bog_order_id;
            $details = $api->getOrderStatus($check_id);
            
            $code = $details['payment_detail']['code'] ?? '';

            // 100 ან 200 არის წარმატება, ყველა სხვა - შეცდომა
            $status_key = ($code == '100' || $code == '200') ? 'success' : 'fail';
            $log_json = json_encode($details, JSON_UNESCAPED_UNICODE);
            
            // სტატუსის განახლება ბაზაში
            $ok_db->query("UPDATE $tbl SET status = ?, full_log = ? WHERE id = ?", [$status_key, $log_json, $trans->id]);

            // --- HOOK TRIGGER (მთავარი ნაწილი) ---
            // ჰუკი იშვება როგორც წარმატებაზე, ისე მარცხზე
            // შესწორება: მონაცემები იგზავნება მასივად
            if (function_exists('do_ok_action')) {
                do_ok_action('ok_bog_payment_processed', [
                    'module'    => $trans->module_name,
                    'record_id' => $trans->module_record_id,
                    'status'    => $status_key,
                    'data'      => $trans
                ]);
            }
        }
        
        http_response_code(200);
        exit;
    }

    // --- RESULT PAGE (Modern UI) ---
    if (isset($_GET['ok_action']) && $_GET['ok_action'] == 'bog_result') {
        $oid = $_GET['oid'] ?? null; 
        
        $is_success = false;
        $status_text = "ინფორმაციის მიღება ვერ მოხერხდა";
        $amount_display = "";
        $order_number = "";

        if ($oid) {
            $row = $ok_db->get_row("SELECT * FROM $tbl WHERE external_order_id = ?", [$oid]);
            if ($row) {
                // ბოლო შემოწმება API-დან
                if ($row->bog_order_id) {
                    $api = new OkBogAPI();
                    $details = $api->getOrderStatus($row->bog_order_id);

                    $code = $details['payment_detail']['code'] ?? 'unknown';
                    
                    $new_status = ($code == '100' || $code == '200') ? 'success' : 'fail';
                    $log_json = json_encode($details, JSON_UNESCAPED_UNICODE);
                    
                    // განახლება და Hook-ის გაშვება
                    if ($new_status != $row->status) {
                        $ok_db->query("UPDATE $tbl SET status = ?, full_log = ? WHERE id = ?", [$new_status, $log_json, $row->id]);

                        // შესწორება: მონაცემები იგზავნება მასივად
                        if (function_exists('do_ok_action')) {
                            do_ok_action('ok_bog_payment_processed', [
                                'module'    => $row->module_name,
                                'record_id' => $row->module_record_id,
                                'status'    => $new_status,
                                'data'      => $row
                            ]);
                        }
                    }

                    if ($new_status == 'success') $is_success = true;
                    else $is_success = false;

                    $status_text = ok_bog_get_status_text($code);
                    $amount_display = $row->amount . " ₾";
                    $order_number = $row->id;
                }
            } else {
                $status_text = "ტრანზაქცია ვერ მოიძებნა";
            }
        }

        // --- RENDER HTML (Modern UI) ---
        ?>
        <!DOCTYPE html>
        <html lang="ka">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>გადახდის შედეგი</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
            <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;600;700&display=swap" rel="stylesheet">
            <style>
                body { background: #f0f2f5; font-family: 'Noto Sans Georgian', sans-serif; height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
                .result-card { background: #fff; border-radius: 24px; box-shadow: 0 20px 60px rgba(0,0,0,0.08); padding: 60px 40px; width: 100%; max-width: 600px; text-align: center; position: relative; overflow: hidden; }
                
                .icon-box { width: 120px; height: 120px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 35px; font-size: 60px; position: relative; }
                .icon-box::before { content: ''; position: absolute; width: 100%; height: 100%; border-radius: 50%; opacity: 0.15; animation: pulse 2s infinite; }
                @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.1); } 100% { transform: scale(1); } }

                .success-ui .icon-box { background: linear-gradient(135deg, #28a745, #20c997); color: white; box-shadow: 0 10px 30px rgba(40, 167, 69, 0.3); }
                .success-ui .icon-box::before { background: #28a745; }
                .success-ui .title { color: #155724; }

                .fail-ui .icon-box { background: linear-gradient(135deg, #dc3545, #ff6b6b); color: white; box-shadow: 0 10px 30px rgba(220, 53, 69, 0.3); }
                .fail-ui .icon-box::before { background: #dc3545; }
                .fail-ui .title { color: #721c24; }

                .title { font-weight: 700; font-size: 28px; margin-bottom: 15px; }
                .amount-pill { display: inline-block; padding: 10px 30px; background: #f8f9fa; border-radius: 50px; font-weight: 700; font-size: 24px; color: #333; margin-bottom: 30px; border: 1px solid #e9ecef; }
                .message { color: #666; font-size: 1.1rem; line-height: 1.6; margin-bottom: 40px; padding: 0 20px; }
                
                .btn-home { background: #333; color: #fff; padding: 15px 50px; border-radius: 12px; font-weight: 600; text-decoration: none; transition: 0.3s; display: inline-block; border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.1); width: 100%; }
                .btn-home:hover { background: #000; color: #fff; transform: translateY(-3px); }
                
                .footer-meta { margin-top: 30px; font-size: 0.85rem; color: #adb5bd; }
            </style>
        </head>
        <body>
            <div class="result-card <?php echo $is_success ? 'success-ui' : 'fail-ui'; ?>">
                
                <div class="icon-box">
                    <?php echo $is_success ? '<i class="bi bi-check-lg"></i>' : '<i class="bi bi-x-lg"></i>'; ?>
                </div>

                <h1 class="title">
                    <?php echo $is_success ? 'გადახდა წარმატებულია!' : 'გადახდა ვერ შესრულდა'; ?>
                </h1>

                <?php if($amount_display): ?>
                    <div class="amount-pill"><?php echo $amount_display; ?></div>
                <?php endif; ?>

                <div class="message">
                    <?php echo htmlspecialchars($status_text); ?>
                </div>

                <a href="/" class="btn-home">მთავარ გვერდზე</a>
                
                <?php if($order_number): ?>
                    <div class="footer-meta">შეკვეთის N: #<?php echo $order_number; ?></div>
                <?php endif; ?>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

/* =========================================================
   4. VIRTUAL PAGE (/payments) - ტესტირებისთვის
   ========================================================= */
function ok_bog_check_virtual_page() {
    $request_uri = $_SERVER['REQUEST_URI'];
    $path = parse_url($request_uri, PHP_URL_PATH);

    if (trim($path, '/') === 'payments') {
        if (isset($_GET['cost']) && is_numeric($_GET['cost'])) {
            $res = ok_bog_start_payment('test_module', 0, floatval($_GET['cost']));
            if ($res['success']) {
                header("Location: " . $res['redirect_url']);
                exit;
            } else {
                die("შეცდომა: " . $res['message']);
            }
        }
    }
}

/* =========================================================
   5. API CLASS
   ========================================================= */
class OkBogAPI {
    private $client_id;
    private $secret_key;
    public $last_error = '';
    private $access_token = null;
    public $is_sandbox = false;
    
    private $auth_url;
    private $api_base;

    public function __construct() {
        $this->is_sandbox = (bool)ok_bog_get_setting('bog_is_sandbox', 0);
        
        if ($this->is_sandbox) {
            $this->client_id = trim(ok_bog_get_setting('bog_sandbox_client_id'));
            $this->secret_key = trim(ok_bog_get_setting('bog_sandbox_secret_key'));
            $this->auth_url = 'https://oauth2-sandbox.bog.ge/auth/realms/bog/protocol/openid-connect/token';
            $this->api_base = 'https://api-sandbox.bog.ge/payments/v1';
        } else {
            $this->client_id = trim(ok_bog_get_setting('bog_client_id'));
            $this->secret_key = trim(ok_bog_get_setting('bog_secret_key'));
            $this->auth_url = 'https://oauth2.bog.ge/auth/realms/bog/protocol/openid-connect/token';
            $this->api_base = 'https://api.bog.ge/payments/v1';
        }
    }

    private function request($url, $method = 'GET', $data = [], $headers = []) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($method === 'POST') {
            $post_fields = is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data;
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        }
        
        if (!empty($headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        ok_bog_write_log("API REQ [$method]", ['url' => $url, 'payload' => $data]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        ok_bog_write_log("API RES [$http_code]", $response);

        if ($curl_error) { $this->last_error = "cURL: " . $curl_error; return false; }

        $json = json_decode($response, true);
        if ($http_code >= 400) {
            $this->last_error = isset($json['message']) ? $json['message'] : "HTTP $http_code";
            return false;
        }

        return $json;
    }

    private function getToken() {
        if ($this->access_token) return $this->access_token;
        if (empty($this->client_id) || empty($this->secret_key)) { $this->last_error = 'Keys Missing'; return false; }
        
        $auth = base64_encode($this->client_id . ':' . $this->secret_key);
        $headers = ["Authorization: Basic $auth", "Content-Type: application/x-www-form-urlencoded"];
        $res = $this->request($this->auth_url, 'POST', 'grant_type=client_credentials', $headers);
        
        if ($res && isset($res['access_token'])) {
            $this->access_token = $res['access_token'];
            return $res['access_token'];
        }
        return false;
    }

    public function createOrder($amount, $external_id) {
        $token = $this->getToken();
        if (!$token) return false;
        
        $callback_url = OK_BOG_CALLBACK_URL;
        $host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
        
        // პირდაპირ შედეგის გვერდზე გადამისამართება
        $redirect_final = $host . "/?ok_action=bog_result&oid=$external_id";

        $amount_float = (float)$amount; 
        $product_label = $this->is_sandbox ? 'Test Product' : 'Purchase';

        $payload = [
            'callback_url' => $callback_url,
            'external_order_id' => $external_id,
            'capture_method' => 'AUTOMATIC',
            'purchase_units' => [
                'currency' => 'GEL',
                'total_amount' => $amount_float,
                'basket' => [[
                    'quantity' => 1,
                    'unit_price' => $amount_float,
                    'product_id' => $product_label
                ]]
            ],
            'redirect_urls' => [
                'fail' => $redirect_final, 
                'success' => $redirect_final 
            ]
        ];
        
        $headers = ["Authorization: Bearer $token", "Content-Type: application/json; charset=utf-8", "Accept-Language: ka"];
        return $this->request($this->api_base . '/ecommerce/orders', 'POST', $payload, $headers);
    }
    
    public function getOrderStatus($bog_order_id) {
        $token = $this->getToken();
        if (!$token) return false;
        $url = $this->api_base . '/receipt/' . trim($bog_order_id);
        $headers = ["Authorization: Bearer $token"];
        return $this->request($url, 'GET', [], $headers);
    }

    public function refund($bog_order_id, $amount) {
        $token = $this->getToken();
        if (!$token) return false;
        $url = $this->api_base . '/payment/refund/' . trim($bog_order_id);
        $payload = ['amount' => number_format((float)$amount, 2, '.', '')];
        $headers = ["Authorization: Bearer $token", "Content-Type: application/json"];
        return $this->request($url, 'POST', $payload, $headers);
    }
}

/* =========================================================
   6. ADMIN UI
   ========================================================= */
function ok_bog_render_admin() {
    global $ok_db;
    $tbl = OK_BOG_TBL;
    $msg = '';

    if (isset($_POST['ok_bog_save_settings'])) {
        ok_bog_update_setting('bog_client_id', trim($_POST['bog_client_id']));
        ok_bog_update_setting('bog_secret_key', trim($_POST['bog_secret_key']));
        ok_bog_update_setting('bog_sandbox_client_id', trim($_POST['bog_sandbox_client_id']));
        ok_bog_update_setting('bog_sandbox_secret_key', trim($_POST['bog_sandbox_secret_key']));
        
        $is_sandbox = isset($_POST['bog_is_sandbox']) ? 1 : 0;
        ok_bog_update_setting('bog_is_sandbox', $is_sandbox);

        $enable_logs = isset($_POST['bog_enable_logs']) ? 1 : 0;
        ok_bog_update_setting('bog_enable_logs', $enable_logs);
        
        $msg = '<div class="alert alert-success mt-3">პარამეტრები შენახულია!</div>';
    }

    if (isset($_POST['ok_bog_sync_action'])) {
        $t_id = intval($_POST['trans_id']);
        $row = $ok_db->get_row("SELECT * FROM $tbl WHERE id = ?", [$t_id]);
        if ($row && ($row->bog_order_id || $row->external_order_id)) {
            $api = new OkBogAPI();
            $check_id = $row->bog_order_id ?: $row->external_order_id;
            $details = $api->getOrderStatus($check_id);
            
            $code = $details['payment_detail']['code'] ?? '';
            $ns = ($code == '100' || $code == '200') ? 'success' : 'fail';
            $log_json = json_encode($details, JSON_UNESCAPED_UNICODE);

            $ok_db->query("UPDATE $tbl SET status = ?, full_log = ? WHERE id = ?", [$ns, $log_json, $t_id]);
            
            // შესწორება: აქაც გადავცემთ მასივს
            if (function_exists('do_ok_action')) {
                do_ok_action('ok_bog_payment_processed', [
                    'module'    => $row->module_name,
                    'record_id' => $row->module_record_id,
                    'status'    => $ns,
                    'data'      => $row
                ]);
            }
            
            $msg = '<div class="alert alert-info mt-3">განახლდა: '.$ns.' (Code: '.$code.')</div>';
        }
    }

    if (isset($_POST['ok_bog_refund_action'])) {
        $t_id = intval($_POST['trans_id']);
        $amt = floatval($_POST['refund_amount']);
        $row = $ok_db->get_row("SELECT * FROM $tbl WHERE id = ?", [$t_id]);
        if ($row && $row->status == 'success') {
            $api = new OkBogAPI();
            $ref = $api->refund($row->bog_order_id, $amt);
            if ($ref && (isset($ref['status']) || isset($ref['id']))) {
                $ok_db->query("UPDATE $tbl SET status='refunded', refund_amount=?, refund_date=NOW() WHERE id=?", [$amt, $t_id]);
                $msg = '<div class="alert alert-success mt-3">დაბრუნებულია!</div>';
            } else {
                $msg = '<div class="alert alert-danger mt-3">შეცდომა: '.$api->last_error.'</div>';
            }
        }
    }

    $current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'logs';
    $is_sandbox = (bool)ok_bog_get_setting('bog_is_sandbox', 0);
    $check_sand = $is_sandbox ? 'checked' : '';
    $check_logs = ok_bog_get_setting('bog_enable_logs', 0) ? 'checked' : '';
    
    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

    ob_start();
    ?>
    <style>
        .nav-tabs-custom { border-bottom: 2px solid #ddd; display: flex; gap: 20px; margin-bottom: 20px; }
        .nav-tabs-custom a { text-decoration: none; color: #555; padding-bottom: 10px; font-weight: bold; border-bottom: 3px solid transparent; }
        .nav-tabs-custom a.active { color: #0d6efd; border-bottom: 3px solid #0d6efd; }
        .ok-card { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .switch { position: relative; display: inline-block; width: 50px; height: 26px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #f6c23e; } 
        input:checked + .slider:before { transform: translateX(24px); }
    </style>

    <div class="wrap container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>BOG ინტეგრაცია</h3>
            <div class="nav-tabs-custom">
                <a href="?page=ok-bog-logs&tab=logs" class="<?=$current_tab=='logs'?'active':''?>">ტრანზაქციები</a>
                <a href="?page=ok-bog-logs&tab=settings" class="<?=$current_tab=='settings'?'active':''?>">პარამეტრები</a>
            </div>
        </div>
        
        <?=$msg?>

        <?php if ($current_tab == 'settings'): ?>
            <div class="ok-card" style="max-width: 600px;">
                <form method="post">
                    <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-light border rounded">
                        <div><strong>Sandbox (Test Mode)</strong></div>
                        <label class="switch">
                            <input type="checkbox" name="bog_is_sandbox" id="sandboxSwitch" <?=$check_sand?> onclick="toggleSandbox()">
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="mb-4"> 
                        <label><input type="checkbox" name="bog_enable_logs" <?=$check_logs?>> Enable Logs</label>
                    </div>

                    <div id="prodFields">
                        <h5 class="text-primary">Production Keys</h5>
                        <div class="mb-3"><label>Client ID</label><input type="text" name="bog_client_id" class="form-control" value="<?=htmlspecialchars(ok_bog_get_setting('bog_client_id'))?>"></div>
                        <div class="mb-3"><label>Secret Key</label><input type="password" name="bog_secret_key" class="form-control" value="<?=htmlspecialchars(ok_bog_get_setting('bog_secret_key'))?>"></div>
                    </div>

                    <div id="sandFields" style="display:none;">
                        <h5 class="text-warning">Sandbox Keys</h5>
                        <div class="mb-3"><label>Test Client ID</label><input type="text" name="bog_sandbox_client_id" class="form-control" value="<?=htmlspecialchars(ok_bog_get_setting('bog_sandbox_client_id'))?>"></div>
                        <div class="mb-3"><label>Test Secret Key</label><input type="password" name="bog_sandbox_secret_key" class="form-control" value="<?=htmlspecialchars(ok_bog_get_setting('bog_sandbox_secret_key'))?>"></div>
                    </div>

                    <button class="btn btn-primary w-100 mt-3" name="ok_bog_save_settings">შენახვა</button>
                </form>
            </div>
            <script>
            function toggleSandbox() {
                var chk = document.getElementById("sandboxSwitch");
                document.getElementById("prodFields").style.display = chk.checked ? "none" : "block";
                document.getElementById("sandFields").style.display = chk.checked ? "block" : "none";
            }
            toggleSandbox();
            </script>
            
        <?php else: ?>
            <div class="ok-card p-0">
                <table class="table table-striped mb-0">
                    <thead><tr><th>ID</th><th>Order ID</th><th>თანხა</th><th>სტატუსი</th><th>მოდული</th><th>მოქმედება</th></tr></thead>
                    <tbody>
                    <?php 
                    $rows = $ok_db->get_results("SELECT * FROM $tbl ORDER BY id DESC LIMIT 50");
                    if($rows): foreach($rows as $r): ?>
                        <tr>
                            <td><?=$r->id?></td>
                            <td><small><?=$r->bog_order_id ?: $r->external_order_id?></small></td>
                            <td><?=$r->amount?> ₾</td>
                            <td><span class="badge bg-<?=($r->status=='success'?'success':'secondary')?>"><?=$r->status?></span></td>
                            <td><small><?=$r->module_name?> (#<?=$r->module_record_id?>)</small></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info" onclick='showDetails(<?=json_encode(json_decode($r->full_log), JSON_HEX_APOS)?>)'>Log</button>
                                <?php if($r->status=='success'): ?>
                                    <form method="post" class="d-inline" onsubmit="return confirmRefund(this, '<?=$r->amount?>');">
                                        <input type="hidden" name="trans_id" value="<?=$r->id?>">
                                        <input type="hidden" name="refund_amount" value="<?=$r->amount?>">
                                        <input type="hidden" name="ok_bog_refund_action" value="1">
                                        <button class="btn btn-sm btn-danger">Refund</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="trans_id" value="<?=$r->id?>">
                                        <input type="hidden" name="ok_bog_sync_action" value="1">
                                        <button class="btn btn-sm btn-primary">Sync</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; else: echo "<tr><td colspan='6' class='text-center p-3'>ცარიელია</td></tr>"; endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <script>
    function showDetails(json) {
         Swal.fire({
            title: 'დეტალები',
            html: '<pre style="text-align:left; background:#f8f9fa; padding:15px; border-radius:8px; max-height:300px; overflow:auto;">' + JSON.stringify(json, null, 2) + '</pre>',
            width: 600
        });
    }
    function confirmRefund(form, amount) {
        Swal.fire({
            title: 'თანხის დაბრუნება',
            text: `ნამდვილად გსურთ ${amount} ლარის დაბრუნება?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'კი, დაბრუნება!',
            cancelButtonText: 'გაუქმება'
        }).then((result) => { if (result.isConfirmed) form.submit(); });
        return false;
    }
    </script>
    <?php
    echo ob_get_clean();
}