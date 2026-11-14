<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $auth->getUserId();
$financeManager = new FinanceManager();

$status_filter = isset($_GET['status']) && in_array($_GET['status'], ['active', 'paused', 'cancelled']) ? $_GET['status'] : null;
$bills_res = $financeManager->getRecurringBills($user_id, $status_filter);

$accounts_res = $financeManager->getAccounts($user_id);
$accounts = [];
while ($acc = $accounts_res->fetch_assoc()) {
    $accounts[] = $acc;
}

$bills = [];
$total_monthly_commitment = 0;
$active_count = 0;
$due_soon_count = 0;
$today = date('Y-m-d');
$next_bill = null;

while ($row = $bills_res->fetch_assoc()) {
    $bills[] = $row;
    if ($row['status'] === 'active') {
        $active_count++;
        // Monthly normalization
        $amt = (float)$row['amount'];
        switch ($row['frequency']) {
            case 'weekly':
                $total_monthly_commitment += ($amt * 4.333);
                break;
            case 'quarterly':
                $total_monthly_commitment += ($amt / 3);
                break;
            case 'yearly':
                $total_monthly_commitment += ($amt / 12);
                break;
            case 'monthly':
            default:
                $total_monthly_commitment += $amt;
                break;
        }

        $due_ts = strtotime($row['next_due_date']);
        $diff_days = floor(($due_ts - strtotime($today)) / 86400);
        if ($diff_days <= 7) {
            $due_soon_count++;
        }

        if ($next_bill === null && $due_ts >= strtotime($today)) {
            $next_bill = $row;
        }
    }
}

$categories = [
    'Utilities & Bills',
    'Subscription & Media',
    'Housing & Rent',
    'Loan & EMI',
    'Insurance',
    'Internet & Mobile',
    'Education & Tuition',
    'Fitness & Health',
    'Software & Cloud',
    'Other'
];

$page_title = "Recurring Bills & Subscriptions";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="icon" href="../assets/img/logo.svg" data-next-head=""/>
    <link rel="stylesheet" href="../assets/css/tailwind.css?v=<?php echo asset_version(); ?>">
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex flex-col font-sans antialiased">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-6 border-b border-slate-200 gap-4">
            <div>
                <div class="flex items-center space-x-2 text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">
                    <span>Obligations & Commitments</span>
                    <span>/</span>
                    <span class="text-slate-800">Recurring Bills</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Recurring Bills & Subscriptions</h1>
                <p class="text-sm text-slate-600 mt-1">Track upcoming lifecycle dues, frequencies, and record payments with 1-click execution.</p>
            </div>
            <div class="flex items-center space-x-3">
                <button type="button" onclick="openBillModal()" class="inline-flex items-center justify-center px-4 py-2 bg-slate-900 text-white hover:bg-slate-800 rounded-lg text-sm font-semibold transition shadow-sm">
                    <svg class="h-4 w-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Add Recurring Bill
                </button>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mt-6">
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider">Normalized Monthly Burn</span>
                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </div>
                </div>
                <div class="text-2xl font-bold text-slate-900">
                    ₹<?php echo number_format($total_monthly_commitment, 2); ?>
                </div>
                <div class="text-xs text-slate-500 mt-1">Total annualized recurring expenses / mo</div>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider">Active Subscriptions</span>
                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-blue-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                    </div>
                </div>
                <div class="text-2xl font-bold text-slate-900">
                    <?php echo $active_count; ?>
                </div>
                <div class="text-xs text-slate-500 mt-1">Active automated subscriptions</div>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider">Due In Next 7 Days</span>
                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-amber-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                </div>
                <div class="text-2xl font-bold <?php echo $due_soon_count > 0 ? 'text-amber-700' : 'text-slate-900'; ?>">
                    <?php echo $due_soon_count; ?>
                </div>
                <div class="text-xs text-slate-500 mt-1">Requires liquidity attention soon</div>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider">Next Upcoming Bill</span>
                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <polyline points="9 11 12 14 22 4"/>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                        </svg>
                    </div>
                </div>
                <?php if ($next_bill): ?>
                    <div class="text-lg font-bold text-slate-900 truncate" title="<?php echo htmlspecialchars($next_bill['title']); ?>">
                        <?php echo htmlspecialchars($next_bill['title']); ?>
                    </div>
                    <div class="text-xs font-semibold text-slate-600 mt-1">
                        ₹<?php echo number_format($next_bill['amount'], 2); ?> &bull; <?php echo date('M j, Y', strtotime($next_bill['next_due_date'])); ?>
                    </div>
                <?php else: ?>
                    <div class="text-lg font-bold text-slate-400">None Scheduled</div>
                    <div class="text-xs text-slate-500 mt-1">All obligations clear</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filter Tab Strip -->
        <div class="mt-8 flex items-center space-x-2 border-b border-slate-200 pb-3">
            <a href="bills.php" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition <?php echo empty($status_filter) ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'; ?>">
                All Bills
            </a>
            <a href="bills.php?status=active" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition <?php echo $status_filter === 'active' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'; ?>">
                Active Only
            </a>
            <a href="bills.php?status=paused" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition <?php echo $status_filter === 'paused' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'; ?>">
                Paused
            </a>
            <a href="bills.php?status=cancelled" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition <?php echo $status_filter === 'cancelled' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'; ?>">
                Cancelled
            </a>
        </div>

        <!-- Bills List -->
        <div class="mt-4 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <?php if (empty($bills)): ?>
                <div class="text-center py-16 px-4">
                    <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900">No Recurring Bills Found</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1 mb-4">Add your recurring software subscriptions, utility bills, rent, or EMI payments to monitor due dates effortlessly.</p>
                    <button type="button" onclick="openBillModal()" class="px-4 py-2 bg-slate-900 text-white rounded-lg text-xs font-semibold hover:bg-slate-800 transition">
                        Add First Bill
                    </button>
                </div>
            <?php else: ?>
                <!-- Desktop Table View with Fixed Height & Inner Scrolling -->
                <div class="hidden md:block overflow-x-auto max-h-[520px] overflow-y-auto border border-slate-200 rounded-xl inscrolling-table-container">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead class="sticky top-0 z-10 bg-slate-50 border-b border-slate-200">
                            <tr class="bg-slate-50 text-slate-600 text-xs uppercase font-semibold">
                                <th class="py-3 px-5 bg-slate-50">Bill / Title</th>
                                <th class="py-3 px-5 bg-slate-50">Category & Account</th>
                                <th class="py-3 px-5 bg-slate-50">Frequency</th>
                                <th class="py-3 px-5 bg-slate-50">Amount</th>
                                <th class="py-3 px-5 bg-slate-50">Next Due Date</th>
                                <th class="py-3 px-5 bg-slate-50">Status</th>
                                <th class="py-3 px-5 text-right bg-slate-50">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <?php foreach ($bills as $bill): 
                                $due_ts = strtotime($bill['next_due_date']);
                                $diff_days = floor(($due_ts - strtotime($today)) / 86400);
                            ?>
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="py-4 px-5">
                                        <div class="font-bold text-slate-900"><?php echo htmlspecialchars($bill['title']); ?></div>
                                    </td>
                                    <td class="py-4 px-5">
                                        <div class="text-xs font-semibold text-slate-800"><?php echo htmlspecialchars($bill['category']); ?></div>
                                        <div class="text-xs text-slate-500 mt-0.5 flex items-center space-x-1">
                                            <?php echo account_icon('w-3.5 h-3.5 text-slate-400'); ?>
                                            <span><?php echo htmlspecialchars($bill['account_name'] ?? 'Primary Account'); ?></span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold uppercase bg-slate-100 text-slate-700">
                                            <?php echo htmlspecialchars($bill['frequency']); ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-5 font-bold text-slate-900">
                                        ₹<?php echo number_format($bill['amount'], 2); ?>
                                    </td>
                                    <td class="py-4 px-5">
                                        <div class="font-semibold text-slate-900 text-xs">
                                            <?php echo date('M j, Y', $due_ts); ?>
                                        </div>
                                        <div class="mt-0.5">
                                            <?php if ($bill['status'] !== 'active'): ?>
                                                <span class="text-[11px] text-slate-400 font-medium">Inactive</span>
                                            <?php elseif ($diff_days < 0): ?>
                                                <span class="inline-flex items-center text-[11px] font-bold text-rose-700 bg-rose-50 border border-rose-200 px-1.5 py-0.5 rounded">
                                                    Overdue by <?php echo abs($diff_days); ?> day<?php echo abs($diff_days) > 1 ? 's' : ''; ?>
                                                </span>
                                            <?php elseif ($diff_days === 0): ?>
                                                <span class="inline-flex items-center text-[11px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded">
                                                    Due Today!
                                                </span>
                                            <?php elseif ($diff_days <= 3): ?>
                                                <span class="inline-flex items-center text-[11px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded">
                                                    Due in <?php echo $diff_days; ?> days
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center text-[11px] font-medium text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded">
                                                    In <?php echo $diff_days; ?> days
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="py-4 px-5">
                                        <span class="text-xs font-bold uppercase tracking-wider px-2 py-0.5 rounded <?php 
                                            if ($bill['status'] === 'active') echo 'bg-emerald-50 text-emerald-700 border border-emerald-200';
                                            elseif ($bill['status'] === 'paused') echo 'bg-amber-50 text-amber-700 border border-amber-200';
                                            else echo 'bg-slate-100 text-slate-600 border border-slate-200';
                                        ?>">
                                            <?php echo htmlspecialchars($bill['status']); ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-5 text-right space-x-2">
                                        <?php if ($bill['status'] === 'active'): ?>
                                            <button type="button" onclick="markAsPaid(<?php echo $bill['id']; ?>, '<?php echo htmlspecialchars(addslashes($bill['title'])); ?>', <?php echo $bill['amount']; ?>)" class="text-xs font-bold text-emerald-800 bg-emerald-100 hover:bg-emerald-200 px-2.5 py-1 rounded transition">
                                                Mark Paid
                                            </button>
                                        <?php endif; ?>
                                        <button type="button" onclick="editBill(<?php echo htmlspecialchars(json_encode($bill)); ?>)" class="text-xs font-semibold text-slate-700 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 px-2.5 py-1 rounded transition">
                                            Edit
                                        </button>
                                        <button type="button" onclick="deleteBill(<?php echo $bill['id']; ?>, '<?php echo htmlspecialchars(addslashes($bill['title'])); ?>')" class="text-xs font-semibold text-rose-600 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 px-2.5 py-1 rounded transition">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card View -->
                <div class="block md:hidden divide-y divide-slate-200 max-h-[520px] overflow-y-auto inscrolling-table-container">
                    <?php foreach ($bills as $bill): 
                        $due_ts = strtotime($bill['next_due_date']);
                        $diff_days = floor(($due_ts - strtotime($today)) / 86400);
                    ?>
                        <div class="p-4 space-y-3">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="font-bold text-slate-900 text-sm"><?php echo htmlspecialchars($bill['title']); ?></div>
                                    <div class="text-xs text-slate-500 mt-0.5"><?php echo htmlspecialchars($bill['category']); ?> &bull; <?php echo htmlspecialchars($bill['frequency']); ?></div>
                                </div>
                                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded <?php 
                                    if ($bill['status'] === 'active') echo 'bg-emerald-50 text-emerald-700 border border-emerald-200';
                                    elseif ($bill['status'] === 'paused') echo 'bg-amber-50 text-amber-700 border border-amber-200';
                                    else echo 'bg-slate-100 text-slate-600 border border-slate-200';
                                ?>">
                                    <?php echo htmlspecialchars($bill['status']); ?>
                                </span>
                            </div>

                            <div class="flex items-center justify-between text-xs">
                                <div>
                                    <span class="text-slate-500 block">Amount</span>
                                    <span class="font-bold text-slate-900 text-sm">₹<?php echo number_format($bill['amount'], 2); ?></span>
                                </div>
                                <div class="text-right">
                                    <span class="text-slate-500 block">Next Due</span>
                                    <span class="font-bold text-slate-900"><?php echo date('M j, Y', $due_ts); ?></span>
                                    <div class="mt-0.5">
                                        <?php if ($diff_days < 0): ?>
                                            <span class="text-[10px] font-bold text-rose-700 bg-rose-50 px-1 py-0.5 rounded">Overdue (<?php echo abs($diff_days); ?>d)</span>
                                        <?php elseif ($diff_days <= 3): ?>
                                            <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-1 py-0.5 rounded">Due in <?php echo $diff_days; ?>d</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
                                <?php if ($bill['status'] === 'active'): ?>
                                    <button type="button" onclick="markAsPaid(<?php echo $bill['id']; ?>, '<?php echo htmlspecialchars(addslashes($bill['title'])); ?>', <?php echo $bill['amount']; ?>)" class="text-xs font-bold text-emerald-800 bg-emerald-100 px-3 py-1.5 rounded">
                                        Mark Paid
                                    </button>
                                <?php endif; ?>
                                <button type="button" onclick="editBill(<?php echo htmlspecialchars(json_encode($bill)); ?>)" class="text-xs font-semibold text-slate-700 bg-slate-100 px-3 py-1.5 rounded">
                                    Edit
                                </button>
                                <button type="button" onclick="deleteBill(<?php echo $bill['id']; ?>, '<?php echo htmlspecialchars(addslashes($bill['title'])); ?>')" class="text-xs font-semibold text-rose-600 bg-rose-50 px-3 py-1.5 rounded">
                                    Delete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Add / Edit Bill Modal -->
    <div id="billModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 hidden">
        <div class="bg-white rounded-xl max-w-md w-full border border-slate-200 shadow-xl overflow-hidden">
            <div class="p-5 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <h3 id="billModalTitle" class="text-base font-bold text-slate-900">Add Recurring Bill</h3>
                <button type="button" onclick="closeBillModal()" class="text-slate-400 hover:text-slate-600 p-1">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <form id="billForm" onsubmit="submitBillForm(event)" class="p-5 space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo $auth->getCSRFToken(); ?>">
                <input type="hidden" id="billAction" name="action" value="add_bill">
                <input type="hidden" id="billId" name="id" value="">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Bill / Subscription Title</label>
                    <input type="text" id="billTitle" name="title" required placeholder="e.g. Jio Fiber, Netflix, Apartment Rent" class="w-full px-3 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Amount (₹)</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 font-bold">
                                ₹
                            </div>
                            <input type="number" step="0.01" min="0.01" id="billAmount" name="amount" required placeholder="0.00" class="w-full pl-8 pr-3 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Frequency</label>
                        <select id="billFrequency" name="frequency" required class="w-full px-3 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900">
                            <option value="monthly">Monthly</option>
                            <option value="weekly">Weekly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Category</label>
                        <select id="billCategory" name="category" required class="w-full px-3 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Payment Account</label>
                        <select id="billAccountId" name="account_id" class="w-full px-3 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900">
                            <option value="">Default Account</option>
                            <?php foreach ($accounts as $acc): ?>
                                <option value="<?php echo $acc['id']; ?>"><?php echo htmlspecialchars($acc['name']); ?> (<?php echo htmlspecialchars($acc['type']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Next Due Date</label>
                        <input type="date" id="billNextDueDate" name="next_due_date" required class="w-full px-3 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900">
                    </div>
                    <div id="billStatusContainer" class="hidden">
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Status</label>
                        <select id="billStatus" name="status" class="w-full px-3 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900">
                            <option value="active">Active</option>
                            <option value="paused">Paused</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>

                <div id="billModalAlert" class="hidden p-3 rounded-lg text-xs font-semibold"></div>

                <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-200">
                    <button type="button" onclick="closeBillModal()" class="px-4 py-2 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                        Cancel
                    </button>
                    <button type="submit" id="saveBillBtn" class="px-4 py-2 bg-slate-900 text-white rounded-lg text-xs font-semibold hover:bg-slate-800 transition">
                        Save Bill
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Mark Paid Confirmation Modal -->
    <div id="markPaidModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 hidden">
        <div class="bg-white rounded-xl max-w-sm w-full border border-slate-200 shadow-xl overflow-hidden p-5">
            <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center mb-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
            <h3 class="text-base font-bold text-slate-900">Record Bill Payment</h3>
            <p class="text-xs text-slate-600 mt-2">
                Marking <span id="markPaidTitle" class="font-bold text-slate-900"></span> as paid will deduct <span id="markPaidAmount" class="font-bold text-slate-900"></span> from your linked account as an expense and automatically advance the next due date.
            </p>
            <div class="flex items-center justify-end space-x-3 mt-5">
                <button type="button" onclick="closeMarkPaidModal()" class="px-3.5 py-1.5 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="button" id="confirmMarkPaidBtn" class="px-3.5 py-1.5 bg-emerald-700 hover:bg-emerald-800 text-white rounded-lg text-xs font-semibold">Confirm Payment</button>
            </div>
        </div>
    </div>

    <!-- Confirm Delete Modal -->
    <div id="deleteBillModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 hidden">
        <div class="bg-white rounded-xl max-w-sm w-full border border-slate-200 shadow-xl overflow-hidden p-5">
            <h3 class="text-base font-bold text-slate-900">Remove Recurring Bill</h3>
            <p class="text-xs text-slate-600 mt-2">Are you sure you want to delete <span id="deleteBillTitle" class="font-bold text-slate-900"></span>? Past transactions will remain safe in your ledger.</p>
            <div class="flex items-center justify-end space-x-3 mt-5">
                <button type="button" onclick="closeDeleteBillModal()" class="px-3.5 py-1.5 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="button" id="confirmDeleteBillBtn" class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-semibold">Delete</button>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        const billModal = document.getElementById('billModal');
        const alertBox = document.getElementById('billModalAlert');
        let currentPaidId = null;
        let currentDeleteId = null;

        function openBillModal() {
            document.getElementById('billModalTitle').textContent = 'Add Recurring Bill';
            document.getElementById('billForm').reset();
            document.getElementById('billAction').value = 'add_bill';
            document.getElementById('billId').value = '';
            document.getElementById('billStatusContainer').classList.add('hidden');
            document.getElementById('billNextDueDate').value = new Date().toISOString().split('T')[0];
            alertBox.classList.add('hidden');
            billModal.classList.remove('hidden');
        }

        function closeBillModal() {
            billModal.classList.add('hidden');
        }

        function editBill(bill) {
            document.getElementById('billModalTitle').textContent = 'Edit ' + bill.title;
            document.getElementById('billAction').value = 'update_bill';
            document.getElementById('billId').value = bill.id;
            document.getElementById('billTitle').value = bill.title;
            document.getElementById('billAmount').value = bill.amount;
            document.getElementById('billFrequency').value = bill.frequency;
            document.getElementById('billCategory').value = bill.category;
            document.getElementById('billAccountId').value = bill.account_id || '';
            document.getElementById('billNextDueDate').value = bill.next_due_date;
            document.getElementById('billStatus').value = bill.status;
            document.getElementById('billStatusContainer').classList.remove('hidden');
            alertBox.classList.add('hidden');
            billModal.classList.remove('hidden');
        }

        async function submitBillForm(e) {
            e.preventDefault();
            const btn = document.getElementById('saveBillBtn');
            btn.disabled = true;
            btn.textContent = 'Saving...';
            alertBox.classList.add('hidden');

            const formData = new FormData(document.getElementById('billForm'));
            try {
                const res = await fetch('../ajax/bills.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    alertBox.className = 'p-3 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200 block flex items-center space-x-2';
                    alertBox.innerHTML = '<svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><span>Successfully Saved! Updating...</span>';
                    btn.textContent = 'Successfully Saved!';
                    btn.className = 'px-4 py-2 bg-emerald-600 text-white rounded-lg text-xs font-semibold';
                    sessionStorage.setItem('flash_toast', JSON.stringify({ 
                        title: 'Successfully Saved', 
                        message: data.message || 'Recurring bill saved successfully.', 
                        type: 'success' 
                    }));
                    setTimeout(() => window.location.reload(), 450);
                } else {
                    alertBox.className = 'p-3 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 block';
                    alertBox.textContent = data.message || 'Failed to save recurring bill.';
                    btn.disabled = false;
                    btn.textContent = 'Save Bill';
                    btn.className = 'px-4 py-2 bg-slate-900 text-white rounded-lg text-xs font-semibold hover:bg-slate-800 transition';
                }
            } catch (err) {
                alertBox.className = 'p-3 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 block';
                alertBox.textContent = 'Network or server error occurred.';
                btn.disabled = false;
                btn.textContent = 'Save Bill';
                btn.className = 'px-4 py-2 bg-slate-900 text-white rounded-lg text-xs font-semibold hover:bg-slate-800 transition';
            }
        }

        function markAsPaid(id, title, amount) {
            currentPaidId = id;
            document.getElementById('markPaidTitle').textContent = title;
            document.getElementById('markPaidAmount').textContent = '₹' + parseFloat(amount).toFixed(2);
            document.getElementById('markPaidModal').classList.remove('hidden');
        }

        function closeMarkPaidModal() {
            document.getElementById('markPaidModal').classList.add('hidden');
            currentPaidId = null;
        }

        document.getElementById('confirmMarkPaidBtn').addEventListener('click', async function() {
            if (!currentPaidId) return;
            const btn = this;
            btn.disabled = true;
            btn.textContent = 'Recording...';

            const formData = new FormData();
            formData.append('csrf_token', '<?php echo $auth->getCSRFToken(); ?>');
            formData.append('action', 'mark_paid');
            formData.append('id', currentPaidId);

            try {
                const res = await fetch('../ajax/bills.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    btn.textContent = 'Successfully Recorded!';
                    btn.className = 'px-3.5 py-1.5 bg-emerald-600 text-white rounded-lg text-xs font-semibold';
                    sessionStorage.setItem('flash_toast', JSON.stringify({ 
                        title: 'Successfully Recorded', 
                        message: data.message || 'Bill payment recorded successfully.', 
                        type: 'success' 
                    }));
                    setTimeout(() => window.location.reload(), 450);
                } else {
                    if (window.showToast) window.showToast('Action Failed', data.message || 'Failed to record bill payment', 'error');
                    else alert(data.message || 'Failed to record bill payment');
                    btn.disabled = false;
                    btn.textContent = 'Confirm Payment';
                }
            } catch (err) {
                if (window.showToast) window.showToast('Action Failed', 'An error occurred', 'error');
                else alert('An error occurred');
                btn.disabled = false;
                btn.textContent = 'Confirm Payment';
            }
        });

        function deleteBill(id, title) {
            currentDeleteId = id;
            document.getElementById('deleteBillTitle').textContent = title;
            document.getElementById('deleteBillModal').classList.remove('hidden');
        }

        function closeDeleteBillModal() {
            deleteBillModal.classList.add('hidden');
            currentDeleteId = null;
        }

        document.getElementById('confirmDeleteBillBtn').addEventListener('click', async function() {
            if (!currentDeleteId) return;
            const btn = this;
            btn.disabled = true;
            btn.textContent = 'Removing...';

            const formData = new FormData();
            formData.append('csrf_token', '<?php echo $auth->getCSRFToken(); ?>');
            formData.append('action', 'delete_bill');
            formData.append('id', currentDeleteId);

            try {
                const res = await fetch('../ajax/bills.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    btn.textContent = 'Successfully Deleted!';
                    btn.className = 'px-3.5 py-1.5 bg-emerald-600 text-white rounded-lg text-xs font-semibold';
                    sessionStorage.setItem('flash_toast', JSON.stringify({ 
                        title: 'Successfully Deleted', 
                        message: data.message || 'Recurring bill deleted successfully.', 
                        type: 'success' 
                    }));
                    setTimeout(() => window.location.reload(), 450);
                } else {
                    if (window.showToast) window.showToast('Action Failed', data.message || 'Failed to delete bill', 'error');
                    else alert(data.message || 'Failed to delete bill');
                    btn.disabled = false;
                    btn.textContent = 'Delete';
                }
            } catch (err) {
                if (window.showToast) window.showToast('Action Failed', 'An error occurred during deletion', 'error');
                else alert('An error occurred during deletion');
                btn.disabled = false;
                btn.textContent = 'Delete';
            }
        });
    </script>
</body>
</html>
