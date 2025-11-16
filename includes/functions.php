<?php
require_once __DIR__ . '/../config/database.php';

class FinanceManager
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    // Get database connection (for external use if needed)
    public function getDb()
    {
        return $this->db;
    }

    // ACCOUNT METHODS
    public function getAccounts($user_id)
    {
        $stmt = $this->db->conn->prepare("SELECT * FROM accounts WHERE user_id = ? AND deleted_at IS NULL ORDER BY created_at DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getAccount($account_id, $user_id)
    {
        $stmt = $this->db->conn->prepare("SELECT * FROM accounts WHERE id = ? AND user_id = ? AND deleted_at IS NULL");
        $stmt->bind_param("ii", $account_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function getAccountBalance($account_id, $user_id)
    {
        $stmt = $this->db->conn->prepare("
            SELECT current_balance 
            FROM accounts 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->bind_param("ii", $account_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $account = $result->fetch_assoc();
        return $account ? $account['current_balance'] : 0;
    }

    public function createAccount($user_id, $name, $type, $initial_balance)
    {
        $stmt = $this->db->conn->prepare("INSERT INTO accounts (user_id, name, type, initial_balance, current_balance) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issdd", $user_id, $name, $type, $initial_balance, $initial_balance);
        return $stmt->execute();
    }

    public function updateAccount($account_id, $user_id, $name, $type)
    {
        $stmt = $this->db->conn->prepare("UPDATE accounts SET name = ?, type = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ssii", $name, $type, $account_id, $user_id);

        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Account updated successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to update account'];
        }
    }

    public function deleteAccount($account_id, $user_id)
    {
        // Check if account has transactions
        if ($this->hasTransactions($account_id)) {
            return ['success' => false, 'message' => 'Cannot delete account with transactions'];
        }

        // Check if account has transfers
        $stmt = $this->db->conn->prepare("SELECT COUNT(*) as count FROM transfers WHERE (from_account_id = ? OR to_account_id = ?) AND deleted_at IS NULL");
        $stmt->bind_param("ii", $account_id, $account_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result['count'] > 0) {
            return ['success' => false, 'message' => 'Cannot delete account with transfer history'];
        }

        // Soft delete account
        $stmt = $this->db->conn->prepare("UPDATE accounts SET deleted_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $account_id, $user_id);

        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Account deleted successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to delete account'];
        }
    }

    public function hasTransactions($account_id)
    {
        $stmt = $this->db->conn->prepare("SELECT COUNT(*) as count FROM transactions WHERE account_id = ? AND deleted_at IS NULL");
        $stmt->bind_param("i", $account_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'] > 0;
    }

    public function getTotalBalance($user_id)
    {
        $stmt = $this->db->conn->prepare("
            SELECT 
                SUM(CASE WHEN type = 'bank' THEN current_balance ELSE 0 END) as bank_balance,
                SUM(CASE WHEN type = 'cash' THEN current_balance ELSE 0 END) as cash_balance,
                SUM(current_balance) as total_balance
            FROM accounts 
            WHERE user_id = ? AND deleted_at IS NULL
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // TRANSACTION METHODS
    public function getTransactions($user_id, $filters = [])
    {
        $sql = "SELECT t.*, a.name as account_name 
                FROM transactions t 
                JOIN accounts a ON t.account_id = a.id 
                WHERE t.user_id = ? AND t.deleted_at IS NULL";
        $params = ["i"];
        $param_values = [$user_id];

        if (!empty($filters['account_id'])) {
            $sql .= " AND t.account_id = ?";
            $params[0] .= "i";
            $param_values[] = $filters['account_id'];
        }

        if (!empty($filters['type'])) {
            $sql .= " AND t.type = ?";
            $params[0] .= "s";
            $param_values[] = $filters['type'];
        }

        if (!empty($filters['start_date'])) {
            $sql .= " AND t.transaction_date >= ?";
            $params[0] .= "s";
            $param_values[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $sql .= " AND t.transaction_date <= ?";
            $params[0] .= "s";
            $param_values[] = $filters['end_date'];
        }

        // Handle limit separately as it's not a parameter for WHERE clause
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT ?";
            $params[0] .= "i";
            $param_values[] = $filters['limit'];
        }

        $sql .= " ORDER BY t.transaction_date DESC, t.created_at DESC";

        $stmt = $this->db->conn->prepare($sql);

        // Only bind parameters if we have more than just the user_id
        if (count($param_values) > 1) {
            $stmt->bind_param(...array_merge($params, $param_values));
        } else {
            $stmt->bind_param($params[0], $param_values[0]);
        }

        $stmt->execute();
        return $stmt->get_result();
    }

    public function getTransaction($transaction_id, $user_id)
    {
        $stmt = $this->db->conn->prepare("
            SELECT t.*, a.name as account_name, a.type as account_type 
            FROM transactions t 
            JOIN accounts a ON t.account_id = a.id 
            WHERE t.id = ? AND t.user_id = ? AND t.deleted_at IS NULL
        ");
        $stmt->bind_param("ii", $transaction_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function addTransaction($user_id, $account_id, $type, $amount, $description, $category, $transaction_date)
    {
        // Start transaction
        $this->db->conn->begin_transaction();

        try {
            // Insert transaction
            $stmt = $this->db->conn->prepare("
                INSERT INTO transactions (user_id, account_id, type, amount, description, category, transaction_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iisdsss", $user_id, $account_id, $type, $amount, $description, $category, $transaction_date);
            $stmt->execute();

            // Update account balance
            $balance_change = ($type === 'income') ? $amount : -$amount;
            $stmt = $this->db->conn->prepare("
                UPDATE accounts 
                SET current_balance = current_balance + ? 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->bind_param("dii", $balance_change, $account_id, $user_id);
            $stmt->execute();

            $this->db->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->db->conn->rollback();
            return false;
        }
    }

    public function updateTransaction($transaction_id, $user_id, $account_id, $type, $amount, $description, $category, $transaction_date)
    {
        // Get old transaction details first
        $old_transaction = $this->getTransaction($transaction_id, $user_id);
        if (!$old_transaction) {
            return ['success' => false, 'message' => 'Transaction not found'];
        }

        $this->db->conn->begin_transaction();

        try {
            // Reverse old transaction effect
            $old_balance_change = ($old_transaction['type'] === 'income') ? -$old_transaction['amount'] : $old_transaction['amount'];
            $stmt = $this->db->conn->prepare("UPDATE accounts SET current_balance = current_balance + ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("dii", $old_balance_change, $old_transaction['account_id'], $user_id);
            $stmt->execute();

            // Apply new transaction effect
            $new_balance_change = ($type === 'income') ? $amount : -$amount;
            $stmt = $this->db->conn->prepare("UPDATE accounts SET current_balance = current_balance + ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("dii", $new_balance_change, $account_id, $user_id);
            $stmt->execute();

            // Update transaction
            $stmt = $this->db->conn->prepare("
                UPDATE transactions 
                SET account_id = ?, type = ?, amount = ?, description = ?, category = ?, transaction_date = ? 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->bind_param("isdsssii", $account_id, $type, $amount, $description, $category, $transaction_date, $transaction_id, $user_id);
            $stmt->execute();

            $this->db->conn->commit();
            return ['success' => true, 'message' => 'Transaction updated successfully'];
        } catch (Exception $e) {
            $this->db->conn->rollback();
            return ['success' => false, 'message' => 'Failed to update transaction: ' . $e->getMessage()];
        }
    }

    public function deleteTransaction($transaction_id, $user_id)
    {
        $transaction = $this->getTransaction($transaction_id, $user_id);
        if (!$transaction) {
            return ['success' => false, 'message' => 'Transaction not found'];
        }

        $this->db->conn->begin_transaction();

        try {
            // Reverse transaction effect on account balance
            $balance_change = ($transaction['type'] === 'income') ? -$transaction['amount'] : $transaction['amount'];
            $stmt = $this->db->conn->prepare("UPDATE accounts SET current_balance = current_balance + ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("dii", $balance_change, $transaction['account_id'], $user_id);
            $stmt->execute();

            // Soft delete transaction
            $stmt = $this->db->conn->prepare("UPDATE transactions SET deleted_at = NOW() WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $transaction_id, $user_id);
            $stmt->execute();

            $this->db->conn->commit();
            return ['success' => true, 'message' => 'Transaction deleted successfully'];
        } catch (Exception $e) {
            $this->db->conn->rollback();
            return ['success' => false, 'message' => 'Failed to delete transaction: ' . $e->getMessage()];
        }
    }

    public function getRecentTransactions($user_id, $limit = 5)
    {
        $stmt = $this->db->conn->prepare("
            SELECT t.*, a.name as account_name 
            FROM transactions t 
            JOIN accounts a ON t.account_id = a.id 
            WHERE t.user_id = ? AND t.deleted_at IS NULL
            ORDER BY t.transaction_date DESC, t.created_at DESC 
            LIMIT ?
        ");
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        return $stmt->get_result();
    }

    // TRANSFER METHODS
    public function transferFunds($user_id, $from_account_id, $to_account_id, $amount, $description, $transfer_date)
    {
        // Validate transfer
        if ($from_account_id == $to_account_id) {
            return ['success' => false, 'message' => 'Cannot transfer to the same account'];
        }

        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Transfer amount must be positive'];
        }

        // Check if from account has sufficient balance
        $from_balance = $this->getAccountBalance($from_account_id, $user_id);
        if ($from_balance < $amount) {
            return ['success' => false, 'message' => 'Insufficient balance in source account'];
        }

        // Check if both accounts belong to the user
        $stmt = $this->db->conn->prepare("SELECT id FROM accounts WHERE id IN (?, ?) AND user_id = ? AND deleted_at IS NULL");
        $stmt->bind_param("iii", $from_account_id, $to_account_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows != 2) {
            return ['success' => false, 'message' => 'Invalid accounts'];
        }

        // Start transaction
        $this->db->conn->begin_transaction();

        try {
            // Deduct from source account
            $stmt = $this->db->conn->prepare("UPDATE accounts SET current_balance = current_balance - ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("dii", $amount, $from_account_id, $user_id);
            $stmt->execute();

            // Add to destination account
            $stmt = $this->db->conn->prepare("UPDATE accounts SET current_balance = current_balance + ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("dii", $amount, $to_account_id, $user_id);
            $stmt->execute();

            // Record the transfer
            $stmt = $this->db->conn->prepare("INSERT INTO transfers (user_id, from_account_id, to_account_id, amount, description, transfer_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiidss", $user_id, $from_account_id, $to_account_id, $amount, $description, $transfer_date);
            $stmt->execute();

            $this->db->conn->commit();
            return ['success' => true, 'message' => 'Transfer completed successfully'];
        } catch (Exception $e) {
            $this->db->conn->rollback();
            return ['success' => false, 'message' => 'Transfer failed: ' . $e->getMessage()];
        }
    }

    public function getTransfers($user_id, $filters = [])
    {
        $sql = "SELECT t.*, 
                       fa.name as from_account_name, fa.type as from_account_type,
                       ta.name as to_account_name, ta.type as to_account_type
                FROM transfers t
                JOIN accounts fa ON t.from_account_id = fa.id
                JOIN accounts ta ON t.to_account_id = ta.id
                WHERE t.user_id = ? AND t.deleted_at IS NULL";

        $params = ["i"];
        $param_values = [$user_id];

        if (!empty($filters['account_id'])) {
            $sql .= " AND (t.from_account_id = ? OR t.to_account_id = ?)";
            $params[0] .= "ii";
            $param_values[] = $filters['account_id'];
            $param_values[] = $filters['account_id'];
        }

        if (!empty($filters['start_date'])) {
            $sql .= " AND t.transfer_date >= ?";
            $params[0] .= "s";
            $param_values[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $sql .= " AND t.transfer_date <= ?";
            $params[0] .= "s";
            $param_values[] = $filters['end_date'];
        }

        $sql .= " ORDER BY t.transfer_date DESC, t.created_at DESC";

        $stmt = $this->db->conn->prepare($sql);

        if (count($param_values) > 1) {
            $stmt->bind_param(...array_merge($params, $param_values));
        } else {
            $stmt->bind_param($params[0], $param_values[0]);
        }

        $stmt->execute();
        return $stmt->get_result();
    }

    public function getTransfer($transfer_id, $user_id)
    {
        $stmt = $this->db->conn->prepare("
            SELECT t.*, 
                   fa.name as from_account_name, fa.type as from_account_type,
                   ta.name as to_account_name, ta.type as to_account_type
            FROM transfers t
            JOIN accounts fa ON t.from_account_id = fa.id
            JOIN accounts ta ON t.to_account_id = ta.id
            WHERE t.id = ? AND t.user_id = ? AND t.deleted_at IS NULL
        ");
        $stmt->bind_param("ii", $transfer_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function updateTransfer($transfer_id, $user_id, $from_account_id, $to_account_id, $amount, $description, $transfer_date)
    {
        $old_transfer = $this->getTransfer($transfer_id, $user_id);
        if (!$old_transfer) {
            return ['success' => false, 'message' => 'Transfer not found'];
        }

        if ($from_account_id == $to_account_id) {
            return ['success' => false, 'message' => 'Cannot transfer to the same account'];
        }

        $this->db->conn->begin_transaction();

        try {
            // Reverse old transfer
            $stmt = $this->db->conn->prepare("UPDATE accounts SET current_balance = current_balance + ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("dii", $old_transfer['amount'], $old_transfer['from_account_id'], $user_id);
            $stmt->execute();

            $stmt = $this->db->conn->prepare("UPDATE accounts SET current_balance = current_balance - ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("dii", $old_transfer['amount'], $old_transfer['to_account_id'], $user_id);
            $stmt->execute();

            // Apply new transfer
            $stmt = $this->db->conn->prepare("UPDATE accounts SET current_balance = current_balance - ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("dii", $amount, $from_account_id, $user_id);
            $stmt->execute();

            $stmt = $this->db->conn->prepare("UPDATE accounts SET current_balance = current_balance + ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("dii", $amount, $to_account_id, $user_id);
            $stmt->execute();

            // Update transfer record
            $stmt = $this->db->conn->prepare("
                UPDATE transfers 
                SET from_account_id = ?, to_account_id = ?, amount = ?, description = ?, transfer_date = ? 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->bind_param("iidssii", $from_account_id, $to_account_id, $amount, $description, $transfer_date, $transfer_id, $user_id);
            $stmt->execute();

            $this->db->conn->commit();
            return ['success' => true, 'message' => 'Transfer updated successfully'];
        } catch (Exception $e) {
            $this->db->conn->rollback();
            return ['success' => false, 'message' => 'Failed to update transfer: ' . $e->getMessage()];
        }
    }

    public function deleteTransfer($transfer_id, $user_id)
    {
        $transfer = $this->getTransfer($transfer_id, $user_id);
        if (!$transfer) {
            return ['success' => false, 'message' => 'Transfer not found'];
        }

        $this->db->conn->begin_transaction();

        try {
            // Reverse transfer effect
            $stmt = $this->db->conn->prepare("UPDATE accounts SET current_balance = current_balance + ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("dii", $transfer['amount'], $transfer['from_account_id'], $user_id);
            $stmt->execute();

            $stmt = $this->db->conn->prepare("UPDATE accounts SET current_balance = current_balance - ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("dii", $transfer['amount'], $transfer['to_account_id'], $user_id);
            $stmt->execute();

            // Soft delete transfer
            $stmt = $this->db->conn->prepare("UPDATE transfers SET deleted_at = NOW() WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $transfer_id, $user_id);
            $stmt->execute();

            $this->db->conn->commit();
            return ['success' => true, 'message' => 'Transfer deleted successfully'];
        } catch (Exception $e) {
            $this->db->conn->rollback();
            return ['success' => false, 'message' => 'Failed to delete transfer: ' . $e->getMessage()];
        }
    }

    public function getTransferHistory($user_id, $limit = 10)
    {
        $stmt = $this->db->conn->prepare("
            SELECT t.*, 
                   fa.name as from_account_name, fa.type as from_account_type,
                   ta.name as to_account_name, ta.type as to_account_type
            FROM transfers t
            JOIN accounts fa ON t.from_account_id = fa.id
            JOIN accounts ta ON t.to_account_id = ta.id
            WHERE t.user_id = ? AND t.deleted_at IS NULL
            ORDER BY t.transfer_date DESC, t.created_at DESC
            LIMIT ?
        ");
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        return $stmt->get_result();
    }
}
function rupee_icon($class = "h-4 w-4", $color = "currentColor")
{
    return '<svg class="' . $class . '" xmlns="http://www.w3.org/2000/svg" version="1.0" viewBox="40 -1 170 250" fill="' . $color . '">
        <path d="M153 23h41l15-23H55L40 23h26c27 0 52 2 62 25H55L40 71h91v1c0 17-14 43-60 43H48v22l90 113h41L85 133c39-2 75-24 80-62h29l15-23h-45c-1-9-5-18-11-25z"/>
    </svg>';
}
