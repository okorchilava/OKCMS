<?php
// ვიჯეტების არეების რეგისტრაცია
if (function_exists('ok_register_sidebar')) {
    
    // 1. მთავარი საიდბარი
    ok_register_sidebar([
        'id'          => 'sidebar-main',
        'name'        => 'მთავარი საიდბარი',
        'description' => 'გამოჩნდება პოსტების და გვერდების მარჯვნივ.'
    ]);

    // 2. ფუთერი 1 (მარცხენა)
    ok_register_sidebar([
        'id'          => 'footer-1',
        'name'        => 'Footer სვეტი 1',
        'description' => 'ფუთერის მარცხენა მხარე.'
    ]);

    // 3. ფუთერი 2 (შუა)
    ok_register_sidebar([
        'id'          => 'footer-2',
        'name'        => 'Footer სვეტი 2',
        'description' => 'ფუთერის შუა მხარე.'
    ]);

    // 4. ფუთერი 3 (მარჯვენა)
    ok_register_sidebar([
        'id'          => 'footer-3',
        'name'        => 'Footer სვეტი 3',
        'description' => 'ფუთერის შუა მხარე.'
    ]);
	
	    // 4. ფუთერი 3 (მარჯვენა)
    ok_register_sidebar([
        'id'          => 'footer-4',
        'name'        => 'Footer სვეტი 4',
        'description' => 'ფუთერის მარჯვენა მხარე.'
    ]);
}

// თემის მენიუების რეგისტრაცია
if (function_exists('ok_register_theme_menus')) {
    ok_register_theme_menus([
        'header-menu'  => 'მთავარი მენიუ (Header)',
        'footer-menu'  => 'ფუთერის მენიუ (Footer)',  // <--- ეს უნდა იყოს რომ სიაში გამოჩნდეს
        'sidebar-menu' => 'საიდბარის მენიუ'       // <--- ესეც
    ]);
}
?>