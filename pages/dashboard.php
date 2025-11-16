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
$balance_summary = $finance->getTotalBalance($user_id);
$recent_transactions = $finance->getRecentTransactions($user_id, 5);
$recent_transfers = $finance->getTransferHistory($user_id, 3);

// Calculate some stats for the cards
$accounts_count = $finance->getAccounts($user_id)->num_rows;
$transactions_count = $finance->getTransactions($user_id)->num_rows;
$transfers_count = $finance->getTransfers($user_id)->num_rows;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="icon" href="/assets/img/logo.svg" data-next-head=""/>
    <link rel="stylesheet" href="/assets/css/tailwind.css">
</head>

<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Header Section -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">Dashboard</h1>
            <p class="text-gray-600">Welcome back! Here's your financial overview.</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Accounts Card -->
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Accounts</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $accounts_count; ?></p>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-xl">
                        <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Transactions Card -->
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Transactions</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $transactions_count; ?></p>
                    </div>
                    <div class="p-3 bg-green-100 rounded-xl">
                        <svg class="h-6 w-6 text-green-600" xmlns="http://www.w3.org/2000/svg" version="1.0" viewBox="40 -1 170 250">
                            <path fill="#10B981" d="M153 23h41l15-23H55L40 23h26c27 0 52 2 62 25H55L40 71h91v1c0 17-14 43-60 43H48v22l90 113h41L85 133c39-2 75-24 80-62h29l15-23h-45c-1-9-5-18-11-25z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Transfers Card -->
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Transfers</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $transfers_count; ?></p>
                    </div>
                    <div class="p-3 bg-purple-100 rounded-xl">
                        <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Net Worth Card -->
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-indigo-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Net Worth</p>
                        <p class="text-2xl font-bold text-indigo-600 flex items-center">
                            <svg class="h-6 w-6 mr-1" xmlns="http://www.w3.org/2000/svg" version="1.0" viewBox="40 -1 170 250">
                                <path fill="#8B5CF6" d="M153 23h41l15-23H55L40 23h26c27 0 52 2 62 25H55L40 71h91v1c0 17-14 43-60 43H48v22l90 113h41L85 133c39-2 75-24 80-62h29l15-23h-45c-1-9-5-18-11-25z" />
                            </svg>
                            <?php echo number_format($balance_summary['total_balance'] ?? 0, 2); ?>
                        </p>
                    </div>
                    <div class="p-3 bg-indigo-100 rounded-xl">
                        <svg class="h-6 w-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" version="1.0" viewBox="40 -1 170 250">
                            <path fill="#8B5CF6" d="M153 23h41l15-23H55L40 23h26c27 0 52 2 62 25H55L40 71h91v1c0 17-14 43-60 43H48v22l90 113h41L85 133c39-2 75-24 80-62h29l15-23h-45c-1-9-5-18-11-25z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Balance Overview -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-800">Balance Overview</h2>
                        <div class="p-2 bg-gray-100 rounded-lg">
                            <svg class="h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Bank Balance -->
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 border border-blue-200">
                            <div class="flex items-center space-x-3">
                                <div class="p-2 bg-blue-500 rounded-lg">
                                    <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-blue-600">Bank Balance</p>
                                    <p class="text-lg font-bold text-blue-800">₹<?php echo number_format($balance_summary['bank_balance'] ?? 0, 2); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Cash Balance -->
                        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4 border border-green-200">
                            <div class="flex items-center space-x-3">
                                <div class="p-2 bg-green-500 rounded-lg">
                                    <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-green-600">Cash Balance</p>
                                    <p class="text-lg font-bold text-green-800">₹<?php echo number_format($balance_summary['cash_balance'] ?? 0, 2); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Total Balance -->
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-4 border border-purple-200">
                            <div class="flex items-center space-x-3">
                                <div class="p-2 bg-purple-500 rounded-lg">
                                    <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-purple-600">Total Balance</p>
                                    <p class="text-lg font-bold text-purple-800">₹<?php echo number_format($balance_summary['total_balance'] ?? 0, 2); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-800">Recent Transactions</h2>
                        <a href="transactions.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center space-x-1">
                            <span>View All</span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>

                    <div class="space-y-4">
                        <?php if ($recent_transactions->num_rows > 0): ?>
                            <?php while ($transaction = $recent_transactions->fetch_assoc()): ?>
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition duration-200 gap-3">
                                    <!-- Left Section - Icon and Details -->
                                    <div class="flex items-start sm:items-center space-x-4 flex-1 min-w-0">
                                        <!-- Icon -->
                                        <div class="<?php echo $transaction['type'] === 'income' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'; ?> p-3 rounded-lg flex-shrink-0">
                                            <?php if ($transaction['type'] === 'income'): ?>
                                                <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                                </svg>
                                            <?php else: ?>
                                                <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m0 0l-4-4m4 4l4-4" />
                                                </svg>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Text Content -->
                                        <div class="min-w-0 flex-1">
                                            <p class="font-medium text-gray-800 text-sm sm:text-base truncate">
                                                <?php echo htmlspecialchars($transaction['description']); ?>
                                            </p>
                                            <div class="flex flex-col xs:flex-row xs:items-center text-xs sm:text-sm text-gray-500 gap-1 xs:gap-2 mt-1">
                                                <span class="truncate"><?php echo $transaction['account_name']; ?></span>
                                                <span class="hidden xs:inline">•</span>
                                                <span><?php echo date('M j, Y', strtotime($transaction['transaction_date'])); ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right Section - Amount and Type -->
                                    <div class="flex items-center justify-between sm:justify-end sm:flex-col sm:items-end gap-2 sm:gap-1 flex-shrink-0">
                                        <p class="<?php echo $transaction['type'] === 'income' ? 'text-green-600' : 'text-red-600'; ?> font-semibold text-sm sm:text-base whitespace-nowrap">
                                            <?php echo $transaction['type'] === 'income' ? '+' : '-'; ?>₹<?php echo number_format($transaction['amount'], 2); ?>
                                        </p>
                                        <p class="text-xs text-gray-500 capitalize hidden sm:block">
                                            <?php echo $transaction['type']; ?>
                                        </p>
                                        <!-- Mobile type badge -->
                                        <span class="sm:hidden text-xs px-2 py-1 rounded-full <?php echo $transaction['type'] === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo $transaction['type']; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <div class="p-4 bg-gray-100 rounded-full inline-flex mb-4">
                                    <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                    </svg>
                                </div>
                                <p class="text-gray-500 mb-2">No transactions yet</p>
                                <a href="transactions.php" class="text-blue-600 hover:text-blue-800 font-medium">Add your first transaction!</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-8">
                <!-- Quick Actions -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-800">Quick Actions</h2>
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <svg class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <a href="transactions.php?action=add"
                            class="flex items-center justify-between p-4 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl hover:from-green-600 hover:to-green-700 transition duration-200 group">
                            <div class="flex items-center space-x-3">
                                <div class="p-2 bg-white bg-opacity-20 rounded-lg">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                </div>
                                <span class="font-medium">Add Income</span>
                            </div>
                            <svg class="h-4 w-4 transform group-hover:translate-x-1 transition duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>

                        <a href="transactions.php?action=add&type=expense"
                            class="flex items-center justify-between p-4 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl hover:from-red-600 hover:to-red-700 transition duration-200 group">
                            <div class="flex items-center space-x-3">
                                <div class="p-2 bg-white bg-opacity-20 rounded-lg">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                    </svg>
                                </div>
                                <span class="font-medium">Add Expense</span>
                            </div>
                            <svg class="h-4 w-4 transform group-hover:translate-x-1 transition duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>

                        <a href="accounts.php"
                            class="flex items-center justify-between p-4 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl hover:from-blue-600 hover:to-blue-700 transition duration-200 group">
                            <div class="flex items-center space-x-3">
                                <div class="p-2 bg-white bg-opacity-20 rounded-lg">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <span class="font-medium">Manage Accounts</span>
                            </div>
                            <svg class="h-4 w-4 transform group-hover:translate-x-1 transition duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>

                        <a href="transfers.php"
                            class="flex items-center justify-between p-4 bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-xl hover:from-purple-600 hover:to-purple-700 transition duration-200 group">
                            <div class="flex items-center space-x-3">
                                <div class="p-2 bg-white bg-opacity-20 rounded-lg">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                    </svg>
                                </div>
                                <span class="font-medium">Transfer Funds</span>
                            </div>
                            <svg class="h-4 w-4 transform group-hover:translate-x-1 transition duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Recent Transfers -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-800">Recent Transfers</h2>
                        <a href="transfers.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center space-x-1">
                            <span>View All</span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>

                    <div class="space-y-4">
                        <?php if ($recent_transfers->num_rows > 0): ?>
                            <?php while ($transfer = $recent_transfers->fetch_assoc()): ?>
                                <div class="p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition duration-200">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center space-x-2">
                                            <div class="p-2 bg-purple-100 rounded-lg">
                                                <svg class="h-4 w-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                                </svg>
                                            </div>
                                            <span class="text-purple-600 font-semibold">₹<?php echo number_format($transfer['amount'], 2); ?></span>
                                        </div>
                                        <span class="text-xs text-gray-500"><?php echo date('M j', strtotime($transfer['transfer_date'])); ?></span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600 font-medium"><?php echo htmlspecialchars($transfer['from_account_name']); ?></span>
                                        <svg class="h-4 w-4 text-gray-400 mx-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                        <span class="text-gray-600 font-medium"><?php echo htmlspecialchars($transfer['to_account_name']); ?></span>
                                    </div>
                                    <?php if ($transfer['description']): ?>
                                        <p class="text-xs text-gray-500 mt-2 truncate"><?php echo htmlspecialchars($transfer['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <div class="p-4 bg-gray-100 rounded-full inline-flex mb-4">
                                    <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                    </svg>
                                </div>
                                <p class="text-gray-500 mb-2">No transfers yet</p>
                                <a href="transfers.php" class="text-blue-600 hover:text-blue-800 font-medium">Make your first transfer!</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>