<?php
if (!defined('OK_LOADED')) exit;

// =========================================================
// 1. გადახდის მეთოდების კონფიგურაცია
// =========================================================
function ok_get_active_payment_methods() {
    $bog_active = function_exists('ok_bog_start_payment');
    return [
        'bog' => [
            'title'   => 'საქართველოს ბანკი',
            'logo'    => 'https://bankofgeorgia.ge/assets/logos/bank_of_georgia_ka.svg',
            'enabled' => $bog_active
        ]
    ];
}

/**
 * დამხმარე ფუნქცია ქართული სახელების სწორი ბრუნვისთვის
 */
function ok_format_ka_name($name) {
    if (empty($name)) return '';
    // ჩამოვაჭრათ ბოლოში "ი", თუ ის არსებობს (სახელობითი ბრუნვის ნიშანი)
    return preg_replace('/ი$/u', '', trim((string)$name));
}

// =========================================================
// 2. გადახდის პროცესის ინიციალიზაცია
// =========================================================
function ok_process_payment_request($appointment_id, $method) {
    global $ok_db;
    
    $app = $ok_db->get_row("
        SELECT a.*, s.title as service_title, s.price, s.provider_id,
               u_client.display_name as client_name,
               u_prov.display_name as provider_name
        FROM ok_appointments a 
        JOIN ok_services s ON a.service_id = s.id 
        LEFT JOIN ok_users u_client ON a.customer_id = u_client.id
        LEFT JOIN ok_users u_prov ON s.provider_id = u_prov.id
        WHERE a.id = ?
    ", [$appointment_id]);

    if (!$app) return ['success' => false, 'message' => 'ჯავშანი არ არსებობს'];
    
    // თუ უფასოა
    if ($app->price <= 0) {
        $ok_db->query("UPDATE ok_appointments SET status = 'paid' WHERE id = ?", [$app->id]);

        if (function_exists('ok_add_notification')) {
            $client_label = ok_format_ka_name($app->client_name) ?: 'მომხმარებელ';
            $provider_label = $app->provider_name ?: 'უცნობი პროვაიდერი';
            
            $msg = "{$client_label}-მა წარმატებით დაჯავშნა სერვისი: \"{$app->service_title}\" ({$app->appointment_date} {$app->appointment_time}) პროვაიდერთან: {$provider_label}";
            
            ok_add_notification($msg, 'info', [$app->customer_id, $app->provider_id], 'index.php?page=ok-app', (int)$app->customer_id);
        }

        return ['success' => true, 'redirect_url' => '?ok_page=success'];
    }

    if ($method == 'bog' && function_exists('ok_bog_start_payment')) {
        return ok_bog_start_payment('appointment', $app->id, $app->price);
    }
    
    return ['success' => false, 'message' => 'გადახდის მეთოდი არ არის ხელმისაწვდომი'];
}

// =========================================================
// 3. მთავარი HOOK LISTENER (BOG გადახდის დამუშავება)
// =========================================================
add_ok_action('ok_bog_payment_processed', function($params) {
    
    if (!is_array($params)) return;

    $module    = $params['module'] ?? '';
    $record_id = intval($params['record_id'] ?? 0);
    $status    = $params['status'] ?? '';

    if ($module !== 'appointment' || $record_id <= 0) return;

    global $ok_db;

    $app = $ok_db->get_row("
        SELECT a.*, s.title as service_title, s.provider_id, 
               u_client.display_name as client_name,
               u_prov.display_name as provider_name
        FROM ok_appointments a 
        JOIN ok_services s ON a.service_id = s.id 
        LEFT JOIN ok_users u_client ON a.customer_id = u_client.id
        LEFT JOIN ok_users u_prov ON s.provider_id = u_prov.id
        WHERE a.id = ?
    ", [$record_id]);

    if (!$app) return; 

    $client_base_name = ok_format_ka_name($app->client_name) ?: 'მომხმარებელ';
    $provider_label = $app->provider_name ?: 'უცნობი პროვაიდერი';

    // --- A. წარმატება (SUCCESS) ---
    if ($status === 'success') {
        $ok_db->query("UPDATE ok_appointments SET status = 'paid' WHERE id = ?", [$record_id]);

        $msg = "{$client_base_name}-მ(ა) წარმატებით დაჯავშნა სერვისი: \"{$app->service_title}\" ({$app->appointment_date} {$app->appointment_time}) პროვაიდერთან: {$provider_label}";
        
        if (function_exists('ok_add_notification')) {
            ok_add_notification($msg, 'success', [$app->customer_id, $app->provider_id], 'index.php?page=ok-app', (int)$app->customer_id);
        }
    } 
    
    // --- B. მარცხი (FAIL) ---
    elseif ($status === 'fail') {
        // აქ ვიყენებთ "ს"-ს (მიცემითი ბრუნვა)
        $msg_fail = "{$client_base_name}-ს გაუუქმდა ჯავშანი სერვისზე: \"{$app->service_title}\" ({$app->appointment_date} {$app->appointment_time}) პროვაიდერთან: {$provider_label}, წარუმატებელი გადახდის გამო";
        
        if (function_exists('ok_add_notification')) {
            ok_add_notification($msg_fail, 'danger', [$app->customer_id, $app->provider_id], '', (int)$app->customer_id);
        }

        $ok_db->query("INSERT INTO ok_appointment_logs (original_app_id, customer_id, service_id, appointment_date, appointment_time, reason) VALUES (?, ?, ?, ?, ?, ?)", 
            [$app->id, $app->customer_id, $app->service_id, $app->appointment_date, $app->appointment_time, "გადახდა უარყოფილია"]);
            
        $ok_db->query("DELETE FROM ok_appointments WHERE id = ?", [$record_id]);
    }
});