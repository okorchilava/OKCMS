<?php
if (!defined('OK_LOADED')) exit;

/**
 * 1. ამოწმებს, არის თუ არა მომხმარებელი პროვაიდერი
 */
function ok_is_user_provider($user_id) {
    global $ok_db;
    $res = $ok_db->get_var("SELECT id FROM ok_providers WHERE user_id = ?", [$user_id]);
    return $res ? true : false;
}

/**
 * 2. ამოწმებს, არის თუ არა პროვაიდერი შვებულებაში კონკრეტულ თარიღში
 */
function ok_is_provider_on_vacation($provider_id, $date) {
    global $ok_db;
    $count = $ok_db->get_var("SELECT COUNT(*) FROM ok_vacations 
                              WHERE provider_id = ? 
                              AND ? BETWEEN start_date AND end_date", 
                              [$provider_id, $date]);
    return $count > 0;
}

/**
 * 3. აგენერირებს თავისუფალ სლოტებს (საათებს)
 */
function ok_get_available_slots($service_id, $date) {
    global $ok_db;

    // A. ვიღებთ სერვისის და პროვაიდერის ინფოს
    $service = $ok_db->get_row("SELECT duration, provider_id FROM ok_services WHERE id = ?", [$service_id]);
    if (!$service) return ['error' => 'სერვისი ვერ მოიძებნა'];

    // B. ვამოწმებთ შვებულებას
    if (ok_is_provider_on_vacation($service->provider_id, $date)) {
        return ['error' => 'პროვაიდერი შვებულებაშია']; 
    }

    // C. ვიღებთ სამუშაო საათებს ამ დღისთვის (1=ორშ ... 7=კვირა)
    $day_of_week = date('N', strtotime($date));
    $hours = $ok_db->get_results("SELECT start_time, end_time FROM ok_service_hours 
                                  WHERE service_id = ? AND day_of_week = ?", [$service_id, $day_of_week]);
    
    if (empty($hours)) return ['error' => 'ამ დღეს პროვაიდერი არ მუშაობს'];

    // D. ვიღებთ უკვე დაკავებულ დროებს
    $booked_rows = $ok_db->get_results("SELECT appointment_time FROM ok_appointments 
                                      WHERE service_id = ? AND appointment_date = ? 
                                      AND status != 'cancelled'", [$service_id, $date]);
    
    $booked_times = [];
    if ($booked_rows) {
        foreach ($booked_rows as $row) {
            $booked_times[] = date('H:i', strtotime($row->appointment_time)); 
        }
    }

    // E. სლოტების გენერაცია
    $available_slots = [];
    $duration_min = intval($service->duration);
    if ($duration_min < 1) $duration_min = 30; 

    foreach ($hours as $h) {
        $start_ts = strtotime($date . ' ' . $h->start_time);
        $end_ts   = strtotime($date . ' ' . $h->end_time);

        while (($start_ts + ($duration_min * 60)) <= $end_ts) {
            $slot_str = date('H:i', $start_ts);

            if (!in_array($slot_str, $booked_times)) {
                $available_slots[] = $slot_str;
            }
            
            $start_ts += ($duration_min * 60);
        }
    }

    if (empty($available_slots)) {
        return ['error' => 'ყველა ადგილი დაკავებულია'];
    }

    return $available_slots;
}

/**
 * 4. AJAX მოთხოვნების დამმუშავებელი (API)
 */
add_ok_action('init', function() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ok_action'])) {
        
        // Output Buffer-ის გასუფთავება, რომ სუფთა JSON დაბრუნდეს
        if (ob_get_length()) ob_clean();
        
        header('Content-Type: application/json');
        global $ok_db;

        try {

            // --- A. სერვისების წამოღება ---
            if ($_POST['ok_action'] === 'get_services') {
                $pid = intval($_POST['provider_id']);
                $services = $ok_db->get_results("SELECT id, title, price FROM ok_services WHERE provider_id = ? AND is_active = 1", [$pid]);
                echo json_encode(['success' => true, 'services' => $services ? $services : []]);
                exit;
            }

            // --- B. სლოტების წამოღება ---
            if ($_POST['ok_action'] === 'get_slots') {
                $sid = intval($_POST['service_id']);
                $date = $_POST['date'];
                
                $result = ok_get_available_slots($sid, $date);
                
                if (isset($result['error'])) {
                    echo json_encode(['success' => false, 'message' => $result['error'], 'slots' => []]);
                } else {
                    echo json_encode(['success' => true, 'slots' => $result]);
                }
                exit;
            }

            // --- C. ჯავშნის გაგზავნა (STEP 1) ---
            if ($_POST['ok_action'] === 'submit_booking') {
                
                // 1. ავტორიზაცია
                $current_user_id = 0;
                if (isset($_SESSION['ok_app_user_id']) && $_SESSION['ok_app_user_id'] > 0) {
                    $current_user_id = $_SESSION['ok_app_user_id'];
                }

                if ($current_user_id <= 0) {
                    echo json_encode(['success' => false, 'message' => 'გთხოვთ გაიაროთ ავტორიზაცია']);
                    exit;
                }

                // 2. მონაცემების მიღება
                $sid = intval($_POST['service_id']);
                $date = $_POST['date'];
                $time = $_POST['time'];

                // 3. სერვისის ფასის გაგება
                $service_row = $ok_db->get_row("SELECT price FROM ok_services WHERE id = ?", [$sid]);
                if (!$service_row) {
                    echo json_encode(['success' => false, 'message' => 'სერვისი არ არსებობს']);
                    exit;
                }
                $price = floatval($service_row->price);

                // 4. ვალიდაცია: თავისუფალი ადგილის შემოწმება
                $check_slots = ok_get_available_slots($sid, $date);
                if (isset($check_slots['error'])) {
                      echo json_encode(['success' => false, 'message' => $check_slots['error']]);
                      exit;
                }
                if (!in_array($time, $check_slots)) {
                    echo json_encode(['success' => false, 'message' => 'სამწუხაროდ, ეს დრო უკვე დაიკავეს ან არასწორია.']);
                    exit;
                }

                // 5. ჯავშნის ჩაწერა ბაზაში
                $insert = $ok_db->query("INSERT INTO ok_appointments (customer_id, service_id, appointment_date, appointment_time, status, created_at) 
                                         VALUES (?, ?, ?, ?, 'on-hold', NOW())", 
                                         [$current_user_id, $sid, $date, $time]);

                if ($insert) {
                    $app_id = $ok_db->get_var("SELECT LAST_INSERT_ID()");
                    if (!$app_id && isset($ok_db->insert_id)) {
                        $app_id = $ok_db->insert_id;
                    }

                    if (!$app_id) {
                        echo json_encode(['success' => false, 'message' => 'შეცდომა: ჯავშნის ID ვერ განისაზღვრა.']);
                        exit;
                    }

                    // 6. უფასო vs ფასიანი
                    if ($price <= 0) {
                        $ok_db->query("UPDATE ok_appointments SET status = 'confirmed' WHERE id = ?", [$app_id]);
                        echo json_encode([
                            'success' => true,
                            'payment_required' => false,
                            'message' => 'ჯავშანი დადასტურებულია (უფასო)'
                        ]);
                    } 
                    else {
                        // ფასიანია -> ვიღებთ მეთოდებს
                        $methods = [];
                        if (function_exists('ok_get_active_payment_methods')) {
                            $all_methods = ok_get_active_payment_methods();
                            foreach ($all_methods as $key => $m) {
                                if (isset($m['enabled']) && $m['enabled']) {
                                    $methods[] = [
                                        'id' => $key,
                                        'title' => $m['title'],
                                        'logo' => $m['logo']
                                    ];
                                }
                            }
                        }

                        // დაცვა: თუ მეთოდები ცარიელია, მაგრამ ფული გადასახდელია
                        if (empty($methods)) {
                            echo json_encode(['success' => false, 'message' => 'გადახდის მეთოდები არ არის გააქტიურებული სისტემაში.']);
                            exit;
                        }

                        echo json_encode([
                            'success' => true,
                            'payment_required' => true,
                            'message' => 'ჯავშანი შექმნილია. აირჩიეთ გადახდის მეთოდი.',
                            'appointment_id' => $app_id,
                            'payment_methods' => $methods
                        ]);
                    }

                } else {
                    echo json_encode(['success' => false, 'message' => 'ბაზის შეცდომა ჯავშნის შენახვისას.']);
                }
                exit;
            }

            // --- D. გადახდის მეთოდის არჩევა (STEP 2) ---
            if ($_POST['ok_action'] === 'select_payment_method') {
                $app_id = intval($_POST['appointment_id']);
                $method = $_POST['method'];

                if (function_exists('ok_process_payment_request')) {
                    $res = ok_process_payment_request($app_id, $method);
                    echo json_encode($res);
                } else {
                    echo json_encode(['success' => false, 'message' => 'გადახდის სისტემის ფუნქცია არ მოიძებნა.']);
                }
                exit;
            }

            // --- E. ჯავშნის გაუქმება (მომხმარებლის მიერ) ---
            if ($_POST['ok_action'] === 'cancel_booking') {
                $app_id = intval($_POST['appointment_id']);
                $current_user_id = $_SESSION['ok_app_user_id'] ?? 0;

                if ($current_user_id <= 0) {
                    echo json_encode(['success' => false, 'message' => 'გთხოვთ გაიაროთ ავტორიზაცია']);
                    exit;
                }

                // ვეძებთ ჯავშანს და ვამოწმებთ ეკუთვნის თუ არა ამ მომხმარებელს
                $app = $ok_db->get_row("SELECT * FROM ok_appointments WHERE id = ? AND customer_id = ?", [$app_id, $current_user_id]);

                if (!$app) {
                    echo json_encode(['success' => false, 'message' => 'ჯავშანი არ მოიძებნა ან არ გაქვთ წაშლის უფლება']);
                    exit;
                }

                // ლოგირება (ისტორიაში გადატანა)
                $reason = "მომხმარებელმა გააუქმა";
                $ok_db->query("INSERT INTO ok_appointment_logs 
                    (original_app_id, customer_id, service_id, appointment_date, appointment_time, reason) 
                    VALUES (?, ?, ?, ?, ?, ?)", 
                    [$app->id, $app->customer_id, $app->service_id, $app->appointment_date, $app->appointment_time, $reason]
                );

                // წაშლა
                $delete = $ok_db->query("DELETE FROM ok_appointments WHERE id = ?", [$app_id]);

                if ($delete) {
                    echo json_encode(['success' => true, 'message' => 'ჯავშანი წარმატებით გაუქმდა']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'შეცდომა წაშლისას']);
                }
                exit;
            }

        } catch (Throwable $e) {
            echo json_encode([
                'success' => false, 
                'message' => 'სისტემური შეცდომა: ' . $e->getMessage()
            ]);
            exit;
        }
    }
});