<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$finance = new FinanceManager();
$user_id = $auth->getUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create_account':
            $name = $_POST['name'] ?? '';
            $type = $_POST['type'] ?? '';
            $initial_balance = $_POST['initial_balance'] ?? 0;

            $stmt = $finance->db->conn->prepare("INSERT INTO accounts (user_id, name, type, initial_balance, current_balance) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issdd", $user_id, $name, $type, $initial_balance, $initial_balance);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Account created successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create account']);
            }
            break;

        case 'update_balance':
            $account_id = $_POST['account_id'] ?? '';
            $new_balance = $_POST['new_balance'] ?? 0;

            $stmt = $finance->db->conn->prepare("UPDATE accounts SET current_balance = ? WHERE id = ? AND user_id = ?");
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
            $account_id = $_GET['account_id'] ?? '';
            $balance = $finance->getAccountBalance($account_id, $user_id);

            echo json_encode(['success' => true, 'balance' => $balance]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}
