<?php
/**
 * Paata Theme — functions.php
 * Sidebar + Footer widget areas (with safe fallbacks)
 */

if (!defined('OK_LOADED')) {
    die('OK core not loaded.');
}

/* -------------------------------------------------------------------------
   Footer helpers — FALLBACKS
   თუ Widgets API-ში ჯერ არ გაქვს register_footer()/dynamic_footer(),
   აქვე განვსაზღვროთ და გამოვიყენოთ.
   ------------------------------------------------------------------------- */
if (!function_exists('register_footer')) {
    /**
     * register_footer — alias register_sidebar-ზე (footer-თვის მოსახერხებელი სახელი)
     * @param array $args
     */
    function register_footer(array $args): void
    {
        if (!function_exists('register_sidebar')) {
            // თუ ბირთვი საერთოდ არ ატვირთავს Widgets API-ს, მშვიდად გავიდეთ.
            return;
        }

        // Footer-ს შეესაბამება მსუბუქი ნაგულისხმევი wrapper-ები
        $defaults = [
            'id'            => null,
            'name'          => '',
            'description'   => '',
            'before_widget' => '<div id="%1$s" class="widget %2$s mb-4">',
            'after_widget'  => '</div>',
            'before_title'  => '<h5 class="widget-title mb-3">',
            'after_title'   => '</h5>',
        ];
        $args = array_merge($defaults, $args);

        // თუ ID არ უწერია — დავაგენერიროთ უნიკალური
        static $footer_seq = 1;
        if (empty($args['id'])) {
            $args['id'] = 'footer-area' . $footer_seq++;
        }

        register_sidebar($args);
    }
}

if (!function_exists('dynamic_footer')) {
    /**
     * dynamic_footer — alias dynamic_sidebar-ზე
     * @param string $area_id
     */
    function dynamic_footer(string $area_id): void
    {
        if (function_exists('dynamic_sidebar')) {
            dynamic_sidebar($area_id);
        }
    }
}

/* -------------------------------------------------------------------------
   მთავარი საიდბარი (უკვე გქონდა) — ვტოვებთ უცვლელად
   ------------------------------------------------------------------------- */
register_sidebar([
    'name'          => 'მთავარი საიდბარი',
    'id'            => 'main-sidebar', // უნიკალური ID
    'description'   => 'ეს არის მთავარი საიდბარი, რომელიც გამოჩნდება გვერდითა პანელზე.',
    'before_widget' => '<div id="%1$s" class="widget %2$s card card-body mb-4">',
    'after_widget'  => '</div>',
    'before_title'  => '<h5 class="widget-title">',
    'after_title'   => '</h5>',
]);

/* -------------------------------------------------------------------------
   ფუთერის ვიჯეტ არეები (3 სვეტი) — ახლა register_footer()-ით, ფატალის გარეშე
   IDs: footer-area1, footer-area2, footer-area3
   ------------------------------------------------------------------------- */
register_footer([
    'id'            => 'footer-area1',
    'name'          => 'ფუთერი — ვიჯეტ ზონა #1',
    'description'   => 'ფუთერის მარცხენა სვეტი (Col 1/4).',
    // სურვილისამებრ შეგიძლია გადააწერო wrapper-ები:
    // 'before_widget' => '<div id="%1$s" class="widget %2$s mb-4">',
    // 'after_widget'  => '</div>',
    // 'before_title'  => '<h5 class="widget-title mb-3">',
    // 'after_title'   => '</h5>',
]);

register_footer([
    'id'            => 'footer-area2',
    'name'          => 'ფუთერი — ვიჯეტ ზონა #2',
    'description'   => 'ფუთერის შუა სვეტი (Col 2/4).',
]);

register_footer([
    'id'            => 'footer-area3',
    'name'          => 'ფუთერი — ვიჯეტ ზონა #3',
    'description'   => 'ფუთერის მარჯვენა სვეტი (Col 3/4).',
]);
register_footer([
    'id'            => 'footer-area4',
    'name'          => 'ფუთერი — ვიჯეტ ზონა #4',
    'description'   => 'ფუთერის მარჯვენა სვეტი (Col 4/4).',
]);
