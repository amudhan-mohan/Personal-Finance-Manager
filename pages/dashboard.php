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

// Balances and KPIs
$balance_summary = $finance->getTotalBalance($user_id);
$net_worth = (float)($balance_summary['net_worth'] ?? 0);
$liquid_balance = (float)($balance_summary['liquid_balance'] ?? 0);

// Recent activity
$recent_transactions = $finance->getTransactions($user_id, ['limit' => 5]);
$recent_transfers = $finance->getTransferHistory($user_id, 3);

// Budgets snapshot for current month
$budget_perf = $finance->getBudgetPerformance($user_id);

// Upcoming bills due in next 7 days
$bills_res = $finance->getRecurringBills($user_id, 'active');
$upcoming_bills = [];
$bills_due_soon_count = 0;
$today_ts = strtotime(date('Y-m-d'));

while ($bill = $bills_res->fetch_assoc()) {
    $due_ts = strtotime($bill['next_due_date']);
    $diff_days = floor(($due_ts - $today_ts) / 86400);
    if ($diff_days <= 14) {
        $bill['days_left'] = $diff_days;
        $upcoming_bills[] = $bill;
        if ($diff_days <= 7) {
            $bills_due_soon_count++;
        }
    }
}

// Cash Flow Forecast snapshot (30 days)
$forecast_30 = $finance->generateCashFlowForecast($user_id, 30);

// Savings goals snapshot
$goals_res = $finance->getSavingsGoals($user_id);
$featured_goals = [];
while ($g = $goals_res->fetch_assoc()) {
    if (count($featured_goals) < 3) {
        $featured_goals[] = $g;
    }
}

// Counts
$accounts_count = $finance->getAccounts($user_id)->num_rows;
$transactions_count = $finance->getTransactions($user_id)->num_rows;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Executive Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="icon" href="../assets/img/logo.svg" data-next-head=""/>
    <link rel="stylesheet" href="../assets/css/tailwind.css?v=<?php echo asset_version(); ?>">
</head>

<body class="bg-slate-50 min-h-screen text-slate-900 font-sans antialiased">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8 gap-4 pb-6 border-b border-slate-200">
            <div>
                <div class="flex items-center space-x-2 text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">
                    <span>Portfolio Cockpit</span>
                    <span>/</span>
                    <span class="text-slate-800">Consolidated Intelligence</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">Executive Wealth & Cash Flow Overview</h1>
                <p class="text-sm text-slate-600 mt-1">Real-time ledger audit, forward cash projection, and obligations management.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="import.php" class="inline-flex items-center px-3 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg shadow-xs transition">
                    <svg class="h-4 w-4 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Import CSV
                </a>
                <a href="transactions.php" class="inline-flex items-center px-3.5 py-2 text-xs font-semibold text-white bg-slate-900 hover:bg-slate-800 rounded-lg shadow-xs transition">
                    <svg class="h-4 w-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    New Transaction
                </a>
            </div>
        </div>

        <!-- Metric KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
            <!-- Consolidated Net Worth Card -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Consolidated Net Worth</p>
                        <p class="text-2xl font-bold <?php echo $net_worth >= 0 ? 'text-slate-900' : 'text-rose-700'; ?> mt-1">
                            ₹<?php echo number_format($net_worth, 2); ?>
                        </p>
                    </div>
                    <div class="p-2.5 bg-slate-100 text-slate-800 rounded-lg">
                        <?php echo rupee_icon('h-5 w-5 text-slate-800'); ?>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <span>Assets - Liabilities</span>
                    <a href="accounts.php" class="font-semibold text-slate-800 hover:underline">Portfolios &rarr;</a>
                </div>
            </div>

            <!-- Liquid Cash Reserves -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Liquid Cash Reserves</p>
                        <p class="text-2xl font-bold text-slate-900 mt-1">
                            ₹<?php echo number_format($liquid_balance, 2); ?>
                        </p>
                    </div>
                    <div class="p-2.5 bg-blue-50 text-blue-700 rounded-lg">
                        <?php echo account_icon('h-5 w-5 text-blue-700'); ?>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <span>Bank & Cash Available</span>
                    <span class="text-slate-700 font-semibold"><?php echo $accounts_count; ?> accounts</span>
                </div>
            </div>

            <!-- Monthly Budget Guardrail -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Monthly Budget Burn</p>
                        <p class="text-2xl font-bold text-slate-900 mt-1">
                            <?php echo $budget_perf['overall_pct']; ?>%
                        </p>
                    </div>
                    <div class="p-2.5 bg-amber-50 text-amber-700 rounded-lg">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path d="M21.21 15.89A10 10 0 1 1 8 2.83"/>
                            <path d="M22 12A10 10 0 0 0 12 2v10z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-100">
                    <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden mb-1">
                        <div class="h-full rounded-full <?php 
                            if ($budget_perf['overall_pct'] >= 100) echo 'bg-rose-600';
                            elseif ($budget_perf['overall_pct'] >= 80) echo 'bg-amber-500';
                            else echo 'bg-emerald-600';
                        ?>" style="width: <?php echo min(100, $budget_perf['overall_pct']); ?>%;"></div>
                    </div>
                    <div class="flex justify-between text-[11px] text-slate-500">
                        <span>Spent: ₹<?php echo number_format($budget_perf['total_spent'], 0); ?></span>
                        <a href="budgets.php" class="font-semibold text-slate-800 hover:underline">Limits &rarr;</a>
                    </div>
                </div>
            </div>

            <!-- 30-Day Forward Forecast -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">30-Day Cash Forecast</p>
                        <p class="text-2xl font-bold <?php echo $forecast_30['ending_balance'] >= 0 ? 'text-slate-900' : 'text-rose-700'; ?> mt-1">
                            ₹<?php echo number_format($forecast_30['ending_balance'], 2); ?>
                        </p>
                    </div>
                    <div class="p-2.5 bg-indigo-50 text-indigo-700 rounded-lg">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/>
                            <polyline points="16 7 22 7 22 13"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <span>Valley: ₹<?php echo number_format($forecast_30['min_balance'], 0); ?></span>
                    <a href="forecast.php" class="font-semibold text-indigo-700 hover:underline">Simulation &rarr;</a>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Recent Ledger & Forward Cash Flow -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Forward Liquidity Horizon Snapshot -->
                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Forward Liquidity Horizon</h2>
                            <p class="text-xs text-slate-500">Run-rate simulation considering recurring bills over next 30 days</p>
                        </div>
                        <a href="forecast.php" class="text-xs font-semibold text-slate-700 hover:text-slate-900 flex items-center space-x-1">
                            <span>Open 90-Day Engine</span>
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"/>
                            </svg>
                        </a>
                    </div>

                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <span class="text-[11px] font-semibold uppercase text-slate-500 block">Daily Discretionary Velocity</span>
                            <span class="text-base font-bold <?php echo $forecast_30['daily_velocity'] >= 0 ? 'text-emerald-700' : 'text-rose-700'; ?>">
                                <?php echo ($forecast_30['daily_velocity'] >= 0 ? '+₹' : '-₹') . number_format(abs($forecast_30['daily_velocity']), 2); ?>/day
                            </span>
                        </div>
                        <div>
                            <span class="text-[11px] font-semibold uppercase text-slate-500 block">Projected 30d Valley</span>
                            <span class="text-base font-bold text-slate-900">
                                ₹<?php echo number_format($forecast_30['min_balance'], 2); ?>
                            </span>
                            <span class="text-[10px] text-slate-400 block"><?php echo date('M j', strtotime($forecast_30['min_balance_date'])); ?></span>
                        </div>
                        <div>
                            <span class="text-[11px] font-semibold uppercase text-slate-500 block">Scheduled Obligations</span>
                            <span class="text-base font-bold text-slate-900">
                                ₹<?php echo number_format($forecast_30['total_bills_projected'], 2); ?>
                            </span>
                        </div>
                        <div>
                            <a href="forecast.php" class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-semibold transition">
                                View Trajectory
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Recent Ledger Entries -->
                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Recent Ledger Activity</h2>
                            <p class="text-xs text-slate-500">Latest income and expense entries posted</p>
                        </div>
                        <a href="transactions.php" class="text-xs font-semibold text-slate-700 hover:text-slate-900 flex items-center space-x-1">
                            <span>View All Entries (<?php echo $transactions_count; ?>)</span>
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"/>
                            </svg>
                        </a>
                    </div>

                    <div class="space-y-3 max-h-[460px] overflow-y-auto pr-1 inscrolling-table-container">
                        <?php if ($recent_transactions->num_rows > 0): ?>
                            <?php while ($transaction = $recent_transactions->fetch_assoc()): ?>
                                <div class="flex items-center justify-between p-3.5 bg-slate-50 hover:bg-slate-100/70 border border-slate-200 rounded-xl transition duration-150">
                                    <div class="flex items-center space-x-3.5 min-w-0">
                                        <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 <?php echo $transaction['type'] === 'income' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'; ?>">
                                            <?php if ($transaction['type'] === 'income'): ?>
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                                    <line x1="12" y1="19" x2="12" y2="5"/>
                                                    <polyline points="5 12 12 5 19 12"/>
                                                </svg>
                                            <?php else: ?>
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                                    <line x1="12" y1="5" x2="12" y2="19"/>
                                                    <polyline points="19 12 12 19 5 12"/>
                                                </svg>
                                            <?php endif; ?>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-slate-900 truncate">
                                                <?php echo htmlspecialchars($transaction['description']); ?>
                                            </p>
                                            <div class="flex items-center text-xs text-slate-500 space-x-2 mt-0.5">
                                                <span><?php echo htmlspecialchars($transaction['account_name']); ?></span>
                                                <span>&bull;</span>
                                                <span><?php echo htmlspecialchars($transaction['category'] ?: 'Uncategorized'); ?></span>
                                                <span>&bull;</span>
                                                <span><?php echo date('M j, Y', strtotime($transaction['transaction_date'])); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right flex-shrink-0 pl-3">
                                        <p class="text-sm font-bold <?php echo $transaction['type'] === 'income' ? 'text-emerald-700' : 'text-slate-900'; ?>">
                                            <?php echo $transaction['type'] === 'income' ? '+' : '-'; ?>₹<?php echo number_format($transaction['amount'], 2); ?>
                                        </p>
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-semibold uppercase tracking-wider <?php echo $transaction['type'] === 'income' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'; ?>">
                                            <?php echo $transaction['type']; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-10 bg-slate-50 rounded-xl border border-dashed border-slate-300">
                                <p class="text-xs font-medium text-slate-500">No transactions recorded yet.</p>
                                <a href="transactions.php" class="text-xs font-semibold text-slate-900 underline mt-1 inline-block">Create first entry</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: Upcoming Bills & Quick Actions -->
            <div class="space-y-8">
                <!-- Upcoming Bills Due Widget -->
                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-2">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            <h2 class="text-base font-bold text-slate-900">Upcoming Bills (14d)</h2>
                        </div>
                        <a href="bills.php" class="text-xs font-semibold text-slate-700 hover:text-slate-900">Manage</a>
                    </div>

                    <div class="space-y-2.5">
                        <?php if (!empty($upcoming_bills)): ?>
                            <?php foreach ($upcoming_bills as $b): ?>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between">
                                    <div class="min-w-0 pr-2">
                                        <div class="font-bold text-xs text-slate-900 truncate"><?php echo htmlspecialchars($b['title']); ?></div>
                                        <div class="text-[11px] text-slate-500 mt-0.5">
                                            Due <?php echo date('M j', strtotime($b['next_due_date'])); ?> 
                                            <?php if ($b['days_left'] < 0): ?>
                                                <span class="text-rose-600 font-bold">(Overdue)</span>
                                            <?php elseif ($b['days_left'] <= 3): ?>
                                                <span class="text-amber-700 font-bold">(In <?php echo $b['days_left']; ?>d)</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <div class="text-xs font-bold text-slate-900">₹<?php echo number_format($b['amount'], 2); ?></div>
                                        <a href="bills.php" class="text-[10px] font-bold text-emerald-800 hover:underline mt-0.5 inline-block">Review &rarr;</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-xs text-slate-500 py-3 text-center">No bills scheduled in the next 14 days.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Savings Goals Quick View -->
                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-base font-bold text-slate-900">Savings Goals</h2>
                        <a href="goals.php" class="text-xs font-semibold text-slate-700 hover:text-slate-900">View All</a>
                    </div>

                    <div class="space-y-3 max-h-[460px] overflow-y-auto pr-1 inscrolling-table-container">
                        <?php if (!empty($featured_goals)): ?>
                            <?php foreach ($featured_goals as $fg): 
                                $fpct = ($fg['target_amount'] > 0) ? round(($fg['current_amount'] / $fg['target_amount']) * 100, 1) : 0;
                            ?>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                                    <div class="flex justify-between items-center text-xs mb-1 font-semibold">
                                        <span class="text-slate-900 truncate pr-2"><?php echo htmlspecialchars($fg['title']); ?></span>
                                        <span class="text-slate-600"><?php echo $fpct; ?>%</span>
                                    </div>
                                    <div class="w-full bg-slate-200 h-1.5 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full bg-emerald-600" style="width: <?php echo min(100, $fpct); ?>%;"></div>
                                    </div>
                                    <div class="flex justify-between text-[10px] text-slate-500 mt-1">
                                        <span>₹<?php echo number_format($fg['current_amount'], 0); ?></span>
                                        <span>₹<?php echo number_format($fg['target_amount'], 0); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-xs text-slate-500 py-2 text-center">No active milestone goals defined.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Operations Navigator -->
                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                    <h2 class="text-base font-bold text-slate-900 mb-4">Quick Navigation</h2>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <a href="budgets.php" class="p-2.5 bg-slate-50 hover:bg-slate-100 rounded-lg border border-slate-200 text-slate-800 font-semibold text-center transition">
                            Category Budgets
                        </a>
                        <a href="bills.php" class="p-2.5 bg-slate-50 hover:bg-slate-100 rounded-lg border border-slate-200 text-slate-800 font-semibold text-center transition">
                            Recurring Bills
                        </a>
                        <a href="goals.php" class="p-2.5 bg-slate-50 hover:bg-slate-100 rounded-lg border border-slate-200 text-slate-800 font-semibold text-center transition">
                            Savings Goals
                        </a>
                        <a href="forecast.php" class="p-2.5 bg-slate-50 hover:bg-slate-100 rounded-lg border border-slate-200 text-slate-800 font-semibold text-center transition">
                            Cash Flow Engine
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>
