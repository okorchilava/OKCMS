<?php
declare(strict_types=1);

/**
 * OK ძრავის პოსტის მეტა-მონაცემების (Post Meta) API ფუნქციები
 * განახლებულია: ok_postmeta ცხრილისთვის
 */

if ( ! defined( 'OK_LOADED' ) ) {
    die('Access Denied.');
}

/**
 * გლობალური ქეში
 */
$GLOBALS['ok_post_meta_cache'] = [];

/**
 * მეტა მონაცემის მიღება
 */
function get_post_meta(int $post_id, string $meta_key, bool $single = true) {
    global $ok_db, $ok_post_meta_cache;

    // 1. ქეშის შემოწმება
    if (isset($ok_post_meta_cache[$post_id][$meta_key])) {
        $val = $ok_post_meta_cache[$post_id][$meta_key];
        return $single ? $val : [$val]; 
    }

    // 2. ბაზიდან წამოღება (ok_postmeta)
    $result = $ok_db->get_results(
        "SELECT meta_value FROM ok_postmeta WHERE post_id = ? AND meta_key = ?", 
        [$post_id, $meta_key]
    );
    
    if (empty($result)) {
        return $single ? '' : [];
    }

    // 3. მონაცემების დამუშავება
    $processed_values = array_map(function($row) {
        return ok_meta_maybe_unserialize($row->meta_value);
    }, $result);

    $final_value = $processed_values[0];

    // 4. ქეშში შენახვა
    $ok_post_meta_cache[$post_id][$meta_key] = $final_value;

    return $single ? $final_value : $processed_values;
}

/**
 * მეტა მონაცემის განახლება
 */
function update_post_meta(int $post_id, string $meta_key, $meta_value): bool {
    global $ok_db, $ok_post_meta_cache;

    $meta_key = trim($meta_key);
    if (empty($meta_key)) return false;

    $value_to_save = ok_meta_maybe_serialize($meta_value);

    // [!] ok_postmeta
    // ვამოწმებთ არსებობს თუ არა (მარტივი ლოგიკა)
    $exists = $ok_db->get_var("SELECT meta_id FROM ok_postmeta WHERE post_id = ? AND meta_key = ?", [$post_id, $meta_key]);

    if ($exists) {
        $result = $ok_db->query("UPDATE ok_postmeta SET meta_value = ? WHERE meta_id = ?", [$value_to_save, $exists]);
    } else {
        $result = $ok_db->query("INSERT INTO ok_postmeta (post_id, meta_key, meta_value) VALUES (?, ?, ?)", [$post_id, $meta_key, $value_to_save]);
    }

    if ($result !== false) {
        $ok_post_meta_cache[$post_id][$meta_key] = $meta_value;
        return true;
    }

    return false;
}

/**
 * მეტა მონაცემის წაშლა
 */
function delete_post_meta(int $post_id, string $meta_key): bool {
    global $ok_db, $ok_post_meta_cache;
    
    // [!] ok_postmeta
    $result = $ok_db->query(
        "DELETE FROM ok_postmeta WHERE post_id = ? AND meta_key = ?", 
        [$post_id, $meta_key]
    );

    if (isset($ok_post_meta_cache[$post_id][$meta_key])) {
        unset($ok_post_meta_cache[$post_id][$meta_key]);
    }

    return $result !== false;
}

/* --------------------------------------------------------------------------
 * Internal Helpers
 * -------------------------------------------------------------------------- */

function ok_meta_maybe_unserialize($original) {
    if (function_exists('ok_maybe_unserialize')) return ok_maybe_unserialize($original);
    
    if (is_string($original) && ok_meta_is_serialized($original)) {
        $unserialized = @unserialize($original, ['allowed_classes' => false]);
        if ($unserialized !== false || $original === 'b:0;') return $unserialized;
    }
    return $original;
}

function ok_meta_maybe_serialize($data) {
    if (function_exists('ok_maybe_serialize')) return ok_maybe_serialize($data);
    if (is_array($data) || is_object($data)) return serialize($data);
    if (is_string($data) && ok_meta_is_serialized($data, false)) return serialize($data);
    return $data;
}

function ok_meta_is_serialized($data, $strict = true): bool {
    if (function_exists('ok_is_serialized')) return ok_is_serialized($data, $strict);
    if (!is_string($data)) return false;
    $data = trim($data);
    if ('N;' === $data) return true;
    if (strlen($data) < 4) return false;
    if (':' !== $data[1]) return false;
    $lastc = substr($data, -1);
    if (';' !== $lastc && '}' !== $lastc) return false;
    return true;
}