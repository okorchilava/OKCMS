<?php
/*
Plugin Name: OK Weather (Night Mode Fixed)
Description: ამინდის ვიჯეტი ღამის რეჟიმის მხარდაჭერით.
Version: 3.4
Author: OK Engine
*/

if (!defined('OK_LOADED')) die('Access Denied.');

// ─────────────────────────────────────────────────────────────────────────────
// 1. დამხმარე ფუნქციები
// ─────────────────────────────────────────────────────────────────────────────

function ok_get_wmo_info($code, $is_day = 1) {
    // ფერები
    $sun_color  = 'text-warning'; // ყვითელი
    $moon_color = 'text-info';    // ღია ცისფერი (ღამისთვის უკეთესია)

    switch ($code) {
        case 0: // მოწმენდილი
            return $is_day 
                ? ['icon' => 'bi-sun-fill', 'desc' => 'მოწმენდილი', 'color' => $sun_color]
                : ['icon' => 'bi-moon-stars-fill', 'desc' => 'მოწმენდილი', 'color' => $moon_color];
        
        case 1: 
        case 2: // ნაწილობრივ ღრუბლიანი
            return $is_day 
                ? ['icon' => 'bi-cloud-sun', 'desc' => 'ნაწილობრივ ღრუბლიანი', 'color' => $sun_color]
                : ['icon' => 'bi-cloud-moon-fill', 'desc' => 'ნაწილობრივ ღრუბლიანი', 'color' => $moon_color];
        
        case 3: return ['icon' => 'bi-cloud-fill', 'desc' => 'მოღრუბლული', 'color' => 'text-secondary'];
        case 45:
        case 48: return ['icon' => 'bi-cloud-haze2', 'desc' => 'ნისლი', 'color' => 'text-secondary'];
        case 51:
        case 53:
        case 55: return ['icon' => 'bi-cloud-drizzle', 'desc' => 'ჟინჟღლი', 'color' => 'text-info'];
        case 61:
        case 63:
        case 65: return ['icon' => 'bi-cloud-rain-heavy', 'desc' => 'წვიმა', 'color' => 'text-primary'];
        case 71:
        case 73:
        case 75: return ['icon' => 'bi-snow', 'desc' => 'თოვლი', 'color' => 'text-info'];
        case 95:
        case 96:
        case 99: return ['icon' => 'bi-cloud-lightning-rain', 'desc' => 'ჭექა-ქუხილი', 'color' => 'text-warning'];
        default: return ['icon' => 'bi-cloud', 'desc' => 'უცნობი', 'color' => 'text-muted'];
    }
}

function ok_get_geo_coords($city) {
    if (empty($city)) return false;
    $cache_key = 'geo_' . md5(strtolower($city));
    if (isset($_SESSION[$cache_key])) return $_SESSION[$cache_key];

    $url = "https://geocoding-api.open-meteo.com/v1/search?name=" . urlencode($city) . "&count=1&language=ka&format=json";
    $response = @file_get_contents($url);
    if ($response) {
        $data = json_decode($response, true);
        if (!empty($data['results'][0])) {
            $coords = [
                'lat' => $data['results'][0]['latitude'],
                'lon' => $data['results'][0]['longitude'],
                'name' => $data['results'][0]['name']
            ];
            $_SESSION[$cache_key] = $coords;
            return $coords;
        }
    }
    return false;
}

function ok_get_open_weather($lat, $lon) {
    // 🛑 შევცვალეთ სახელი v2-ზე, რომ ძველი ქეში არ წაიკითხოს!
    $cache_key = 'open_meteo_v2_' . $lat . '_' . $lon;
    
    if (isset($_SESSION[$cache_key]) && $_SESSION[$cache_key]['time'] > time() - 3600) {
        return $_SESSION[$cache_key]['data'];
    }
    
    // is_day პარამეტრი აუცილებელია!
    $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&current=temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m,is_day";
    
    $response = @file_get_contents($url);
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['current'])) {
            $_SESSION[$cache_key] = [ 'time' => time(), 'data' => $data['current'] ];
            return $data['current'];
        }
    }
    return false;
}

// ─────────────────────────────────────────────────────────────────────────────
// 2. ვიჯეტის რეგისტრაცია
// ─────────────────────────────────────────────────────────────────────────────
if (function_exists('ok_register_widget')) {
    
    ok_register_widget('ok_weather', [
        'name' => 'ამინდი',
        'icon' => 'bi-brightness-high',
        'desc' => 'Open-Meteo უფასო სერვისი',
        'admin_template' => '
            <div class="mb-2">
                <label class="small fw-bold text-muted">სათაური:</label>
                <input type="text" name="__NAME_PREFIX__[title]" class="form-control form-control-sm" value="__TITLE__" placeholder="მაგ: პროგნოზი">
            </div>
            <div class="mb-2">
                <label class="small fw-bold text-muted">ქალაქი (საძიებო):</label>
                <input type="text" name="__NAME_PREFIX__[city]" class="form-control form-control-sm" value="__CITY__" placeholder="Tbilisi">
            </div>
            <div class="mb-2">
                <label class="small fw-bold text-muted">ქალაქი (ვიზუალი):</label>
                <input type="text" name="__NAME_PREFIX__[display_name]" class="form-control form-control-sm" value="__DISPLAY_NAME__" placeholder="მაგ: ბათუმი">
            </div>',
            
        'render_callback' => function($data) {
            $input_city = !empty($data['city']) ? $data['city'] : ($data['content'] ?? 'Tbilisi');
            $display_city = !empty($data['display_name']) ? $data['display_name'] : '';
            
            $geo = ok_get_geo_coords($input_city);
            
            if ($geo) {
                if (empty($display_city)) { $display_city = $geo['name']; }
                $weather = ok_get_open_weather($geo['lat'], $geo['lon']);
                
                if ($weather) {
                    $temp = round($weather['temperature_2m']);
                    $humid = $weather['relative_humidity_2m'];
                    $wind = $weather['wind_speed_10m'];
                    $code = $weather['weather_code'];
                    
                    // 🛑 დღეა თუ ღამე?
                    $is_day = isset($weather['is_day']) ? $weather['is_day'] : 1;
                    $info = ok_get_wmo_info($code, $is_day);

                    echo '
                    <div class="weather-widget text-center p-3">
                        <h6 class="text-uppercase text-muted small fw-bold mb-3">'.htmlspecialchars($display_city).'</h6>
                        <div class="d-flex align-items-center justify-content-center mb-3">
                            <i class="bi '.$info['icon'].' display-4 '.$info['color'].'"></i>
                            <div class="text-start ms-3">
                                <h2 class="mb-0 fw-bold display-6" style="line-height:1;">'.$temp.'°</h2>
                                <p class="mb-0 text-muted small">'.$info['desc'].'</p>
                            </div>
                        </div>
                        <div class="row border-top pt-2 small text-muted g-0">
                            <div class="col-6 border-end"><i class="bi bi-droplet-half text-primary"></i> '.$humid.'%</div>
                            <div class="col-6"><i class="bi bi-wind text-secondary"></i> '.$wind.' კმ/სთ</div>
                        </div>
                    </div>';
                } else {
                    echo '<div class="alert alert-warning p-2 small">სერვისი მიუწვდომელია.</div>';
                }
            } else {
                echo '<div class="alert alert-danger p-2 small">ქალაქი ვერ მოიძებნა.</div>';
            }
        }
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// 3. შორთკოდი
// ─────────────────────────────────────────────────────────────────────────────
if (function_exists('ok_add_shortcode')) {
    ok_add_shortcode('weather', function($atts) {
        $input_city = !empty($atts['city']) ? $atts['city'] : 'Tbilisi';
        $label = !empty($atts['label']) ? $atts['label'] : '';
        
        $geo = ok_get_geo_coords($input_city);
        if (!$geo) return '';
        if (empty($label)) $label = $geo['name'];

        $weather = ok_get_open_weather($geo['lat'], $geo['lon']);
        if (!$weather) return '';

        $temp = round($weather['temperature_2m']);
        $is_day = isset($weather['is_day']) ? $weather['is_day'] : 1;
        $info = ok_get_wmo_info($weather['weather_code'], $is_day);
        
        return '<div class="d-inline-flex align-items-center bg-light rounded px-3 py-1 border shadow-sm"><i class="bi '.$info['icon'].' fs-4 me-2 '.$info['color'].'"></i><span class="fw-bold me-2">'.$label.':</span><span class="fs-5 fw-bold text-dark">'.$temp.'°C</span><span class="ms-2 small text-muted">('.$info['desc'].')</span></div>';
    });
}
?>