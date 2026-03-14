<?php

class Database {

    private static $instance = null;

    private static $host = 'localhost';
    private static $user = 'shuklaap_helpdeskuser';   // Your MySQL user
    private static $password = 'Kushum123!'; // <-- change this
    private static $db = 'shuklaap_helpdesk';         // Your database name

    public static function getInstance() {

        if (self::$instance === null) {

            self::$instance = new mysqli(
                self::$host,
                self::$user,
                self::$password,
                self::$db
            );

            if (self::$instance->connect_error) {
                die("Database connection failed: " . self::$instance->connect_error);
            }

            self::$instance->set_charset("utf8mb4");
        }

        return self::$instance;
    }
}
