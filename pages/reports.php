<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$finance = new FinanceManager();
$user_id = $auth->getUserId();

// Time period options
$period = $_GET['period'] ?? 'this_month';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Set date range based on period
switch ($period) {
    case 'last_7_days':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        $end_date = date('Y-m-d');
        break;
    case 'this_month':
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
        break;
    case 'last_month':
        $start_date = date('Y-m-01', strtotime('-1 month'));
        $end_date = date('Y-m-t', strtotime('-1 month'));
        break;
    case 'this_year':
        $start_date = date('Y-01-01');
        $end_date = date('Y-12-31');
        break;
    case 'last_year':
        $start_date = date('Y-01-01', strtotime('-1 year'));
        $end_date = date('Y-12-31', strtotime('-1 year'));
        break;
    case 'overall':
        $start_date = '1970-01-01'; // Very early date
        $end_date = date('Y-m-d');
        break;
    case 'custom':
        // Use custom dates from form
        if (empty($start_date)) $start_date = date('Y-m-01');
        if (empty($end_date)) $end_date = date('Y-m-t');
        break;
    default:
        $period = 'this_month';
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
}

// Get transactions for the period
$filters = [
    'start_date' => $start_date,
    'end_date' => $end_date
];
$transactions = $finance->getTransactions($user_id, $filters);

// Calculate summary
$income_total = 0;
$expense_total = 0;
$category_totals = [];

// Store transactions for CSV
$transactions_data = [];
while ($transaction = $transactions->fetch_assoc()) {
    $transactions_data[] = $transaction;

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

// Get data for trend charts
$monthly_data = getMonthlyData($finance, $user_id);
$yearly_data = getYearlyData($finance, $user_id);
$weekly_data = getWeeklyData($finance, $user_id);

// Helper functions for chart data
function getMonthlyData($finance, $user_id)
{
    $data = [];
    for ($i = 5; $i >= 0; $i--) {
        $month_start = date('Y-m-01', strtotime("-$i months"));
        $month_end = date('Y-m-t', strtotime("-$i months"));
        $month_name = date('M Y', strtotime("-$i months"));

        $filters = ['start_date' => $month_start, 'end_date' => $month_end];
        $transactions = $finance->getTransactions($user_id, $filters);

        $income = 0;
        $expense = 0;
        while ($transaction = $transactions->fetch_assoc()) {
            if ($transaction['type'] === 'income') {
                $income += $transaction['amount'];
            } else {
                $expense += $transaction['amount'];
            }
        }

        $data[] = [
            'month' => $month_name,
            'income' => $income,
            'expense' => $expense,
            'net' => $income - $expense
        ];
    }
    return $data;
}

function getYearlyData($finance, $user_id)
{
    $data = [];
    $current_year = date('Y');
    for ($year = $current_year - 2; $year <= $current_year; $year++) {
        $year_start = "$year-01-01";
        $year_end = "$year-12-31";

        $filters = ['start_date' => $year_start, 'end_date' => $year_end];
        $transactions = $finance->getTransactions($user_id, $filters);

        $income = 0;
        $expense = 0;
        while ($transaction = $transactions->fetch_assoc()) {
            if ($transaction['type'] === 'income') {
                $income += $transaction['amount'];
            } else {
                $expense += $transaction['amount'];
            }
        }

        $data[] = [
            'year' => $year,
            'income' => $income,
            'expense' => $expense,
            'net' => $income - $expense
        ];
    }
    return $data;
}

function getWeeklyData($finance, $user_id)
{
    $data = [];
    for ($i = 3; $i >= 0; $i--) {
        $week_start = date('Y-m-d', strtotime("-$i weeks"));
        $week_end = date('Y-m-d', strtotime("-$i weeks +6 days"));
        $week_label = date('M j', strtotime("-$i weeks")) . ' - ' . date('M j', strtotime("-$i weeks +6 days"));

        $filters = ['start_date' => $week_start, 'end_date' => $week_end];
        $transactions = $finance->getTransactions($user_id, $filters);

        $income = 0;
        $expense = 0;
        while ($transaction = $transactions->fetch_assoc()) {
            if ($transaction['type'] === 'income') {
                $income += $transaction['amount'];
            } else {
                $expense += $transaction['amount'];
            }
        }

        $data[] = [
            'week' => $week_label,
            'income' => $income,
            'expense' => $expense,
            'net' => $income - $expense
        ];
    }
    return $data;
}

// Handle CSV download
if (isset($_GET['download_csv'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="financial_report_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');

    // CSV headers
    fputcsv($output, ['Date', 'Type', 'Description', 'Category', 'Account', 'Amount', 'Transaction Date']);

    // CSV data
    foreach ($transactions_data as $transaction) {
        fputcsv($output, [
            $transaction['transaction_date'],
            ucfirst($transaction['type']),
            $transaction['description'],
            $transaction['category'] ?: 'Uncategorized',
            $transaction['account_name'],
            $transaction['amount'],
            $transaction['created_at']
        ]);
    }

    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?php echo SITE_NAME; ?></title>
    <link rel="icon" href="/assets/img/logo.svg" data-next-head=""/>
    <link rel="stylesheet" href="/assets/css/tailwind.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-gray-100">
    <?php include '../includes/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <h1 class="text-3xl font-bold text-gray-800">Financial Reports</h1>

            <!-- Export Button -->
            <div class="flex gap-3">
                <a href="?<?php echo http_build_query($_GET); ?>&download_csv=1"
                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-200 shadow-sm">
                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Download CSV
                </a>

                <!-- Print Button -->
                <button onclick="window.print()"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200 shadow-sm">
                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Print Report
                </button>
            </div>
        </div>

        <!-- Quick Period Filters -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h3 class="text-xl font-semibold mb-4">Quick Period Filters</h3>
            <div class="flex flex-wrap gap-2 mb-4">
                <a href="?period=last_7_days" class="px-4 py-2 rounded-lg <?php echo $period === 'last_7_days' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition duration-200">
                    Last 7 Days
                </a>
                <a href="?period=this_month" class="px-4 py-2 rounded-lg <?php echo $period === 'this_month' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition duration-200">
                    This Month
                </a>
                <a href="?period=last_month" class="px-4 py-2 rounded-lg <?php echo $period === 'last_month' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition duration-200">
                    Last Month
                </a>
                <a href="?period=this_year" class="px-4 py-2 rounded-lg <?php echo $period === 'this_year' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition duration-200">
                    This Year
                </a>
                <a href="?period=last_year" class="px-4 py-2 rounded-lg <?php echo $period === 'last_year' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition duration-200">
                    Last Year
                </a>
                <a href="?period=overall" class="px-4 py-2 rounded-lg <?php echo $period === 'overall' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition duration-200">
                    Overall
                </a>
            </div>

            <!-- Custom Date Range -->
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input type="hidden" name="period" value="custom">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                    <input type="date" id="start_date" name="start_date"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        value="<?php echo $start_date; ?>">
                </div>

                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                    <input type="date" id="end_date" name="end_date"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        value="<?php echo $end_date; ?>">
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit"
                        class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200">
                        Apply Dates
                    </button>
                    <a href="reports.php"
                        class="flex-1 bg-gray-600 text-white py-2 px-4 rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition duration-200 text-center">
                        Reset
                    </a>
                </div>
            </form>

            <!-- Period Info -->
            <div class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
                <div class="flex items-center text-sm text-blue-700">
                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Showing data from <strong class="mx-1"><?php echo date('M j, Y', strtotime($start_date)); ?></strong> to <strong class="mx-1"><?php echo date('M j, Y', strtotime($end_date)); ?></strong>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-600 mb-2">Total Income</h3>
                        <p class="text-2xl font-bold text-green-600">₹<?php echo number_format($income_total, 2); ?></p>
                    </div>
                    <div class="p-3 bg-green-100 rounded-lg">
                        <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-red-500">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-600 mb-2">Total Expenses</h3>
                        <p class="text-2xl font-bold text-red-600">₹<?php echo number_format($expense_total, 2); ?></p>
                    </div>
                    <div class="p-3 bg-red-100 rounded-lg">
                        <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m0 0l-4-4m4 4l4-4" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-600 mb-2">Net Flow</h3>
                        <p class="text-2xl font-bold <?php echo $net_flow >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                            ₹<?php echo number_format($net_flow, 2); ?>
                        </p>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Comprehensive Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Monthly Trend Chart -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-xl font-semibold mb-4">6-Month Trend</h3>
                <div class="h-64">
                    <canvas id="monthlyTrendChart"></canvas>
                </div>
            </div>

            <!-- Expense by Category Chart -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-xl font-semibold mb-4">Expenses by Category</h3>
                <div class="h-64">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>

            <!-- Income vs Expense Chart -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-xl font-semibold mb-4">Income vs Expenses</h3>
                <div class="h-64">
                    <canvas id="incomeExpenseChart"></canvas>
                </div>
            </div>

            <!-- Yearly Comparison Chart -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-xl font-semibold mb-4">Yearly Comparison</h3>
                <div class="h-64">
                    <canvas id="yearlyChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Transaction Data Table -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-4">
                <h3 class="text-xl font-semibold">Transaction Details</h3>
                <div class="text-sm text-gray-600">
                    <?php echo count($transactions_data); ?> transactions found
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">S.No</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php $s = 1;
                        if (!empty($transactions_data)): ?>
                            <?php foreach ($transactions_data as $transaction): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo $s++; ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M j, Y', strtotime($transaction['transaction_date'])); ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        <?php echo htmlspecialchars($transaction['account_name']); ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold <?php echo $transaction['type'] === 'income' ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo $transaction['type'] === 'income' ? '+' : '-'; ?>₹<?php echo number_format($transaction['amount'], 2); ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $transaction['type'] === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo ucfirst($transaction['type']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <?php echo htmlspecialchars($transaction['description']); ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        <?php echo htmlspecialchars($transaction['category'] ?: 'Uncategorized'); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-4 py-12">
                                    <div class="flex flex-col items-center justify-center text-center text-gray-500 space-y-3">
                                        <svg class="h-16 w-16 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <div>
                                            <p class="text-base font-medium text-gray-600">No transactions found</p>
                                            <p class="text-sm text-gray-500 mt-1">Try selecting a different time period or add some transactions.</p>
                                        </div>
                                        <a href="transactions.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200 text-sm">
                                            <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                            </svg>
                                            Add Transaction
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        // Monthly Trend Chart
        const monthlyTrendCtx = document.getElementById('monthlyTrendChart').getContext('2d');
        const monthlyTrendChart = new Chart(monthlyTrendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($monthly_data, 'month')); ?>,
                datasets: [{
                        label: 'Income',
                        data: <?php echo json_encode(array_column($monthly_data, 'income')); ?>,
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Expenses',
                        data: <?php echo json_encode(array_column($monthly_data, 'expense')); ?>,
                        borderColor: '#EF4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Expense by Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryChart = new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_keys($category_totals)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($category_totals)); ?>,
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                        '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF',
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Income vs Expense Chart
        const incomeExpenseCtx = document.getElementById('incomeExpenseChart').getContext('2d');
        const incomeExpenseChart = new Chart(incomeExpenseCtx, {
            type: 'bar',
            data: {
                labels: ['Income', 'Expenses'],
                datasets: [{
                    label: 'Amount',
                    data: [<?php echo $income_total; ?>, <?php echo $expense_total; ?>],
                    backgroundColor: ['#10B981', '#EF4444']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Yearly Comparison Chart
        const yearlyCtx = document.getElementById('yearlyChart').getContext('2d');
        const yearlyChart = new Chart(yearlyCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($yearly_data, 'year')); ?>,
                datasets: [{
                        label: 'Income',
                        data: <?php echo json_encode(array_column($yearly_data, 'income')); ?>,
                        backgroundColor: '#10B981'
                    },
                    {
                        label: 'Expenses',
                        data: <?php echo json_encode(array_column($yearly_data, 'expense')); ?>,
                        backgroundColor: '#EF4444'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>

</html>