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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $filter_type = $_GET['filter_type'] ?? '';

    switch ($filter_type) {
        case 'transaction_summary':
            $start_date = $_GET['start_date'] ?? date('Y-m-01');
            $end_date = $_GET['end_date'] ?? date('Y-m-t');

            $filters = [
                'start_date' => $start_date,
                'end_date' => $end_date
            ];

            $transactions = $finance->getTransactions($user_id, $filters);

            $income_total = 0;
            $expense_total = 0;
            $category_totals = [];

            while ($transaction = $transactions->fetch_assoc()) {
                if ($transaction['type'] === 'income') {
                    $income_total += $transaction['amount'];
                } else {
                    $expense_total += $transaction['amount'];

                    $category = $transaction['category'] ?: 'Uncategorized';
                    if (!isset($category_totals[$category])) {
                        $category_totals[$category] = 0;
                    }
                    $category_totals[$category] += $transaction['amount'];
                }
            }

            $net_flow = $income_total - $expense_total;

            echo json_encode([
                'success' => true,
                'summary' => [
                    'income_total' => $income_total,
                    'expense_total' => $expense_total,
                    'net_flow' => $net_flow,
                    'category_totals' => $category_totals
                ]
            ]);
            break;

        case 'account_balances':
            $accounts = $finance->getAccounts($user_id);
            $account_balances = [];

            while ($account = $accounts->fetch_assoc()) {
                $account_balances[] = [
                    'id' => $account['id'],
                    'name' => $account['name'],
                    'type' => $account['type'],
                    'balance' => $account['current_balance']
                ];
            }

            $balance_summary = $finance->getTotalBalance($user_id);

            echo json_encode([
                'success' => true,
                'account_balances' => $account_balances,
                'balance_summary' => $balance_summary
            ]);
            break;

        case 'category_analysis':
            $start_date = $_GET['start_date'] ?? date('Y-m-01', strtotime('-1 month'));
            $end_date = $_GET['end_date'] ?? date('Y-m-t');

            $filters = [
                'start_date' => $start_date,
                'end_date' => $end_date,
                'type' => 'expense'
            ];

            $transactions = $finance->getTransactions($user_id, $filters);
            $category_analysis = [];

            while ($transaction = $transactions->fetch_assoc()) {
                $category = $transaction['category'] ?: 'Uncategorized';
                if (!isset($category_analysis[$category])) {
                    $category_analysis[$category] = 0;
                }
                $category_analysis[$category] += $transaction['amount'];
            }

            arsort($category_analysis);

            echo json_encode([
                'success' => true,
                'category_analysis' => $category_analysis,
                'period' => [
                    'start_date' => $start_date,
                    'end_date' => $end_date
                ]
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid filter type']);
    }
}
