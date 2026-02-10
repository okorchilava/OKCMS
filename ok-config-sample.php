<?php
/**
 * OK ძრავის საბაზისო კონფიგურაციის ფაილი.
 *
 * ეს ფაილი გამოიყენება ინსტალაციის პროცესში. თქვენ არ გჭირდებათ მისი ხელით რედაქტირება,
 * მაგრამ თუ ამას აკეთებთ, დააკოპირეთ ეს ფაილი და დაარქვით 'ok-config.php'
 * და შეავსეთ ქვემოთ მოცემული ველები.
 *
 * @package OK_Engine
 */

// ** MySQL პარამეტრები - ეს ინფორმაცია მოგეწოდებათ თქვენი ვებ-ჰოსტინგის მიერ ** //
/** მონაცემთა ბაზის სახელი OK-სთვის */
define( 'DB_NAME', 'database_name_here' );

/** მონაცემთა ბაზის მომხმარებლის სახელი */
define( 'DB_USER', 'username_here' );

/** მონაცემთა ბაზის პაროლი */
define( 'DB_PASSWORD', 'password_here' );

/** MySQL ჰოსტის მისამართი */
define( 'DB_HOST', 'localhost' );

/** მონაცემთა ბაზის ცხრილებისთვის გამოსაყენებელი კოდირება. */
define( 'DB_CHARSET', 'utf8mb4' );


// ** უსაფრთხოების უნიკალური გასაღებები და "მარილები" (Salts) ** //
// შეცვალეთ ესენი უნიკალური ფრაზებით!
// შეგიძლიათ მათი გენერირება OK-ს საიდუმლო გასაღების გენერატორის სერვისით. (მომავალში)
// ეს გასაღებები გამოიყენება cookie-ების და სხვა მონაცემების უსაფრთხო შიფრაციისთვის.
define( 'AUTH_KEY',         'put your unique phrase here' );
define( 'SECURE_AUTH_KEY',  'put your unique phrase here' );
define( 'LOGGED_IN_KEY',    'put your unique phrase here' );
define( 'NONCE_KEY',        'put your unique phrase here' );
define( 'AUTH_SALT',        'put your unique phrase here' );
define( 'SECURE_AUTH_SALT', 'put your unique phrase here' );
define( 'LOGGED_IN_SALT',   'put your unique phrase here' );
define( 'NONCE_SALT',       'put your unique phrase here' );


// ** OK ძრავის გამართვის (Debug) რეჟიმი ** //
// დეველოპმენტის პროცესში ჩართეთ true, რათა ნახოთ შეცდომები.
// დასრულებულ საიტზე აუცილებლად გადაიყვანეთ false-ზე.
define( 'OK_DEBUG', false );


/* ეს არის ყველაფერი, შეწყვიტეთ რედაქტირება! წარმატებულ ბლოგინგს გისურვებთ. */

/** OK ძრავის ფაილების აბსოლუტური მისამართი. */
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}