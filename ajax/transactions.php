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
        case 'add_transaction':
            $account_id = $_POST['account_id'] ?? '';
            $type = $_POST['type'] ?? '';
            $amount = $_POST['amount'] ?? 0;
            $description = $_POST['description'] ?? '';
            $category = $_POST['category'] ?? '';
            $transaction_date = $_POST['transaction_date'] ?? date('Y-m-d');

            if ($finance->addTransaction($user_id, $account_id, $type, $amount, $description, $category, $transaction_date)) {
                echo json_encode(['success' => true, 'message' => 'Transaction added successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add transaction']);
            }
            break;

        case 'update_transaction':
            $transaction_id = $_POST['transaction_id'] ?? '';
            $account_id = $_POST['account_id'] ?? '';
            $type = $_POST['type'] ?? '';
            $amount = $_POST['amount'] ?? 0;
            $description = $_POST['description'] ?? '';
            $category = $_POST['category'] ?? '';
            $transaction_date = $_POST['transaction_date'] ?? '';

            // First get the old transaction details
            $stmt = $finance->db->conn->prepare("SELECT * FROM transactions WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $transaction_id, $user_id);
            $stmt->execute();
            $old_transaction = $stmt->get_result()->fetch_assoc();

            if (!$old_transaction) {
                echo json_encode(['success' => false, 'message' => 'Transaction not found']);
                break;
            }

            // Start transaction for data consistency
            $finance->db->conn->begin_transaction();

            try {
                // Reverse old transaction effect
                $old_balance_change = ($old_transaction['type'] === 'income') ? -$old_transaction['amount'] : $old_transaction['amount'];
                $stmt = $finance->db->conn->prepare("UPDATE accounts SET current_balance = current_balance + ? WHERE id = ? AND user_id = ?");
                $stmt->bind_param("dii", $old_balance_change, $old_transaction['account_id'], $user_id);
                $stmt->execute();

                // Update transaction
                $stmt = $finance->db->conn->prepare("
                    UPDATE transactions 
                    SET account_id = ?, type = ?, amount = ?, description = ?, category = ?, transaction_date = ? 
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->bind_param("isdsssii", $account_id, $type, $amount, $description, $category, $transaction_date, $transaction_id, $user_id);
                $stmt->execute();

                // Apply new transaction effect
                $new_balance_change = ($type === 'income') ? $amount : -$amount;
                $stmt = $finance->db->conn->prepare("UPDATE accounts SET current_balance = current_balance + ? WHERE id = ? AND user_id = ?");
                $stmt->bind_param("dii", $new_balance_change, $account_id, $user_id);
                $stmt->execute();

                $finance->db->conn->commit();
                echo json_encode(['success' => true, 'message' => 'Transaction updated successfully']);
            } catch (Exception $e) {
                $finance->db->conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Failed to update transaction']);
            }
            break;

        case 'delete_transaction':
            $transaction_id = $_POST['transaction_id'] ?? '';

            // First get transaction details
            $stmt = $finance->db->conn->prepare("SELECT * FROM transactions WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $transaction_id, $user_id);
            $stmt->execute();
            $transaction = $stmt->get_result()->fetch_assoc();

            if (!$transaction) {
                echo json_encode(['success' => false, 'message' => 'Transaction not found']);
                break;
            }

            // Start transaction
            $finance->db->conn->begin_transaction();

            try {
                // Reverse transaction effect on account balance
                $balance_change = ($transaction['type'] === 'income') ? -$transaction['amount'] : $transaction['amount'];
                $stmt = $finance->db->conn->prepare("UPDATE accounts SET current_balance = current_balance + ? WHERE id = ? AND user_id = ?");
                $stmt->bind_param("dii", $balance_change, $transaction['account_id'], $user_id);
                $stmt->execute();

                // Delete transaction
                $stmt = $finance->db->conn->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
                $stmt->bind_param("ii", $transaction_id, $user_id);
                $stmt->execute();

                $finance->db->conn->commit();
                echo json_encode(['success' => true, 'message' => 'Transaction deleted successfully']);
            } catch (Exception $e) {
                $finance->db->conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Failed to delete transaction']);
            }
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
            $transaction_id = $_GET['transaction_id'] ?? '';

            $stmt = $finance->db->conn->prepare("SELECT * FROM transactions WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $transaction_id, $user_id);
            $stmt->execute();
            $transaction = $stmt->get_result()->fetch_assoc();

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
