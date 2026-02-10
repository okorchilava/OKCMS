<?php
declare(strict_types=1);

if (!defined('OK_LOADED')) {
    exit('Access Denied.');
}

class OkNotificationManager
{
    private const TABLE_NOTIF = 'ok_notifications';
    private const TABLE_USERS = 'ok_users';
    private const TABLE_OPTIONS = 'ok_options';
    private const MAIL_TEMPLATE_PATH = '/ok-public/templates/mail/notification.html';

    public static function add(string $message, string $type = 'info', $targets = [], string $link = '', $authorId = null): void
    {
        global $ok_db;

        $authorId = $authorId === null ? (int)($_SESSION['user_id'] ?? 0) : (int)$authorId;
        $safeType = self::sanitizeType($type);
        $safeLink = self::sanitizeLink($link);

        $authorDisplayName = 'მომხმარებელი';
        $authorUsername = '';
        $authorEmail = '';

        if ($authorId > 0) {
            $userRow = $ok_db->get_row(
                "SELECT display_name, email, username FROM " . self::TABLE_USERS . " WHERE id = ? LIMIT 1",
                [$authorId]
            );

            if ($userRow) {
                $authorDisplayName = trim((string)($userRow->display_name ?? '')) ?: $authorDisplayName;
                $authorUsername = trim((string)($userRow->username ?? ''));
                $authorEmail = trim((string)($userRow->email ?? ''));
            }
        }

        $recipientIds = self::resolveRecipients($targets);
        if (empty($recipientIds)) {
            return;
        }

        $declinedName = self::declineName($authorDisplayName);
        $actorOthers = trim($declinedName . ($authorUsername !== '' ? " ({$authorUsername})" : ''));

        $msgForOthers = self::sanitizeMessage(self::parseMessage($message, $actorOthers, false));
        $msgForSelf = self::sanitizeMessage(self::parseMessage($message, 'თქვენ', true));

        $selfRecps = [];
        $otherRecps = [];
        foreach ($recipientIds as $uid) {
            if ($uid === $authorId) {
                $selfRecps[] = $uid;
            } else {
                $otherRecps[] = $uid;
            }
        }

        if (!empty($otherRecps)) {
            $ok_db->query(
                "INSERT INTO " . self::TABLE_NOTIF . " (user_id, for_user_id, read_by_users, type, message, link, created_at) VALUES (?, ?, '', ?, ?, ?, NOW())",
                [$authorId, implode(',', $otherRecps), $safeType, $msgForOthers, $safeLink]
            );
        }

        if (!empty($selfRecps)) {
            $ok_db->query(
                "INSERT INTO " . self::TABLE_NOTIF . " (user_id, for_user_id, read_by_users, type, message, link, created_at) VALUES (?, ?, '', ?, ?, ?, NOW())",
                [$authorId, implode(',', $selfRecps), $safeType, $msgForSelf, $safeLink]
            );
        }

        $adminEmail = self::getOption('admin_email');
        self::trySendEmail($msgForOthers, $safeLink, $adminEmail);

        if ($authorEmail !== '' && filter_var($authorEmail, FILTER_VALIDATE_EMAIL) && $authorEmail !== $adminEmail) {
            self::trySendEmail($msgForSelf, $safeLink, $authorEmail);
        }
    }

    private static function sanitizeType(string $type): string
    {
        $type = strtolower(trim($type));
        $allowed = ['info', 'success', 'warning', 'danger'];
        if (!in_array($type, $allowed, true)) {
            return 'info';
        }
        return $type;
    }

    private static function sanitizeMessage(string $message): string
    {
        $message = trim(strip_tags($message));
        $message = preg_replace('/\s+/u', ' ', $message);
        return mb_substr((string)$message, 0, 500);
    }

    private static function sanitizeLink(string $link): string
    {
        $link = trim($link);
        if ($link === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $link)) {
            $host = parse_url($link, PHP_URL_HOST);
            $currHost = $_SERVER['HTTP_HOST'] ?? '';
            if (!$host || strcasecmp((string)$host, (string)$currHost) !== 0) {
                return '';
            }
            return $link;
        }

        if ($link[0] !== '/') {
            $link = '/' . $link;
        }
        return preg_replace('/[^a-zA-Z0-9\-._~:\/?#\[\]@!$&\'"()*+,;=%]/', '', $link);
    }

    private static function parseMessage(string $raw, string $actor, bool $isSelf): string
    {
        $text = trim($raw);

        if ($isSelf) {
            $text = preg_replace('/([ა-ჰ]+)ა(\s|$)/u', '$1ეთ$2', $text);
        }

        if (strpos($text, '{actor}') !== false) {
            return str_replace('{actor}', $actor, $text);
        }

        return trim($actor . ' ' . $text);
    }

    private static function declineName(string $name): string
    {
        $name = trim($name);
        if (mb_strlen($name) === 0) {
            return '';
        }

        $lastChar = mb_substr($name, -1);
        if (in_array($lastChar, ['ა', 'ე', 'ო', 'უ'], true)) {
            return $name . 'მ';
        }

        if ($lastChar === 'ი') {
            return mb_substr($name, 0, -1) . 'მა';
        }

        return $name . 'მა';
    }

    private static function trySendEmail(string $message, string $link, string $targetEmail): void
    {
        if (self::getOption('mail_notifications_enabled', '0') !== '1') {
            return;
        }

        if (!filter_var($targetEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $path = $_SERVER['DOCUMENT_ROOT'] . self::MAIL_TEMPLATE_PATH;
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $siteTitle = self::sanitizeMessage(self::getOption('site_title', 'OK Engine'));
        $siteTagline = self::sanitizeMessage(self::getOption('site_tagline', ''));
        $logoOption = self::getOption('site_logo');
        $siteLogo = $logoOption !== '' ? self::generateFullLink($logoOption) : '';

        $body = (string)file_get_contents($path);
        $body = str_replace(
            ['{{site_title}}', '{{site_tagline}}', '{{site_logo}}', '{{message}}', '{{link}}', '{{year}}'],
            [
                htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($siteTagline, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($siteLogo, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars(self::generateFullLink($link), ENT_QUOTES, 'UTF-8'),
                date('Y')
            ],
            $body
        );

        $defaultFrom = 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $fromAddress = self::getOption('mail_from_address', $defaultFrom);
        if (!filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
            $fromAddress = $defaultFrom;
        }

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $siteTitle . ' <' . $fromAddress . '>'
        ];

        @mail($targetEmail, 'შეტყობინება: ' . $siteTitle, $body, implode("\r\n", $headers));
    }

    private static function resolveRecipients($targets): array
    {
        global $ok_db;

        $res = [];
        $admins = $ok_db->get_results("SELECT id FROM " . self::TABLE_USERS . " WHERE user_role = 'admin'");
        if ($admins) {
            foreach ($admins as $admin) {
                $res[] = (int)$admin->id;
            }
        }

        if (!empty($targets)) {
            $targets = is_array($targets) ? $targets : [$targets];
            foreach ($targets as $target) {
                $id = (int)$target;
                if ($id > 0) {
                    $res[] = $id;
                }
            }
        }

        $res = array_values(array_unique($res));
        return $res;
    }

    public static function getUnreadCount(): int
    {
        global $ok_db;
        $uid = (int)($_SESSION['user_id'] ?? 0);
        if ($uid === 0) {
            return 0;
        }

        return (int)$ok_db->get_var(
            "SELECT COUNT(*) FROM " . self::TABLE_NOTIF . " WHERE FIND_IN_SET(?, for_user_id) AND (read_by_users IS NULL OR read_by_users = '' OR NOT FIND_IN_SET(?, read_by_users))",
            [$uid, $uid]
        );
    }

    public static function getLatestMessage(): string
    {
        global $ok_db;
        $uid = (int)($_SESSION['user_id'] ?? 0);
        if ($uid === 0) {
            return '';
        }

        return (string)$ok_db->get_var(
            "SELECT message FROM " . self::TABLE_NOTIF . " WHERE FIND_IN_SET(?, for_user_id) AND (read_by_users IS NULL OR read_by_users = '' OR NOT FIND_IN_SET(?, read_by_users)) ORDER BY created_at DESC LIMIT 1",
            [$uid, $uid]
        );
    }

    private static function getOption(string $name, string $default = ''): string
    {
        global $ok_db;

        $value = $ok_db->get_var(
            "SELECT option_value FROM " . self::TABLE_OPTIONS . " WHERE option_name = ? LIMIT 1",
            [$name]
        );

        return $value !== null && $value !== '' ? (string)$value : $default;
    }

    private static function generateFullLink(string $link): string
    {
        $link = trim($link);
        if ($link === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $link)) {
            return $link;
        }

        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $proto . '://' . $host . '/' . ltrim($link, '/');
    }


    public static function enforcePollRateLimit(): void
    {
        $now = time();
        if (!isset($_SESSION['_ok_notif_poll_times']) || !is_array($_SESSION['_ok_notif_poll_times'])) {
            $_SESSION['_ok_notif_poll_times'] = [];
        }

        $_SESSION['_ok_notif_poll_times'] = array_values(array_filter(
            $_SESSION['_ok_notif_poll_times'],
            static function ($ts) use ($now) {
                return is_int($ts) && ($now - $ts) <= 60;
            }
        ));

        if (count($_SESSION['_ok_notif_poll_times']) >= 60) {
            http_response_code(429);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Too Many Requests'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $_SESSION['_ok_notif_poll_times'][] = $now;
    }

    public static function renderScript(): void
    {
        if (!isset($_SESSION['user_id'])) {
            return;
        }
        ?>
        <script>
        (function() {
            const OkNotifier = {
                state: { lastCount: -1, interacted: false, audio: new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3') },
                async poll() {
                    try {
                        const r = await fetch('?ajax_action=get_unread_count', { credentials: 'same-origin' });
                        if (!r.ok) return;
                        const d = await r.json();
                        const c = parseInt(d.count, 10) || 0;

                        if (this.state.lastCount !== -1 && c > this.state.lastCount) {
                            if (this.state.interacted) this.state.audio.play().catch(() => {});
                            if (window.Notification && Notification.permission === 'granted') {
                                new Notification('ახალი შეტყობინება', { body: String(d.latest_message || '') });
                            }
                        }

                        this.state.lastCount = c;
                        const b = document.getElementById('ok-notif-badge');
                        if (b) {
                            b.innerText = c;
                            b.style.display = c > 0 ? 'inline-block' : 'none';
                        }
                    } catch (e) {}
                },
                init() {
                    document.addEventListener('click', () => {
                        this.state.interacted = true;
                        if (window.Notification && Notification.permission === 'default') {
                            Notification.requestPermission();
                        }
                    }, { once: true });
                    this.poll();
                    setInterval(() => this.poll(), 5000);
                }
            };
            document.addEventListener('DOMContentLoaded', () => OkNotifier.init());
        })();
        </script>
        <?php
    }
}

function ok_add_notification($message, $type = 'info', $targets = [], $link = '', $authorId = null)
{
    OkNotificationManager::add((string)$message, (string)$type, $targets, (string)$link, $authorId);
}

if (isset($_GET['ajax_action']) && $_GET['ajax_action'] === 'get_unread_count') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }

    OkNotificationManager::enforcePollRateLimit();

    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'count' => OkNotificationManager::getUnreadCount(),
        'latest_message' => OkNotificationManager::getLatestMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

add_ok_action('ok_footer', ['OkNotificationManager', 'renderScript']);
declare(strict_types=1);if (!defined('OK_LOADED')) {    exit('Access Denied.');}/** * Class OkNotificationManager * სისტემური ნოთიფიკაციების მართვა სრულყოფილი მართლწერით */class OkNotificationManager{    private const TABLE_NOTIF = 'ok_notifications';    private const TABLE_USERS = 'ok_users';    private const TABLE_OPTIONS = 'ok_options';    private const MAIL_TEMPLATE_PATH = '/ok-public/templates/mail/notification.html';    public static function add(string $message, string $type = 'info', array|int $targets = [], string $link = '', ?int $authorId = null): void    {        global $ok_db;        $authorId = $authorId ?? (isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0);        $authorDisplayName = 'მომხმარებელი';        $authorUsername = '';        $authorEmail = '';        if ($authorId > 0) {            $userRow = $ok_db->get_row("SELECT display_name, email, username FROM " . self::TABLE_USERS . " WHERE id = '$authorId'");            if ($userRow) {                $authorDisplayName = $userRow->display_name ?: $authorDisplayName;                $authorUsername = $userRow->username ?: '';                $authorEmail = $userRow->email ?: '';            }        }        $recipientIds = self::resolveRecipients($targets);        if (empty($recipientIds)) return;        // --- მართლწერის ფილტრი ---                // სხვებისთვის: "პაატა ჟორჟოლიანმა (paata)"        $declinedName = self::declineName($authorDisplayName);        $actorOthers = $declinedName . ($authorUsername ? " ({$authorUsername})" : "");        $msgForOthers = self::parseMessage($message, $actorOthers, false);        // თქვენთვის: უბრალოდ "თქვენ"        $msgForSelf = self::parseMessage($message, 'თქვენ', true);        // --- ბაზაში ჩაწერა ---        $selfRecps = []; $otherRecps = [];        foreach ($recipientIds as $uid) {            ($uid === $authorId) ? $selfRecps[] = $uid : $otherRecps[] = $uid;        }        if (!empty($otherRecps)) {            $ok_db->query("INSERT INTO ".self::TABLE_NOTIF." (user_id, for_user_id, read_by_users, type, message, link, created_at) VALUES (?, ?, '', ?, ?, ?, NOW())",             [$authorId, implode(',', $otherRecps), $type, $msgForOthers, $link]);        }        if (!empty($selfRecps)) {            $ok_db->query("INSERT INTO ".self::TABLE_NOTIF." (user_id, for_user_id, read_by_users, type, message, link, created_at) VALUES (?, ?, '', ?, ?, ?, NOW())",             [$authorId, implode(',', $selfRecps), $type, $msgForSelf, $link]);        }        // --- მეილის გაგზავნა ---        $adminEmail = self::getOption('admin_email');        self::trySendEmail($msgForOthers, $link, $adminEmail);        if (!empty($authorEmail) && filter_var($authorEmail, FILTER_VALIDATE_EMAIL) && $authorEmail !== $adminEmail) {            self::trySendEmail($msgForSelf, $link, $authorEmail);        }    }    private static function parseMessage(string $raw, string $actor, bool $isSelf): string    {        $text = $raw;        if ($isSelf) {            // ზმნის შეთანხმება: ატვირთა -> ატვირთეთ            $text = preg_replace('/([ა-ჰ]+)ა(\s|$)/u', '$1ეთ$2', $text);        }        // თუ {actor} თეგი არსებობს, ვანაცვლებთ პირდაპირ.         // თუ არ არსებობს, უბრალოდ წინ ვუწერთ (ზედმეტი ტირეების გარეშე)        if (strpos($text, '{actor}') !== false) {            return str_replace('{actor}', $actor, $text);        }        return $actor . ' ' . $text;    }    private static function declineName(string $name): string    {        $name = trim($name);        if (mb_strlen($name) === 0) return '';        $lastChar = mb_substr($name, -1);        if (in_array($lastChar, ['ა', 'ე', 'ო', 'უ'])) return $name . 'მ';        if ($lastChar === 'ი') return mb_substr($name, 0, -1) . 'მა';        return $name . 'მა';    }    private static function trySendEmail(string $message, string $link, string $targetEmail): void    {        if (self::getOption('mail_notifications_enabled', '0') !== '1') return;        $path = $_SERVER['DOCUMENT_ROOT'] . self::MAIL_TEMPLATE_PATH;        if (!file_exists($path)) return;        $siteTitle = self::getOption('site_title', 'OK Engine');        $siteTagline = self::getOption('site_tagline', '');        // ლოგოს მისამართის გარანტირებული სრული ბმული        $logoOption = self::getOption('site_logo');        $siteLogo = !empty($logoOption) ? self::generateFullLink($logoOption) : '';                $body = file_get_contents($path);        $body = str_replace(            ['{{site_title}}', '{{site_tagline}}', '{{site_logo}}', '{{message}}', '{{link}}', '{{year}}'],            [$siteTitle, $siteTagline, $siteLogo, $message, self::generateFullLink($link), date('Y')],            $body        );        $headers = [            'MIME-Version: 1.0',            'Content-type: text/html; charset=UTF-8',            "From: {$siteTitle} <".self::getOption('mail_from_address', 'noreply@'.$_SERVER['HTTP_HOST']).">"        ];        @mail($targetEmail, "შეტყობინება: {$siteTitle}", $body, implode("\r\n", $headers));    }    private static function resolveRecipients($targets): array    {        global $ok_db;        $res = [];        $admins = $ok_db->get_results("SELECT id FROM " . self::TABLE_USERS . " WHERE user_role = 'admin'");        if ($admins) foreach ($admins as $a) $res[] = (int)$a->id;        if (!empty($targets)) {            $targets = is_array($targets) ? $targets : [$targets];            foreach ($targets as $t) $res[] = (int)$t;        }        return array_unique($res);    }    public static function getUnreadCount(): int {        global $ok_db;        $uid = (int)($_SESSION['user_id'] ?? 0);        return $uid === 0 ? 0 : (int)$ok_db->get_var("SELECT COUNT(*) FROM ".self::TABLE_NOTIF." WHERE FIND_IN_SET(?, for_user_id) AND (read_by_users IS NULL OR read_by_users = '' OR NOT FIND_IN_SET(?, read_by_users))", [$uid, $uid]);    }    public static function getLatestMessage(): string {        global $ok_db;        $uid = (int)($_SESSION['user_id'] ?? 0);        return $uid === 0 ? '' : (string)$ok_db->get_var("SELECT message FROM ".self::TABLE_NOTIF." WHERE FIND_IN_SET(?, for_user_id) AND (read_by_users IS NULL OR read_by_users = '' OR NOT FIND_IN_SET(?, read_by_users)) ORDER BY created_at DESC LIMIT 1", [$uid, $uid]);    }    private static function getOption(string $n, string $d = ''): string {        global $ok_db;        $val = $ok_db->get_var("SELECT option_value FROM ".self::TABLE_OPTIONS." WHERE option_name = '$n'");        return $val ?: $d;    }    private static function generateFullLink(string $l): string {        if (empty($l) || strpos($l, 'http') === 0) return $l;        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';        return $proto . "://" . $host . "/" . ltrim($l, '/');    }    public static function renderScript(): void    {        if (!isset($_SESSION['user_id'])) return;        ?>        <script>        (function() {            const OkNotifier = {                state: { lastCount: -1, interacted: false, audio: new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3') },                async poll() {                    try {                        const r = await fetch('?ajax_action=get_unread_count');                        const d = await r.json();                        const c = parseInt(d.count);                        if (this.state.lastCount !== -1 && c > this.state.lastCount) {                            if (this.state.interacted) this.state.audio.play().catch(()=>{});                            if (Notification.permission === "granted") new Notification("ახალი შეტყობინება", { body: d.latest_message });                        }                        this.state.lastCount = c;                        const b = document.getElementById('ok-notif-badge');                        if (b) { b.innerText = c; b.style.display = c > 0 ? 'inline-block' : 'none'; }                    } catch(e) {}                },                init() {                    document.addEventListener('click', () => {                         this.state.interacted = true;                         if (Notification.permission === 'default') Notification.requestPermission();                    }, {once: true});                    this.poll(); setInterval(() => this.poll(), 5000);                }            };            document.addEventListener('DOMContentLoaded', () => OkNotifier.init());        })();        </script>        <?php    }}function ok_add_notification($m, $t = 'info', $tg = [], $l = '', $a = null) { OkNotificationManager::add($m, $t, $tg, $l, $a); }if (isset($_GET['ajax_action']) && $_GET['ajax_action'] === 'get_unread_count') {    header('Content-Type: application/json');    echo json_encode(['count' => OkNotificationManager::getUnreadCount(), 'latest_message' => OkNotificationManager::getLatestMessage()]);    exit;}if (function_exists('add_ok_action')) add_ok_action('ok_footer', ['OkNotificationManager', 'renderScript']);