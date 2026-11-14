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
        if (isset($_POST['make_transfer'])) {
            $from_account_id = intval($_POST['from_account_id'] ?? 0);
            $to_account_id = intval($_POST['to_account_id'] ?? 0);
            $amount = floatval($_POST['amount'] ?? 0);
            $description = trim($_POST['description'] ?? '');
            $transfer_date = $_POST['transfer_date'] ?? date('Y-m-d');

            if ($amount <= 0) {
                $_SESSION['flash_toast'] = ['title' => 'Action Failed', 'message' => 'Transfer amount must be greater than zero!', 'type' => 'error'];
            } elseif ($from_account_id === $to_account_id) {
                $_SESSION['flash_toast'] = ['title' => 'Action Failed', 'message' => 'Cannot transfer to the same account!', 'type' => 'error'];
            } else {
                $result = $finance->transferFunds($user_id, $from_account_id, $to_account_id, $amount, $description, $transfer_date);
                $_SESSION['flash_toast'] = [
                    'title' => $result['success'] ? 'Successfully Completed' : 'Action Failed',
                    'message' => $result['message'],
                    'type' => $result['success'] ? 'success' : 'error'
                ];
            }
        }

        if (isset($_POST['update_transfer'])) {
            $transfer_id = intval($_POST['transfer_id'] ?? 0);
            $from_account_id = intval($_POST['from_account_id'] ?? 0);
            $to_account_id = intval($_POST['to_account_id'] ?? 0);
            $amount = floatval($_POST['amount'] ?? 0);
            $description = trim($_POST['description'] ?? '');
            $transfer_date = $_POST['transfer_date'] ?? date('Y-m-d');

            if ($amount <= 0) {
                $_SESSION['flash_toast'] = ['title' => 'Action Failed', 'message' => 'Transfer amount must be greater than zero!', 'type' => 'error'];
            } elseif ($from_account_id === $to_account_id) {
                $_SESSION['flash_toast'] = ['title' => 'Action Failed', 'message' => 'Cannot transfer to the same account!', 'type' => 'error'];
            } else {
                $result = $finance->updateTransfer($transfer_id, $user_id, $from_account_id, $to_account_id, $amount, $description, $transfer_date);
                $_SESSION['flash_toast'] = [
                    'title' => $result['success'] ? 'Successfully Completed' : 'Action Failed',
                    'message' => $result['message'],
                    'type' => $result['success'] ? 'success' : 'error'
                ];
            }
        }

        if (isset($_POST['delete_transfer'])) {
            $transfer_id = intval($_POST['transfer_id'] ?? 0);
            $result = $finance->deleteTransfer($transfer_id, $user_id);
            $_SESSION['flash_toast'] = [
                'title' => $result['success'] ? 'Successfully Completed' : 'Action Failed',
                'message' => $result['message'],
                'type' => $result['success'] ? 'success' : 'error'
            ];
        }
    }
    header('Location: transfers.php');
    exit;
}

// Get transfer for editing
$edit_transfer = null;
if (isset($_GET['edit_transfer'])) {
    $edit_transfer = $finance->getTransfer(intval($_GET['edit_transfer']), $user_id);
}

$accounts = $finance->getAccounts($user_id);
$recent_transfers = $finance->getTransfers($user_id, $_GET);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Transfers - <?php echo SITE_NAME; ?></title>
    <link rel="icon" href="../assets/img/logo.svg" data-next-head=""/>
    <link rel="stylesheet" href="../assets/css/tailwind.css?v=<?php echo asset_version(); ?>">
</head>

<body class="bg-slate-50 min-h-screen text-slate-900 font-sans antialiased">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8 pb-6 border-b border-slate-200">
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">Inter-Account Transfers</h1>
            <p class="text-sm text-slate-500 mt-1">Reallocate capital between institutional banking accounts and operational cash reserves.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Transfer Form -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs">
                    <div class="flex items-center space-x-2.5 mb-5 pb-3 border-b border-slate-100">
                        <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path d="M17 2l4 4-4 4"/>
                                <path d="M3 6h18"/>
                                <path d="M7 22l-4-4 4-4"/>
                                <path d="M21 18H3"/>
                            </svg>
                        </div>
                        <h2 class="text-base font-bold text-slate-900">
                            <?php echo $edit_transfer ? 'Edit Transfer' : 'Execute Transfer'; ?>
                        </h2>
                    </div>

                    <form method="POST" class="space-y-4" id="transferForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <?php if ($edit_transfer): ?>
                            <input type="hidden" name="update_transfer" value="1">
                            <input type="hidden" name="transfer_id" value="<?php echo $edit_transfer['id']; ?>">
                        <?php else: ?>
                            <input type="hidden" name="make_transfer" value="1">
                        <?php endif; ?>

                        <div>
                            <label for="from_account_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Source Portfolio (From)</label>
                            <select id="from_account_id" name="from_account_id" required
                                class="w-full px-3.5 py-2.5 text-base md:text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900 transition bg-white text-slate-900"
                                onchange="onSourceAccountChange()">
                                <option value="">Select source account</option>
                                <?php
                                $accounts->data_seek(0);
                                while ($account = $accounts->fetch_assoc()): ?>
                                    <option value="<?php echo $account['id']; ?>"
                                        data-balance="<?php echo $account['current_balance']; ?>"
                                        <?php echo ($edit_transfer && $edit_transfer['from_account_id'] == $account['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($account['name']); ?> (<?php echo ucfirst($account['type']); ?>)
                                        - ₹<?php echo number_format($account['current_balance'], 2); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <div id="fromAccountBalance" class="text-xs text-slate-500 mt-1"></div>
                        </div>

                        <div>
                            <label for="to_account_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Destination Portfolio (To)</label>
                            <select id="to_account_id" name="to_account_id" required
                                class="w-full px-3.5 py-2.5 text-base md:text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900 transition bg-white text-slate-900"
                                onchange="onDestinationAccountChange()">
                                <option value="">Select destination account</option>
                                <?php
                                $accounts->data_seek(0);
                                while ($account = $accounts->fetch_assoc()): ?>
                                    <option value="<?php echo $account['id']; ?>"
                                        <?php echo ($edit_transfer && $edit_transfer['to_account_id'] == $account['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($account['name']); ?> (<?php echo ucfirst($account['type']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <div id="accountMatchError" class="hidden text-xs font-semibold text-rose-600 mt-1.5 items-center space-x-1">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="12" y1="8" x2="12" y2="12"/>
                                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                                </svg>
                                <span>Source and destination accounts cannot be identical.</span>
                            </div>
                        </div>

                        <div>
                            <label for="amount" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Transfer Amount (INR)</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 font-bold">₹</span>
                                <input type="number" id="amount" name="amount" step="0.01" min="0.01" required
                                    class="w-full pl-8 pr-3.5 py-2.5 text-base md:text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900 transition bg-white text-slate-900 placeholder:text-slate-400"
                                    value="<?php echo $edit_transfer ? htmlspecialchars($edit_transfer['amount']) : ''; ?>"
                                    oninput="validateAmount()"
                                    placeholder="0.00">
                            </div>
                            <div id="amountError" class="text-xs font-medium text-rose-600 mt-1 hidden"></div>
                        </div>

                        <div>
                            <label for="description" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Reference / Memo</label>
                            <input type="text" id="description" name="description"
                                class="w-full px-3.5 py-2.5 text-base md:text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900 transition bg-white text-slate-900 placeholder:text-slate-400"
                                placeholder="e.g., ATM Cash withdrawal, Payroll balance top-up"
                                value="<?php echo $edit_transfer ? htmlspecialchars($edit_transfer['description']) : ''; ?>">
                        </div>

                        <div>
                            <label for="transfer_date" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Transfer Date</label>
                            <input type="date" id="transfer_date" name="transfer_date" required
                                class="w-full px-3.5 py-2.5 text-base md:text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900 transition bg-white text-slate-900"
                                value="<?php echo $edit_transfer ? htmlspecialchars($edit_transfer['transfer_date']) : date('Y-m-d'); ?>">
                        </div>

                        <div class="flex space-x-2.5 pt-2">
                            <button type="submit" id="transferButton"
                                class="flex-1 bg-slate-900 hover:bg-slate-800 text-white py-2.5 px-4 rounded-lg text-xs font-semibold uppercase tracking-wider transition shadow-xs">
                                <?php echo $edit_transfer ? 'Save Transfer' : 'Execute Transfer'; ?>
                            </button>
                            <?php if ($edit_transfer): ?>
                                <a href="transfers.php"
                                    class="px-4 py-2.5 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg text-xs font-semibold uppercase tracking-wider transition">
                                    Cancel
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Transfer History -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs">
                    <div class="flex items-center justify-between mb-5">
                        <h2 class="text-base font-bold text-slate-900">Transfer Journal</h2>
                        <span class="text-xs text-slate-500 font-medium"><?php echo $recent_transfers->num_rows; ?> transfers logged</span>
                    </div>

                    <?php if ($recent_transfers->num_rows > 0): ?>
                        <!-- Mobile / WebView Card List (< md screens) -->
                        <div class="block md:hidden space-y-3 max-h-[520px] overflow-y-auto pr-1 inscrolling-table-container">
                            <?php
                            $recent_transfers->data_seek(0);
                            $mob_tr_sno = 1;
                            while ($transfer = $recent_transfers->fetch_assoc()): ?>
                                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 hover:border-slate-300 transition">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-xs text-slate-400 font-mono">#<?php echo $mob_tr_sno++; ?></span>
                                            <span class="font-bold text-slate-900 text-sm">₹<?php echo number_format($transfer['amount'], 2); ?></span>
                                        </div>
                                        <span class="text-[11px] text-slate-500"><?php echo date('M j, Y', strtotime($transfer['transfer_date'])); ?></span>
                                    </div>
                                    <div class="flex items-center text-xs text-slate-800 font-medium mt-1">
                                        <span class="truncate"><?php echo htmlspecialchars($transfer['from_account_name']); ?></span>
                                        <svg class="h-3 w-3 mx-1 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <line x1="5" y1="12" x2="19" y2="12"/>
                                            <polyline points="12 5 19 12 12 19"/>
                                        </svg>
                                        <span class="truncate"><?php echo htmlspecialchars($transfer['to_account_name']); ?></span>
                                    </div>
                                    <?php if ($transfer['description']): ?>
                                        <p class="text-xs text-slate-500 mt-1"><?php echo htmlspecialchars($transfer['description']); ?></p>
                                    <?php endif; ?>
                                    <div class="flex justify-end space-x-2 mt-3 pt-2.5 border-t border-slate-200/80">
                                        <a href="?edit_transfer=<?php echo $transfer['id']; ?>"
                                            class="p-1.5 text-slate-600 hover:text-slate-900 hover:bg-slate-200/60 rounded-lg transition" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this transfer?')">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <input type="hidden" name="delete_transfer" value="1">
                                            <input type="hidden" name="transfer_id" value="<?php echo $transfer['id']; ?>">
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
                                        <th class="px-4 py-3 bg-slate-50">Date</th>
                                        <th class="px-4 py-3 bg-slate-50">Amount</th>
                                        <th class="px-4 py-3 bg-slate-50">Source &rarr; Target</th>
                                        <th class="px-4 py-3 bg-slate-50">Reference</th>
                                        <th class="px-4 py-3 text-right bg-slate-50">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    <?php
                                    $recent_transfers->data_seek(0);
                                    $sno = 1;
                                    while ($transfer = $recent_transfers->fetch_assoc()): ?>
                                        <tr class="hover:bg-slate-50 transition">
                                            <td class="px-4 py-3.5 text-xs text-slate-400 font-mono"><?php echo $sno++; ?></td>
                                            <td class="px-4 py-3.5 text-xs font-medium text-slate-600">
                                                <?php echo date('M j, Y', strtotime($transfer['transfer_date'])); ?>
                                            </td>
                                            <td class="px-4 py-3.5 text-sm font-bold text-slate-900">
                                                ₹<?php echo number_format($transfer['amount'], 2); ?>
                                            </td>
                                            <td class="px-4 py-3.5">
                                                <div class="flex items-center text-xs font-semibold text-slate-900">
                                                    <span><?php echo htmlspecialchars($transfer['from_account_name']); ?></span>
                                                    <svg class="h-3.5 w-3.5 mx-1.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                        <line x1="5" y1="12" x2="19" y2="12"/>
                                                        <polyline points="12 5 19 12 12 19"/>
                                                    </svg>
                                                    <span><?php echo htmlspecialchars($transfer['to_account_name']); ?></span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3.5 text-xs text-slate-500">
                                                <?php echo htmlspecialchars($transfer['description'] ?: 'Fund reallocation'); ?>
                                            </td>
                                            <td class="px-4 py-3.5 text-right">
                                                <div class="inline-flex items-center space-x-1">
                                                    <a href="?edit_transfer=<?php echo $transfer['id']; ?>"
                                                        class="p-1.5 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition"
                                                        title="Edit Transfer">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                            <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                        </svg>
                                                    </a>
                                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this transfer?')">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                        <input type="hidden" name="delete_transfer" value="1">
                                                        <input type="hidden" name="transfer_id" value="<?php echo $transfer['id']; ?>">
                                                        <button type="submit"
                                                            class="p-1.5 text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded-lg transition"
                                                            title="Delete Transfer">
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
                            <p class="text-xs text-slate-500">No transfers recorded yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        function syncBidirectionalAccounts() {
            const fromSelect = document.getElementById('from_account_id');
            const toSelect = document.getElementById('to_account_id');
            const fromVal = fromSelect.value;
            const toVal = toSelect.value;

            // 1. In Destination dropdown, disable the option chosen in Source
            Array.from(toSelect.options).forEach(opt => {
                if (!opt.value) return; // Keep placeholder
                if (fromVal && opt.value === fromVal) {
                    opt.disabled = true;
                    opt.classList.add('text-slate-300', 'bg-slate-100');
                    if (toSelect.value === fromVal) {
                        toSelect.value = '';
                    }
                } else {
                    opt.disabled = false;
                    opt.classList.remove('text-slate-300', 'bg-slate-100');
                }
            });

            // 2. In Source dropdown, disable the option chosen in Destination
            Array.from(fromSelect.options).forEach(opt => {
                if (!opt.value) return; // Keep placeholder
                if (toVal && opt.value === toVal) {
                    opt.disabled = true;
                    opt.classList.add('text-slate-300', 'bg-slate-100');
                    if (fromSelect.value === toVal) {
                        fromSelect.value = '';
                    }
                } else {
                    opt.disabled = false;
                    opt.classList.remove('text-slate-300', 'bg-slate-100');
                }
            });

            validateAccounts();
        }

        function onSourceAccountChange() {
            const fromAccountSelect = document.getElementById('from_account_id');
            const balanceDisplay = document.getElementById('fromAccountBalance');
            const selectedOption = fromAccountSelect.options[fromAccountSelect.selectedIndex];

            if (selectedOption && selectedOption.value) {
                const balance = selectedOption.getAttribute('data-balance');
                balanceDisplay.textContent = `Available: ₹${parseFloat(balance).toLocaleString('en-IN', {minimumFractionDigits: 2})}`;
                balanceDisplay.className = 'text-xs font-semibold text-emerald-700 mt-1';
            } else {
                balanceDisplay.textContent = '';
            }

            syncBidirectionalAccounts();
        }

        function onDestinationAccountChange() {
            syncBidirectionalAccounts();
        }

        function validateAccounts() {
            const fromSelect = document.getElementById('from_account_id');
            const toSelect = document.getElementById('to_account_id');
            const matchError = document.getElementById('accountMatchError');
            const transferButton = document.getElementById('transferButton');

            const isSame = fromSelect.value && toSelect.value && (fromSelect.value === toSelect.value);

            if (isSame) {
                matchError.classList.remove('hidden');
                matchError.classList.add('flex');
                toSelect.classList.add('border-rose-500', 'ring-1', 'ring-rose-500');
                fromSelect.classList.add('border-rose-500', 'ring-1', 'ring-rose-500');
                transferButton.disabled = true;
                return false;
            } else {
                matchError.classList.add('hidden');
                matchError.classList.remove('flex');
                toSelect.classList.remove('border-rose-500', 'ring-1', 'ring-rose-500');
                fromSelect.classList.remove('border-rose-500', 'ring-1', 'ring-rose-500');
                validateAmount();
                return true;
            }
        }

        function validateAmount() {
            const fromAccountSelect = document.getElementById('from_account_id');
            const toAccountSelect = document.getElementById('to_account_id');
            const amountInput = document.getElementById('amount');
            const amountError = document.getElementById('amountError');
            const transferButton = document.getElementById('transferButton');

            // Block if accounts are invalid or same
            if (!fromAccountSelect.value || !toAccountSelect.value || fromAccountSelect.value === toAccountSelect.value) {
                transferButton.disabled = true;
                return;
            }

            const selectedOption = fromAccountSelect.options[fromAccountSelect.selectedIndex];
            const balance = selectedOption && selectedOption.value ? parseFloat(selectedOption.getAttribute('data-balance')) : 0;
            const amount = parseFloat(amountInput.value) || 0;

            if (amount <= 0) {
                amountError.textContent = 'Amount must be greater than zero.';
                amountError.classList.remove('hidden');
                transferButton.disabled = true;
            } else if (amount > balance) {
                amountError.textContent = 'Amount exceeds available source balance.';
                amountError.classList.remove('hidden');
                transferButton.disabled = true;
            } else {
                amountError.classList.add('hidden');
                transferButton.disabled = false;
            }
        }

        document.getElementById('transferForm').addEventListener('submit', function(e) {
            if (!validateAccounts()) {
                e.preventDefault();
                return false;
            }
            const amountInput = document.getElementById('amount');
            const amount = parseFloat(amountInput.value) || 0;
            if (amount <= 0) {
                e.preventDefault();
                validateAmount();
                return false;
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            onSourceAccountChange();
            syncBidirectionalAccounts();
        });
    </script>
</body>

</html>
