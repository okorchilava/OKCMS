<?php
/**
 * OK Engine - User Functions
 * მორგებულია ახალ ბაზაზე (ok_users)
 */

if ( ! defined( 'OK_LOADED' ) ) die;

/**
 * აბრუნებს მიმდინარე მომხმარებლის ID-ს
 */
function get_current_user_id() {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
}

/**
 * აბრუნებს მიმდინარე მომხმარებლის მთლიან ობიექტს ბაზიდან
 */
function ok_get_current_user() {
    global $ok_db;
    
    $user_id = get_current_user_id();
    if ( $user_id <= 0 ) return null;

    // [!] აქ არის შესწორება: ok_users
    // ვიყენებთ მარტივ ქეშირებას გლობალურ ცვლადში, რომ ბაზა ბევრჯერ არ შეწუხდეს
    if ( isset($GLOBALS['ok_current_user_cache']) && $GLOBALS['ok_current_user_cache']->id == $user_id ) {
        return $GLOBALS['ok_current_user_cache'];
    }

    $user = $ok_db->get_row("SELECT * FROM ok_users WHERE id = ? LIMIT 1", [$user_id]);
    
    if ($user) {
        $GLOBALS['ok_current_user_cache'] = $user;
    }
    
    return $user;
}

/**
 * უფლებების შემოწმება (მთავარი ფუნქცია)
 * * @param string $capability რისი გაკეთება სურს? (მაგ: 'edit_posts', 'manage_options')
 * @return bool აქვს თუ არა უფლება
 */
function current_user_can( $capability ) {
    $user = ok_get_current_user();

    if ( ! $user ) {
        return false;
    }

    // როლის მიღება
    $role = $user->user_role;

    // 1. სუპერ ადმინი (ყველაფრის უფლება აქვს)
    if ( $role === 'admin' || $role === 'administrator' ) {
        return true;
    }

    // 2. ედიტორი (პოსტების მართვა)
    if ( $role === 'editor' ) {
        $editor_caps = ['read', 'edit_posts', 'edit_pages', 'upload_files'];
        if ( in_array( $capability, $editor_caps ) ) return true;
    }

    // 3. ავტორი (მხოლოდ პოსტები)
    if ( $role === 'author' ) {
        $author_caps = ['read', 'edit_posts', 'upload_files'];
        if ( in_array( $capability, $author_caps ) ) return true;
    }

    // 4. გამომწერი (მხოლოდ კითხვა)
    if ( $role === 'subscriber' ) {
        if ( $capability === 'read' ) return true;
    }

    return false;
}

/**
 * ამოწმებს, არის თუ არა მომხმარებელი შესული
 */
function is_user_logged_in() {
    return ( get_current_user_id() > 0 );
}