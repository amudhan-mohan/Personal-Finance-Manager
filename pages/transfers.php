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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['make_transfer'])) {
        $from_account_id = $_POST['from_account_id'];
        $to_account_id = $_POST['to_account_id'];
        $amount = $_POST['amount'];
        $description = $_POST['description'];
        $transfer_date = $_POST['transfer_date'];

        $result = $finance->transferFunds($user_id, $from_account_id, $to_account_id, $amount, $description, $transfer_date);
        $message = [
            'type' => $result['success'] ? 'success' : 'error',
            'text' => $result['message']
        ];
    }

    if (isset($_POST['update_transfer'])) {
        $transfer_id = $_POST['transfer_id'];
        $from_account_id = $_POST['from_account_id'];
        $to_account_id = $_POST['to_account_id'];
        $amount = $_POST['amount'];
        $description = $_POST['description'];
        $transfer_date = $_POST['transfer_date'];

        $result = $finance->updateTransfer($transfer_id, $user_id, $from_account_id, $to_account_id, $amount, $description, $transfer_date);
        $message = [
            'type' => $result['success'] ? 'success' : 'error',
            'text' => $result['message']
        ];
    }
}

// Handle delete action
if (isset($_GET['delete_transfer'])) {
    $transfer_id = $_GET['delete_transfer'];
    $result = $finance->deleteTransfer($transfer_id, $user_id);
    $message = [
        'type' => $result['success'] ? 'success' : 'error',
        'text' => $result['message']
    ];
}

// Get transfer for editing
$edit_transfer = null;
if (isset($_GET['edit_transfer'])) {
    $edit_transfer = $finance->getTransfer($_GET['edit_transfer'], $user_id);
}

$accounts = $finance->getAccounts($user_id);
$recent_transfers = $finance->getTransfers($user_id, $_GET);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer Funds - <?php echo SITE_NAME; ?></title>
    <link rel="icon" href="/assets/img/logo.svg" data-next-head=""/>
    <link rel="stylesheet" href="/assets/css/tailwind.css">
</head>

<body class="bg-gray-100">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Transfer Funds</h1>

        <?php if (isset($message)): ?>
            <div class="mb-4 p-4 rounded-md <?php echo $message['type'] === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?php echo $message['text']; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Transfer Form -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-xl font-semibold mb-4">
                        <?php echo $edit_transfer ? 'Edit Transfer' : 'Make Transfer'; ?>
                    </h3>
                    <form method="POST" class="space-y-4" id="transferForm">
                        <?php if ($edit_transfer): ?>
                            <input type="hidden" name="update_transfer" value="1">
                            <input type="hidden" name="transfer_id" value="<?php echo $edit_transfer['id']; ?>">
                        <?php else: ?>
                            <input type="hidden" name="make_transfer" value="1">
                        <?php endif; ?>

                        <div>
                            <label for="from_account_id" class="block text-sm font-medium text-gray-700">From Account</label>
                            <select id="from_account_id" name="from_account_id" required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                onchange="updateFromAccountBalance()">
                                <option value="">Select Source Account</option>
                                <?php while ($account = $accounts->fetch_assoc()): ?>
                                    <option value="<?php echo $account['id']; ?>"
                                        data-balance="<?php echo $account['current_balance']; ?>"
                                        <?php echo ($edit_transfer && $edit_transfer['from_account_id'] == $account['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($account['name']); ?> (<?php echo $account['type']; ?>)
                                        - ₹<?php echo number_format($account['current_balance'], 2); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <div id="fromAccountBalance" class="text-sm text-gray-500 mt-1"></div>
                        </div>

                        <div>
                            <label for="to_account_id" class="block text-sm font-medium text-gray-700">To Account</label>
                            <select id="to_account_id" name="to_account_id" required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Destination Account</option>
                                <?php
                                $accounts->data_seek(0);
                                while ($account = $accounts->fetch_assoc()): ?>
                                    <option value="<?php echo $account['id']; ?>"
                                        <?php echo ($edit_transfer && $edit_transfer['to_account_id'] == $account['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($account['name']); ?> (<?php echo $account['type']; ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-700">Amount</label>
                            <input type="number" id="amount" name="amount" step="0.01" min="0.01" required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                value="<?php echo $edit_transfer ? $edit_transfer['amount'] : ''; ?>"
                                onchange="validateAmount()">
                            <div id="amountError" class="text-sm text-red-600 mt-1 hidden"></div>
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                            <input type="text" id="description" name="description"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                placeholder="e.g., Monthly transfer, Emergency fund, etc."
                                value="<?php echo $edit_transfer ? htmlspecialchars($edit_transfer['description']) : ''; ?>">
                        </div>

                        <div>
                            <label for="transfer_date" class="block text-sm font-medium text-gray-700">Transfer Date</label>
                            <input type="date" id="transfer_date" name="transfer_date" required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                value="<?php echo $edit_transfer ? $edit_transfer['transfer_date'] : date('Y-m-d'); ?>">
                        </div>

                        <div class="flex space-x-3">
                            <button type="submit" id="transferButton"
                                class="flex-1 bg-purple-600 text-white py-2 px-4 rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                <?php echo $edit_transfer ? 'Update Transfer' : 'Transfer Funds'; ?>
                            </button>
                            <?php if ($edit_transfer): ?>
                                <a href="transfers.php"
                                    class="flex-1 bg-gray-600 text-white text-center py-2 px-4 rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                    Cancel
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Transfer History and Quick Transfers -->
            <div class="lg:col-span-2">
                <!-- Quick Transfer Templates -->
                <?php if (!$edit_transfer): ?>
                    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                        <h3 class="text-xl font-semibold mb-4">Quick Transfers</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Bank to Cash -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-800 mb-2">Bank → Cash</h4>
                                <p class="text-sm text-gray-600 mb-3">Withdraw cash from bank account</p>
                                <button onclick="setQuickTransfer('bank_to_cash')"
                                    class="w-full bg-blue-100 text-blue-700 py-2 px-4 rounded-md hover:bg-blue-200 text-sm">
                                    Use This Template
                                </button>
                            </div>

                            <!-- Cash to Bank -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-800 mb-2">Cash → Bank</h4>
                                <p class="text-sm text-gray-600 mb-3">Deposit cash to bank account</p>
                                <button onclick="setQuickTransfer('cash_to_bank')"
                                    class="w-full bg-green-100 text-green-700 py-2 px-4 rounded-md hover:bg-green-200 text-sm">
                                    Use This Template
                                </button>
                            </div>

                            <!-- Bank to Bank -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-800 mb-2">Bank → Bank</h4>
                                <p class="text-sm text-gray-600 mb-3">Transfer between bank accounts</p>
                                <button onclick="setQuickTransfer('bank_to_bank')"
                                    class="w-full bg-purple-100 text-purple-700 py-2 px-4 rounded-md hover:bg-purple-200 text-sm">
                                    Use This Template
                                </button>
                            </div>

                            <!-- Cash to Cash -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-800 mb-2">Cash → Cash</h4>
                                <p class="text-sm text-gray-600 mb-3">Transfer between cash wallets</p>
                                <button onclick="setQuickTransfer('cash_to_cash')"
                                    class="w-full bg-yellow-100 text-yellow-700 py-2 px-4 rounded-md hover:bg-yellow-200 text-sm">
                                    Use This Template
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Recent Transfers -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-xl font-semibold mb-4">Transfer History</h3>

                    <?php if ($recent_transfers->num_rows > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">S.No</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">From → To</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php $sno = 1;
                                    while ($transfer = $recent_transfers->fetch_assoc()): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $sno++; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('M j, Y', strtotime($transfer['transfer_date'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-purple-600">
                                                ₹<?php echo number_format($transfer['amount'], 2); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($transfer['from_account_name']); ?>
                                                    <span class="text-gray-400 mx-2">→</span>
                                                    <?php echo htmlspecialchars($transfer['to_account_name']); ?>
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                                                <?php echo $transfer['from_account_type'] === 'bank' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'; ?>">
                                                        <?php echo ucfirst($transfer['from_account_type']); ?>
                                                    </span>
                                                    <span class="mx-1">→</span>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                                                <?php echo $transfer['to_account_type'] === 'bank' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'; ?>">
                                                        <?php echo ucfirst($transfer['to_account_type']); ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($transfer['description'] ?: 'No description'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex items-center space-x-3">
                                                    <!-- Edit Icon -->
                                                    <a href="?edit_transfer=<?php echo $transfer['id']; ?>"
                                                        class="text-blue-600 hover:text-blue-900 transition-colors duration-200" title="Edit Transfer">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                    </a>

                                                    <!-- Delete Icon -->
                                                    <a href="?delete_transfer=<?php echo $transfer['id']; ?>"
                                                        class="text-red-600 hover:text-red-900 transition-colors duration-200"
                                                        onclick="return confirm('Are you sure you want to delete this transfer?')"
                                                        title="Delete Transfer">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <p class="text-gray-500">No transfers yet. Make your first transfer!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        let accountsData = [];

        // Load accounts data for JavaScript
        <?php
        $accounts->data_seek(0);
        while ($account = $accounts->fetch_assoc()): ?>
            accountsData.push({
                id: <?php echo $account['id']; ?>,
                name: '<?php echo $account['name']; ?>',
                type: '<?php echo $account['type']; ?>',
                balance: <?php echo $account['current_balance']; ?>
            });
        <?php endwhile; ?>

        function updateFromAccountBalance() {
            const fromAccountSelect = document.getElementById('from_account_id');
            const balanceDisplay = document.getElementById('fromAccountBalance');
            const selectedOption = fromAccountSelect.options[fromAccountSelect.selectedIndex];

            if (selectedOption.value) {
                const balance = selectedOption.getAttribute('data-balance');
                balanceDisplay.textContent = `Available balance: ₹${parseFloat(balance).toFixed(2)}`;
                balanceDisplay.className = 'text-sm text-green-600 mt-1';
            } else {
                balanceDisplay.textContent = '';
            }
            validateAmount();
        }

        function validateAmount() {
            const fromAccountSelect = document.getElementById('from_account_id');
            const amountInput = document.getElementById('amount');
            const amountError = document.getElementById('amountError');
            const transferButton = document.getElementById('transferButton');

            const selectedOption = fromAccountSelect.options[fromAccountSelect.selectedIndex];
            const balance = selectedOption.value ? parseFloat(selectedOption.getAttribute('data-balance')) : 0;
            const amount = parseFloat(amountInput.value) || 0;

            if (amount > balance) {
                amountError.textContent = 'Amount exceeds available balance';
                amountError.classList.remove('hidden');
                transferButton.disabled = true;
            } else if (amount <= 0) {
                amountError.textContent = 'Amount must be greater than 0';
                amountError.classList.remove('hidden');
                transferButton.disabled = true;
            } else {
                amountError.classList.add('hidden');
                transferButton.disabled = false;
            }
        }

        function setQuickTransfer(type) {
            const bankAccounts = accountsData.filter(acc => acc.type === 'bank');
            const cashAccounts = accountsData.filter(acc => acc.type === 'cash');

            let fromAccount, toAccount;

            switch (type) {
                case 'bank_to_cash':
                    if (bankAccounts.length > 0 && cashAccounts.length > 0) {
                        fromAccount = bankAccounts[0];
                        toAccount = cashAccounts[0];
                    }
                    break;
                case 'cash_to_bank':
                    if (cashAccounts.length > 0 && bankAccounts.length > 0) {
                        fromAccount = cashAccounts[0];
                        toAccount = bankAccounts[0];
                    }
                    break;
                case 'bank_to_bank':
                    if (bankAccounts.length > 1) {
                        fromAccount = bankAccounts[0];
                        toAccount = bankAccounts[1];
                    }
                    break;
                case 'cash_to_cash':
                    if (cashAccounts.length > 1) {
                        fromAccount = cashAccounts[0];
                        toAccount = cashAccounts[1];
                    }
                    break;
            }

            if (fromAccount && toAccount) {
                document.getElementById('from_account_id').value = fromAccount.id;
                document.getElementById('to_account_id').value = toAccount.id;
                document.getElementById('description').value = getDefaultDescription(type);
                updateFromAccountBalance();
            } else {
                alert('Not enough accounts for this transfer type. Please create more accounts.');
            }
        }

        function getDefaultDescription(type) {
            const descriptions = {
                'bank_to_cash': 'Cash withdrawal',
                'cash_to_bank': 'Cash deposit',
                'bank_to_bank': 'Inter-bank transfer',
                'cash_to_cash': 'Cash transfer between wallets'
            };
            return descriptions[type] || 'Fund transfer';
        }

        // Initialize form validation
        document.addEventListener('DOMContentLoaded', function() {
            validateAmount();
        });
    </script>
</body>

</html>