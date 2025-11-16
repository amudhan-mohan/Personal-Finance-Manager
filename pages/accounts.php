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
    if (isset($_POST['create_account'])) {
        $name = $_POST['name'];
        $type = $_POST['type'];
        $initial_balance = $_POST['initial_balance'];

        if ($finance->createAccount($user_id, $name, $type, $initial_balance)) {
            $message = ['type' => 'success', 'text' => 'Account created successfully!'];
        } else {
            $message = ['type' => 'error', 'text' => 'Failed to create account!'];
        }
    }

    if (isset($_POST['update_account'])) {
        $account_id = $_POST['account_id'];
        $name = $_POST['name'];
        $type = $_POST['type'];

        $result = $finance->updateAccount($account_id, $user_id, $name, $type);
        $message = [
            'type' => $result['success'] ? 'success' : 'error',
            'text' => $result['message']
        ];
    }
}

// Handle delete action
if (isset($_GET['delete_account'])) {
    $account_id = $_GET['delete_account'];
    $result = $finance->deleteAccount($account_id, $user_id);
    $message = [
        'type' => $result['success'] ? 'success' : 'error',
        'text' => $result['message']
    ];
}

// Get account for editing
$edit_account = null;
if (isset($_GET['edit_account'])) {
    $edit_account = $finance->getAccount($_GET['edit_account'], $user_id);
}

$accounts = $finance->getAccounts($user_id);
$balance_summary = $finance->getTotalBalance($user_id);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounts - <?php echo SITE_NAME; ?></title>
    <link rel="icon" href="/assets/img/logo.svg" data-next-head=""/>
    <link rel="stylesheet" href="/assets/css/tailwind.css">
</head>

<body class="bg-gray-100">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Accounts Management</h1>

        <?php if (isset($message)): ?>
            <div class="mb-4 p-4 rounded-md <?php echo $message['type'] === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?php echo $message['text']; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Create/Edit Account Form -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-xl font-semibold mb-4">
                        <?php echo $edit_account ? 'Edit Account' : 'Create New Account'; ?>
                    </h3>
                    <form method="POST" class="space-y-4">
                        <?php if ($edit_account): ?>
                            <input type="hidden" name="update_account" value="1">
                            <input type="hidden" name="account_id" value="<?php echo $edit_account['id']; ?>">
                        <?php else: ?>
                            <input type="hidden" name="create_account" value="1">
                        <?php endif; ?>

                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Account Name</label>
                            <input type="text" id="name" name="name" required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                placeholder="e.g., ICICI Bank, Cash Wallet"
                                value="<?php echo $edit_account ? htmlspecialchars($edit_account['name']) : ''; ?>">
                        </div>

                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700">Account Type</label>
                            <select id="type" name="type" required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="bank" <?php echo ($edit_account && $edit_account['type'] === 'bank') ? 'selected' : ''; ?>>Bank Account</option>
                                <option value="cash" <?php echo ($edit_account && $edit_account['type'] === 'cash') ? 'selected' : ''; ?>>Cash Account</option>
                            </select>
                        </div>

                        <?php if (!$edit_account): ?>
                            <div>
                                <label for="initial_balance" class="block text-sm font-medium text-gray-700">Initial Balance</label>
                                <input type="number" id="initial_balance" name="initial_balance" step="0.01" value="0.00"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        <?php else: ?>
                            <div class="p-3 bg-gray-50 rounded-md">
                                <p class="text-sm text-gray-600">Current Balance</p>
                                <p class="text-lg font-semibold text-green-600">₹<?php echo number_format($edit_account['current_balance'], 2); ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="flex space-x-3">
                            <button type="submit"
                                class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <?php echo $edit_account ? 'Update Account' : 'Create Account'; ?>
                            </button>
                            <?php if ($edit_account): ?>
                                <a href="accounts.php"
                                    class="flex-1 bg-gray-600 text-white text-center py-2 px-4 rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                    Cancel
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Balance Summary -->
                <div class="bg-white rounded-lg shadow-md p-6 mt-6">
                    <h3 class="text-xl font-semibold mb-4">Balance Summary</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Total Bank Balance:</span>
                            <span class="text-green-600 font-semibold">₹<?php echo number_format($balance_summary['bank_balance'] ?? 0, 2); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Total Cash Balance:</span>
                            <span class="text-blue-600 font-semibold">₹<?php echo number_format($balance_summary['cash_balance'] ?? 0, 2); ?></span>
                        </div>
                        <div class="flex justify-between items-center border-t pt-2">
                            <span class="text-gray-800 font-medium">Total Balance:</span>
                            <span class="text-purple-600 font-bold">₹<?php echo number_format($balance_summary['total_balance'] ?? 0, 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Accounts List -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-xl font-semibold mb-4">Your Accounts</h3>

                    <?php if ($accounts->num_rows > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">S.No</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Balance</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php $sno = 1;
                                    while ($account = $accounts->fetch_assoc()): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $sno++; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($account['name']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            <?php echo $account['type'] === 'bank' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'; ?>">
                                                    <?php echo ucfirst($account['type']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold 
                                        <?php echo $account['current_balance'] >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                                ₹<?php echo number_format($account['current_balance'], 2); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex items-center space-x-3">
                                                    <!-- Edit Icon -->
                                                    <a href="?edit_account=<?php echo $account['id']; ?>"
                                                        class="text-blue-600 hover:text-blue-900 transition-colors duration-200"
                                                        title="Edit Account">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                    </a>

                                                    <!-- Transactions Icon -->
                                                    <a href="transactions.php?account_id=<?php echo $account['id']; ?>"
                                                        class="text-green-600 hover:text-green-900 transition-colors duration-200"
                                                        title="View Transactions">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                        </svg>
                                                    </a>

                                                    <!-- Delete Icon -->
                                                    <?php if (!$finance->hasTransactions($account['id'])): ?>
                                                        <a href="?delete_account=<?php echo $account['id']; ?>"
                                                            class="text-red-600 hover:text-red-900 transition-colors duration-200"
                                                            onclick="return confirm('Are you sure you want to delete this account?')"
                                                            title="Delete Account">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-gray-400 cursor-not-allowed transition-colors duration-200"
                                                            title="Cannot delete account with transactions">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <p class="text-gray-500">No accounts found. Create your first account to get started!</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Account Type Views -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                    <!-- Bank Accounts -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-xl font-semibold mb-4 text-blue-600">Bank Accounts</h3>
                        <?php
                        $accounts->data_seek(0);
                        $has_bank_accounts = false;
                        ?>
                        <div class="space-y-3">
                            <?php while ($account = $accounts->fetch_assoc()): ?>
                                <?php if ($account['type'] === 'bank'): ?>
                                    <?php $has_bank_accounts = true; ?>
                                    <div class="flex justify-between items-center border-b pb-2">
                                        <div>
                                            <p class="font-medium"><?php echo htmlspecialchars($account['name']); ?></p>
                                        </div>
                                        <span class="text-green-600 font-semibold">₹<?php echo number_format($account['current_balance'], 2); ?></span>
                                    </div>
                                <?php endif; ?>
                            <?php endwhile; ?>
                            <?php if (!$has_bank_accounts): ?>
                                <p class="text-gray-500 text-center">No bank accounts</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Cash Accounts -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-xl font-semibold mb-4 text-green-600">Cash Accounts</h3>
                        <?php
                        $accounts->data_seek(0);
                        $has_cash_accounts = false;
                        ?>
                        <div class="space-y-3">
                            <?php while ($account = $accounts->fetch_assoc()): ?>
                                <?php if ($account['type'] === 'cash'): ?>
                                    <?php $has_cash_accounts = true; ?>
                                    <div class="flex justify-between items-center border-b pb-2">
                                        <div>
                                            <p class="font-medium"><?php echo htmlspecialchars($account['name']); ?></p>
                                        </div>
                                        <span class="text-green-600 font-semibold">₹<?php echo number_format($account['current_balance'], 2); ?></span>
                                    </div>
                                <?php endif; ?>
                            <?php endwhile; ?>
                            <?php if (!$has_cash_accounts): ?>
                                <p class="text-gray-500 text-center">No cash accounts</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>