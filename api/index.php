<?php
header('Content-Type: application/json');

$request_uri = $_SERVER['REQUEST_URI'];
$base_path = '/antigravityXAMPP/api/';
$path = str_replace($base_path, '', explode('?', $request_uri)[0]);
$method = $_SERVER['REQUEST_METHOD'];

require_once __DIR__ . '/Controllers/AuthController.php';
require_once __DIR__ . '/Controllers/FileController.php';
require_once __DIR__ . '/Controllers/NetworkController.php';

$authController = new AuthController();
$fileController = new FileController();
$networkController = new NetworkController();

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

if ($path == 'auth/register' && $method == 'POST') {
    echo json_encode($authController->register($data));
} elseif ($path == 'auth/login' && $method == 'POST') {
    echo json_encode($authController->login($data));
} elseif ($path == 'auth/logout') {
    echo json_encode($authController->logout());
} elseif ($path == 'auth/session') {
    echo json_encode($authController->session());
} elseif ($path == 'nodes/active') {
    echo json_encode($networkController->activeNodes());
} elseif ($path == 'files/list' && $method == 'POST') {
    echo json_encode($fileController->listNodes($data));
} elseif ($path == 'files/public') {
    echo json_encode($fileController->publicNodes());
} elseif ($path == 'files/create' && $method == 'POST') {
    echo json_encode($fileController->createNode($data));
} elseif ($path == 'files/read' && $method == 'POST') {
    echo json_encode($fileController->readFile($data));
} elseif ($path == 'files/save' && $method == 'POST') {
    echo json_encode($fileController->saveFile($data));
} elseif ($path == 'files/permission' && $method == 'POST') {
    echo json_encode($fileController->updatePermission($data));
} elseif ($path == 'files/delete' && $method == 'POST') {
    echo json_encode($fileController->deleteNode($data));
} elseif ($path == 'files/rename' && $method == 'POST') {
    echo json_encode($fileController->rename($data));
} elseif ($path == 'files/move' && $method == 'POST') {
    echo json_encode($fileController->move($data));
} elseif ($path == 'files/upload' && $method == 'POST') {
    echo json_encode($fileController->upload());
} elseif ($path == 'files/download' && $method == 'GET') {
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    if($id) {
        $fileController->download($id);
    } else {
        echo json_encode(['success' => false, 'message' => 'No ID provided']);
    }
} else {
    header("HTTP/1.0 404 Not Found");
    echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
}
