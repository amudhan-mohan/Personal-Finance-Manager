<?php
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new Auth();
    $login = (string)($_POST['login'] ?? ''); 
    $password = (string)($_POST['password'] ?? '');
    $csrf_token = (string)($_POST['csrf_token'] ?? '');

    $result = $auth->login($login, $password, $csrf_token);
    if (!$result['success']) {
        http_response_code(401);
    }
    echo json_encode($result);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
