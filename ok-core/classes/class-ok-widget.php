<?php
declare(strict_types=1);

/**
 * OK ძრავის საბაზისო ვიჯეტის კლასი
 *
 * @package OK_Engine
 * @version 1.2
 */

// --- პირდაპირი წვდომის ბლოკი ---
if (!defined('OK_LOADED')) {
    if (!headers_sent()) { http_response_code(403); }
    $tpl403 = __DIR__ . '/../errors/403.php';
    if (is_file($tpl403)) {
        // ცვლადები შაბლონისთვის
        $error_title   = 'წვდომა აკრძალულია';
        $error_message = 'დაფიქსირდა პირდაპირი წვდომის მცდელობა.';
        $error_home    = '/';
        require $tpl403;
    } else {
        echo '<h1>403 — Forbidden</h1>';
    }
    exit;
}

abstract class OK_Widget
{
    /** @var string უნიკალური იდენტიფიკატორი (მაგ: 'recent_posts') */
    public string $id_base;

    /** @var string ვიჯეტის სახელი ადმინ პანელში */
    public string $name;

    /** @var array ვიჯეტის პარამეტრები (classname, description...) */
    public array $widget_options = [];

    /** @var array საკონტროლო პარამეტრები (width, height...) */
    public array $control_options = [];

    /** @var int|false ვიჯეტის ნომერი (თუ მრავალჯერადია) */
    public $number = false;

    /**
     * @param string $id_base
     * @param string $name
     * @param array  $widget_options
     * @param array  $control_options
     */
    public function __construct(string $id_base, string $name, array $widget_options = [], array $control_options = [])
    {
        $this->id_base = $id_base;
        $this->name    = $name;
        $this->widget_options = $widget_options;
        $this->control_options = $control_options;

        // ID-ს ვალიდაცია
        if (empty($this->id_base)) {
            $this->id_base = 'ok_widget_' . bin2hex(random_bytes(4));
        } else {
            // მხოლოდ უსაფრთხო სიმბოლოები
            $this->id_base = preg_replace('/[^a-z0-9_\-]/i', '', $this->id_base);
        }
    }

    /**
     * Frontend Display
     * @param array $args (before_widget, after_widget, before_title...)
     * @param array $instance შენახული მონაცემები
     */
    abstract public function widget(array $args, array $instance): void;

    /**
     * Admin Form
     * @param array $instance შენახული მონაცემები
     */
    abstract public function form(array $instance): void;

    /**
     * Settings Update
     * @param array $new_instance ფორმიდან მოსული მონაცემები
     * @param array $old_instance ძველი მონაცემები
     * @return array შესანახი მონაცემები
     */
    public function update(array $new_instance, array $old_instance): array
    {
        // Default: მარტივი სანიტაიზაცია.
        // რთული ვიჯეტებისთვის (მაგ: HTML კონტენტი) ეს მეთოდი გადაწერეთ (override) child კლასში.
        return $this->sanitize_deep($new_instance);
    }

    /* ====================== Helper Methods (DX Improvement) ====================== */

    /**
     * გენერირებას უკეთებს უნიკალურ `name` ატრიბუტს ფორმისთვის.
     * მაგ: widget-text-1[title]
     * * @param string $field_name ველის სახელი (მაგ: 'title')
     * @return string
     */
    public function get_field_name(string $field_name): string
    {
        return 'widget-' . $this->id_base . '[' . $this->number . '][' . $field_name . ']';
    }

    /**
     * გენერირებას უკეთებს უნიკალურ `id` ატრიბუტს ფორმისთვის.
     * მაგ: widget-text-1-title
     * * @param string $field_name
     * @return string
     */
    public function get_field_id(string $field_name): string
    {
        return 'widget-' . $this->id_base . '-' . $this->number . '-' . $field_name;
    }

    /* ====================== Security & Sanitization ====================== */

    /**
     * რექურსიული გასუფთავება
     * @param mixed $value
     * @return mixed
     */
    protected function sanitize_deep($value)
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->sanitize_deep($v);
            }
            return $value;
        }

        if (is_string($value)) {
            // არ ვშლით ახალ ხაზებს (\n), რადგან textarea-ში საჭიროა
            $val = trim($value);
            // ძირითადი დაცვა XSS-ისგან (strip_tags)
            // თუ HTML გჭირდებათ, Child კლასში გადაწერეთ update() მეთოდი
            return strip_tags($val);
        }

        return $value;
    }

    /**
     * უსაფრთხო ბეჭდვა (HTML Escaping)
     * გამოიყენეთ: value="<?php echo $this->e($instance['title']); ?>"
     */
    protected function e($string): string
    {
        return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * უსაფრთხო URL
     * უშვებს რელატიურ ბმულებს (/about), მაგრამ ბლოკავს javascript:-ს
     */
    protected function e_url($url): string
    {
        $url = (string)$url;
        $url = trim($url);

        if ($url === '') return '';

        // მარტივი დაცვა XSS-ისგან (javascript: alert(...))
        if (stripos($url, 'javascript:') === 0) {
            return '#blocked';
        }

        // URL სანიტაიზაცია
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}