<?php
declare(strict_types=1);

/**
 * OK ძრავის ფაილების ატვირთვის ფუნქციონალი
 *
 * @package OK_Engine
 * @version 1.2 (Secure & Transparency Fix)
 */

if ( ! defined( 'OK_LOADED' ) ) {
    die('Access Denied.');
}

/**
 * ამუშავებს ფაილის ატვირთვას.
 *
 * @param array $file_data $_FILES['input_name'] მასივი.
 * @param int   $post_id   (Optional) მშობელი პოსტის ID.
 * @return int|OK_Error    წარმატებისას Attachment ID, სხვაგვარად შეცდომის ობიექტი.
 */
function handle_file_upload(array $file_data, int $post_id = 0) {
    global $ok_db;

    // 1. ატვირთვის შეცდომის შემოწმება
    if (isset($file_data['error']) && $file_data['error'] !== UPLOAD_ERR_OK) {
        return new OK_Error('Upload error code: ' . $file_data['error']);
    }

    // 2. MIME Type-ის სერვერული შემოწმება (უსაფრთხოება!)
    // არ ვენდობით $file_data['type']-ს!
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $real_mime = $finfo->file($file_data['tmp_name']);

    $allowed_mimes = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'webp' => 'image/webp' // დავამატეთ თანამედროვე ფორმატი
    ];

    if (!in_array($real_mime, $allowed_mimes, true)) {
        return new OK_Error('არასწორი ფაილის ტიპი. დაშვებულია მხოლოდ სურათები.');
    }

    // 3. საქაღალდის მომზადება
    $upload_dir_base = dirname(__DIR__, 2) . '/ok-content/uploads/';
    $upload_dir_monthly = date('Y/m/');
    $upload_dir = $upload_dir_base . $upload_dir_monthly;

    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            return new OK_Error('საქაღალდის შექმნა ვერ მოხერხდა: ' . $upload_dir);
        }
    }

    // 4. სახელის გასუფთავება და უნიკალურობა
    $original_name = pathinfo($file_data['name'], PATHINFO_FILENAME);
    $extension = array_search($real_mime, $allowed_mimes, true);
    if (!$extension) {
        // Fallback: ვიღებთ ორიგინალ ექსტენშენს, თუ უსაფრთხოა
        $extension = strtolower(pathinfo($file_data['name'], PATHINFO_EXTENSION));
    }

    $safe_name = sanitize_file_name($original_name);
    $filename  = time() . '-' . $safe_name . '.' . $extension;
    
    $destination = $upload_dir . $filename;
    $relative_path = 'uploads/' . $upload_dir_monthly . $filename;

    // 5. ფაილის გადატანა
    if (!move_uploaded_file($file_data['tmp_name'], $destination)) {
        return new OK_Error('ფაილის გადატანა ვერ მოხერხდა.');
    }

    // 6. ბაზაში ჩაწერა
    $current_user_id = function_exists('ok_get_current_user_id') ? ok_get_current_user_id() : 0;

    $attachment_data = [
        'post_author'    => $current_user_id,
        'post_title'     => $safe_name, // სათაურში სუფთა სახელი
        'post_slug'      => $safe_name,
        'post_type'      => 'attachment',
        'post_mime_type' => $real_mime,
        'post_parent'    => $post_id,
        'post_status'    => 'inherit'
    ];
    
    // ვიყენებთ სახელობით ველებს SQL-ში
    $ok_db->query(
        "INSERT INTO posts (post_author, post_title, post_slug, post_type, post_mime_type, post_parent, post_status, post_date) 
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
        [
            $attachment_data['post_author'],
            $attachment_data['post_title'],
            $attachment_data['post_slug'],
            $attachment_data['post_type'],
            $attachment_data['post_mime_type'],
            $attachment_data['post_parent'],
            $attachment_data['post_status']
        ]
    );
    
    $attachment_id = $ok_db->last_insert_id();

    if ($attachment_id) {
        update_post_meta((int)$attachment_id, '_file_path', $relative_path);
        // სურვილისამებრ: შევინახოთ ფაილის ზომაც
        update_post_meta((int)$attachment_id, '_file_size', filesize($destination));
    }
    
    // 7. თამბნეილის გენერაცია (გამჭვირვალობის მხარდაჭერით)
    generate_thumbnail($destination, 150, 150);

    return (int)$attachment_id;
}

/**
 * ასუფთავებს ფაილის სახელს (მკაცრი ვალიდაცია).
 */
function sanitize_file_name(string $filename): string {
    // ვაშორებთ HTML ტეგებს
    $filename = strip_tags($filename);
    // ვაშორებთ ყველაფერს, გარდა ლათინური ასოების, ციფრების, ტირესი და ქვედა ტირესი
    // წერტილსაც (.) ვაშორებთ აქ, რადგან ექსტენშენს ხელით ვამატებთ გვიან.
    $filename = preg_replace('/[^a-zA-Z0-9_-]/', '', $filename);
    
    return empty($filename) ? 'file' : $filename;
}

/**
 * ქმნის სურათის პატარა ასლს (thumbnail) Crop-ით და გამჭვირვალობის შენარჩუნებით.
 */
function generate_thumbnail(string $file_path, int $thumb_width, int $thumb_height): bool {
    if (!function_exists('getimagesize')) return false;

    $image_info = @getimagesize($file_path);
    if (!$image_info) return false;

    list($width, $height, $type) = $image_info;

    // წყაროს შექმნა
    $source = null;
    switch ($type) {
        case IMAGETYPE_JPEG: $source = imagecreatefromjpeg($file_path); break;
        case IMAGETYPE_PNG:  $source = imagecreatefrompng($file_path); break;
        case IMAGETYPE_GIF:  $source = imagecreatefromgif($file_path); break;
        case IMAGETYPE_WEBP: $source = imagecreatefromwebp($file_path); break;
    }

    if (!$source) return false;

    // Crop კალკულაცია (ცენტრიდან)
    $original_ratio = $width / $height;
    $thumb_ratio    = $thumb_width / $thumb_height;

    if ($original_ratio > $thumb_ratio) { 
        // სურათი უფრო ფართოა -> ვჭრით სიგანეს
        $src_h = $height;
        $src_w = (int)($height * $thumb_ratio);
        $src_x = (int)(($width - $src_w) / 2);
        $src_y = 0;
    } else { 
        // სურათი უფრო მაღალია -> ვჭრით სიმაღლეს
        $src_w = $width;
        $src_h = (int)($width / $thumb_ratio);
        $src_x = 0;
        $src_y = (int)(($height - $src_h) / 2);
    }

    // ახალი ტილოს შექმნა
    $thumb = imagecreatetruecolor($thumb_width, $thumb_height);

    // --- გამჭვირვალობის (Transparency) შენარჩუნება (PNG/GIF/WEBP) ---
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_WEBP) {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
        imagefilledrectangle($thumb, 0, 0, $thumb_width, $thumb_height, $transparent);
    } elseif ($type == IMAGETYPE_GIF) {
        $transparent_index = imagecolortransparent($source);
        if ($transparent_index >= 0) {
            imagepalettecopy($source, $thumb);
            imagefill($thumb, 0, 0, $transparent_index);
            imagecolortransparent($thumb, $transparent_index);
            imagetruecolortopalette($thumb, true, 256);
        }
    }

    // სურათის გადატანა და ზომის შეცვლა
    imagecopyresampled($thumb, $source, 0, 0, $src_x, $src_y, $thumb_width, $thumb_height, $src_w, $src_h);

    // შენახვა
    $path_info = pathinfo($file_path);
    $thumb_name = $path_info['filename'] . '-' . $thumb_width . 'x' . $thumb_height . '.' . $path_info['extension'];
    $thumb_path = $path_info['dirname'] . '/' . $thumb_name;

    switch ($type) {
        case IMAGETYPE_JPEG: imagejpeg($thumb, $thumb_path, 85); break; // Quality 85
        case IMAGETYPE_PNG:  imagepng($thumb, $thumb_path, 8);   break; // Compression 8
        case IMAGETYPE_GIF:  imagegif($thumb, $thumb_path);      break;
        case IMAGETYPE_WEBP: imagewebp($thumb, $thumb_path, 80); break;
    }

    imagedestroy($source);
    imagedestroy($thumb);

    return true;
}

// შეცდომის კლასი (თუ სხვაგან არ არის განსაზღვრული)
if (!class_exists('OK_Error')) {
    class OK_Error {
        public $message;
        public function __construct(string $message) { 
            $this->message = $message; 
        }
        public function get_error_message(): string {
            return $this->message;
        }
    }
}