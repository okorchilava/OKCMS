<?php
/**
 * Default Theme-ის ფუნქციები
 */

// არეგისტრირებს ვიჯეტების არეას (Sidebar)
register_sidebar([
    'name'          => 'მთავარი საიდბარი',
    'id'            => 'main-sidebar', // უნიკალური ID
    'description'   => 'ეს არის მთავარი საიდბარი, რომელიც გამოჩნდება გვერდითა პანელზე.',
    'before_widget' => '<div id="%1$s" class="widget %2$s card card-body mb-4">',
    'after_widget'  => '</div>',
    'before_title'  => '<h5 class="widget-title">',
    'after_title'   => '</h5>',
]);