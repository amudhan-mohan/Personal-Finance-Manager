<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$finance = new FinanceManager();
$user_id = $auth->getUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$auth->validateCSRFRequest()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid or missing CSRF token']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create_account':
            $name = trim($_POST['name'] ?? '');
            $type = $_POST['type'] ?? '';
            $initial_balance = floatval($_POST['initial_balance'] ?? 0);
            $credit_limit = floatval($_POST['credit_limit'] ?? 0);
            $interest_rate = floatval($_POST['interest_rate'] ?? 0);
            $due_day = !empty($_POST['due_day']) ? intval($_POST['due_day']) : null;

            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Account name is required']);
                break;
            }

            $valid_types = ['bank', 'cash', 'credit_card', 'investment', 'loan'];
            if (!in_array($type, $valid_types, true)) {
                echo json_encode(['success' => false, 'message' => 'Invalid account type']);
                break;
            }

            if ($finance->createAccount($user_id, $name, $type, $initial_balance, $credit_limit, $interest_rate, $due_day)) {
                echo json_encode(['success' => true, 'message' => 'Account created successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create account']);
            }
            break;

        case 'update_balance':
            $account_id = intval($_POST['account_id'] ?? 0);
            $new_balance = floatval($_POST['new_balance'] ?? 0);

            $account = $finance->getAccount($account_id, $user_id);
            if (!$account) {
                echo json_encode(['success' => false, 'message' => 'Account not found']);
                break;
            }

            $stmt = $finance->getDb()->conn->prepare("UPDATE accounts SET current_balance = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("dii", $new_balance, $account_id, $user_id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Balance updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update balance']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'get_accounts':
            $accounts = $finance->getAccounts($user_id);
            $account_list = [];

            while ($account = $accounts->fetch_assoc()) {
                $account_list[] = $account;
            }

            echo json_encode(['success' => true, 'accounts' => $account_list]);
            break;

        case 'get_account_balance':
            $account_id = intval($_GET['account_id'] ?? 0);
            $balance = $finance->getAccountBalance($account_id, $user_id);

            echo json_encode(['success' => true, 'balance' => $balance]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}
