<?php
/**
 * mia/config/Database.php
 *
 * Centralized database connection (singleton PDO).
 */

declare(strict_types=1);

class Database
{
    private static ?PDO $instance = null;

    private const HOST = 'localhost';
    private const NAME = 'mia_db';
    private const USER = 'miauser';
    private const PASS = 'MiaPass2026!';

    public static function get(): PDO
    {
        if (self::$instance === null) {
            $host = defined('DB_HOST') ? DB_HOST : 'localhost';
            $name = defined('DB_NAME') ? DB_NAME : 'mia_db';
            $user = defined('DB_USER') ? DB_USER : 'miauser';
            $pass = defined('DB_PASS') ? DB_PASS : 'MiaPass2026!';
            self::$instance = new PDO(
                'mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4',
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        }
        return self::$instance;
    }

    private function __construct() {}
}
