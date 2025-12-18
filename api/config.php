<?php
// api/config.php

// เช็คก่อนว่า session เริ่มแล้วหรือยัง
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database connection
class Database {
    private static $instance = null;

    public static function connect() {
        if (self::$instance === null) {
            self::$instance = new PDO(
                "mysql:host=localhost;dbname=motorcycle_rental;charset=utf8",
                "Mayochiki_MN",
                "Mayochiki@2003"
            );
            self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return self::$instance;
    }
}
