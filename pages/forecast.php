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

$horizon = isset($_GET['days']) ? intval($_GET['days']) : 60;
if (!in_array($horizon, [30, 60, 90], true)) {
    $horizon = 60;
}

$forecast = $financeManager->generateCashFlowForecast($user_id, $horizon);

$page_title = "Predictive Cash Flow Engine";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="icon" href="../assets/img/logo.svg" data-next-head=""/>
    <link rel="stylesheet" href="../assets/css/tailwind.css?v=<?php echo asset_version(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex flex-col font-sans antialiased">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-6 border-b border-slate-200 gap-4">
            <div>
                <div class="flex items-center space-x-2 text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">
                    <span>Forward Intelligence</span>
                    <span>/</span>
                    <span class="text-slate-800">Liquidity Modeling</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Predictive Cash Flow Engine</h1>
                <p class="text-sm text-slate-600 mt-1">Multi-horizon simulation combining historical daily run-rate with scheduled recurring subscription obligations.</p>
            </div>
            <!-- Horizon Switcher Pills -->
            <div class="inline-flex p-1 bg-white border border-slate-200 rounded-xl shadow-xs self-start sm:self-auto">
                <a href="?days=30" class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition <?php echo $horizon === 30 ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'; ?>">
                    30-Day Outlook
                </a>
                <a href="?days=60" class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition <?php echo $horizon === 60 ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'; ?>">
                    60-Day Forecast
                </a>
                <a href="?days=90" class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition <?php echo $horizon === 90 ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'; ?>">
                    90-Day Strategy
                </a>
            </div>
        </div>

        <!-- Liquidity Radar Threshold Banner -->
        <div class="mt-6">
            <?php if ($forecast['critical_drop_date']): ?>
                <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl flex items-start space-x-3 text-rose-900">
                    <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-rose-900">Liquidity Alert: Capital Reserve Trough Detected</h3>
                        <p class="text-xs text-rose-700 mt-0.5">
                            Cash reserves are projected to breach safety buffers around <strong><?php echo date('F j, Y', strtotime($forecast['critical_drop_date'])); ?></strong>, dipping to <strong>₹<?php echo number_format($forecast['min_balance'], 2); ?></strong> on <?php echo date('M j', strtotime($forecast['min_balance_date'])); ?>. Consider deferring non-essential outflows or buffering accounts in advance.
                        </p>
                    </div>
                </div>
            <?php else: ?>
                <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-start space-x-3 text-emerald-900">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-emerald-900">Solvent Liquidity Runway</h3>
                        <p class="text-xs text-emerald-700 mt-0.5">
                            Cash reserves are projected to remain positive and solvent across the full <?php echo $horizon; ?>-day horizon, with a projected valley low of <strong>₹<?php echo number_format($forecast['min_balance'], 2); ?></strong> on <?php echo date('M j, Y', strtotime($forecast['min_balance_date'])); ?>.
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- KPI Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mt-6">
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider">Current Liquid Base</span>
                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <rect x="2" y="6" width="20" height="12" rx="2"/>
                            <circle cx="12" cy="12" r="2"/>
                            <path d="M6 12h.01M18 12h.01"/>
                        </svg>
                    </div>
                </div>
                <div class="text-2xl font-bold text-slate-900">
                    ₹<?php echo number_format($forecast['starting_balance'], 2); ?>
                </div>
                <div class="text-xs text-slate-500 mt-1">Bank + Cash accounts today</div>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider"><?php echo $horizon; ?>-Day Projected End</span>
                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center <?php echo $forecast['ending_balance'] >= $forecast['starting_balance'] ? 'text-emerald-600' : 'text-slate-700'; ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/>
                            <polyline points="16 7 22 7 22 13"/>
                        </svg>
                    </div>
                </div>
                <div class="text-2xl font-bold <?php echo $forecast['ending_balance'] >= 0 ? 'text-slate-900' : 'text-rose-700'; ?>">
                    ₹<?php echo number_format($forecast['ending_balance'], 2); ?>
                </div>
                <div class="text-xs text-slate-500 mt-1">
                    Net Delta: <?php 
                        $delta = $forecast['ending_balance'] - $forecast['starting_balance'];
                        echo ($delta >= 0 ? '+₹' : '-₹') . number_format(abs($delta), 2);
                    ?>
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider">Projected Valley Low</span>
                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-amber-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <polyline points="22 17 13.5 8.5 8.5 13.5 2 7"/>
                        </svg>
                    </div>
                </div>
                <div class="text-2xl font-bold <?php echo $forecast['min_balance'] >= 0 ? 'text-slate-900' : 'text-rose-700'; ?>">
                    ₹<?php echo number_format($forecast['min_balance'], 2); ?>
                </div>
                <div class="text-xs text-slate-500 mt-1">
                    Estimated on <?php echo date('M j, Y', strtotime($forecast['min_balance_date'])); ?>
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider">Scheduled Fixed Bills</span>
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
                    ₹<?php echo number_format($forecast['total_bills_projected'], 2); ?>
                </div>
                <div class="text-xs text-slate-500 mt-1">Total recurring obligations in window</div>
            </div>
        </div>

        <!-- Chart.js Projection Visualization -->
        <div class="mt-8 bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-slate-100 gap-2 mb-4">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Projected Daily Liquidity Trajectory</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Dynamic curve accounting for daily net velocity (₹<?php echo number_format($forecast['daily_velocity'], 2); ?>/day) and scheduled bill events.</p>
                </div>
                <div class="flex items-center space-x-3 text-xs font-semibold">
                    <span class="flex items-center text-slate-700">
                        <span class="w-3 h-3 rounded-full bg-slate-900 mr-1.5"></span>
                        Projected Balance
                    </span>
                    <span class="flex items-center text-rose-600">
                        <span class="w-3 h-0.5 bg-rose-500 mr-1.5"></span>
                        Zero Line Buffer
                    </span>
                </div>
            </div>

            <div class="h-80 w-full">
                <canvas id="cashFlowForecastChart"></canvas>
            </div>
        </div>

        <!-- Day-by-Day Cash Flow Schedule Ledger -->
        <div class="mt-8 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-50">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Day-by-Day Forward Schedule</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Chronological forward ledger pinpointing upcoming recurring bills and projected daily balances.</p>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="text-xs font-semibold text-slate-500">
                        Showing <?php echo count($forecast['timeline']); ?> Days Forward
                    </span>
                </div>
            </div>

            <!-- Desktop Ledger Table with Fixed Height & Inner Scrolling -->
            <div class="hidden md:block overflow-x-auto max-h-[520px] overflow-y-auto border border-slate-200 rounded-xl inscrolling-table-container">
                <table class="w-full text-left border-collapse text-sm">
                    <thead class="sticky top-0 z-10 bg-slate-50 border-b border-slate-200">
                        <tr class="bg-slate-50 text-slate-600 text-xs uppercase font-semibold">
                            <th class="py-3 px-5 bg-slate-50">Day / Date</th>
                            <th class="py-3 px-5 bg-slate-50">Scheduled Events & Bills</th>
                            <th class="py-3 px-5 bg-slate-50">Discretionary Run-Rate</th>
                            <th class="py-3 px-5 bg-slate-50">Bill Deductions</th>
                            <th class="py-3 px-5 bg-slate-50">Projected Ending Balance</th>
                            <th class="py-3 px-5 text-right bg-slate-50">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php foreach ($forecast['timeline'] as $item): 
                            $has_bills = !empty($item['bills']);
                            $is_trough = ($item['date'] === $forecast['min_balance_date']);
                        ?>
                            <tr class="<?php echo $has_bills ? 'bg-amber-50/40 hover:bg-amber-50/70' : ($is_trough ? 'bg-blue-50/40 hover:bg-blue-50/70' : 'hover:bg-slate-50'); ?> transition">
                                <td class="py-3.5 px-5">
                                    <div class="font-bold text-slate-900 text-xs"><?php echo $item['day_label']; ?></div>
                                    <div class="text-[10px] text-slate-400 font-mono"><?php echo $item['day_num'] === 0 ? 'Today' : '+' . $item['day_num'] . ' days'; ?></div>
                                </td>
                                <td class="py-3.5 px-5">
                                    <?php if ($has_bills): ?>
                                        <div class="space-y-1">
                                            <?php foreach ($item['bills'] as $b): ?>
                                                <div class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-white border border-amber-300 text-slate-900 shadow-2xs mr-1.5">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span>
                                                    <span><?php echo htmlspecialchars($b['title']); ?>: -₹<?php echo number_format($b['amount'], 2); ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-5 text-xs font-semibold <?php echo $item['daily_velocity'] >= 0 ? 'text-emerald-700' : 'text-rose-700'; ?>">
                                    <?php echo ($item['daily_velocity'] >= 0 ? '+₹' : '-₹') . number_format(abs($item['daily_velocity']), 2); ?>
                                </td>
                                <td class="py-3.5 px-5 text-xs font-semibold text-rose-700">
                                    <?php echo $item['bills_total'] > 0 ? '-₹' . number_format($item['bills_total'], 2) : '&mdash;'; ?>
                                </td>
                                <td class="py-3.5 px-5 font-bold text-xs <?php echo $item['balance'] >= 0 ? 'text-slate-900' : 'text-rose-700'; ?>">
                                    ₹<?php echo number_format($item['balance'], 2); ?>
                                </td>
                                <td class="py-3.5 px-5 text-right">
                                    <?php if ($is_trough): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-blue-100 text-blue-800 border border-blue-200">
                                            Valley Low
                                        </span>
                                    <?php elseif ($item['balance'] < 2000): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-rose-100 text-rose-800 border border-rose-200">
                                            Low Cash
                                        </span>
                                    <?php elseif ($has_bills): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-200">
                                            Bill Event
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-400 text-xs font-medium">Normal</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View -->
            <div class="block md:hidden divide-y divide-slate-200 max-h-[520px] overflow-y-auto inscrolling-table-container">
                <?php foreach ($forecast['timeline'] as $item): 
                    $has_bills = !empty($item['bills']);
                    $is_trough = ($item['date'] === $forecast['min_balance_date']);
                ?>
                    <div class="p-4 space-y-2 <?php echo $has_bills ? 'bg-amber-50/30' : ($is_trough ? 'bg-blue-50/30' : ''); ?>">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-900 text-xs"><?php echo $item['day_label']; ?> (<?php echo $item['day_num'] === 0 ? 'Today' : '+' . $item['day_num'] . 'd'; ?>)</span>
                            <span class="font-bold text-xs <?php echo $item['balance'] >= 0 ? 'text-slate-900' : 'text-rose-700'; ?>">
                                ₹<?php echo number_format($item['balance'], 2); ?>
                            </span>
                        </div>
                        <?php if ($has_bills): ?>
                            <div class="space-y-1 pt-1">
                                <?php foreach ($item['bills'] as $b): ?>
                                    <div class="text-xs text-amber-800 flex justify-between">
                                        <span>&bull; <?php echo htmlspecialchars($b['title']); ?></span>
                                        <span class="font-bold">-₹<?php echo number_format($b['amount'], 2); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        const timelineData = <?php echo json_encode($forecast['timeline']); ?>;
        const labels = timelineData.map(d => d.day_label);
        const balances = timelineData.map(d => d.balance);

        const ctx = document.getElementById('cashFlowForecastChart').getContext('2d');
        const chartFont = { family: 'inherit', size: 11 };

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Projected Liquidity',
                    data: balances,
                    borderColor: '#0f172a',
                    backgroundColor: 'rgba(15, 23, 42, 0.04)',
                    fill: true,
                    tension: 0.25,
                    borderWidth: 2,
                    pointRadius: function(context) {
                        const index = context.dataIndex;
                        const item = timelineData[index];
                        if (item && item.bills && item.bills.length > 0) return 5;
                        if (index === 0 || index === timelineData.length - 1) return 4;
                        return 0;
                    },
                    pointBackgroundColor: function(context) {
                        const index = context.dataIndex;
                        const item = timelineData[index];
                        if (item && item.bills && item.bills.length > 0) return '#f59e0b';
                        return '#0f172a';
                    },
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 1.5,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const val = context.parsed.y;
                                const item = timelineData[context.dataIndex];
                                let txt = ' Projected: ₹' + val.toLocaleString('en-IN', { minimumFractionDigits: 2 });
                                if (item && item.bills && item.bills.length > 0) {
                                    const bNames = item.bills.map(b => b.title + ' (₹' + b.amount + ')').join(', ');
                                    txt += ' [Bills Due: ' + bNames + ']';
                                }
                                return txt;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: chartFont,
                            maxTicksLimit: 12
                        }
                    },
                    y: {
                        grid: { color: 'rgba(226, 232, 240, 0.7)' },
                        ticks: {
                            font: chartFont,
                            callback: function(val) {
                                return '₹' + val.toLocaleString('en-IN');
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
