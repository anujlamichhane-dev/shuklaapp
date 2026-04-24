<?php

class Database {

    private static $instance = null;

    private static $host = 'localhost';
    private static $user = 'shuklaap_helpdeskuser';
    private static $password = 'Kushum123!';
    private static $db = 'shuklaap_helpdesk';

    private static function config(string $name, string $fallback): string
    {
        $value = getenv($name);
        return ($value !== false && $value !== '') ? $value : $fallback;
    }

    public static function getInstance() {

        if (self::$instance === null) {

            self::$instance = new mysqli(
                self::config('DB_HOST', self::$host),
                self::config('DB_USER', self::$user),
                self::config('DB_PASS', self::$password),
                self::config('DB_NAME', self::$db)
            );

            if (self::$instance->connect_error) {
                error_log('Database connection failed: ' . self::$instance->connect_error);
                die('Database connection failed.');
            }

            self::$instance->set_charset("utf8mb4");
        }

        return self::$instance;
    }
}
