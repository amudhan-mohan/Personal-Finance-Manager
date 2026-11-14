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

$selected_month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

$budgetPerf = $financeManager->getBudgetPerformance($user_id, $selected_month, $selected_year);

$categories = [
    'Food & Dining',
    'Housing & Rent',
    'Utilities & Bills',
    'Transportation',
    'Entertainment',
    'Healthcare & Medical',
    'Shopping',
    'Personal Care',
    'Education',
    'Savings & Investment',
    'Travel',
    'Other'
];

$page_title = "Budgets & Spending Guardrails";
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
                    <span>Planning & Control</span>
                    <span>/</span>
                    <span class="text-slate-800">Monthly Budgets</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Category Budgets & Guardrails</h1>
                <p class="text-sm text-slate-600 mt-1">Set monthly category ceilings and monitor pacing with threshold warning alerts.</p>
            </div>
            <div class="flex items-center space-x-3">
                <button type="button" onclick="openBudgetModal()" class="inline-flex items-center justify-center px-4 py-2 bg-slate-900 text-white hover:bg-slate-800 rounded-lg text-sm font-semibold transition shadow-sm">
                    <svg class="h-4 w-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Set Budget
                </button>
            </div>
        </div>

        <!-- Month Filter Selector -->
        <div class="mt-6 bg-white p-4 rounded-xl border border-slate-200 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center space-x-3">
                <span class="text-sm font-semibold text-slate-700">Budget Period:</span>
                <span class="inline-flex items-center px-3 py-1 rounded-md text-sm font-bold bg-slate-100 text-slate-800 border border-slate-200">
                    <?php echo $budgetPerf['month_name']; ?>
                </span>
            </div>
            <form method="GET" class="flex items-center space-x-2">
                <select name="month" class="px-3 py-1.5 text-xs font-semibold bg-white border border-slate-300 rounded-lg text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-900">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $m === $selected_month ? 'selected' : ''; ?>>
                            <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <select name="year" class="px-3 py-1.5 text-xs font-semibold bg-white border border-slate-300 rounded-lg text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-900">
                    <?php 
                    $curr_y = intval(date('Y'));
                    for ($y = $curr_y - 2; $y <= $curr_y + 1; $y++): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y === $selected_year ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-lg text-xs font-semibold transition border border-slate-200">
                    Filter
                </button>
            </form>
        </div>

        <!-- Performance Summary KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mt-6">
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider">Total Monthly Budget</span>
                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path d="M21.21 15.89A10 10 0 1 1 8 2.83"/>
                            <path d="M22 12A10 10 0 0 0 12 2v10z"/>
                        </svg>
                    </div>
                </div>
                <div class="text-2xl font-bold text-slate-900">
                    ₹<?php echo number_format($budgetPerf['total_budget'], 2); ?>
                </div>
                <div class="text-xs text-slate-500 mt-1"><?php echo count($budgetPerf['items']); ?> categories allocated</div>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider">Actual Spent</span>
                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-rose-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path d="M12 19V5M5 12l7-7 7 7"/>
                        </svg>
                    </div>
                </div>
                <div class="text-2xl font-bold text-slate-900">
                    ₹<?php echo number_format($budgetPerf['total_spent'], 2); ?>
                </div>
                <div class="text-xs text-slate-500 mt-1">Total recorded expenses this month</div>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider">Remaining Buffer</span>
                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-emerald-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path d="M12 5v14M19 12l-7 7-7-7"/>
                        </svg>
                    </div>
                </div>
                <div class="text-2xl font-bold <?php echo $budgetPerf['total_remaining'] > 0 ? 'text-emerald-700' : 'text-rose-700'; ?>">
                    ₹<?php echo number_format($budgetPerf['total_remaining'], 2); ?>
                </div>
                <div class="text-xs text-slate-500 mt-1">Safe-to-spend headroom</div>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider">Overall Pacing</span>
                    <span class="text-xs font-bold px-2 py-0.5 rounded <?php 
                        if ($budgetPerf['overall_pct'] >= 100) echo 'bg-rose-100 text-rose-800';
                        elseif ($budgetPerf['overall_pct'] >= 80) echo 'bg-amber-100 text-amber-800';
                        else echo 'bg-emerald-100 text-emerald-800';
                    ?>">
                        <?php echo $budgetPerf['overall_pct']; ?>%
                    </span>
                </div>
                <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden mt-3">
                    <div class="h-full rounded-full <?php 
                        if ($budgetPerf['overall_pct'] >= 100) echo 'bg-rose-600';
                        elseif ($budgetPerf['overall_pct'] >= 80) echo 'bg-amber-500';
                        else echo 'bg-emerald-600';
                    ?>" style="width: <?php echo min(100, $budgetPerf['overall_pct']); ?>%;"></div>
                </div>
                <div class="text-xs text-slate-500 mt-2">
                    <?php 
                        if ($budgetPerf['overall_pct'] >= 100) echo 'Budget exceeded for the period';
                        elseif ($budgetPerf['overall_pct'] >= 80) echo 'Caution: Approaching limit';
                        else echo 'Healthy pacing within targets';
                    ?>
                </div>
            </div>
        </div>

        <!-- Budget Guardrails List -->
        <div class="mt-8 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Category Ceilings & Burn Rates</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Automated guardrails flag categories exceeding 80% and 100% pacing thresholds.</p>
                </div>
            </div>

            <?php if (empty($budgetPerf['items'])): ?>
                <div class="text-center py-16 px-4">
                    <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path d="M21.21 15.89A10 10 0 1 1 8 2.83"/>
                            <path d="M22 12A10 10 0 0 0 12 2v10z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900">No Category Budgets Configured</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1 mb-4">Establish monthly spending limits on your primary categories to activate automated guardrails and warnings.</p>
                    <button type="button" onclick="openBudgetModal()" class="px-4 py-2 bg-slate-900 text-white rounded-lg text-xs font-semibold hover:bg-slate-800 transition">
                        Configure First Budget
                    </button>
                </div>
            <?php else: ?>
                <!-- Desktop Table View with Fixed Height & Inner Scrolling -->
                <div class="hidden md:block overflow-x-auto max-h-[520px] overflow-y-auto border border-slate-200 rounded-xl inscrolling-table-container">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead class="sticky top-0 z-10 bg-slate-50 border-b border-slate-200">
                            <tr class="bg-slate-50 text-slate-600 text-xs uppercase font-semibold">
                                <th class="py-3 px-5 bg-slate-50">Category</th>
                                <th class="py-3 px-5 bg-slate-50">Monthly Limit</th>
                                <th class="py-3 px-5 bg-slate-50">Actual Spend</th>
                                <th class="py-3 px-5 bg-slate-50">Remaining</th>
                                <th class="py-3 px-5 bg-slate-50">Pacing & Guardrail</th>
                                <th class="py-3 px-5 text-right bg-slate-50">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <?php foreach ($budgetPerf['items'] as $item): ?>
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="py-4 px-5">
                                        <div class="font-bold text-slate-900"><?php echo htmlspecialchars($item['category']); ?></div>
                                    </td>
                                    <td class="py-4 px-5 font-semibold text-slate-900">
                                        ₹<?php echo number_format($item['monthly_limit'], 2); ?>
                                    </td>
                                    <td class="py-4 px-5 font-semibold text-slate-700">
                                        ₹<?php echo number_format($item['spent'], 2); ?>
                                    </td>
                                    <td class="py-4 px-5">
                                        <span class="font-semibold <?php echo $item['remaining'] > 0 ? 'text-emerald-700' : 'text-rose-700'; ?>">
                                            ₹<?php echo number_format($item['remaining'], 2); ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-5 w-64">
                                        <div class="flex items-center justify-between text-xs font-semibold mb-1">
                                            <span class="<?php 
                                                if ($item['status'] === 'exceeded') echo 'text-rose-700';
                                                elseif ($item['status'] === 'warning') echo 'text-amber-700';
                                                else echo 'text-emerald-700';
                                            ?>">
                                                <?php echo $item['percentage']; ?>%
                                            </span>
                                            <span class="text-[11px] px-1.5 py-0.5 rounded font-bold uppercase tracking-wider <?php 
                                                if ($item['status'] === 'exceeded') echo 'bg-rose-50 text-rose-700 border border-rose-200';
                                                elseif ($item['status'] === 'warning') echo 'bg-amber-50 text-amber-700 border border-amber-200';
                                                else echo 'bg-emerald-50 text-emerald-700 border border-emerald-200';
                                            ?>">
                                                <?php echo str_replace('_', ' ', $item['status']); ?>
                                            </span>
                                        </div>
                                        <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full <?php 
                                                if ($item['status'] === 'exceeded') echo 'bg-rose-600';
                                                elseif ($item['status'] === 'warning') echo 'bg-amber-500';
                                                else echo 'bg-emerald-600';
                                            ?>" style="width: <?php echo min(100, $item['percentage']); ?>%;"></div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-5 text-right space-x-2">
                                        <button type="button" onclick="editBudget(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars(addslashes($item['category'])); ?>', <?php echo $item['monthly_limit']; ?>)" class="text-xs font-semibold text-slate-700 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 px-2.5 py-1 rounded transition">
                                            Edit
                                        </button>
                                        <button type="button" onclick="deleteBudget(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars(addslashes($item['category'])); ?>')" class="text-xs font-semibold text-rose-600 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 px-2.5 py-1 rounded transition">
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
                    <?php foreach ($budgetPerf['items'] as $item): ?>
                        <div class="p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="font-bold text-slate-900 text-sm"><?php echo htmlspecialchars($item['category']); ?></div>
                                <span class="text-[10px] px-2 py-0.5 rounded font-bold uppercase tracking-wider <?php 
                                    if ($item['status'] === 'exceeded') echo 'bg-rose-50 text-rose-700 border border-rose-200';
                                    elseif ($item['status'] === 'warning') echo 'bg-amber-50 text-amber-700 border border-amber-200';
                                    else echo 'bg-emerald-50 text-emerald-700 border border-emerald-200';
                                ?>">
                                    <?php echo str_replace('_', ' ', $item['status']); ?>
                                </span>
                            </div>

                            <div class="grid grid-cols-3 gap-2 text-xs">
                                <div>
                                    <span class="text-slate-500 block">Limit</span>
                                    <span class="font-bold text-slate-900">₹<?php echo number_format($item['monthly_limit'], 2); ?></span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block">Spent</span>
                                    <span class="font-bold text-slate-700">₹<?php echo number_format($item['spent'], 2); ?></span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block">Remaining</span>
                                    <span class="font-bold <?php echo $item['remaining'] > 0 ? 'text-emerald-700' : 'text-rose-700'; ?>">
                                        ₹<?php echo number_format($item['remaining'], 2); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <div class="flex justify-between text-xs">
                                    <span class="text-slate-500">Pacing</span>
                                    <span class="font-bold <?php 
                                        if ($item['status'] === 'exceeded') echo 'text-rose-700';
                                        elseif ($item['status'] === 'warning') echo 'text-amber-700';
                                        else echo 'text-emerald-700';
                                    ?>"><?php echo $item['percentage']; ?>%</span>
                                </div>
                                <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full <?php 
                                        if ($item['status'] === 'exceeded') echo 'bg-rose-600';
                                        elseif ($item['status'] === 'warning') echo 'bg-amber-500';
                                        else echo 'bg-emerald-600';
                                    ?>" style="width: <?php echo min(100, $item['percentage']); ?>%;"></div>
                                </div>
                            </div>

                            <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
                                <button type="button" onclick="editBudget(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars(addslashes($item['category'])); ?>', <?php echo $item['monthly_limit']; ?>)" class="text-xs font-semibold text-slate-700 bg-slate-100 px-3 py-1.5 rounded">
                                    Edit
                                </button>
                                <button type="button" onclick="deleteBudget(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars(addslashes($item['category'])); ?>')" class="text-xs font-semibold text-rose-600 bg-rose-50 px-3 py-1.5 rounded">
                                    Delete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Set / Edit Budget Modal -->
    <div id="budgetModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 hidden">
        <div class="bg-white rounded-xl max-w-md w-full border border-slate-200 shadow-xl overflow-hidden">
            <div class="p-5 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <h3 id="budgetModalTitle" class="text-base font-bold text-slate-900">Set Category Budget</h3>
                <button type="button" onclick="closeBudgetModal()" class="text-slate-400 hover:text-slate-600 p-1">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <form id="budgetForm" onsubmit="submitBudgetForm(event)" class="p-5 space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo $auth->getCSRFToken(); ?>">
                <input type="hidden" name="action" value="set_budget">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Category</label>
                    <select id="budgetCategory" name="category" required class="w-full px-3 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900">
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Monthly Ceiling (₹)</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 font-bold">
                            ₹
                        </div>
                        <input type="number" step="0.01" min="1" id="monthlyLimit" name="monthly_limit" required placeholder="0.00" class="w-full pl-8 pr-3 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900">
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Maximum recommended spend for this calendar month.</p>
                </div>

                <div id="modalAlert" class="hidden p-3 rounded-lg text-xs font-semibold"></div>

                <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-200">
                    <button type="button" onclick="closeBudgetModal()" class="px-4 py-2 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                        Cancel
                    </button>
                    <button type="submit" id="saveBudgetBtn" class="px-4 py-2 bg-slate-900 text-white rounded-lg text-xs font-semibold hover:bg-slate-800 transition">
                        Save Budget
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirm Delete Modal -->
    <div id="deleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 hidden">
        <div class="bg-white rounded-xl max-w-sm w-full border border-slate-200 shadow-xl overflow-hidden p-5">
            <h3 class="text-base font-bold text-slate-900">Remove Budget Guardrail</h3>
            <p class="text-xs text-slate-600 mt-2">Are you sure you want to remove the monthly limit for <span id="deleteCategoryName" class="font-bold text-slate-900"></span>? Your transactions will remain intact.</p>
            <div class="flex items-center justify-end space-x-3 mt-5">
                <button type="button" onclick="closeDeleteModal()" class="px-3.5 py-1.5 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="button" id="confirmDeleteBtn" class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-semibold">Delete</button>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        const modal = document.getElementById('budgetModal');
        const deleteModal = document.getElementById('deleteModal');
        const alertBox = document.getElementById('modalAlert');
        let currentDeleteId = null;

        function openBudgetModal() {
            document.getElementById('budgetModalTitle').textContent = 'Set Category Budget';
            document.getElementById('budgetForm').reset();
            document.getElementById('budgetCategory').disabled = false;
            alertBox.classList.add('hidden');
            modal.classList.remove('hidden');
        }

        function closeBudgetModal() {
            modal.classList.add('hidden');
        }

        function editBudget(id, category, limit) {
            document.getElementById('budgetModalTitle').textContent = 'Update ' + category + ' Budget';
            document.getElementById('budgetCategory').value = category;
            document.getElementById('monthlyLimit').value = limit;
            alertBox.classList.add('hidden');
            modal.classList.remove('hidden');
        }

        async function submitBudgetForm(e) {
            e.preventDefault();
            const btn = document.getElementById('saveBudgetBtn');
            btn.disabled = true;
            btn.textContent = 'Saving...';
            alertBox.classList.add('hidden');

            const formData = new FormData(document.getElementById('budgetForm'));
            try {
                const res = await fetch('../ajax/budgets.php', {
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
                        message: data.message || 'Category budget saved successfully.', 
                        type: 'success' 
                    }));
                    setTimeout(() => window.location.reload(), 450);
                } else {
                    alertBox.className = 'p-3 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 block';
                    alertBox.textContent = data.message || 'Failed to save budget.';
                    btn.disabled = false;
                    btn.textContent = 'Save Budget';
                    btn.className = 'px-4 py-2 bg-slate-900 text-white rounded-lg text-xs font-semibold hover:bg-slate-800 transition';
                }
            } catch (err) {
                alertBox.className = 'p-3 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 block';
                alertBox.textContent = 'Network or server error occurred.';
                btn.disabled = false;
                btn.textContent = 'Save Budget';
                btn.className = 'px-4 py-2 bg-slate-900 text-white rounded-lg text-xs font-semibold hover:bg-slate-800 transition';
            }
        }

        function deleteBudget(id, category) {
            currentDeleteId = id;
            document.getElementById('deleteCategoryName').textContent = category;
            deleteModal.classList.remove('hidden');
        }

        function closeDeleteModal() {
            deleteModal.classList.add('hidden');
            currentDeleteId = null;
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
            if (!currentDeleteId) return;
            const btn = this;
            btn.disabled = true;
            btn.textContent = 'Removing...';

            const formData = new FormData();
            formData.append('csrf_token', '<?php echo $auth->getCSRFToken(); ?>');
            formData.append('action', 'delete_budget');
            formData.append('id', currentDeleteId);

            try {
                const res = await fetch('../ajax/budgets.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    btn.textContent = 'Successfully Removed!';
                    btn.className = 'px-3.5 py-1.5 bg-emerald-600 text-white rounded-lg text-xs font-semibold';
                    sessionStorage.setItem('flash_toast', JSON.stringify({ 
                        title: 'Successfully Removed', 
                        message: data.message || 'Category budget removed successfully.', 
                        type: 'success' 
                    }));
                    setTimeout(() => window.location.reload(), 450);
                } else {
                    if (window.showToast) window.showToast('Action Failed', data.message || 'Failed to delete budget', 'error');
                    else alert(data.message || 'Failed to delete budget');
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
