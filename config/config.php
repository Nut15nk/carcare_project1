<?php
// config/config.php
// แก้ค่าตาม environment ของคุณ

define('IMGBB_API_KEY', 'ca1491832f92840f671270432d9afd85');

class Database
{
    private static $host    = 'localhost';
    private static $db      = 'motorcycle_rental_new';
    private static $user    = 'Mayochiki_MN';
    private static $pass    = 'Mayochiki@2003';
    private static $charset = 'utf8mb4';
    private static $pdo     = null;

    public static function connect()
    {
        if (self::$pdo) {
            return self::$pdo;
        }

        $dsn     = "mysql:host=" . self::$host . ";dbname=" . self::$db . ";charset=" . self::$charset;
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
