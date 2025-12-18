<?php
// config/config.php
// แก้ค่าตาม environment ของคุณ
class Database {
    private static $host = 'localhost';
    private static $db   = 'motorcycle_rental';
    private static $user = 'root';
    private static $pass = '';
    private static $charset = 'utf8mb4';
    private static $pdo = null;

    public static function connect() {
        if (self::$pdo) return self::$pdo;
        $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$db . ";charset=" . self::$charset;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            self::$pdo = new PDO($dsn, self::$user, self::$pass, $options);
            return self::$pdo;
        } catch (PDOException $e) {
            error_log("DB Connection error: " . $e->getMessage());
            throw $e;
        }
    }
}