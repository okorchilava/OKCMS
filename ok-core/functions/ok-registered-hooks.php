<?php
declare(strict_types=1);

/**
 * FILE: ok-registered-hooks.php
 * მიზანი: სისტემური ჰუკების ინიციალიზაცია (თუ საჭიროა)
 */

if (!defined('OK_LOADED')) {
    exit('Access Denied.');
}

// უბრალოდ ვქმნით ცარიელ ჰუკებს, რომ შეცდომა არ იყოს, თუ არაფერია მიბმული
if (function_exists('add_ok_action')) {
    // 1. Header
    add_ok_action('ok_head', function() {}); 
    
    // 2. Sidebar
    add_ok_action('ok_sidebar', function() {}); 

    // 3. Footer
    add_ok_action('ok_footer', function() {});

    // 4. Content Hooks (ახლები)
    add_ok_action('ok_before_content', function() {});
    add_ok_action('ok_after_content', function() {});
}