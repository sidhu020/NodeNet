<?php
require_once __DIR__ . '/../Models/User.php';

class AuthController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function register($data) {
        if(empty($data['username']) || empty($data['password'])) {
            return ['success' => false, 'message' => 'Username and password required'];
        }

        $userId = $this->userModel->register($data['username'], $data['password']);
        if($userId) {
            session_start();
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $data['username'];
            return ['success' => true, 'message' => 'Registered successfully'];
        }
        return ['success' => false, 'message' => 'Username already exists'];
    }

    public function login($data) {
        if(empty($data['username']) || empty($data['password'])) {
            return ['success' => false, 'message' => 'Username and password required'];
        }

        $userId = $this->userModel->login($data['username'], $data['password']);
        if($userId) {
            session_start();
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $data['username'];
            return ['success' => true, 'message' => 'Logged in successfully'];
        }
        return ['success' => false, 'message' => 'Invalid credentials'];
    }

    public function logout() {
        session_start();
        session_destroy();
        return ['success' => true, 'message' => 'Logged out'];
    }
    
    public function session() {
        session_start();
        if(isset($_SESSION['user_id'])) {
            $this->userModel->updateLastSeen($_SESSION['user_id']);
            return ['success' => true, 'user' => ['id' => $_SESSION['user_id'], 'username' => $_SESSION['username']]];
        }
        return ['success' => false];
    }
}
