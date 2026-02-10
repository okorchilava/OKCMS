<?php
add_ok_action('init', function() {
    global $ok_db;
    
    // 1. პროვაიდერების სია
    $ok_db->query("CREATE TABLE IF NOT EXISTS ok_providers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. სერვისები
    $ok_db->query("CREATE TABLE IF NOT EXISTS ok_services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        provider_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        price DECIMAL(10,2) DEFAULT 0.00,
        duration INT DEFAULT 30,
        is_active TINYINT DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 3. სერვისის საათები
    $ok_db->query("CREATE TABLE IF NOT EXISTS ok_service_hours (
        id INT AUTO_INCREMENT PRIMARY KEY,
        service_id INT NOT NULL,
        day_of_week TINYINT,
        start_time TIME,
        end_time TIME
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 4. შვებულებები
    $ok_db->query("CREATE TABLE IF NOT EXISTS ok_vacations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        provider_id INT NOT NULL,
        start_date DATE,
        end_date DATE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 5. ჯავშნები
    $ok_db->query("CREATE TABLE IF NOT EXISTS ok_appointments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        service_id INT NOT NULL,
        customer_id INT NOT NULL,
        appointment_date DATE,
        appointment_time TIME,
        status VARCHAR(20) DEFAULT 'on_hold', 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 6. [ახალი] ჯავშნების ჟურნალი
    $ok_db->query("CREATE TABLE IF NOT EXISTS ok_appointment_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        original_app_id INT,
        customer_id INT,
        service_id INT,
        appointment_date DATE,
        appointment_time TIME,
        reason TEXT,
        log_date DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
});