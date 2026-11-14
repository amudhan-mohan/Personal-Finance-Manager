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
        case 'add_transaction':
            $account_id = intval($_POST['account_id'] ?? 0);
            $type = $_POST['type'] ?? '';
            $amount = floatval($_POST['amount'] ?? 0);
            $description = trim($_POST['description'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $transaction_date = $_POST['transaction_date'] ?? date('Y-m-d');

            if ($amount <= 0) {
                echo json_encode(['success' => false, 'message' => 'Amount must be greater than zero']);
                break;
            }

            if (!in_array($type, ['income', 'expense'], true)) {
                echo json_encode(['success' => false, 'message' => 'Invalid transaction type']);
                break;
            }

            if ($finance->addTransaction($user_id, $account_id, $type, $amount, $description, $category, $transaction_date)) {
                echo json_encode(['success' => true, 'message' => 'Transaction added successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add transaction. Please verify the selected account.']);
            }
            break;

        case 'update_transaction':
            $transaction_id = intval($_POST['transaction_id'] ?? 0);
            $account_id = intval($_POST['account_id'] ?? 0);
            $type = $_POST['type'] ?? '';
            $amount = floatval($_POST['amount'] ?? 0);
            $description = trim($_POST['description'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $transaction_date = $_POST['transaction_date'] ?? date('Y-m-d');

            if ($amount <= 0) {
                echo json_encode(['success' => false, 'message' => 'Amount must be greater than zero']);
                break;
            }

            if (!in_array($type, ['income', 'expense'], true)) {
                echo json_encode(['success' => false, 'message' => 'Invalid transaction type']);
                break;
            }

            $result = $finance->updateTransaction($transaction_id, $user_id, $account_id, $type, $amount, $description, $category, $transaction_date);
            echo json_encode($result);
            break;

        case 'delete_transaction':
            $transaction_id = intval($_POST['transaction_id'] ?? 0);
            $result = $finance->deleteTransaction($transaction_id, $user_id);
            echo json_encode($result);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'get_transactions':
            $filters = [
                'account_id' => $_GET['account_id'] ?? '',
                'type' => $_GET['type'] ?? '',
                'start_date' => $_GET['start_date'] ?? '',
                'end_date' => $_GET['end_date'] ?? ''
            ];

            $transactions = $finance->getTransactions($user_id, $filters);
            $transaction_list = [];

            while ($transaction = $transactions->fetch_assoc()) {
                $transaction_list[] = $transaction;
            }

            echo json_encode(['success' => true, 'transactions' => $transaction_list]);
            break;

        case 'get_transaction':
            $transaction_id = intval($_GET['transaction_id'] ?? 0);
            $transaction = $finance->getTransaction($transaction_id, $user_id);

            if ($transaction) {
                echo json_encode(['success' => true, 'transaction' => $transaction]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Transaction not found']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}
