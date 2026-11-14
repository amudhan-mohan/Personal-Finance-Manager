<?php
require_once __DIR__ . '/../config/database.php';

class FinanceManager
{
    public $db;

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

    public function createAccount($user_id, $name, $type, $initial_balance, $credit_limit = 0.00, $interest_rate = 0.00, $due_day = null)
    {
        $credit_limit = (float)$credit_limit;
        $interest_rate = (float)$interest_rate;
        $due_day = !empty($due_day) ? (int)$due_day : null;
        $stmt = $this->db->conn->prepare("INSERT INTO accounts (user_id, name, type, initial_balance, current_balance, credit_limit, interest_rate, due_day) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issddddi", $user_id, $name, $type, $initial_balance, $initial_balance, $credit_limit, $interest_rate, $due_day);
        return $stmt->execute();
    }

    public function updateAccount($account_id, $user_id, $name, $type, $credit_limit = 0.00, $interest_rate = 0.00, $due_day = null)
    {
        $credit_limit = (float)$credit_limit;
        $interest_rate = (float)$interest_rate;
        $due_day = !empty($due_day) ? (int)$due_day : null;
        $stmt = $this->db->conn->prepare("UPDATE accounts SET name = ?, type = ?, credit_limit = ?, interest_rate = ?, due_day = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ssddiii", $name, $type, $credit_limit, $interest_rate, $due_day, $account_id, $user_id);

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
                SUM(CASE WHEN type = 'investment' THEN current_balance ELSE 0 END) as investment_balance,
                SUM(CASE WHEN type = 'credit_card' THEN current_balance ELSE 0 END) as credit_card_balance,
                SUM(CASE WHEN type = 'loan' THEN current_balance ELSE 0 END) as loan_balance,
                SUM(CASE WHEN type IN ('bank', 'cash') THEN current_balance ELSE 0 END) as liquid_balance,
                SUM(CASE 
                    WHEN type IN ('bank', 'cash', 'investment') AND current_balance > 0 THEN current_balance 
                    ELSE 0 
                END) as total_assets,
                SUM(CASE 
                    WHEN type IN ('credit_card', 'loan') THEN ABS(current_balance)
                    WHEN current_balance < 0 THEN ABS(current_balance)
                    ELSE 0 
                END) as total_liabilities,
                SUM(current_balance) as total_balance
            FROM accounts 
            WHERE user_id = ? AND deleted_at IS NULL
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc() ?: [];
        $total_assets = (float)($row['total_assets'] ?? 0);
        $total_liabilities = (float)($row['total_liabilities'] ?? 0);
        $row['net_worth'] = $total_assets - $total_liabilities;
        return $row;
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

        $sql .= " ORDER BY t.transaction_date DESC, t.created_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT ?";
            $params[0] .= "i";
            $param_values[] = (int)$filters['limit'];
        }

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

    // ==========================================
    // PHASE 1: BUDGET METHODS
    // ==========================================
    public function getBudgets($user_id)
    {
        $stmt = $this->db->conn->prepare("SELECT * FROM budgets WHERE user_id = ? ORDER BY category ASC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getBudget($budget_id, $user_id)
    {
        $stmt = $this->db->conn->prepare("SELECT * FROM budgets WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $budget_id, $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function setBudget($user_id, $category, $monthly_limit)
    {
        $monthly_limit = (float)$monthly_limit;
        if ($monthly_limit <= 0) {
            return ['success' => false, 'message' => 'Monthly limit must be greater than zero'];
        }

        $stmt = $this->db->conn->prepare("
            INSERT INTO budgets (user_id, category, monthly_limit) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE monthly_limit = VALUES(monthly_limit), updated_at = NOW()
        ");
        $stmt->bind_param("isd", $user_id, $category, $monthly_limit);
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Budget saved successfully'];
        }
        return ['success' => false, 'message' => 'Failed to save budget'];
    }

    public function deleteBudget($budget_id, $user_id)
    {
        $stmt = $this->db->conn->prepare("DELETE FROM budgets WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $budget_id, $user_id);
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Budget deleted successfully'];
        }
        return ['success' => false, 'message' => 'Failed to delete budget'];
    }

    public function getBudgetPerformance($user_id, $month = null, $year = null)
    {
        $month = $month ?: date('m');
        $year = $year ?: date('Y');
        $start_date = sprintf("%04d-%02d-01", (int)$year, (int)$month);
        $end_date = date('Y-m-t', strtotime($start_date));

        // Fetch all budgets
        $budgets_stmt = $this->db->conn->prepare("SELECT * FROM budgets WHERE user_id = ? ORDER BY category ASC");
        $budgets_stmt->bind_param("i", $user_id);
        $budgets_stmt->execute();
        $budgets_res = $budgets_stmt->get_result();

        // Fetch spending per category this month
        $spend_stmt = $this->db->conn->prepare("
            SELECT category, SUM(amount) as total_spent 
            FROM transactions 
            WHERE user_id = ? AND type = 'expense' AND transaction_date BETWEEN ? AND ? AND deleted_at IS NULL
            GROUP BY category
        ");
        $spend_stmt->bind_param("iss", $user_id, $start_date, $end_date);
        $spend_stmt->execute();
        $spend_res = $spend_stmt->get_result();
        $spending = [];
        while ($row = $spend_res->fetch_assoc()) {
            $spending[$row['category']] = (float)$row['total_spent'];
        }

        $items = [];
        $total_budget = 0;
        $total_spent = 0;

        while ($b = $budgets_res->fetch_assoc()) {
            $cat = $b['category'];
            $limit = (float)$b['monthly_limit'];
            $spent = $spending[$cat] ?? 0.00;
            $remaining = max(0, $limit - $spent);
            $pct = ($limit > 0) ? round(($spent / $limit) * 100, 1) : 0;
            $status = ($pct >= 100) ? 'exceeded' : (($pct >= 80) ? 'warning' : 'on_track');

            $total_budget += $limit;
            $total_spent += $spent;

            $items[] = [
                'id' => (int)$b['id'],
                'category' => $cat,
                'monthly_limit' => $limit,
                'spent' => $spent,
                'remaining' => $remaining,
                'percentage' => $pct,
                'status' => $status
            ];
        }

        return [
            'month' => (int)$month,
            'year' => (int)$year,
            'month_name' => date('F Y', strtotime($start_date)),
            'total_budget' => $total_budget,
            'total_spent' => $total_spent,
            'total_remaining' => max(0, $total_budget - $total_spent),
            'overall_pct' => ($total_budget > 0) ? round(($total_spent / $total_budget) * 100, 1) : 0,
            'items' => $items
        ];
    }

    // ==========================================
    // PHASE 1: RECURRING BILLS & SUBSCRIPTIONS
    // ==========================================
    public function getRecurringBills($user_id, $status = null)
    {
        $sql = "SELECT b.*, a.name as account_name 
                FROM recurring_bills b
                LEFT JOIN accounts a ON b.account_id = a.id
                WHERE b.user_id = ? AND b.deleted_at IS NULL";
        if ($status) {
            $sql .= " AND b.status = ?";
            $stmt = $this->db->conn->prepare($sql . " ORDER BY b.next_due_date ASC");
            $stmt->bind_param("is", $user_id, $status);
        } else {
            $stmt = $this->db->conn->prepare($sql . " ORDER BY b.next_due_date ASC");
            $stmt->bind_param("i", $user_id);
        }
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getRecurringBill($bill_id, $user_id)
    {
        $stmt = $this->db->conn->prepare("
            SELECT b.*, a.name as account_name 
            FROM recurring_bills b 
            LEFT JOIN accounts a ON b.account_id = a.id 
            WHERE b.id = ? AND b.user_id = ? AND b.deleted_at IS NULL
        ");
        $stmt->bind_param("ii", $bill_id, $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function addRecurringBill($user_id, $account_id, $title, $amount, $category, $frequency, $next_due_date)
    {
        $account_id = !empty($account_id) ? (int)$account_id : null;
        $amount = (float)$amount;
        $stmt = $this->db->conn->prepare("
            INSERT INTO recurring_bills (user_id, account_id, title, amount, category, frequency, next_due_date, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
        ");
        $stmt->bind_param("iisdsss", $user_id, $account_id, $title, $amount, $category, $frequency, $next_due_date);
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Recurring bill added successfully', 'id' => $stmt->insert_id];
        }
        return ['success' => false, 'message' => 'Failed to add recurring bill'];
    }

    public function updateRecurringBill($bill_id, $user_id, $account_id, $title, $amount, $category, $frequency, $next_due_date, $status = 'active')
    {
        $account_id = !empty($account_id) ? (int)$account_id : null;
        $amount = (float)$amount;
        $stmt = $this->db->conn->prepare("
            UPDATE recurring_bills 
            SET account_id = ?, title = ?, amount = ?, category = ?, frequency = ?, next_due_date = ?, status = ?
            WHERE id = ? AND user_id = ? AND deleted_at IS NULL
        ");
        $stmt->bind_param("isdssssii", $account_id, $title, $amount, $category, $frequency, $next_due_date, $status, $bill_id, $user_id);
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Recurring bill updated successfully'];
        }
        return ['success' => false, 'message' => 'Failed to update recurring bill'];
    }

    public function deleteRecurringBill($bill_id, $user_id)
    {
        $stmt = $this->db->conn->prepare("UPDATE recurring_bills SET deleted_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $bill_id, $user_id);
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Recurring bill deleted successfully'];
        }
        return ['success' => false, 'message' => 'Failed to delete recurring bill'];
    }

    public function markBillAsPaid($bill_id, $user_id, $paid_date = null)
    {
        $bill = $this->getRecurringBill($bill_id, $user_id);
        if (!$bill) {
            return ['success' => false, 'message' => 'Recurring bill not found'];
        }

        $paid_date = $paid_date ?: date('Y-m-d');
        $account_id = $bill['account_id'];

        if (!$account_id) {
            $acc_res = $this->getAccounts($user_id);
            $first_acc = $acc_res->fetch_assoc();
            $account_id = $first_acc ? $first_acc['id'] : null;
        }

        if (!$account_id) {
            return ['success' => false, 'message' => 'No account available to record payment'];
        }

        $this->db->conn->begin_transaction();
        try {
            // 1. Create expense transaction
            $description = 'Bill Payment: ' . $bill['title'];
            $stmt = $this->db->conn->prepare("
                INSERT INTO transactions (user_id, account_id, type, amount, description, category, transaction_date) 
                VALUES (?, ?, 'expense', ?, ?, ?, ?)
            ");
            $stmt->bind_param("iidsss", $user_id, $account_id, $bill['amount'], $description, $bill['category'], $paid_date);
            $stmt->execute();

            // 2. Deduct from account balance
            $stmt2 = $this->db->conn->prepare("UPDATE accounts SET current_balance = current_balance - ? WHERE id = ? AND user_id = ?");
            $stmt2->bind_param("dii", $bill['amount'], $account_id, $user_id);
            $stmt2->execute();

            // 3. Advance next_due_date
            $curr_due = $bill['next_due_date'];
            switch ($bill['frequency']) {
                case 'weekly':
                    $next_due = date('Y-m-d', strtotime($curr_due . ' +1 week'));
                    break;
                case 'quarterly':
                    $next_due = date('Y-m-d', strtotime($curr_due . ' +3 months'));
                    break;
                case 'yearly':
                    $next_due = date('Y-m-d', strtotime($curr_due . ' +1 year'));
                    break;
                case 'monthly':
                default:
                    $next_due = date('Y-m-d', strtotime($curr_due . ' +1 month'));
                    break;
            }

            $stmt3 = $this->db->conn->prepare("UPDATE recurring_bills SET next_due_date = ? WHERE id = ? AND user_id = ?");
            $stmt3->bind_param("sii", $next_due, $bill_id, $user_id);
            $stmt3->execute();

            $this->db->conn->commit();
            return [
                'success' => true,
                'message' => 'Bill marked as paid! Next due date: ' . date('M j, Y', strtotime($next_due)),
                'next_due_date' => $next_due
            ];
        } catch (Exception $e) {
            $this->db->conn->rollback();
            return ['success' => false, 'message' => 'Error recording payment: ' . $e->getMessage()];
        }
    }

    // ==========================================
    // PHASE 2: SAVINGS GOALS (SINKING FUNDS)
    // ==========================================
    public function getSavingsGoals($user_id)
    {
        $stmt = $this->db->conn->prepare("SELECT * FROM savings_goals WHERE user_id = ? AND deleted_at IS NULL ORDER BY target_date ASC, created_at DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getSavingsGoal($goal_id, $user_id)
    {
        $stmt = $this->db->conn->prepare("SELECT * FROM savings_goals WHERE id = ? AND user_id = ? AND deleted_at IS NULL");
        $stmt->bind_param("ii", $goal_id, $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function addSavingsGoal($user_id, $title, $target_amount, $current_amount = 0.00, $target_date = null)
    {
        $target_date = !empty($target_date) ? $target_date : null;
        $target_amount = (float)$target_amount;
        $current_amount = (float)$current_amount;
        $stmt = $this->db->conn->prepare("INSERT INTO savings_goals (user_id, title, target_amount, current_amount, target_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isdds", $user_id, $title, $target_amount, $current_amount, $target_date);
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Savings goal created successfully', 'id' => $stmt->insert_id];
        }
        return ['success' => false, 'message' => 'Failed to create savings goal'];
    }

    public function updateSavingsGoal($goal_id, $user_id, $title, $target_amount, $current_amount, $target_date = null)
    {
        $target_date = !empty($target_date) ? $target_date : null;
        $target_amount = (float)$target_amount;
        $current_amount = (float)$current_amount;
        $stmt = $this->db->conn->prepare("UPDATE savings_goals SET title = ?, target_amount = ?, current_amount = ?, target_date = ? WHERE id = ? AND user_id = ? AND deleted_at IS NULL");
        $stmt->bind_param("sddsii", $title, $target_amount, $current_amount, $target_date, $goal_id, $user_id);
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Savings goal updated successfully'];
        }
        return ['success' => false, 'message' => 'Failed to update savings goal'];
    }

    public function deleteSavingsGoal($goal_id, $user_id)
    {
        $stmt = $this->db->conn->prepare("UPDATE savings_goals SET deleted_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $goal_id, $user_id);
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Savings goal deleted successfully'];
        }
        return ['success' => false, 'message' => 'Failed to delete savings goal'];
    }

    public function contributeToGoal($goal_id, $user_id, $account_id, $amount, $note = '')
    {
        $goal = $this->getSavingsGoal($goal_id, $user_id);
        if (!$goal) {
            return ['success' => false, 'message' => 'Savings goal not found'];
        }

        $amount = (float)$amount;
        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Contribution amount must be greater than zero'];
        }

        $acc_bal = $this->getAccountBalance($account_id, $user_id);
        if ($acc_bal < $amount) {
            return ['success' => false, 'message' => 'Insufficient funds in the selected account'];
        }

        $this->db->conn->begin_transaction();
        try {
            // Deduct from account as expense (Category: Savings)
            $desc = 'Goal Deposit: ' . $goal['title'] . ($note ? ' (' . $note . ')' : '');
            $category = 'Savings';
            $txn_date = date('Y-m-d');
            $stmt = $this->db->conn->prepare("
                INSERT INTO transactions (user_id, account_id, type, amount, description, category, transaction_date) 
                VALUES (?, ?, 'expense', ?, ?, ?, ?)
            ");
            $stmt->bind_param("iidsss", $user_id, $account_id, $amount, $desc, $category, $txn_date);
            $stmt->execute();

            $stmt2 = $this->db->conn->prepare("UPDATE accounts SET current_balance = current_balance - ? WHERE id = ? AND user_id = ?");
            $stmt2->bind_param("dii", $amount, $account_id, $user_id);
            $stmt2->execute();

            $stmt3 = $this->db->conn->prepare("UPDATE savings_goals SET current_amount = current_amount + ? WHERE id = ? AND user_id = ?");
            $stmt3->bind_param("dii", $amount, $goal_id, $user_id);
            $stmt3->execute();

            $this->db->conn->commit();
            return ['success' => true, 'message' => 'Contributed ₹' . number_format($amount, 2) . ' to "' . htmlspecialchars($goal['title']) . '" successfully!'];
        } catch (Exception $e) {
            $this->db->conn->rollback();
            return ['success' => false, 'message' => 'Failed to record contribution: ' . $e->getMessage()];
        }
    }

    // ==========================================
    // PHASE 3: PREDICTIVE CASH FLOW ENGINE
    // ==========================================
    public function generateCashFlowForecast($user_id, $days = 60)
    {
        $days = max(7, min(180, (int)$days));

        // 1. Starting liquid balance (Bank + Cash accounts)
        $balances = $this->getTotalBalance($user_id);
        $starting_liquid = (float)($balances['liquid_balance'] ?? 0);

        // 2. Historical 60-day daily velocity (average daily net income minus expenses)
        $stmt = $this->db->conn->prepare("
            SELECT 
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as hist_income,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as hist_expense,
                DATEDIFF(MAX(transaction_date), MIN(transaction_date)) as date_span,
                COUNT(*) as tx_count
            FROM transactions 
            WHERE user_id = ? AND deleted_at IS NULL AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 60 DAY)
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $hist = $stmt->get_result()->fetch_assoc();

        $span_days = max(30, (int)($hist['date_span'] ?? 60));
        $net_historical = ((float)($hist['hist_income'] ?? 0)) - ((float)($hist['hist_expense'] ?? 0));
        $daily_net_velocity = round($net_historical / $span_days, 2);

        // 3. Fetch active recurring bills
        $bills_res = $this->getRecurringBills($user_id, 'active');
        $bills = [];
        while ($b = $bills_res->fetch_assoc()) {
            $bills[] = $b;
        }

        // Project scheduled occurrences
        $scheduled_events = [];
        $end_date_time = strtotime("+$days days");
        foreach ($bills as $b) {
            $due_time = strtotime($b['next_due_date']);
            $freq = $b['frequency'];
            $amt = (float)$b['amount'];

            // Advance if in the past
            while ($due_time < strtotime('today')) {
                if ($freq === 'weekly') $due_time = strtotime('+1 week', $due_time);
                elseif ($freq === 'quarterly') $due_time = strtotime('+3 months', $due_time);
                elseif ($freq === 'yearly') $due_time = strtotime('+1 year', $due_time);
                else $due_time = strtotime('+1 month', $due_time);
            }

            while ($due_time <= $end_date_time) {
                $d_str = date('Y-m-d', $due_time);
                if (!isset($scheduled_events[$d_str])) {
                    $scheduled_events[$d_str] = [];
                }
                $scheduled_events[$d_str][] = [
                    'title' => $b['title'],
                    'amount' => $amt,
                    'category' => $b['category'],
                    'frequency' => $b['frequency']
                ];

                if ($freq === 'weekly') $due_time = strtotime('+1 week', $due_time);
                elseif ($freq === 'quarterly') $due_time = strtotime('+3 months', $due_time);
                elseif ($freq === 'yearly') $due_time = strtotime('+1 year', $due_time);
                else $due_time = strtotime('+1 month', $due_time);
            }
        }

        // 4. Generate daily timeline
        $timeline = [];
        $running_balance = $starting_liquid;
        $min_balance = $starting_liquid;
        $min_balance_date = date('Y-m-d');
        $total_projected_bills = 0;
        $critical_drop_date = null;

        for ($i = 0; $i <= $days; $i++) {
            $curr_date = date('Y-m-d', strtotime("+$i days"));
            $day_bills = $scheduled_events[$curr_date] ?? [];
            $day_bills_total = 0;
            foreach ($day_bills as $dbill) {
                $day_bills_total += $dbill['amount'];
            }
            $total_projected_bills += $day_bills_total;

            if ($i > 0) {
                $daily_delta = $daily_net_velocity - $day_bills_total;
                $running_balance += $daily_delta;
            }

            if ($running_balance < $min_balance) {
                $min_balance = $running_balance;
                $min_balance_date = $curr_date;
            }

            if ($running_balance < 2000 && $critical_drop_date === null && $i > 0) {
                $critical_drop_date = $curr_date;
            }

            $timeline[] = [
                'date' => $curr_date,
                'day_num' => $i,
                'day_label' => date('D, M j', strtotime($curr_date)),
                'balance' => round($running_balance, 2),
                'bills' => $day_bills,
                'bills_total' => $day_bills_total,
                'daily_velocity' => ($i === 0) ? 0 : $daily_net_velocity
            ];
        }

        $day_30_idx = min(30, count($timeline) - 1);
        $day_60_idx = min(60, count($timeline) - 1);

        return [
            'horizon_days' => $days,
            'starting_balance' => round($starting_liquid, 2),
            'ending_balance' => round($running_balance, 2),
            'balance_30d' => $timeline[$day_30_idx]['balance'] ?? 0,
            'balance_60d' => $timeline[$day_60_idx]['balance'] ?? 0,
            'min_balance' => round($min_balance, 2),
            'min_balance_date' => $min_balance_date,
            'daily_velocity' => $daily_net_velocity,
            'total_bills_projected' => round($total_projected_bills, 2),
            'critical_drop_date' => $critical_drop_date,
            'timeline' => $timeline
        ];
    }
}

function rupee_icon($class = "h-4 w-4", $color = "currentColor")
{
    return '<svg class="' . $class . '" fill="none" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <path d="M6 3h12M6 8h12M6 13l8.5 8M6 13h3a4 4 0 0 0 0-8"/>
    </svg>';
}

function account_icon($class = "h-4 w-4", $color = "currentColor")
{
    return '<svg class="' . $class . '" fill="' . $color . '" viewBox="0 0 512.001 512.001" xmlns="http://www.w3.org/2000/svg">
        <path d="M454.275,85.672c-68.879,0-116.732-22.029-144.749-40.51c-30.433-20.075-44.786-40.46-44.913-40.641 c-1.944-2.83-5.16-4.512-8.593-4.521c-0.009,0-0.018,0-0.027,0c-3.422,0-6.636,1.688-8.589,4.499 c-0.141,0.204-14.495,20.589-44.927,40.664c-28.016,18.481-75.87,40.51-144.748,40.51c-5.771,0-10.449,4.678-10.449,10.449 v158.065c0,0.079,0.001,0.16,0.002,0.239c1.056,46.063,4.087,87.558,30.591,128.922c29.482,46.013,84.693,86.587,173.75,127.69 c1.39,0.642,2.884,0.961,4.378,0.961s2.99-0.321,4.378-0.961c89.058-41.103,144.268-81.676,173.751-127.69 c26.504-41.364,29.535-82.859,30.591-128.922c0.001-0.079,0.002-0.16,0.002-0.239V96.121 C464.724,90.35,460.046,85.672,454.275,85.672z M443.826,254.068c-1.02,44.329-3.692,81.175-27.291,118.006 c-26.767,41.774-77.836,79.317-160.533,117.957c-82.696-38.639-133.766-76.183-160.532-117.957 c-23.6-36.83-26.272-73.677-27.291-118.006V106.411c69.059-2.128,117.68-25.055,146.76-44.435 c19.66-13.102,33.152-26.244,41.063-35.04c7.911,8.796,21.403,21.938,41.063,35.04c29.081,19.381,77.701,42.308,146.761,44.435 V254.068z"/>
        <path d="M256.001,123.865c-74.902,0-135.84,60.938-135.84,135.841c0,74.902,60.938,135.839,135.84,135.839 s135.84-60.937,135.84-135.839C391.841,184.803,330.903,123.865,256.001,123.865z M256.001,374.646 c-36.545,0-69.16-17.145-90.228-43.813c19.88-18.358,45.799-30.142,73.897-33.436c4.839-0.568,8.643-4.403,9.171-9.247 c0.528-4.843-2.362-9.408-6.964-11.004c-17.332-6.009-28.978-22.375-28.978-40.723c0-23.766,19.335-43.101,43.102-43.101 c23.767,0,43.102,19.335,43.102,43.101c0,18.348-11.646,34.712-28.979,40.723c-4.603,1.596-7.492,6.161-6.964,11.004 c0.528,4.843,4.332,8.679,9.171,9.247c28.098,3.295,54.017,15.078,73.898,33.436C325.16,357.501,292.546,374.646,256.001,374.646z"/>
    </svg>';
}

function send_security_headers()
{
    if (headers_sent()) {
        return;
    }
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: SAMEORIGIN");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
}

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
