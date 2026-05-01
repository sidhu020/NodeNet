<?php
class Database {
    private static $instance = null;
    private $conn;

    private $host = '127.0.0.1';
    private $user = 'root';
    private $pass = '';
    private $name = 'lab_network';

    private function __construct() {
        try {
            $this->conn = new PDO("mysql:host={$this->host};dbname={$this->name}", $this->user, $this->pass);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            die(json_encode(["error" => "Database connection failed: " . $e->getMessage()]));
        }
    }

    public static function getInstance() {
        if(!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance->conn;
    }
}
