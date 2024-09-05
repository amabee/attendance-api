<?php

class DatabaseConnection
{
    private $server = "localhost";
    private $username = "root";
    private $password = "";
    private $database = "it_days_attendance";
    private static $instance = null;
    private $conn;

    private function __construct()
    {
        $this->conn = new PDO('mysql:host=' . $this->server . ';dbname=' . $this->database, $this->username, $this->password);
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new DatabaseConnection();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->conn;
    }
}