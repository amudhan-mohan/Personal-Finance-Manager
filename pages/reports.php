<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

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
        $start_date = '1970-01-01';
        $end_date = date('Y-m-d');
        break;
    case 'custom':
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
$transactions_data = [];

while ($transaction = $transactions->fetch_assoc()) {
    $transactions_data[] = $transaction;

    if ($transaction['type'] === 'income') {
        $income_total += (float)$transaction['amount'];
    } else {
        $expense_total += (float)$transaction['amount'];

        $category = $transaction['category'] ?: 'Uncategorized';
        if (!isset($category_totals[$category])) {
            $category_totals[$category] = 0;
        }
        $category_totals[$category] += (float)$transaction['amount'];
    }
}

$net_flow = $income_total - $expense_total;

// Optimized aggregation for 6-month monthly trend (single query)
$monthly_data = [];
$month_keys = [];
for ($i = 5; $i >= 0; $i--) {
    $k = date('Y-m', strtotime("-$i months"));
    $month_keys[$k] = [
        'month' => date('M Y', strtotime("-$i months")),
        'income' => 0.0,
        'expense' => 0.0,
        'net' => 0.0
    ];
}

$six_months_start = date('Y-m-01', strtotime("-5 months"));
$stmt_monthly = $finance->getDb()->conn->prepare("
    SELECT DATE_FORMAT(transaction_date, '%Y-%m') as month_key,
           type,
           SUM(amount) as total
    FROM transactions
    WHERE user_id = ? AND transaction_date >= ? AND deleted_at IS NULL
    GROUP BY month_key, type
");
if ($stmt_monthly) {
    $stmt_monthly->bind_param("is", $user_id, $six_months_start);
    $stmt_monthly->execute();
    $res_monthly = $stmt_monthly->get_result();
    while ($row = $res_monthly->fetch_assoc()) {
        $mk = $row['month_key'];
        if (isset($month_keys[$mk])) {
            if ($row['type'] === 'income') {
                $month_keys[$mk]['income'] = (float)$row['total'];
            } else {
                $month_keys[$mk]['expense'] = (float)$row['total'];
            }
            $month_keys[$mk]['net'] = $month_keys[$mk]['income'] - $month_keys[$mk]['expense'];
        }
    }
}
$monthly_data = array_values($month_keys);

// Optimized aggregation for 3-year historical comparison (single query)
$yearly_data = [];
$cur_year = (int)date('Y');
$year_keys = [];
for ($yr = $cur_year - 2; $yr <= $cur_year; $yr++) {
    $year_keys[$yr] = [
        'year' => (string)$yr,
        'income' => 0.0,
        'expense' => 0.0,
        'net' => 0.0
    ];
}

$three_year_start = ($cur_year - 2) . '-01-01';
$stmt_yearly = $finance->getDb()->conn->prepare("
    SELECT YEAR(transaction_date) as yr,
           type,
           SUM(amount) as total
    FROM transactions
    WHERE user_id = ? AND transaction_date >= ? AND deleted_at IS NULL
    GROUP BY yr, type
");
if ($stmt_yearly) {
    $stmt_yearly->bind_param("is", $user_id, $three_year_start);
    $stmt_yearly->execute();
    $res_yearly = $stmt_yearly->get_result();
    while ($row = $res_yearly->fetch_assoc()) {
        $y = (int)$row['yr'];
        if (isset($year_keys[$y])) {
            if ($row['type'] === 'income') {
                $year_keys[$y]['income'] = (float)$row['total'];
            } else {
                $year_keys[$y]['expense'] = (float)$row['total'];
            }
            $year_keys[$y]['net'] = $year_keys[$y]['income'] - $year_keys[$y]['expense'];
        }
    }
}
$yearly_data = array_values($year_keys);

// Handle CSV download with Formula Injection Sanitization
if (isset($_GET['download_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="financial_report_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');

    $sanitizeCsv = function ($val) {
        $val = (string)$val;
        if (strlen($val) > 0 && in_array($val[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $val;
        }
        return $val;
    };

    fputcsv($output, ['Date', 'Type', 'Description', 'Category', 'Account', 'Amount', 'Transaction Date']);

    foreach ($transactions_data as $transaction) {
        fputcsv($output, [
            $sanitizeCsv($transaction['transaction_date']),
            $sanitizeCsv(ucfirst($transaction['type'])),
            $sanitizeCsv($transaction['description']),
            $sanitizeCsv($transaction['category'] ?: 'Uncategorized'),
            $sanitizeCsv($transaction['account_name']),
            $sanitizeCsv($transaction['amount']),
            $sanitizeCsv($transaction['created_at'])
        ]);
    }

    fclose($output);
    exit;
}

$quick_periods = [
    'last_7_days' => 'Last 7 Days',
    'this_month' => 'This Month',
    'last_month' => 'Last Month',
    'this_year' => 'This Year',
    'last_year' => 'Last Year',
    'overall' => 'Overall'
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Financial Reports - <?php echo SITE_NAME; ?></title>
    <link rel="icon" href="../assets/img/logo.svg" data-next-head=""/>
    <link rel="stylesheet" href="../assets/css/tailwind.css?v=<?php echo asset_version(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col font-sans">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="flex-1">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between pb-6 mb-6 border-b border-slate-200 gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Financial Reports</h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">Audit income streams, expense distributions, and cash flow trends.</p>
                </div>

                <div class="flex items-center gap-2.5">
                    <!-- Export CSV Button -->
                    <a href="?<?php echo htmlspecialchars(http_build_query($_GET)); ?>&download_csv=1"
                        class="inline-flex items-center px-4 py-2 bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition duration-150 text-xs sm:text-sm font-semibold shadow-xs gap-2">
                        <svg class="h-4 w-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="7 10 12 15 17 10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        <span>Export CSV</span>
                    </a>

                    <!-- Print Button -->
                    <button onclick="window.print()"
                        class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 hover:text-slate-900 transition duration-150 text-xs sm:text-sm font-semibold shadow-xs gap-2">
                        <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 6 2 18 2 18 9"/>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                            <rect x="6" y="14" width="12" height="8"/>
                        </svg>
                        <span>Print</span>
                    </button>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6 shadow-xs mb-8">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Period Filter</span>
                    <span class="text-xs text-slate-500 font-medium">Selected: <strong class="text-slate-800"><?php echo htmlspecialchars($start_date); ?></strong> to <strong class="text-slate-800"><?php echo htmlspecialchars($end_date); ?></strong></span>
                </div>

                <!-- Quick Period Pills -->
                <div class="flex flex-wrap gap-2 mb-6">
                    <?php foreach ($quick_periods as $key => $label): ?>
                        <a href="?period=<?php echo urlencode($key); ?>"
                            class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition <?php echo $period === $key ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 border border-slate-200 text-slate-600 hover:bg-slate-100 hover:text-slate-900'; ?>">
                            <?php echo htmlspecialchars($label); ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Custom Date Range Form -->
                <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-4 border-t border-slate-100 items-end">
                    <input type="hidden" name="period" value="custom">
                    <div>
                        <label for="start_date" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Start Date</label>
                        <input type="date" id="start_date" name="start_date"
                            class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs sm:text-sm text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 shadow-2xs"
                            value="<?php echo htmlspecialchars($start_date); ?>">
                    </div>

                    <div>
                        <label for="end_date" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">End Date</label>
                        <input type="date" id="end_date" name="end_date"
                            class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs sm:text-sm text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 shadow-2xs"
                            value="<?php echo htmlspecialchars($end_date); ?>">
                    </div>

                    <div class="flex items-center gap-2 sm:col-span-2">
                        <button type="submit"
                            class="flex-1 bg-slate-900 text-white py-2 px-4 rounded-lg hover:bg-slate-800 transition duration-150 text-xs sm:text-sm font-semibold shadow-xs">
                            Apply Range
                        </button>
                        <a href="reports.php"
                            class="py-2 px-4 bg-slate-100 border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-200 transition duration-150 text-xs sm:text-sm font-semibold text-center">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- KPI Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
                <!-- Total Income -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-xs">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Income</span>
                            <div class="text-2xl font-bold text-slate-900 tracking-tight mt-1">₹<?php echo number_format($income_total, 2); ?></div>
                            <span class="text-xs font-medium text-emerald-700 mt-1 inline-flex items-center gap-1">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="18 15 12 9 6 15"/>
                                </svg>
                                Inflow for period
                            </span>
                        </div>
                        <div class="w-11 h-11 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="19" x2="12" y2="5"/>
                                <polyline points="5 12 12 5 19 12"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Expenses -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-xs">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Expenses</span>
                            <div class="text-2xl font-bold text-slate-900 tracking-tight mt-1">₹<?php echo number_format($expense_total, 2); ?></div>
                            <span class="text-xs font-medium text-rose-700 mt-1 inline-flex items-center gap-1">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="6 9 12 15 18 9"/>
                                </svg>
                                Outflow for period
                            </span>
                        </div>
                        <div class="w-11 h-11 rounded-lg bg-rose-50 text-rose-700 border border-rose-200 flex items-center justify-center">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"/>
                                <polyline points="19 12 12 19 5 12"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Net Cash Flow -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-xs">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Net Cash Flow</span>
                            <div class="text-2xl font-bold tracking-tight mt-1 <?php echo $net_flow >= 0 ? 'text-emerald-700' : 'text-rose-700'; ?>">
                                ₹<?php echo number_format($net_flow, 2); ?>
                            </div>
                            <span class="text-xs font-medium text-slate-500 mt-1 block">
                                <?php echo $net_flow >= 0 ? 'Surplus balance' : 'Deficit balance'; ?>
                            </span>
                        </div>
                        <div class="w-11 h-11 rounded-lg bg-slate-100 text-slate-800 border border-slate-200 flex items-center justify-center">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 3v18"/>
                                <path d="M3 7l9-4 9 4"/>
                                <path d="M5 10l-2 5h4l-2-5"/>
                                <path d="M19 10l-2 5h4l-2-5"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- 6-Month Trend -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6 shadow-xs">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">6-Month Trend</h3>
                        <span class="text-xs font-medium text-slate-400">Monthly</span>
                    </div>
                    <div class="h-64 sm:h-72">
                        <canvas id="monthlyTrendChart"></canvas>
                    </div>
                </div>

                <!-- Expense Distribution by Category -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6 shadow-xs">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Expense by Category</h3>
                        <span class="text-xs font-medium text-slate-400">Selected Window</span>
                    </div>
                    <div class="h-64 sm:h-72">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>

                <!-- Income vs Expense Bar Ratio -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6 shadow-xs">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Income vs Expenses</h3>
                        <span class="text-xs font-medium text-slate-400">Period Ratio</span>
                    </div>
                    <div class="h-64 sm:h-72">
                        <canvas id="incomeExpenseChart"></canvas>
                    </div>
                </div>

                <!-- 3-Year Historical Comparison -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6 shadow-xs">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Yearly Comparison</h3>
                        <span class="text-xs font-medium text-slate-400">3-Year View</span>
                    </div>
                    <div class="h-64 sm:h-72">
                        <canvas id="yearlyChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Transaction Audit Ledger -->
            <div class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden mb-8">
                <div class="p-5 sm:p-6 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 tracking-tight">Ledger Records</h3>
                        <p class="text-xs text-slate-500 mt-0.5"><?php echo count($transactions_data); ?> transaction entries matching active filter</p>
                    </div>
                </div>

                <!-- Mobile Card List (< md) -->
                <div class="block md:hidden divide-y divide-slate-100 max-h-[520px] overflow-y-auto inscrolling-table-container">
                    <?php if (!empty($transactions_data)): ?>
                        <?php foreach ($transactions_data as $tx): ?>
                            <div class="p-4 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-slate-400 font-mono"><?php echo date('d M Y', strtotime($tx['transaction_date'])); ?></span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold uppercase tracking-wider <?php echo $tx['type'] === 'income' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'; ?>">
                                        <?php echo htmlspecialchars($tx['type']); ?>
                                    </span>
                                </div>
                                <div class="flex items-baseline justify-between">
                                    <span class="text-sm font-semibold text-slate-900"><?php echo htmlspecialchars($tx['description']); ?></span>
                                    <span class="text-sm font-bold <?php echo $tx['type'] === 'income' ? 'text-emerald-700' : 'text-slate-900'; ?>">
                                        <?php echo $tx['type'] === 'income' ? '+' : '-'; ?>₹<?php echo number_format($tx['amount'], 2); ?>
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-xs text-slate-500 pt-1">
                                    <span class="inline-flex items-center gap-1">
                                        <?php echo account_icon('h-3 w-3 text-slate-400'); ?>
                                        <?php echo htmlspecialchars($tx['account_name']); ?>
                                    </span>
                                    <span class="px-2 py-0.5 bg-slate-100 rounded text-[11px] text-slate-600 font-medium">
                                        <?php echo htmlspecialchars($tx['category'] ?: 'General'); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="p-8 text-center text-slate-500 text-xs">
                            No ledger records for the selected period.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Desktop Table View (>= md) with Fixed Height & Inner Scrolling -->
                <div class="hidden md:block overflow-x-auto max-h-[520px] overflow-y-auto border border-slate-200 rounded-xl inscrolling-table-container">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="sticky top-0 z-10 bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider bg-slate-50">#</th>
                                <th scope="col" class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider bg-slate-50">Date</th>
                                <th scope="col" class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider bg-slate-50">Account</th>
                                <th scope="col" class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider bg-slate-50">Description</th>
                                <th scope="col" class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider bg-slate-50">Category</th>
                                <th scope="col" class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider bg-slate-50">Type</th>
                                <th scope="col" class="px-4 py-3 text-right text-[11px] font-semibold text-slate-500 uppercase tracking-wider bg-slate-50">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            <?php $s = 1;
                            if (!empty($transactions_data)): ?>
                                <?php foreach ($transactions_data as $tx): ?>
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-400 font-mono"><?php echo $s++; ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-600 font-medium">
                                            <?php echo date('d M Y', strtotime($tx['transaction_date'])); ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-700 font-semibold">
                                            <?php echo htmlspecialchars($tx['account_name']); ?>
                                        </td>
                                        <td class="px-4 py-3 text-xs text-slate-900 font-medium max-w-xs truncate">
                                            <?php echo htmlspecialchars($tx['description']); ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-600">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700">
                                                <?php echo htmlspecialchars($tx['category'] ?: 'Uncategorized'); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold uppercase tracking-wider <?php echo $tx['type'] === 'income' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'; ?>">
                                                <?php echo htmlspecialchars($tx['type']); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-xs font-bold text-right <?php echo $tx['type'] === 'income' ? 'text-emerald-700' : 'text-slate-900'; ?>">
                                            <?php echo $tx['type'] === 'income' ? '+' : '-'; ?>₹<?php echo number_format($tx['amount'], 2); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-400 text-xs">
                                        No ledger records match the current filter criteria.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        const chartFont = { family: 'Inter, system-ui, -apple-system, sans-serif', size: 11 };

        // 1. Monthly Trend Chart
        const monthlyTrendCtx = document.getElementById('monthlyTrendChart').getContext('2d');
        new Chart(monthlyTrendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($monthly_data, 'month')); ?>,
                datasets: [
                    {
                        label: 'Income',
                        data: <?php echo json_encode(array_column($monthly_data, 'income')); ?>,
                        borderColor: '#059669',
                        backgroundColor: 'rgba(5, 150, 105, 0.08)',
                        borderWidth: 2,
                        tension: 0.35,
                        fill: true,
                        pointRadius: 3,
                        pointHoverRadius: 5
                    },
                    {
                        label: 'Expense',
                        data: <?php echo json_encode(array_column($monthly_data, 'expense')); ?>,
                        borderColor: '#e11d48',
                        backgroundColor: 'rgba(225, 29, 72, 0.08)',
                        borderWidth: 2,
                        tension: 0.35,
                        fill: true,
                        pointRadius: 3,
                        pointHoverRadius: 5
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { font: chartFont, boxWidth: 12, usePointStyle: true }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: chartFont }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(226, 232, 240, 0.7)' },
                        ticks: { font: chartFont }
                    }
                }
            }
        });

        // 2. Category Doughnut Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryKeys = <?php echo json_encode(array_keys($category_totals)); ?>;
        const categoryValues = <?php echo json_encode(array_values($category_totals)); ?>;

        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: categoryKeys.length > 0 ? categoryKeys : ['No Expenses'],
                datasets: [{
                    data: categoryValues.length > 0 ? categoryValues : [1],
                    backgroundColor: [
                        '#0f172a', '#2563eb', '#059669', '#d97706',
                        '#7c3aed', '#0891b2', '#e11d48', '#475569',
                        '#ca8a04', '#4f46e5'
                    ],
                    borderWidth: 1,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { font: chartFont, boxWidth: 10, usePointStyle: true }
                    }
                },
                cutout: '68%'
            }
        });

        // 3. Income vs Expense Ratio Bar Chart
        const incomeExpenseCtx = document.getElementById('incomeExpenseChart').getContext('2d');
        new Chart(incomeExpenseCtx, {
            type: 'bar',
            data: {
                labels: ['Total Inflow', 'Total Outflow'],
                datasets: [{
                    data: [<?php echo $income_total; ?>, <?php echo $expense_total; ?>],
                    backgroundColor: ['#059669', '#e11d48'],
                    borderRadius: 6,
                    maxBarThickness: 56
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: chartFont }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(226, 232, 240, 0.7)' },
                        ticks: { font: chartFont }
                    }
                }
            }
        });

        // 4. Yearly Historical Comparison Bar Chart
        const yearlyCtx = document.getElementById('yearlyChart').getContext('2d');
        new Chart(yearlyCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($yearly_data, 'year')); ?>,
                datasets: [
                    {
                        label: 'Income',
                        data: <?php echo json_encode(array_column($yearly_data, 'income')); ?>,
                        backgroundColor: '#059669',
                        borderRadius: 4,
                        maxBarThickness: 36
                    },
                    {
                        label: 'Expense',
                        data: <?php echo json_encode(array_column($yearly_data, 'expense')); ?>,
                        backgroundColor: '#e11d48',
                        borderRadius: 4,
                        maxBarThickness: 36
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { font: chartFont, boxWidth: 12, usePointStyle: true }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: chartFont }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(226, 232, 240, 0.7)' },
                        ticks: { font: chartFont }
                    }
                }
            }
        });
    </script>
</body>

</html>
