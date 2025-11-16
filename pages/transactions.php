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
    if (isset($_POST['add_transaction'])) {
        $account_id = $_POST['account_id'];
        $type = $_POST['type'];
        $amount = $_POST['amount'];
        $description = $_POST['description'];
        $category = $_POST['category'];
        $transaction_date = $_POST['transaction_date'];

        if ($finance->addTransaction($user_id, $account_id, $type, $amount, $description, $category, $transaction_date)) {
            $message = ['type' => 'success', 'text' => 'Transaction added successfully!'];
        } else {
            $message = ['type' => 'error', 'text' => 'Failed to add transaction!'];
        }
    }

    if (isset($_POST['update_transaction'])) {
        $transaction_id = $_POST['transaction_id'];
        $account_id = $_POST['account_id'];
        $type = $_POST['type'];
        $amount = $_POST['amount'];
        $description = $_POST['description'];
        $category = $_POST['category'];
        $transaction_date = $_POST['transaction_date'];

        $result = $finance->updateTransaction($transaction_id, $user_id, $account_id, $type, $amount, $description, $category, $transaction_date);
        $message = [
            'type' => $result['success'] ? 'success' : 'error',
            'text' => $result['message']
        ];
    }
}

// Handle delete action
if (isset($_GET['delete_transaction'])) {
    $transaction_id = $_GET['delete_transaction'];
    $result = $finance->deleteTransaction($transaction_id, $user_id);
    $message = [
        'type' => $result['success'] ? 'success' : 'error',
        'text' => $result['message']
    ];
}

// Get transaction for editing
$edit_transaction = null;
if (isset($_GET['edit_transaction'])) {
    $edit_transaction = $finance->getTransaction($_GET['edit_transaction'], $user_id);
}

$accounts = $finance->getAccounts($user_id);
$transactions = $finance->getTransactions($user_id, $_GET);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - <?php echo SITE_NAME; ?></title>
    <link rel="icon" href="/assets/img/logo.svg" data-next-head=""/>
    <link rel="stylesheet" href="/assets/css/tailwind.css">
</head>

<body class="bg-gray-100">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Transactions</h1>

        <?php if (isset($message)): ?>
            <div class="mb-4 p-4 rounded-md <?php echo $message['type'] === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?php echo $message['text']; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Add/Edit Transaction Form -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-xl font-semibold mb-4">
                        <?php echo $edit_transaction ? 'Edit Transaction' : 'Add Transaction'; ?>
                    </h3>
                    <form method="POST" class="space-y-4">
                        <?php if ($edit_transaction): ?>
                            <input type="hidden" name="update_transaction" value="1">
                            <input type="hidden" name="transaction_id" value="<?php echo $edit_transaction['id']; ?>">
                        <?php else: ?>
                            <input type="hidden" name="add_transaction" value="1">
                        <?php endif; ?>

                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700">Type</label>
                            <select id="type" name="type" required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="income" <?php echo ($edit_transaction && $edit_transaction['type'] === 'income') ? 'selected' : ''; ?>>Income</option>
                                <option value="expense" <?php echo ($edit_transaction && $edit_transaction['type'] === 'expense') ? 'selected' : ''; ?>>Expense</option>
                            </select>
                        </div>

                        <div>
                            <label for="account_id" class="block text-sm font-medium text-gray-700">Account</label>
                            <select id="account_id" name="account_id" required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <?php while ($account = $accounts->fetch_assoc()): ?>
                                    <option value="<?php echo $account['id']; ?>"
                                        <?php echo ($edit_transaction && $edit_transaction['account_id'] == $account['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($account['name']); ?> (<?php echo $account['type']; ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-700">Amount</label>
                            <input type="number" id="amount" name="amount" step="0.01" min="0" required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                value="<?php echo $edit_transaction ? $edit_transaction['amount'] : ''; ?>">
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                            <input type="text" id="description" name="description" required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                value="<?php echo $edit_transaction ? htmlspecialchars($edit_transaction['description']) : ''; ?>">
                        </div>

                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                            <input type="text" id="category" name="category"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                value="<?php echo $edit_transaction ? htmlspecialchars($edit_transaction['category']) : ''; ?>">
                        </div>

                        <div>
                            <label for="transaction_date" class="block text-sm font-medium text-gray-700">Date</label>
                            <input type="date" id="transaction_date" name="transaction_date" required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                value="<?php echo $edit_transaction ? $edit_transaction['transaction_date'] : date('Y-m-d'); ?>">
                        </div>

                        <div class="flex space-x-3">
                            <button type="submit"
                                class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <?php echo $edit_transaction ? 'Update Transaction' : 'Add Transaction'; ?>
                            </button>
                            <?php if ($edit_transaction): ?>
                                <a href="transactions.php"
                                    class="flex-1 bg-gray-600 text-white text-center py-2 px-4 rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                    Cancel
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Transactions List and Filters -->
            <div class="lg:col-span-2">
                <!-- Filters -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h3 class="text-xl font-semibold mb-4">Filters</h3>
                    <form id="filterForm" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label for="filter_account" class="block text-sm font-medium text-gray-700">Account</label>
                            <select id="filter_account" name="account_id"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
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
                            <label for="filter_type" class="block text-sm font-medium text-gray-700">Type</label>
                            <select id="filter_type" name="type"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Types</option>
                                <option value="income" <?php echo isset($_GET['type']) && $_GET['type'] == 'income' ? 'selected' : ''; ?>>Income</option>
                                <option value="expense" <?php echo isset($_GET['type']) && $_GET['type'] == 'expense' ? 'selected' : ''; ?>>Expense</option>
                            </select>
                        </div>

                        <div>
                            <label for="filter_start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                            <input type="date" id="filter_start_date" name="start_date"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                value="<?php echo $_GET['start_date'] ?? ''; ?>">
                        </div>

                        <div>
                            <label for="filter_end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                            <input type="date" id="filter_end_date" name="end_date"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                value="<?php echo $_GET['end_date'] ?? ''; ?>">
                        </div>

                        <div class="md:col-span-4 flex space-x-4">
                            <button type="submit"
                                class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Apply Filters
                            </button>
                            <button type="button" id="resetFilters"
                                class="bg-gray-600 text-white py-2 px-4 rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                Reset
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Transactions List -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-xl font-semibold mb-4">Transaction History</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">S.No</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php $sno = 1;
                                while ($transaction = $transactions->fetch_assoc()): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $sno++; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M j, Y', strtotime($transaction['transaction_date'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium <?php echo $transaction['type'] === 'income' ? 'text-green-600' : 'text-red-600'; ?>">
                                            <?php echo $transaction['type'] === 'income' ? '+' : '-'; ?>₹<?php echo number_format($transaction['amount'], 2); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($transaction['account_name']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($transaction['description']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($transaction['category']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex items-center space-x-3">
                                                <!-- Edit Icon -->
                                                <a href="?edit_transaction=<?php echo $transaction['id']; ?>"
                                                    class="text-blue-600 hover:text-blue-900 transition-colors duration-200"
                                                    title="Edit Transaction">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </a>

                                                <!-- Delete Icon -->
                                                <a href="?delete_transaction=<?php echo $transaction['id']; ?>"
                                                    class="text-red-600 hover:text-red-900 transition-colors duration-200"
                                                    onclick="return confirm('Are you sure you want to delete this transaction?')"
                                                    title="Delete Transaction">
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
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // Reset filters
        document.getElementById('resetFilters').addEventListener('click', function() {
            window.location.href = 'transactions.php';
        });
    </script>
</body>

</html>