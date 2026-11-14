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
$csrf_token = $auth->generateCSRFToken();

// Handle form submissions (Post-Redirect-Get Pattern to prevent F5 resubmission)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$auth->validateCSRFRequest()) {
        $_SESSION['flash_toast'] = [
            'title' => 'Action Failed',
            'message' => 'Security token invalid or expired. Please try again.',
            'type' => 'error'
        ];
    } else {
        if (isset($_POST['add_transaction'])) {
            $account_id = intval($_POST['account_id'] ?? 0);
            $type = $_POST['type'] ?? '';
            $amount = floatval($_POST['amount'] ?? 0);
            $description = trim($_POST['description'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $transaction_date = $_POST['transaction_date'] ?? date('Y-m-d');

            if ($amount <= 0) {
                $_SESSION['flash_toast'] = ['title' => 'Action Failed', 'message' => 'Transaction amount must be greater than zero!', 'type' => 'error'];
            } elseif (!in_array($type, ['income', 'expense'], true)) {
                $_SESSION['flash_toast'] = ['title' => 'Action Failed', 'message' => 'Invalid transaction type selected!', 'type' => 'error'];
            } elseif ($finance->addTransaction($user_id, $account_id, $type, $amount, $description, $category, $transaction_date)) {
                $_SESSION['flash_toast'] = ['title' => 'Successfully Completed', 'message' => 'Transaction recorded successfully!', 'type' => 'success'];
            } else {
                $_SESSION['flash_toast'] = ['title' => 'Action Failed', 'message' => 'Failed to add transaction. Please verify account ownership!', 'type' => 'error'];
            }
        }

        if (isset($_POST['update_transaction'])) {
            $transaction_id = intval($_POST['transaction_id'] ?? 0);
            $account_id = intval($_POST['account_id'] ?? 0);
            $type = $_POST['type'] ?? '';
            $amount = floatval($_POST['amount'] ?? 0);
            $description = trim($_POST['description'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $transaction_date = $_POST['transaction_date'] ?? date('Y-m-d');

            if ($amount <= 0) {
                $_SESSION['flash_toast'] = ['title' => 'Action Failed', 'message' => 'Transaction amount must be greater than zero!', 'type' => 'error'];
            } elseif (!in_array($type, ['income', 'expense'], true)) {
                $_SESSION['flash_toast'] = ['title' => 'Action Failed', 'message' => 'Invalid transaction type selected!', 'type' => 'error'];
            } else {
                $result = $finance->updateTransaction($transaction_id, $user_id, $account_id, $type, $amount, $description, $category, $transaction_date);
                $_SESSION['flash_toast'] = [
                    'title' => $result['success'] ? 'Successfully Completed' : 'Action Failed',
                    'message' => $result['message'],
                    'type' => $result['success'] ? 'success' : 'error'
                ];
            }
        }

        if (isset($_POST['delete_transaction'])) {
            $transaction_id = intval($_POST['transaction_id'] ?? 0);
            $result = $finance->deleteTransaction($transaction_id, $user_id);
            $_SESSION['flash_toast'] = [
                'title' => $result['success'] ? 'Successfully Completed' : 'Action Failed',
                'message' => $result['message'],
                'type' => $result['success'] ? 'success' : 'error'
            ];
        }
    }

    $redirect_url = 'transactions.php';
    if (!empty($_GET['account_id'])) {
        $redirect_url .= '?account_id=' . intval($_GET['account_id']);
    }
    header('Location: ' . $redirect_url);
    exit;
}

// Get transaction for editing
$edit_transaction = null;
if (isset($_GET['edit_transaction'])) {
    $edit_transaction = $finance->getTransaction(intval($_GET['edit_transaction']), $user_id);
}

$accounts = $finance->getAccounts($user_id);
$transactions = $finance->getTransactions($user_id, $_GET);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Transactions - <?php echo SITE_NAME; ?></title>
    <link rel="icon" href="../assets/img/logo.svg" data-next-head=""/>
    <link rel="stylesheet" href="../assets/css/tailwind.css?v=<?php echo asset_version(); ?>">
</head>

<body class="bg-slate-50 min-h-screen text-slate-900 font-sans antialiased">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8 pb-6 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">General Ledger Transactions</h1>
                <p class="text-sm text-slate-500 mt-1">Audit, register, and reconcile income and operational expense flows.</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="import.php" class="inline-flex items-center px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition shadow-sm">
                    <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Import Statement CSV
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Add/Edit Transaction Form -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs">
                    <div class="flex items-center space-x-2.5 mb-5 pb-3 border-b border-slate-100">
                        <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 3h12M6 8h12M6 13l8.5 8M6 13h3a4 4 0 0 0 0-8"/>
                            </svg>
                        </div>
                        <h2 class="text-base font-bold text-slate-900">
                            <?php echo $edit_transaction ? 'Edit Ledger Entry' : 'Post Transaction'; ?>
                        </h2>
                    </div>

                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <?php if ($edit_transaction): ?>
                            <input type="hidden" name="update_transaction" value="1">
                            <input type="hidden" name="transaction_id" value="<?php echo $edit_transaction['id']; ?>">
                        <?php else: ?>
                            <input type="hidden" name="add_transaction" value="1">
                        <?php endif; ?>

                        <div>
                            <label for="type" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Flow Type</label>
                            <select id="type" name="type" required
                                class="w-full px-3.5 py-2.5 text-base md:text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900 transition bg-white text-slate-900">
                                <option value="income" <?php echo ($edit_transaction && $edit_transaction['type'] === 'income') || (isset($_GET['type']) && $_GET['type'] === 'income') ? 'selected' : ''; ?>>Credit / Inflow (Income)</option>
                                <option value="expense" <?php echo ($edit_transaction && $edit_transaction['type'] === 'expense') || (isset($_GET['type']) && $_GET['type'] === 'expense') ? 'selected' : ''; ?>>Debit / Outflow (Expense)</option>
                            </select>
                        </div>

                        <div>
                            <label for="account_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Portfolio Account</label>
                            <select id="account_id" name="account_id" required
                                class="w-full px-3.5 py-2.5 text-base md:text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900 transition bg-white text-slate-900">
                                <?php
                                $accounts->data_seek(0);
                                while ($account = $accounts->fetch_assoc()): ?>
                                    <option value="<?php echo $account['id']; ?>"
                                        <?php echo ($edit_transaction && $edit_transaction['account_id'] == $account['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($account['name']); ?> (<?php echo ucfirst($account['type']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div>
                            <label for="amount" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Transaction Amount (INR)</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 font-bold">₹</span>
                                <input type="number" id="amount" name="amount" step="0.01" min="0.01" required
                                    class="w-full pl-8 pr-3.5 py-2.5 text-base md:text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900 transition bg-white text-slate-900 placeholder:text-slate-400"
                                    value="<?php echo $edit_transaction ? htmlspecialchars($edit_transaction['amount']) : ''; ?>"
                                    placeholder="0.00">
                            </div>
                        </div>

                        <div>
                            <label for="description" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Description / Memo</label>
                            <input type="text" id="description" name="description" required
                                class="w-full px-3.5 py-2.5 text-base md:text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900 transition bg-white text-slate-900 placeholder:text-slate-400"
                                value="<?php echo $edit_transaction ? htmlspecialchars($edit_transaction['description']) : ''; ?>"
                                placeholder="e.g. AWS Cloud Invoice, Consulting Retainer">
                        </div>

                        <div>
                            <label for="category" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Accounting Category</label>
                            <input type="text" id="category" name="category"
                                class="w-full px-3.5 py-2.5 text-base md:text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900 transition bg-white text-slate-900 placeholder:text-slate-400"
                                value="<?php echo $edit_transaction ? htmlspecialchars($edit_transaction['category']) : ''; ?>"
                                placeholder="e.g. Infrastructure, Payroll, Utilities">
                        </div>

                        <div>
                            <label for="transaction_date" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Posting Date</label>
                            <input type="date" id="transaction_date" name="transaction_date" required
                                class="w-full px-3.5 py-2.5 text-base md:text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900 transition bg-white text-slate-900"
                                value="<?php echo $edit_transaction ? htmlspecialchars($edit_transaction['transaction_date']) : date('Y-m-d'); ?>">
                        </div>

                        <div class="flex space-x-2.5 pt-2">
                            <button type="submit"
                                class="flex-1 bg-slate-900 hover:bg-slate-800 text-white py-2.5 px-4 rounded-lg text-xs font-semibold uppercase tracking-wider transition shadow-xs">
                                <?php echo $edit_transaction ? 'Save Update' : 'Record Transaction'; ?>
                            </button>
                            <?php if ($edit_transaction): ?>
                                <a href="transactions.php"
                                    class="px-4 py-2.5 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg text-xs font-semibold uppercase tracking-wider transition">
                                    Cancel
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Transactions List & Filter Panel -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Filters Card -->
                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-4">Query Ledger</h3>
                    <form id="filterForm" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3.5">
                        <div>
                            <label for="filter_account" class="block text-[11px] font-semibold uppercase text-slate-600 mb-1">Account</label>
                            <select id="filter_account" name="account_id"
                                class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:ring-slate-900 focus:border-slate-900 bg-white text-slate-800">
                                <option value="">All Accounts</option>
                                <?php
                                $accounts->data_seek(0);
                                while ($account = $accounts->fetch_assoc()): ?>
                                    <option value="<?php echo $account['id']; ?>" <?php echo isset($_GET['account_id']) && $_GET['account_id'] == $account['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($account['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div>
                            <label for="filter_type" class="block text-[11px] font-semibold uppercase text-slate-600 mb-1">Flow</label>
                            <select id="filter_type" name="type"
                                class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:ring-slate-900 focus:border-slate-900 bg-white text-slate-800">
                                <option value="">All Flows</option>
                                <option value="income" <?php echo isset($_GET['type']) && $_GET['type'] === 'income' ? 'selected' : ''; ?>>Income (Credit)</option>
                                <option value="expense" <?php echo isset($_GET['type']) && $_GET['type'] === 'expense' ? 'selected' : ''; ?>>Expense (Debit)</option>
                            </select>
                        </div>

                        <div>
                            <label for="filter_start_date" class="block text-[11px] font-semibold uppercase text-slate-600 mb-1">Start Date</label>
                            <input type="date" id="filter_start_date" name="start_date"
                                class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:ring-slate-900 focus:border-slate-900 bg-white text-slate-800"
                                value="<?php echo htmlspecialchars($_GET['start_date'] ?? ''); ?>">
                        </div>

                        <div>
                            <label for="filter_end_date" class="block text-[11px] font-semibold uppercase text-slate-600 mb-1">End Date</label>
                            <input type="date" id="filter_end_date" name="end_date"
                                class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:ring-slate-900 focus:border-slate-900 bg-white text-slate-800"
                                value="<?php echo htmlspecialchars($_GET['end_date'] ?? ''); ?>">
                        </div>

                        <div class="sm:col-span-2 md:col-span-4 flex space-x-2 pt-2">
                            <button type="submit"
                                class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-semibold uppercase tracking-wider transition">
                                Apply Filter
                            </button>
                            <button type="button" id="resetFilters"
                                class="px-4 py-2 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg text-xs font-semibold uppercase tracking-wider transition">
                                Reset
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Ledger Entries List -->
                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs">
                    <div class="flex items-center justify-between mb-5">
                        <h2 class="text-base font-bold text-slate-900">Ledger History</h2>
                        <span class="text-xs text-slate-500 font-medium"><?php echo $transactions->num_rows; ?> records match</span>
                    </div>

                    <?php if ($transactions->num_rows > 0): ?>
                        <!-- Mobile / WebView Card List (< md screens) -->
                        <div class="block md:hidden space-y-3 max-h-[520px] overflow-y-auto pr-1 inscrolling-table-container">
                            <?php
                            $transactions->data_seek(0);
                            $mob_tx_sno = 1;
                            while ($transaction = $transactions->fetch_assoc()): ?>
                                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 hover:border-slate-300 transition">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-xs text-slate-400 font-mono">#<?php echo $mob_tx_sno++; ?></span>
                                            <span class="font-bold text-slate-900"><?php echo htmlspecialchars($transaction['description']); ?></span>
                                        </div>
                                        <span class="font-bold text-sm <?php echo $transaction['type'] === 'income' ? 'text-emerald-700' : 'text-slate-900'; ?>">
                                            <?php echo $transaction['type'] === 'income' ? '+' : '-'; ?>₹<?php echo number_format($transaction['amount'], 2); ?>
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between text-xs text-slate-500 mt-2">
                                        <span><?php echo htmlspecialchars($transaction['account_name']); ?></span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-200/80 text-slate-700"><?php echo htmlspecialchars($transaction['category'] ?: 'Uncategorized'); ?></span>
                                        <span><?php echo date('M j, Y', strtotime($transaction['transaction_date'])); ?></span>
                                    </div>
                                    <div class="flex justify-end space-x-2 mt-3 pt-2.5 border-t border-slate-200/80">
                                        <a href="?edit_transaction=<?php echo $transaction['id']; ?>"
                                            class="p-1.5 text-slate-600 hover:text-slate-900 hover:bg-slate-200/60 rounded-lg transition" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this transaction?')">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <input type="hidden" name="delete_transaction" value="1">
                                            <input type="hidden" name="transaction_id" value="<?php echo $transaction['id']; ?>">
                                            <button type="submit" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Delete">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                    <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>

                        <!-- Desktop Table (md+ screens) with Fixed Height & Inner Scrolling -->
                        <div class="hidden md:block overflow-x-auto max-h-[520px] overflow-y-auto border border-slate-200 rounded-xl inscrolling-table-container">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="sticky top-0 z-10 bg-slate-50 border-b border-slate-200">
                                    <tr class="bg-slate-50 text-slate-500 uppercase text-[11px] font-semibold tracking-wider text-left">
                                        <th class="px-4 py-3 bg-slate-50">S.No</th>
                                        <th class="px-4 py-3 bg-slate-50">Posting Date</th>
                                        <th class="px-4 py-3 bg-slate-50">Amount</th>
                                        <th class="px-4 py-3 bg-slate-50">Portfolio</th>
                                        <th class="px-4 py-3 bg-slate-50">Description</th>
                                        <th class="px-4 py-3 bg-slate-50">Category</th>
                                        <th class="px-4 py-3 text-right bg-slate-50">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    <?php
                                    $transactions->data_seek(0);
                                    $sno = 1;
                                    while ($transaction = $transactions->fetch_assoc()): ?>
                                        <tr class="hover:bg-slate-50 transition">
                                            <td class="px-4 py-3.5 text-xs text-slate-400 font-mono"><?php echo $sno++; ?></td>
                                            <td class="px-4 py-3.5 text-xs font-medium text-slate-600">
                                                <?php echo date('M j, Y', strtotime($transaction['transaction_date'])); ?>
                                            </td>
                                            <td class="px-4 py-3.5 text-sm font-bold <?php echo $transaction['type'] === 'income' ? 'text-emerald-700' : 'text-slate-900'; ?>">
                                                <?php echo $transaction['type'] === 'income' ? '+' : '-'; ?>₹<?php echo number_format($transaction['amount'], 2); ?>
                                            </td>
                                            <td class="px-4 py-3.5 text-xs font-medium text-slate-800">
                                                <?php echo htmlspecialchars($transaction['account_name']); ?>
                                            </td>
                                            <td class="px-4 py-3.5 text-sm font-semibold text-slate-900">
                                                <?php echo htmlspecialchars($transaction['description']); ?>
                                            </td>
                                            <td class="px-4 py-3.5">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700">
                                                    <?php echo htmlspecialchars($transaction['category'] ?: 'Uncategorized'); ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3.5 text-right">
                                                <div class="inline-flex items-center space-x-1">
                                                    <a href="?edit_transaction=<?php echo $transaction['id']; ?>"
                                                        class="p-1.5 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition"
                                                        title="Edit Transaction">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                            <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                        </svg>
                                                    </a>
                                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this transaction?')">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                        <input type="hidden" name="delete_transaction" value="1">
                                                        <input type="hidden" name="transaction_id" value="<?php echo $transaction['id']; ?>">
                                                        <button type="submit"
                                                            class="p-1.5 text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded-lg transition"
                                                            title="Delete Transaction">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                                <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-10 bg-slate-50 rounded-xl border border-dashed border-slate-300">
                            <p class="text-xs text-slate-500">No transactions found matching your filters.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        document.getElementById('resetFilters').addEventListener('click', function() {
            window.location.href = 'transactions.php';
        });
    </script>
</body>

</html>
