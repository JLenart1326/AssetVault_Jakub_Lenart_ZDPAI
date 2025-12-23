<?php

class Database
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        $host = 'db'; // nazwa usługi z docker-compose.yml
        $dbname = 'assetvault'; // nazwa bazy danych
        $username = 'assetuser';    // użytkownik bazy
        $password = 'assetpass';  

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $this->pdo = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password, $options);
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->pdo;
    }
}