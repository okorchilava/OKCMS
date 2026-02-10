<?php
declare(strict_types=1);

/**
 * OK Engine — Database Layer (Native PDO)
 * @package OK_Engine
 * @version 3.0.1 (Native Edition - Fixed)
 */

if (!defined('OK_LOADED')) {
    // თუ OK_LOADED არ არის, უბრალოდ გავჩერდეთ (500-ის გარეშე)
    http_response_code(403);
    exit('Access Denied');
}

final class OK_DB
{
    private static ?self $instance = null;
    private ?PDO $pdo = null;
    private ?PDOStatement $stmt = null;

    private function __construct()
    {
        $host    = defined('DB_HOST') ? (string)DB_HOST : 'localhost';
        $dbname  = defined('DB_NAME') ? (string)DB_NAME : '';
        $user    = defined('DB_USER') ? (string)DB_USER : '';
        
        $pass = '';
        if (defined('DB_PASSWORD')) $pass = (string)DB_PASSWORD;
        elseif (defined('DB_PASS')) $pass = (string)DB_PASS;

        $charset = defined('DB_CHARSET') ? (string)DB_CHARSET : 'utf8mb4';

        if ($dbname === '' || $user === '') {
            error_log('[OK_DB] DB Configuration missing.');
            exit('DB Config Error');
        }

        $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
            PDO::MYSQL_ATTR_LOCAL_INFILE => false, 
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (Throwable $e) {
            error_log("[DB ERROR] Connection failed: " . $e->getMessage());
            // 500-ის ნაცვლად კონკრეტული შეტყობინება
            die("<h1>Database Connection Failed</h1><p>Check error logs.</p>");
        }
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * 🛡️ მთავარი მეთოდი (Native Prepared Statements)
     */
    public function query(string $sql, array $params = []): ?PDOStatement
    {
        try {
            if (!$this->pdo) {
                // აქ ვისროდით RuntimeException-ს, რომელიც არ იჭერებოდა
                // ახლა ეს დაიჭერება ქვემოთ Throwable-ში
                throw new RuntimeException('DB Connection lost.');
            }

            $this->stmt = $this->pdo->prepare($sql);
            $this->stmt->execute($params);

            return $this->stmt;

        } catch (Throwable $e) { // შეიცვალა PDOException -> Throwable-ზე
            // ვლოგავთ ნებისმიერ შეცდომას (სინტაქსი, კავშირი, PDO)
            error_log("[SQL ERROR] " . $e->getMessage() . " | Query: $sql");
            return null;
        }
    }

    // --- Helper Methods ---

    public function get_results(string $sql, array $params = [], int $fetchMode = PDO::FETCH_OBJ): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt ? ($stmt->fetchAll($fetchMode) ?: []) : [];
    }

    public function get_row(string $sql, array $params = [], int $fetchMode = PDO::FETCH_OBJ)
    {
        $stmt = $this->query($sql, $params);
        return $stmt ? ($stmt->fetch($fetchMode) ?: null) : null;
    }

    public function get_var(string $sql, array $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt ? ($stmt->fetchColumn() ?: null) : null;
    }

    public function last_insert_id(): int
    {
        // 100% ინტეჯერის დაბრუნება, რომ Type Error არ მოხდეს
        if ($this->pdo) {
            $id = $this->pdo->lastInsertId();
            return is_numeric($id) ? (int)$id : 0;
        }
        return 0;
    }

    // --- Magic & Safety ---

    public function __destruct()
    {
        $this->stmt = null;
        $this->pdo  = null;
    }

    private function __clone() {}
    public function __wakeup() {}
}
?>