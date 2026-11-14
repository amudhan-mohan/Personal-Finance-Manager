<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $auth->getUserId();
$financeManager = new FinanceManager();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !$auth->validateCSRFToken($_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh and try again.']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add_goal') {
        $title = trim($_POST['title'] ?? '');
        $target_amount = floatval($_POST['target_amount'] ?? 0);
        $current_amount = floatval($_POST['current_amount'] ?? 0);
        $target_date = !empty($_POST['target_date']) ? trim($_POST['target_date']) : null;

        if (empty($title)) {
            echo json_encode(['success' => false, 'message' => 'Goal title is required']);
            exit;
        }

        if ($target_amount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Target amount must be greater than zero']);
            exit;
        }

        $res = $financeManager->addSavingsGoal($user_id, $title, $target_amount, $current_amount, $target_date);
        echo json_encode($res);
        exit;
    }

    if ($action === 'update_goal') {
        $goal_id = intval($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $target_amount = floatval($_POST['target_amount'] ?? 0);
        $current_amount = floatval($_POST['current_amount'] ?? 0);
        $target_date = !empty($_POST['target_date']) ? trim($_POST['target_date']) : null;

        if ($goal_id <= 0 || empty($title) || $target_amount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Please provide all required fields']);
            exit;
        }

        $res = $financeManager->updateSavingsGoal($goal_id, $user_id, $title, $target_amount, $current_amount, $target_date);
        echo json_encode($res);
        exit;
    }

    if ($action === 'delete_goal') {
        $goal_id = intval($_POST['id'] ?? 0);
        if ($goal_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid goal ID']);
            exit;
        }

        $res = $financeManager->deleteSavingsGoal($goal_id, $user_id);
        echo json_encode($res);
        exit;
    }

    if ($action === 'contribute') {
        $goal_id = intval($_POST['id'] ?? 0);
        $account_id = intval($_POST['account_id'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);
        $note = trim($_POST['note'] ?? '');

        if ($goal_id <= 0 || $account_id <= 0 || $amount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid contribution parameters']);
            exit;
        }

        $res = $financeManager->contributeToGoal($goal_id, $user_id, $account_id, $amount, $note);
        echo json_encode($res);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'get';
    if ($action === 'get') {
        $goal_id = intval($_GET['id'] ?? 0);
        $goal = $financeManager->getSavingsGoal($goal_id, $user_id);
        if ($goal) {
            echo json_encode(['success' => true, 'goal' => $goal]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Goal not found']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}
