<?php
declare(strict_types=1);

/**
 * OK Engine - Options API
 * მხოლოდ პარამეტრების მართვის ფუნქციები.
 */

if (!defined('OK_LOADED')) {
    die('Access Denied.');
}

$GLOBALS['ok_options_cache'] = [];

// 1. პარამეტრის მიღება
function get_ok_option(string $option_name, $default_value = null) {
    global $ok_db, $ok_options_cache;

    $option_name = trim($option_name);
    if (empty($option_name)) return $default_value;

    if (array_key_exists($option_name, $ok_options_cache)) {
        return $ok_options_cache[$option_name];
    }

    $row = $ok_db->get_row("SELECT option_value FROM ok_options WHERE option_name = ? LIMIT 1", [$option_name]);

    if ($row) {
        $value = $row->option_value;
        $final_value = ok_maybe_unserialize($value);
        $ok_options_cache[$option_name] = $final_value;
        return $final_value;
    }

    return $default_value;
}

// 2. პარამეტრის განახლება/შექმნა
function update_ok_option(string $option_name, $option_value): bool {
    global $ok_db, $ok_options_cache;

    $option_name = trim($option_name);
    if (empty($option_name)) return false;

    $old_value = get_ok_option($option_name);
    if ($old_value === $option_value) {
        return true;
    }

    $value_to_save = ok_maybe_serialize($option_value);

    $sql = "INSERT INTO ok_options (option_name, option_value) VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)";
    
    $result = $ok_db->query($sql, [$option_name, $value_to_save]);

    if ($result !== false) {
        $ok_options_cache[$option_name] = $option_value;
        return true;
    }

    return false;
}

// 3. პარამეტრის წაშლა
function delete_ok_option(string $option_name): bool {
    global $ok_db, $ok_options_cache;
    
    $option_name = trim($option_name);
    if (empty($option_name)) return false;

    $result = $ok_db->query("DELETE FROM ok_options WHERE option_name = ?", [$option_name]);
    
    if (isset($ok_options_cache[$option_name])) {
        unset($ok_options_cache[$option_name]);
    }

    return $result !== false;
}

// --- HELPER FUNCTIONS ---

function ok_is_serialized($data, $strict = true): bool {
    if (!is_string($data)) return false;
    $data = trim($data);
    if ('N;' === $data) return true;
    if (strlen($data) < 4) return false;
    if (':' !== $data[1]) return false;
    if ($strict) {
        $lastc = substr($data, -1);
        if (';' !== $lastc && '}' !== $lastc) return false;
    } else {
        $semicolon = strpos($data, ';');
        $brace     = strpos($data, '}');
        if (false === $semicolon && false === $brace) return false;
        if (false !== $semicolon && $semicolon < 3) return false;
        if (false !== $brace && $brace < 4) return false;
    }
    $token = $data[0];
    switch ($token) {
        case 's':
            if ($strict) {
                if ('"' !== substr($data, -2, 1)) return false;
            } elseif (false === strpos($data, '"')) return false;
        case 'a':
        case 'O':
            return (bool) preg_match("/^{$token}:[0-9]+:/s", $data);
        case 'b':
        case 'i':
        case 'd':
            $end = $strict ? '$' : '';
            return (bool) preg_match("/^{$token}:[0-9.E+-]+;$end/", $data);
    }
    return false;
}

function ok_maybe_serialize($data) {
    if (is_array($data) || is_object($data)) return serialize($data);
    if (ok_is_serialized($data, false)) return serialize($data);
    return $data;
}

function ok_maybe_unserialize($original) {
    if (ok_is_serialized($original)) { 
        $unserialized = @unserialize($original, ['allowed_classes' => false]);
        if ($unserialized !== false || $original === 'b:0;') return $unserialized;
    }
    return $original;
}