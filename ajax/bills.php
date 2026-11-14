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

    if ($action === 'add_bill') {
        $title = trim($_POST['title'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        $category = trim($_POST['category'] ?? 'Utilities & Bills');
        $frequency = trim($_POST['frequency'] ?? 'monthly');
        $next_due_date = trim($_POST['next_due_date'] ?? '');
        $account_id = !empty($_POST['account_id']) ? intval($_POST['account_id']) : null;

        if (empty($title)) {
            echo json_encode(['success' => false, 'message' => 'Bill title is required']);
            exit;
        }

        if ($amount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Amount must be greater than zero']);
            exit;
        }

        if (empty($next_due_date)) {
            echo json_encode(['success' => false, 'message' => 'Next due date is required']);
            exit;
        }

        $valid_freqs = ['weekly', 'monthly', 'quarterly', 'yearly'];
        if (!in_array($frequency, $valid_freqs, true)) {
            $frequency = 'monthly';
        }

        $res = $financeManager->addRecurringBill($user_id, $account_id, $title, $amount, $category, $frequency, $next_due_date);
        echo json_encode($res);
        exit;
    }

    if ($action === 'update_bill') {
        $bill_id = intval($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        $category = trim($_POST['category'] ?? 'Utilities & Bills');
        $frequency = trim($_POST['frequency'] ?? 'monthly');
        $next_due_date = trim($_POST['next_due_date'] ?? '');
        $status = trim($_POST['status'] ?? 'active');
        $account_id = !empty($_POST['account_id']) ? intval($_POST['account_id']) : null;

        if ($bill_id <= 0 || empty($title) || $amount <= 0 || empty($next_due_date)) {
            echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
            exit;
        }

        $res = $financeManager->updateRecurringBill($bill_id, $user_id, $account_id, $title, $amount, $category, $frequency, $next_due_date, $status);
        echo json_encode($res);
        exit;
    }

    if ($action === 'delete_bill') {
        $bill_id = intval($_POST['id'] ?? 0);
        if ($bill_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid bill ID']);
            exit;
        }

        $res = $financeManager->deleteRecurringBill($bill_id, $user_id);
        echo json_encode($res);
        exit;
    }

    if ($action === 'mark_paid') {
        $bill_id = intval($_POST['id'] ?? 0);
        if ($bill_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid bill ID']);
            exit;
        }

        $res = $financeManager->markBillAsPaid($bill_id, $user_id);
        echo json_encode($res);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'get';
    if ($action === 'get') {
        $bill_id = intval($_GET['id'] ?? 0);
        $bill = $financeManager->getRecurringBill($bill_id, $user_id);
        if ($bill) {
            echo json_encode(['success' => true, 'bill' => $bill]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Bill not found']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}
