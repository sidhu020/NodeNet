<?php
require_once __DIR__ . '/../Config/Database.php';

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function register($username, $password) {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if($stmt->rowCount() > 0) return false;

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO users (username, password_hash, last_seen) VALUES (?, ?, NOW())");
        if($stmt->execute([$username, $hash])) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function login($username, $password) {
        $stmt = $this->db->prepare("SELECT id, password_hash FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if(password_verify($password, $row['password_hash'])) {
                $this->updateLastSeen($row['id']);
                return $row['id'];
            }
        }
        return false;
    }

    public function updateLastSeen($user_id) {
        $stmt = $this->db->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?");
        $stmt->execute([$user_id]);
    }

    public function getActiveNodes() {
        $stmt = $this->db->prepare("SELECT COUNT(*) as active_nodes FROM users WHERE last_seen > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['active_nodes'];
    }

    public function getUsername($user_id) {
        $stmt = $this->db->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['username'] : null;
    }
}
