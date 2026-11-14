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

$valid_types = ['bank', 'cash', 'credit_card', 'investment', 'loan'];

// Handle form submissions (Post-Redirect-Get Pattern to prevent F5 resubmission)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$auth->validateCSRFRequest()) {
        $_SESSION['flash_toast'] = [
            'title' => 'Action Failed',
            'message' => 'Security token invalid or expired. Please try again.',
            'type' => 'error'
        ];
    } else {
        if (isset($_POST['create_account'])) {
            $name = trim($_POST['name'] ?? '');
            $type = $_POST['type'] ?? '';
            $initial_balance = floatval($_POST['initial_balance'] ?? 0);
            $credit_limit = floatval($_POST['credit_limit'] ?? 0);
            $interest_rate = floatval($_POST['interest_rate'] ?? 0);
            $due_day = !empty($_POST['due_day']) ? intval($_POST['due_day']) : null;

            if (empty($name)) {
                $_SESSION['flash_toast'] = ['title' => 'Action Failed', 'message' => 'Account name cannot be empty!', 'type' => 'error'];
            } elseif (!in_array($type, $valid_types, true)) {
                $_SESSION['flash_toast'] = ['title' => 'Action Failed', 'message' => 'Invalid account classification selected!', 'type' => 'error'];
            } elseif ($finance->createAccount($user_id, $name, $type, $initial_balance, $credit_limit, $interest_rate, $due_day)) {
                $_SESSION['flash_toast'] = ['title' => 'Successfully Completed', 'message' => 'Account registered successfully!', 'type' => 'success'];
            } else {
                $_SESSION['flash_toast'] = ['title' => 'Action Failed', 'message' => 'Failed to create account!', 'type' => 'error'];
            }
        }

        if (isset($_POST['update_account'])) {
            $account_id = intval($_POST['account_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $type = $_POST['type'] ?? '';
            $credit_limit = floatval($_POST['credit_limit'] ?? 0);
            $interest_rate = floatval($_POST['interest_rate'] ?? 0);
            $due_day = !empty($_POST['due_day']) ? intval($_POST['due_day']) : null;

            if (empty($name)) {
                $_SESSION['flash_toast'] = ['title' => 'Action Failed', 'message' => 'Account name cannot be empty!', 'type' => 'error'];
            } elseif (!in_array($type, $valid_types, true)) {
                $_SESSION['flash_toast'] = ['title' => 'Action Failed', 'message' => 'Invalid account classification selected!', 'type' => 'error'];
            } else {
                $result = $finance->updateAccount($account_id, $user_id, $name, $type, $credit_limit, $interest_rate, $due_day);
                $_SESSION['flash_toast'] = [
                    'title' => $result['success'] ? 'Successfully Completed' : 'Action Failed',
                    'message' => $result['message'],
                    'type' => $result['success'] ? 'success' : 'error'
                ];
            }
        }

        if (isset($_POST['delete_account'])) {
            $account_id = intval($_POST['account_id'] ?? 0);
            $result = $finance->deleteAccount($account_id, $user_id);
            $_SESSION['flash_toast'] = [
                'title' => $result['success'] ? 'Successfully Completed' : 'Action Failed',
                'message' => $result['message'],
                'type' => $result['success'] ? 'success' : 'error'
            ];
        }
    }
    header('Location: accounts.php');
    exit;
}

// Get account for editing
$edit_account = null;
if (isset($_GET['edit_account'])) {
    $edit_account = $finance->getAccount(intval($_GET['edit_account']), $user_id);
}

$accounts = $finance->getAccounts($user_id);
$balance_summary = $finance->getTotalBalance($user_id);
$net_worth = (float)($balance_summary['net_worth'] ?? 0);
$total_assets = (float)($balance_summary['total_assets'] ?? 0);
$total_liabilities = (float)($balance_summary['total_liabilities'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Accounts & Portfolios - <?php echo SITE_NAME; ?></title>
    <link rel="icon" href="../assets/img/logo.svg" data-next-head=""/>
    <link rel="stylesheet" href="../assets/css/tailwind.css?v=<?php echo asset_version(); ?>">
</head>

<body class="bg-slate-50 min-h-screen text-slate-900 font-sans antialiased">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8 pb-6 border-b border-slate-200">
            <div class="flex items-center space-x-2 text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">
                <span>Asset Management</span>
                <span>/</span>
                <span class="text-slate-800">Portfolio Structure</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">Accounts & Portfolio Management</h1>
            <p class="text-sm text-slate-600 mt-1">Configure banking institutions, physical cash reserves, credit cards, investments, and loan facilities.</p>
        </div>

        <!-- Net Worth KPI Strip -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider">Consolidated Net Worth</span>
                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-800">
                        <?php echo rupee_icon('w-4 h-4 text-slate-800'); ?>
                    </div>
                </div>
                <div class="text-2xl font-bold <?php echo $net_worth >= 0 ? 'text-slate-900' : 'text-rose-700'; ?>">
                    ₹<?php echo number_format($net_worth, 2); ?>
                </div>
                <div class="text-xs text-slate-500 mt-1">Total Assets minus Total Liabilities</div>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider">Total Gross Assets</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/>
                            <polyline points="16 7 22 7 22 13"/>
                        </svg>
                    </div>
                </div>
                <div class="text-2xl font-bold text-emerald-700">
                    ₹<?php echo number_format($total_assets, 2); ?>
                </div>
                <div class="text-xs text-slate-500 mt-1">Bank + Cash + Investment Holdings</div>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider">Total Liabilities</span>
                    <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-700 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <polyline points="22 17 13.5 8.5 8.5 13.5 2 7"/>
                            <polyline points="16 17 22 17 22 11"/>
                        </svg>
                    </div>
                </div>
                <div class="text-2xl font-bold text-rose-700">
                    ₹<?php echo number_format($total_liabilities, 2); ?>
                </div>
                <div class="text-xs text-slate-500 mt-1">Credit Cards + Loan Outstanding</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Form & Portfolio Breakdown -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Create/Edit Form -->
                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                    <div class="flex items-center space-x-2.5 mb-5 pb-3 border-b border-slate-100">
                        <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
                            <?php echo account_icon('h-4 w-4 text-slate-700'); ?>
                        </div>
                        <h2 class="text-base font-bold text-slate-900">
                            <?php echo $edit_account ? 'Edit Portfolio / Account' : 'Register New Account'; ?>
                        </h2>
                    </div>

                    <form method="POST" class="space-y-4" id="accountForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <?php if ($edit_account): ?>
                            <input type="hidden" name="update_account" value="1">
                            <input type="hidden" name="account_id" value="<?php echo $edit_account['id']; ?>">
                        <?php else: ?>
                            <input type="hidden" name="create_account" value="1">
                        <?php endif; ?>

                        <div>
                            <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Account Label</label>
                            <input type="text" id="name" name="name" required
                                class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900 transition bg-white text-slate-900 placeholder:text-slate-400"
                                placeholder="e.g. HDFC Salary, SBI Demat, Axis Credit Card"
                                value="<?php echo $edit_account ? htmlspecialchars($edit_account['name']) : ''; ?>">
                        </div>

                        <div>
                            <label for="type" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Account Classification</label>
                            <select id="type" name="type" required onchange="handleAccountTypeChange(this.value)"
                                class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900 transition bg-white text-slate-900">
                                <option value="bank" <?php echo ($edit_account && $edit_account['type'] === 'bank') ? 'selected' : ''; ?>>Bank / Savings / Current</option>
                                <option value="cash" <?php echo ($edit_account && $edit_account['type'] === 'cash') ? 'selected' : ''; ?>>Physical Cash / Wallet</option>
                                <option value="credit_card" <?php echo ($edit_account && $edit_account['type'] === 'credit_card') ? 'selected' : ''; ?>>Credit Card</option>
                                <option value="investment" <?php echo ($edit_account && $edit_account['type'] === 'investment') ? 'selected' : ''; ?>>Investment / Stocks / Mutual Fund</option>
                                <option value="loan" <?php echo ($edit_account && $edit_account['type'] === 'loan') ? 'selected' : ''; ?>>Loan / EMI Obligation</option>
                            </select>
                        </div>

                        <?php if (!$edit_account): ?>
                            <div>
                                <label for="initial_balance" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Initial Opening Balance (₹)</label>
                                <input type="number" id="initial_balance" name="initial_balance" step="0.01" value="0.00"
                                    class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900 transition bg-white text-slate-900 placeholder:text-slate-400">
                                <p class="text-[11px] text-slate-500 mt-1">For Credit Cards/Loans, enter initial outstanding as negative or zero.</p>
                            </div>
                        <?php else: ?>
                            <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
                                <p class="text-[11px] font-semibold uppercase text-slate-500">Current Ledger Balance</p>
                                <p class="text-lg font-bold text-slate-900 mt-0.5">₹<?php echo number_format($edit_account['current_balance'], 2); ?></p>
                            </div>
                        <?php endif; ?>

                        <!-- Conditional Credit Card Fields -->
                        <div id="creditCardFields" class="<?php echo ($edit_account && $edit_account['type'] === 'credit_card') ? '' : 'hidden'; ?> space-y-3 pt-2 border-t border-slate-100">
                            <div>
                                <label for="credit_limit" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Total Credit Limit (₹)</label>
                                <input type="number" id="credit_limit" name="credit_limit" step="0.01" min="0"
                                    class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900 bg-white"
                                    value="<?php echo $edit_account ? $edit_account['credit_limit'] : '0.00'; ?>">
                            </div>
                            <div>
                                <label for="due_day_cc" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Billing Due Day (Day of Month)</label>
                                <input type="number" id="due_day_cc" name="due_day" min="1" max="31" placeholder="e.g. 15"
                                    class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900 bg-white"
                                    value="<?php echo $edit_account ? $edit_account['due_day'] : ''; ?>">
                            </div>
                        </div>

                        <!-- Conditional Loan Fields -->
                        <div id="loanFields" class="<?php echo ($edit_account && $edit_account['type'] === 'loan') ? '' : 'hidden'; ?> space-y-3 pt-2 border-t border-slate-100">
                            <div>
                                <label for="interest_rate" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Interest Rate (% p.a.)</label>
                                <input type="number" id="interest_rate" name="interest_rate" step="0.01" min="0" placeholder="e.g. 8.50"
                                    class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900 bg-white"
                                    value="<?php echo $edit_account ? $edit_account['interest_rate'] : '0.00'; ?>">
                            </div>
                        </div>

                        <div class="flex space-x-2.5 pt-2">
                            <button type="submit"
                                class="flex-1 bg-slate-900 hover:bg-slate-800 text-white py-2.5 px-4 rounded-lg text-xs font-semibold uppercase tracking-wider transition shadow-sm">
                                <?php echo $edit_account ? 'Save Changes' : 'Create Account'; ?>
                            </button>
                            <?php if ($edit_account): ?>
                                <a href="accounts.php"
                                    class="px-4 py-2.5 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg text-xs font-semibold uppercase tracking-wider transition">
                                    Cancel
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Detailed Liquidity Breakdown -->
                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-4">Portfolio Allocation</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between items-center">
                            <span class="flex items-center text-slate-600">
                                <span class="w-2.5 h-2.5 rounded-full bg-blue-600 mr-2"></span>
                                Bank Accounts
                            </span>
                            <span class="font-bold text-slate-900">₹<?php echo number_format($balance_summary['bank_balance'] ?? 0, 2); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="flex items-center text-slate-600">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 mr-2"></span>
                                Cash Reserves
                            </span>
                            <span class="font-bold text-slate-900">₹<?php echo number_format($balance_summary['cash_balance'] ?? 0, 2); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="flex items-center text-slate-600">
                                <span class="w-2.5 h-2.5 rounded-full bg-indigo-600 mr-2"></span>
                                Investments / Stocks
                            </span>
                            <span class="font-bold text-slate-900">₹<?php echo number_format($balance_summary['investment_balance'] ?? 0, 2); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="flex items-center text-slate-600">
                                <span class="w-2.5 h-2.5 rounded-full bg-purple-600 mr-2"></span>
                                Credit Card Balance
                            </span>
                            <span class="font-bold text-purple-700">₹<?php echo number_format($balance_summary['credit_card_balance'] ?? 0, 2); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="flex items-center text-slate-600">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-600 mr-2"></span>
                                Loan Liabilities
                            </span>
                            <span class="font-bold text-amber-700">₹<?php echo number_format($balance_summary['loan_balance'] ?? 0, 2); ?></span>
                        </div>
                        <div class="flex justify-between items-center border-t border-slate-200 pt-3 font-semibold">
                            <span class="text-slate-800">Liquid Cash Flow Total</span>
                            <span class="font-bold text-slate-900">₹<?php echo number_format($balance_summary['liquid_balance'] ?? 0, 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Registered Portfolios List -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-5">
                        <h2 class="text-base font-bold text-slate-900">Registered Accounts & Holdings</h2>
                        <span class="text-xs text-slate-500 font-medium"><?php echo $accounts->num_rows; ?> accounts listed</span>
                    </div>

                    <?php if ($accounts->num_rows > 0): ?>
                        <!-- Mobile / WebView Card List (< md screens) -->
                        <div class="block md:hidden space-y-3 max-h-[520px] overflow-y-auto pr-1 inscrolling-table-container">
                            <?php
                            $accounts->data_seek(0);
                            $mob_sno = 1;
                            while ($account = $accounts->fetch_assoc()): 
                                $acc_type = $account['type'];
                            ?>
                                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 hover:border-slate-300 transition">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-xs text-slate-400 font-mono">#<?php echo $mob_sno++; ?></span>
                                            <span class="font-bold text-slate-900"><?php echo htmlspecialchars($account['name']); ?></span>
                                        </div>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider <?php 
                                            if ($acc_type === 'bank') echo 'bg-blue-50 text-blue-700 border border-blue-200';
                                            elseif ($acc_type === 'cash') echo 'bg-emerald-50 text-emerald-700 border border-emerald-200';
                                            elseif ($acc_type === 'credit_card') echo 'bg-purple-50 text-purple-700 border border-purple-200';
                                            elseif ($acc_type === 'investment') echo 'bg-indigo-50 text-indigo-700 border border-indigo-200';
                                            elseif ($acc_type === 'loan') echo 'bg-amber-50 text-amber-700 border border-amber-200';
                                        ?>">
                                            <?php echo str_replace('_', ' ', $acc_type); ?>
                                        </span>
                                    </div>

                                    <div class="flex items-center justify-between mt-3 pt-2.5 border-t border-slate-200/80">
                                        <div>
                                            <p class="text-[10px] uppercase font-semibold text-slate-500">Balance</p>
                                            <p class="font-bold text-slate-900 text-sm">
                                                ₹<?php echo number_format($account['current_balance'], 2); ?>
                                            </p>
                                            <p class="text-[11px] text-slate-500 mt-0.5">
                                                <?php if ($acc_type === 'credit_card' && $account['credit_limit'] > 0): ?>
                                                    Limit: ₹<?php echo number_format($account['credit_limit'], 2); ?>
                                                <?php elseif ($acc_type === 'loan' && $account['interest_rate'] > 0): ?>
                                                    Rate: <?php echo $account['interest_rate']; ?>% p.a.
                                                <?php else: ?>
                                                    Opening: ₹<?php echo number_format($account['initial_balance'], 2); ?>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <a href="?edit_account=<?php echo $account['id']; ?>"
                                                class="p-2 text-slate-600 hover:text-slate-900 hover:bg-slate-200/60 rounded-lg transition" title="Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                    <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </a>
                                            <a href="transactions.php?account_id=<?php echo $account['id']; ?>"
                                                class="p-2 text-slate-600 hover:text-slate-900 hover:bg-slate-200/60 rounded-lg transition" title="Transactions">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M6 3h12M6 8h12M6 13l8.5 8M6 13h3a4 4 0 0 0 0-8"/>
                                                </svg>
                                            </a>
                                            <?php if (!$finance->hasTransactions($account['id'])): ?>
                                                <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this account?')">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                    <input type="hidden" name="delete_account" value="1">
                                                    <input type="hidden" name="account_id" value="<?php echo $account['id']; ?>">
                                                    <button type="submit" class="p-2 text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Delete">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                            <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
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
                                        <th class="px-4 py-3 bg-slate-50">Account Name</th>
                                        <th class="px-4 py-3 bg-slate-50">Classification</th>
                                        <th class="px-4 py-3 bg-slate-50">Parameters / Opening</th>
                                        <th class="px-4 py-3 bg-slate-50">Balance</th>
                                        <th class="px-4 py-3 text-right bg-slate-50">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    <?php
                                    $accounts->data_seek(0);
                                    $sno = 1;
                                    while ($account = $accounts->fetch_assoc()): 
                                        $acc_type = $account['type'];
                                    ?>
                                        <tr class="hover:bg-slate-50 transition">
                                            <td class="px-4 py-3.5 text-xs text-slate-400 font-mono"><?php echo $sno++; ?></td>
                                            <td class="px-4 py-3.5 text-sm font-bold text-slate-900"><?php echo htmlspecialchars($account['name']); ?></td>
                                            <td class="px-4 py-3.5">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider <?php 
                                                    if ($acc_type === 'bank') echo 'bg-blue-50 text-blue-700 border border-blue-200';
                                                    elseif ($acc_type === 'cash') echo 'bg-emerald-50 text-emerald-700 border border-emerald-200';
                                                    elseif ($acc_type === 'credit_card') echo 'bg-purple-50 text-purple-700 border border-purple-200';
                                                    elseif ($acc_type === 'investment') echo 'bg-indigo-50 text-indigo-700 border border-indigo-200';
                                                    elseif ($acc_type === 'loan') echo 'bg-amber-50 text-amber-700 border border-amber-200';
                                                ?>">
                                                    <?php echo str_replace('_', ' ', $acc_type); ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3.5 text-xs text-slate-500">
                                                <?php if ($acc_type === 'credit_card'): ?>
                                                    <?php if ($account['credit_limit'] > 0): ?>
                                                        <span class="font-medium text-slate-700">Limit: ₹<?php echo number_format($account['credit_limit'], 2); ?></span>
                                                        <?php if ($account['due_day']): ?>
                                                            <span class="block text-[11px] text-slate-400">Due: <?php echo $account['due_day']; ?>th of month</span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="font-medium text-slate-700">Revolving Facility</span>
                                                        <span class="block text-[11px] text-slate-400">Opening: ₹<?php echo number_format($account['initial_balance'], 2); ?></span>
                                                    <?php endif; ?>
                                                <?php elseif ($acc_type === 'loan'): ?>
                                                    <?php if ($account['interest_rate'] > 0): ?>
                                                        <span class="font-medium text-slate-700">Rate: <?php echo $account['interest_rate']; ?>% p.a.</span>
                                                    <?php else: ?>
                                                        <span class="font-medium text-slate-700">Borrowing Facility</span>
                                                    <?php endif; ?>
                                                    <span class="block text-[11px] text-slate-400">Opening: ₹<?php echo number_format($account['initial_balance'], 2); ?></span>
                                                <?php elseif ($acc_type === 'bank'): ?>
                                                    <span class="font-medium text-slate-700">Opening: ₹<?php echo number_format($account['initial_balance'], 2); ?></span>
                                                    <span class="block text-[11px] text-slate-400">Liquid Depository</span>
                                                <?php elseif ($acc_type === 'cash'): ?>
                                                    <span class="font-medium text-slate-700">Opening: ₹<?php echo number_format($account['initial_balance'], 2); ?></span>
                                                    <span class="block text-[11px] text-slate-400">Physical Cash Reserve</span>
                                                <?php elseif ($acc_type === 'investment'): ?>
                                                    <span class="font-medium text-slate-700">Opening: ₹<?php echo number_format($account['initial_balance'], 2); ?></span>
                                                    <span class="block text-[11px] text-slate-400">Holdings & Equities</span>
                                                <?php else: ?>
                                                    <span class="font-medium text-slate-700">Opening: ₹<?php echo number_format($account['initial_balance'], 2); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-4 py-3.5 text-sm font-bold text-slate-900">
                                                ₹<?php echo number_format($account['current_balance'], 2); ?>
                                            </td>
                                            <td class="px-4 py-3.5 text-right">
                                                <div class="inline-flex items-center space-x-1">
                                                    <a href="?edit_account=<?php echo $account['id']; ?>"
                                                        class="p-1.5 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition"
                                                        title="Edit Account">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                            <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                        </svg>
                                                    </a>
                                                    <a href="transactions.php?account_id=<?php echo $account['id']; ?>"
                                                        class="p-1.5 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition"
                                                        title="View Ledger">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="M6 3h12M6 8h12M6 13l8.5 8M6 13h3a4 4 0 0 0 0-8"/>
                                                        </svg>
                                                    </a>
                                                    <?php if (!$finance->hasTransactions($account['id'])): ?>
                                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this account?')">
                                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                            <input type="hidden" name="delete_account" value="1">
                                                            <input type="hidden" name="account_id" value="<?php echo $account['id']; ?>">
                                                            <button type="submit"
                                                                class="p-1.5 text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded-lg transition"
                                                                title="Delete Account">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                                    <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                                </svg>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-10 bg-slate-50 rounded-xl border border-dashed border-slate-300">
                            <p class="text-xs text-slate-500">No portfolios created. Use the form to add an account.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        function handleAccountTypeChange(val) {
            const ccFields = document.getElementById('creditCardFields');
            const loanFields = document.getElementById('loanFields');
            
            if (val === 'credit_card') {
                ccFields.classList.remove('hidden');
                loanFields.classList.add('hidden');
            } else if (val === 'loan') {
                loanFields.classList.remove('hidden');
                ccFields.classList.add('hidden');
            } else {
                ccFields.classList.add('hidden');
                loanFields.classList.add('hidden');
            }
        }
    </script>
</body>
</html>
