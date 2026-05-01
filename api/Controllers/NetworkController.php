<?php
require_once __DIR__ . '/../Models/User.php';

class NetworkController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function activeNodes() {
        session_start();
        if(isset($_SESSION['user_id'])) {
            $this->userModel->updateLastSeen($_SESSION['user_id']);
        }
        $count = $this->userModel->getActiveNodes();
        return ['success' => true, 'active_nodes' => $count];
    }
}
