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

$goals_res = $financeManager->getSavingsGoals($user_id);
$goals = [];
$total_target = 0;
$total_saved = 0;

while ($row = $goals_res->fetch_assoc()) {
    $goals[] = $row;
    $total_target += (float)$row['target_amount'];
    $total_saved += (float)$row['current_amount'];
}

$accounts_res = $financeManager->getAccounts($user_id);
$funding_accounts = [];
while ($acc = $accounts_res->fetch_assoc()) {
    if (in_array($acc['type'], ['bank', 'cash'], true)) {
        $funding_accounts[] = $acc;
    }
}

$total_needed = max(0, $total_target - $total_saved);
$overall_completion_pct = ($total_target > 0) ? round(($total_saved / $total_target) * 100, 1) : 0;

$page_title = "Savings Goals & Sinking Funds";
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
                    <span>Wealth Building</span>
                    <span>/</span>
                    <span class="text-slate-800">Sinking Funds</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Milestone Savings Goals</h1>
                <p class="text-sm text-slate-600 mt-1">Allocate funds towards long-term objectives, emergency reserves, and major expenditures.</p>
            </div>
            <div class="flex items-center space-x-3">
                <button type="button" onclick="openGoalModal()" class="inline-flex items-center justify-center px-4 py-2 bg-slate-900 text-white hover:bg-slate-800 rounded-lg text-sm font-semibold transition shadow-sm">
                    <svg class="h-4 w-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Create New Goal
                </button>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mt-6">
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider">Total Target Capital</span>
                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <circle cx="12" cy="12" r="6"/>
                            <circle cx="12" cy="12" r="2"/>
                        </svg>
                    </div>
                </div>
                <div class="text-2xl font-bold text-slate-900">
                    ₹<?php echo number_format($total_target, 2); ?>
                </div>
                <div class="text-xs text-slate-500 mt-1"><?php echo count($goals); ?> planned milestone goals</div>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider">Accumulated Funds</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center">
                        <?php echo rupee_icon('w-4 h-4 text-emerald-700'); ?>
                    </div>
                </div>
                <div class="text-2xl font-bold text-emerald-700">
                    ₹<?php echo number_format($total_saved, 2); ?>
                </div>
                <div class="text-xs text-slate-500 mt-1">Current allocated capital</div>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider">Capital Remaining</span>
                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/>
                        </svg>
                    </div>
                </div>
                <div class="text-2xl font-bold text-slate-900">
                    ₹<?php echo number_format($total_needed, 2); ?>
                </div>
                <div class="text-xs text-slate-500 mt-1">Still required to fund all goals</div>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider">Overall Progress</span>
                    <span class="text-xs font-bold px-2 py-0.5 rounded bg-blue-50 text-blue-800 border border-blue-200">
                        <?php echo $overall_completion_pct; ?>%
                    </span>
                </div>
                <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden mt-3">
                    <div class="h-full rounded-full bg-blue-600" style="width: <?php echo min(100, $overall_completion_pct); ?>%;"></div>
                </div>
                <div class="text-xs text-slate-500 mt-2">
                    <?php echo $total_saved >= $total_target && $total_target > 0 ? 'All milestones fully funded!' : 'Ongoing accumulation across goals'; ?>
                </div>
            </div>
        </div>

        <!-- Goals Cards Grid -->
        <div class="mt-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-slate-900">Active Goals & Sinking Funds</h2>
                <span class="text-xs text-slate-500 font-medium"><?php echo count($goals); ?> milestones defined</span>
            </div>

            <?php if (empty($goals)): ?>
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm text-center py-16 px-4">
                    <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <circle cx="12" cy="12" r="6"/>
                            <circle cx="12" cy="12" r="2"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900">No Savings Goals Set</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1 mb-4">Define dedicated sinking funds for your emergency reserve, travel, vehicle purchases, or home improvements.</p>
                    <button type="button" onclick="openGoalModal()" class="px-4 py-2 bg-slate-900 text-white rounded-lg text-xs font-semibold hover:bg-slate-800 transition">
                        Create First Goal
                    </button>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($goals as $goal): 
                        $target = (float)$goal['target_amount'];
                        $current = (float)$goal['current_amount'];
                        $remaining = max(0, $target - $current);
                        $pct = ($target > 0) ? round(($current / $target) * 100, 1) : 0;
                        
                        // Target date & monthly pace calculation
                        $has_date = !empty($goal['target_date']);
                        $months_remaining = 0;
                        $monthly_pace = 0;
                        if ($has_date) {
                            $target_ts = strtotime($goal['target_date']);
                            $now_ts = strtotime(date('Y-m-d'));
                            $days_diff = ceil(($target_ts - $now_ts) / 86400);
                            $months_remaining = max(1, ceil($days_diff / 30.4));
                            if ($remaining > 0 && $days_diff > 0) {
                                $monthly_pace = round($remaining / $months_remaining, 2);
                            }
                        }
                    ?>
                        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:border-slate-300 transition flex flex-col justify-between">
                            <div>
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h3 class="text-base font-bold text-slate-900"><?php echo htmlspecialchars($goal['title']); ?></h3>
                                        <?php if ($has_date): ?>
                                            <p class="text-xs text-slate-500 mt-0.5">Target: <?php echo date('M j, Y', strtotime($goal['target_date'])); ?></p>
                                        <?php else: ?>
                                            <p class="text-xs text-slate-400 mt-0.5">Open-ended horizon</p>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-xs font-bold px-2 py-0.5 rounded <?php 
                                        if ($pct >= 100) echo 'bg-emerald-50 text-emerald-700 border border-emerald-200';
                                        elseif ($pct >= 50) echo 'bg-blue-50 text-blue-700 border border-blue-200';
                                        else echo 'bg-slate-100 text-slate-700 border border-slate-200';
                                    ?>">
                                        <?php echo $pct >= 100 ? 'Achieved!' : $pct . '%'; ?>
                                    </span>
                                </div>

                                <!-- Progress Bar -->
                                <div class="mt-4">
                                    <div class="flex justify-between text-xs mb-1.5 font-semibold">
                                        <span class="text-emerald-700">₹<?php echo number_format($current, 2); ?></span>
                                        <span class="text-slate-500">₹<?php echo number_format($target, 2); ?></span>
                                    </div>
                                    <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full <?php echo $pct >= 100 ? 'bg-emerald-600' : 'bg-blue-600'; ?>" style="width: <?php echo min(100, $pct); ?>%;"></div>
                                    </div>
                                </div>

                                <!-- Metric Pill Grid -->
                                <div class="grid grid-cols-2 gap-2 mt-4 pt-3 border-t border-slate-100 text-xs">
                                    <div class="p-2 bg-slate-50 rounded-lg">
                                        <span class="text-slate-500 block text-[10px] uppercase font-semibold">Remaining Needed</span>
                                        <span class="font-bold text-slate-900 mt-0.5 block">₹<?php echo number_format($remaining, 2); ?></span>
                                    </div>
                                    <div class="p-2 bg-slate-50 rounded-lg">
                                        <span class="text-slate-500 block text-[10px] uppercase font-semibold">Required Pace</span>
                                        <?php if ($monthly_pace > 0): ?>
                                            <span class="font-bold text-slate-900 mt-0.5 block">₹<?php echo number_format($monthly_pace, 2); ?>/mo</span>
                                        <?php elseif ($pct >= 100): ?>
                                            <span class="font-bold text-emerald-700 mt-0.5 block">Goal Reached</span>
                                        <?php else: ?>
                                            <span class="font-bold text-slate-500 mt-0.5 block">Flexible</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Footer Actions -->
                            <div class="mt-5 pt-3 border-t border-slate-100 flex items-center justify-between">
                                <button type="button" onclick="openContributeModal(<?php echo $goal['id']; ?>, '<?php echo htmlspecialchars(addslashes($goal['title'])); ?>', <?php echo $remaining; ?>)" class="inline-flex items-center text-xs font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-3 py-1.5 rounded-lg transition">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <line x1="12" y1="5" x2="12" y2="19"/>
                                        <line x1="5" y1="12" x2="19" y2="12"/>
                                    </svg>
                                    Contribute
                                </button>
                                <div class="flex items-center space-x-1">
                                    <button type="button" onclick="editGoal(<?php echo htmlspecialchars(json_encode($goal)); ?>)" class="text-xs font-semibold text-slate-700 hover:text-slate-900 p-1.5 hover:bg-slate-100 rounded transition" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button type="button" onclick="deleteGoal(<?php echo $goal['id']; ?>, '<?php echo htmlspecialchars(addslashes($goal['title'])); ?>')" class="text-xs font-semibold text-rose-600 hover:text-rose-800 p-1.5 hover:bg-rose-50 rounded transition" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Create / Edit Goal Modal -->
    <div id="goalModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 hidden">
        <div class="bg-white rounded-xl max-w-md w-full border border-slate-200 shadow-xl overflow-hidden">
            <div class="p-5 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <h3 id="goalModalTitle" class="text-base font-bold text-slate-900">Create Savings Goal</h3>
                <button type="button" onclick="closeGoalModal()" class="text-slate-400 hover:text-slate-600 p-1">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <form id="goalForm" onsubmit="submitGoalForm(event)" class="p-5 space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo $auth->getCSRFToken(); ?>">
                <input type="hidden" id="goalAction" name="action" value="add_goal">
                <input type="hidden" id="goalId" name="id" value="">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Goal Title</label>
                    <input type="text" id="goalTitle" name="title" required placeholder="e.g. Emergency Fund (6 Months), New Vehicle, Maldives Trip" class="w-full px-3 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Target Amount (₹)</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 font-bold">
                                ₹
                            </div>
                            <input type="number" step="0.01" min="1" id="goalTargetAmount" name="target_amount" required placeholder="0.00" class="w-full pl-8 pr-3 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Current Saved (₹)</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 font-bold">
                                ₹
                            </div>
                            <input type="number" step="0.01" min="0" id="goalCurrentAmount" name="current_amount" value="0.00" class="w-full pl-8 pr-3 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Target Completion Date (Optional)</label>
                    <input type="date" id="goalTargetDate" name="target_date" class="w-full px-3 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900">
                    <p class="text-xs text-slate-500 mt-1">Allows the engine to compute your necessary monthly savings velocity.</p>
                </div>

                <div id="goalModalAlert" class="hidden p-3 rounded-lg text-xs font-semibold"></div>

                <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-200">
                    <button type="button" onclick="closeGoalModal()" class="px-4 py-2 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                        Cancel
                    </button>
                    <button type="submit" id="saveGoalBtn" class="px-4 py-2 bg-slate-900 text-white rounded-lg text-xs font-semibold hover:bg-slate-800 transition">
                        Save Goal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Contribute Modal -->
    <div id="contributeModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 hidden">
        <div class="bg-white rounded-xl max-w-md w-full border border-slate-200 shadow-xl overflow-hidden">
            <div class="p-5 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Deposit into Goal</h3>
                    <p id="contributeGoalName" class="text-xs text-slate-500 font-semibold"></p>
                </div>
                <button type="button" onclick="closeContributeModal()" class="text-slate-400 hover:text-slate-600 p-1">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <form id="contributeForm" onsubmit="submitContributeForm(event)" class="p-5 space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo $auth->getCSRFToken(); ?>">
                <input type="hidden" name="action" value="contribute">
                <input type="hidden" id="contributeGoalId" name="id" value="">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Source Account (Funding)</label>
                    <select id="contributeAccountId" name="account_id" required class="w-full px-3 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900">
                        <option value="">Select funding account</option>
                        <?php foreach ($funding_accounts as $acc): ?>
                            <option value="<?php echo $acc['id']; ?>">
                                <?php echo htmlspecialchars($acc['name']); ?> (Available: ₹<?php echo number_format($acc['current_balance'], 2); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Contribution Amount (₹)</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 font-bold">
                            ₹
                        </div>
                        <input type="number" step="0.01" min="0.01" id="contributeAmount" name="amount" required placeholder="0.00" class="w-full pl-8 pr-3 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Memo / Note (Optional)</label>
                    <input type="text" id="contributeNote" name="note" placeholder="e.g. November salary savings allocation" class="w-full px-3 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900">
                </div>

                <div id="contributeModalAlert" class="hidden p-3 rounded-lg text-xs font-semibold"></div>

                <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-200">
                    <button type="button" onclick="closeContributeModal()" class="px-4 py-2 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                        Cancel
                    </button>
                    <button type="submit" id="saveContributeBtn" class="px-4 py-2 bg-emerald-700 text-white rounded-lg text-xs font-semibold hover:bg-emerald-800 transition">
                        Confirm Deposit
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirm Delete Modal -->
    <div id="deleteGoalModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 hidden">
        <div class="bg-white rounded-xl max-w-sm w-full border border-slate-200 shadow-xl overflow-hidden p-5">
            <h3 class="text-base font-bold text-slate-900">Remove Savings Goal</h3>
            <p class="text-xs text-slate-600 mt-2">Are you sure you want to delete <span id="deleteGoalTitle" class="font-bold text-slate-900"></span>? Your transactions and ledger balance will remain unchanged.</p>
            <div class="flex items-center justify-end space-x-3 mt-5">
                <button type="button" onclick="closeDeleteGoalModal()" class="px-3.5 py-1.5 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="button" id="confirmDeleteGoalBtn" class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-semibold">Delete</button>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        const goalModal = document.getElementById('goalModal');
        const goalAlert = document.getElementById('goalModalAlert');
        const contributeModal = document.getElementById('contributeModal');
        const contributeAlert = document.getElementById('contributeModalAlert');
        let currentGoalDeleteId = null;

        function openGoalModal() {
            document.getElementById('goalModalTitle').textContent = 'Create Savings Goal';
            document.getElementById('goalForm').reset();
            document.getElementById('goalAction').value = 'add_goal';
            document.getElementById('goalId').value = '';
            goalAlert.classList.add('hidden');
            goalModal.classList.remove('hidden');
        }

        function closeGoalModal() {
            goalModal.classList.add('hidden');
        }

        function editGoal(goal) {
            document.getElementById('goalModalTitle').textContent = 'Edit ' + goal.title;
            document.getElementById('goalAction').value = 'update_goal';
            document.getElementById('goalId').value = goal.id;
            document.getElementById('goalTitle').value = goal.title;
            document.getElementById('goalTargetAmount').value = goal.target_amount;
            document.getElementById('goalCurrentAmount').value = goal.current_amount;
            document.getElementById('goalTargetDate').value = goal.target_date || '';
            goalAlert.classList.add('hidden');
            goalModal.classList.remove('hidden');
        }

        async function submitGoalForm(e) {
            e.preventDefault();
            const btn = document.getElementById('saveGoalBtn');
            btn.disabled = true;
            btn.textContent = 'Saving...';
            goalAlert.classList.add('hidden');

            const formData = new FormData(document.getElementById('goalForm'));
            try {
                const res = await fetch('../ajax/goals.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    goalAlert.className = 'p-3 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200 block flex items-center space-x-2';
                    goalAlert.innerHTML = '<svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><span>Successfully Saved Goal! Updating...</span>';
                    btn.textContent = 'Successfully Saved!';
                    btn.className = 'px-4 py-2 bg-emerald-600 text-white rounded-lg text-xs font-semibold';
                    sessionStorage.setItem('flash_toast', JSON.stringify({ 
                        title: 'Successfully Saved', 
                        message: data.message || 'Savings goal saved successfully.', 
                        type: 'success' 
                    }));
                    setTimeout(() => window.location.reload(), 450);
                } else {
                    goalAlert.className = 'p-3 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 block';
                    goalAlert.textContent = data.message || 'Failed to save savings goal.';
                    btn.disabled = false;
                    btn.textContent = 'Save Goal';
                }
            } catch (err) {
                goalAlert.className = 'p-3 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 block';
                goalAlert.textContent = 'Network or server error occurred.';
                btn.disabled = false;
                btn.textContent = 'Save Goal';
            }
        }

        function openContributeModal(id, title, remaining) {
            document.getElementById('contributeGoalId').value = id;
            document.getElementById('contributeGoalName').textContent = title + ' (Remaining: ₹' + remaining.toFixed(2) + ')';
            document.getElementById('contributeForm').reset();
            document.getElementById('contributeGoalId').value = id;
            contributeAlert.classList.add('hidden');
            contributeModal.classList.remove('hidden');
        }

        function closeContributeModal() {
            contributeModal.classList.add('hidden');
        }

        async function submitContributeForm(e) {
            e.preventDefault();
            const btn = document.getElementById('saveContributeBtn');
            btn.disabled = true;
            btn.textContent = 'Depositing...';
            contributeAlert.classList.add('hidden');

            const formData = new FormData(document.getElementById('contributeForm'));
            try {
                const res = await fetch('../ajax/goals.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    contributeAlert.className = 'p-3 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200 block flex items-center space-x-2';
                    contributeAlert.innerHTML = '<svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><span>Successfully Contributed! Updating...</span>';
                    btn.textContent = 'Successfully Contributed!';
                    btn.className = 'px-4 py-2 bg-emerald-600 text-white rounded-lg text-xs font-semibold';
                    sessionStorage.setItem('flash_toast', JSON.stringify({ 
                        title: 'Successfully Contributed', 
                        message: data.message || 'Funds allocated to savings goal.', 
                        type: 'success' 
                    }));
                    setTimeout(() => window.location.reload(), 450);
                } else {
                    contributeAlert.className = 'p-3 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 block';
                    contributeAlert.textContent = data.message || 'Contribution failed.';
                    btn.disabled = false;
                    btn.textContent = 'Confirm Deposit';
                }
            } catch (err) {
                contributeAlert.className = 'p-3 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 block';
                contributeAlert.textContent = 'Network or server error.';
                btn.disabled = false;
                btn.textContent = 'Confirm Deposit';
            }
        }

        function deleteGoal(id, title) {
            currentGoalDeleteId = id;
            document.getElementById('deleteGoalTitle').textContent = title;
            document.getElementById('deleteGoalModal').classList.remove('hidden');
        }

        function closeDeleteGoalModal() {
            document.getElementById('deleteGoalModal').classList.add('hidden');
            currentGoalDeleteId = null;
        }

        document.getElementById('confirmDeleteGoalBtn').addEventListener('click', async function() {
            if (!currentGoalDeleteId) return;
            const btn = this;
            btn.disabled = true;
            btn.textContent = 'Removing...';

            const formData = new FormData();
            formData.append('csrf_token', '<?php echo $auth->getCSRFToken(); ?>');
            formData.append('action', 'delete_goal');
            formData.append('id', currentGoalDeleteId);

            try {
                const res = await fetch('../ajax/goals.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    btn.textContent = 'Successfully Removed!';
                    btn.className = 'px-3.5 py-1.5 bg-emerald-600 text-white rounded-lg text-xs font-semibold';
                    sessionStorage.setItem('flash_toast', JSON.stringify({ 
                        title: 'Successfully Removed', 
                        message: data.message || 'Savings goal removed.', 
                        type: 'success' 
                    }));
                    setTimeout(() => window.location.reload(), 450);
                } else {
                    if (window.showToast) window.showToast('Action Failed', data.message || 'Failed to delete goal', 'error');
                    else alert(data.message || 'Failed to delete goal');
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
