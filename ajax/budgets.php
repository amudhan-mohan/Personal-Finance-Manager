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
    // CSRF verification
    if (!isset($_POST['csrf_token']) || !$auth->validateCSRFToken($_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh and try again.']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'set_budget') {
        $category = trim($_POST['category'] ?? '');
        $monthly_limit = floatval($_POST['monthly_limit'] ?? 0);

        if (empty($category)) {
            echo json_encode(['success' => false, 'message' => 'Category is required']);
            exit;
        }

        if ($monthly_limit <= 0) {
            echo json_encode(['success' => false, 'message' => 'Budget limit must be greater than zero']);
            exit;
        }

        $result = $financeManager->setBudget($user_id, $category, $monthly_limit);
        echo json_encode($result);
        exit;
    }

    if ($action === 'delete_budget') {
        $budget_id = intval($_POST['id'] ?? 0);
        if ($budget_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid budget ID']);
            exit;
        }

        $result = $financeManager->deleteBudget($budget_id, $user_id);
        echo json_encode($result);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'performance';

    if ($action === 'performance') {
        $month = isset($_GET['month']) ? intval($_GET['month']) : null;
        $year = isset($_GET['year']) ? intval($_GET['year']) : null;
        $perf = $financeManager->getBudgetPerformance($user_id, $month, $year);
        echo json_encode(['success' => true, 'data' => $perf]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}
