<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
send_security_headers();
$auth = new Auth();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<header class="bg-white border-b border-slate-200 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Brand Logo -->
            <div class="flex items-center space-x-3">
                <a href="dashboard.php" class="flex items-center space-x-2.5 group">
                    <img src="../assets/img/logo.svg" alt="<?php echo SITE_NAME; ?>" class="w-9 h-9 object-contain transition group-hover:scale-105">
                    <div class="flex flex-col">
                        <span class="text-base font-bold text-slate-900 tracking-tight leading-tight"><?php echo SITE_NAME; ?></span>
                        <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Fintech Platform</span>
                    </div>
                </a>
            </div>

            <?php if ($auth->isLoggedIn()): 
                $more_pages = ['budgets.php', 'bills.php', 'goals.php', 'forecast.php', 'import.php'];
                $is_more_active = in_array($current_page, $more_pages, true);
            ?>
                <!-- Desktop Navigation: [Dashboard, Accounts, Transactions, Transfers, Reports, More] -->
                <nav class="hidden md:flex items-center space-x-1">
                    <a href="dashboard.php" class="flex items-center space-x-1.5 px-3 py-2 rounded-lg text-xs font-medium transition <?php echo $current_page === 'dashboard.php' ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'; ?>">
                        <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="9" rx="1"/>
                            <rect x="14" y="3" width="7" height="5" rx="1"/>
                            <rect x="14" y="12" width="7" height="9" rx="1"/>
                            <rect x="3" y="16" width="7" height="5" rx="1"/>
                        </svg>
                        <span>Dashboard</span>
                    </a>
                    <a href="accounts.php" class="flex items-center space-x-1.5 px-3 py-2 rounded-lg text-xs font-medium transition <?php echo $current_page === 'accounts.php' ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'; ?>">
                        <?php echo account_icon('h-4 w-4 text-slate-500'); ?>
                        <span>Accounts</span>
                    </a>
                    <a href="transactions.php" class="flex items-center space-x-1.5 px-3 py-2 rounded-lg text-xs font-medium transition <?php echo $current_page === 'transactions.php' ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'; ?>">
                        <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 3h12M6 8h12M6 13l8.5 8M6 13h3a4 4 0 0 0 0-8"/>
                        </svg>
                        <span>Transactions</span>
                    </a>
                    <a href="transfers.php" class="flex items-center space-x-1.5 px-3 py-2 rounded-lg text-xs font-medium transition <?php echo $current_page === 'transfers.php' ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'; ?>">
                        <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 2l4 4-4 4"/>
                            <path d="M3 6h18"/>
                            <path d="M7 22l-4-4 4-4"/>
                            <path d="M21 18H3"/>
                        </svg>
                        <span>Transfers</span>
                    </a>
                    <a href="reports.php" class="flex items-center space-x-1.5 px-3 py-2 rounded-lg text-xs font-medium transition <?php echo $current_page === 'reports.php' ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'; ?>">
                        <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <line x1="18" y1="20" x2="18" y2="10"/>
                            <line x1="12" y1="20" x2="12" y2="4"/>
                            <line x1="6" y1="20" x2="6" y2="14"/>
                        </svg>
                        <span>Reports</span>
                    </a>

                    <!-- More Dropdown -->
                    <div class="relative" id="navMoreDropdownContainer">
                        <button type="button" id="navMoreBtn" 
                            class="flex items-center space-x-1.5 px-3 py-2 rounded-lg text-xs font-medium transition cursor-pointer <?php echo $is_more_active ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'; ?>"
                            aria-expanded="false" aria-haspopup="true">
                            <span>More</span>
                            <?php if ($is_more_active): ?>
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-900"></span>
                            <?php endif; ?>
                            <svg class="h-3.5 w-3.5 text-slate-400 transition-transform duration-200" id="navMoreChevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>

                        <div id="navMoreMenu" class="absolute left-0 mt-2 w-72 bg-white rounded-xl border border-slate-200 shadow-xl py-2 z-50 hidden opacity-0 translate-y-1 transition-all duration-150">
                            <!-- Budgets -->
                            <a href="budgets.php" class="flex items-center space-x-3 px-3.5 py-2.5 text-xs transition <?php echo $current_page === 'budgets.php' ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-700 hover:bg-slate-50 hover:text-slate-900'; ?>">
                                <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center shrink-0 border border-amber-100">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path d="M21.21 15.89A10 10 0 1 1 8 2.83"/>
                                        <path d="M22 12A10 10 0 0 0 12 2v10z"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-900">Budgets</p>
                                    <p class="text-[11px] text-slate-500 truncate">Category spending limits & pacing</p>
                                </div>
                            </a>

                            <!-- Bills & Subscriptions -->
                            <a href="bills.php" class="flex items-center space-x-3 px-3.5 py-2.5 text-xs transition <?php echo $current_page === 'bills.php' ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-700 hover:bg-slate-50 hover:text-slate-900'; ?>">
                                <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-700 flex items-center justify-center shrink-0 border border-purple-100">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                        <line x1="16" y1="2" x2="16" y2="6"/>
                                        <line x1="8" y1="2" x2="8" y2="6"/>
                                        <line x1="3" y1="10" x2="21" y2="10"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-900">Bills & Subscriptions</p>
                                    <p class="text-[11px] text-slate-500 truncate">Upcoming dues & lifecycle payments</p>
                                </div>
                            </a>

                            <!-- Savings Goals -->
                            <a href="goals.php" class="flex items-center space-x-3 px-3.5 py-2.5 text-xs transition <?php echo $current_page === 'goals.php' ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-700 hover:bg-slate-50 hover:text-slate-900'; ?>">
                                <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0 border border-emerald-100">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                        <circle cx="12" cy="12" r="6"/>
                                        <circle cx="12" cy="12" r="2"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-900">Savings Goals</p>
                                    <p class="text-[11px] text-slate-500 truncate">Milestone targets & sinking funds</p>
                                </div>
                            </a>

                            <!-- Cash Flow -->
                            <a href="forecast.php" class="flex items-center space-x-3 px-3.5 py-2.5 text-xs transition <?php echo $current_page === 'forecast.php' ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-700 hover:bg-slate-50 hover:text-slate-900'; ?>">
                                <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-700 flex items-center justify-center shrink-0 border border-indigo-100">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/>
                                        <polyline points="16 7 22 7 22 13"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-900">Cash Flow Engine</p>
                                    <p class="text-[11px] text-slate-500 truncate">30/60/90-day forward trajectory</p>
                                </div>
                            </a>

                            <div class="border-t border-slate-100 my-1"></div>

                            <!-- Import Statement CSV -->
                            <a href="import.php" class="flex items-center space-x-3 px-3.5 py-2.5 text-xs transition <?php echo $current_page === 'import.php' ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-700 hover:bg-slate-50 hover:text-slate-900'; ?>">
                                <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center shrink-0 border border-blue-100">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                        <polyline points="7 10 12 15 17 10"/>
                                        <line x1="12" y1="15" x2="12" y2="3"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-900">Import Statement</p>
                                    <p class="text-[11px] text-slate-500 truncate">Bank CSV parser & duplicate audit</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </nav>

                <!-- User Badge & Actions (Desktop & Tablet) -->
                <div class="hidden md:flex items-center space-x-2.5 pl-3 border-l border-slate-200">
                    <a href="profile.php" title="My Profile" class="flex items-center space-x-2 px-2.5 py-1.5 bg-slate-100 rounded-lg hover:bg-slate-200 transition">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span class="text-xs font-semibold text-slate-800"><?php echo htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                    </a>
                    <a href="../logout.php" class="inline-flex items-center px-2.5 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-100 hover:text-slate-900 transition">
                        <svg class="h-3.5 w-3.5 mr-1 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                        Sign Out
                    </a>
                </div>

                <!-- Mobile Drawer Menu Button (Visible below md) -->
                <div class="md:hidden flex items-center">
                    <button type="button" id="mobile-menu-button" class="p-2 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 focus:outline-none transition" aria-label="Toggle Navigation">
                        <svg class="h-6 w-6" id="menu-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="3" y1="12" x2="21" y2="12"/>
                            <line x1="3" y1="6" x2="21" y2="6"/>
                            <line x1="3" y1="18" x2="21" y2="18"/>
                        </svg>
                        <svg class="h-6 w-6 hidden" id="close-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Mobile & Tablet Menu Panel -->
        <?php if ($auth->isLoggedIn()): ?>
            <div id="mobile-backdrop" class="fixed inset-0 bg-slate-900/40 z-40 hidden transition-opacity duration-200 opacity-0"></div>

            <div id="mobile-menu" class="fixed top-0 right-0 h-full w-72 max-w-[85vw] bg-white border-l border-slate-200 shadow-xl z-50 transform transition-transform duration-200 ease-in-out translate-x-full">
                <div class="flex flex-col h-full">
                    <!-- Drawer Header -->
                    <div class="flex items-center justify-between p-4 border-b border-slate-200 bg-slate-50">
                        <div class="flex items-center space-x-2">
                            <img src="../assets/img/logo.svg" alt="<?php echo SITE_NAME; ?>" class="w-7 h-7 object-contain">
                            <span class="text-sm font-bold text-slate-900">Navigation</span>
                        </div>
                        <button id="close-mobile-menu" class="p-1.5 text-slate-500 hover:text-slate-800 rounded-md hover:bg-slate-200 transition" aria-label="Close Navigation">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"/>
                                <line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Drawer Links -->
                    <div class="flex-1 overflow-y-auto p-4 space-y-1">
                        <a href="dashboard.php" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition <?php echo $current_page === 'dashboard.php' ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'; ?>">
                            <svg class="h-5 w-5 mr-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <rect x="3" y="3" width="7" height="9" rx="1"/>
                                <rect x="14" y="3" width="7" height="5" rx="1"/>
                                <rect x="14" y="12" width="7" height="9" rx="1"/>
                                <rect x="3" y="16" width="7" height="5" rx="1"/>
                            </svg>
                            Dashboard
                        </a>
                        <a href="accounts.php" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition <?php echo $current_page === 'accounts.php' ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'; ?>">
                            <?php echo account_icon('h-5 w-5 mr-3 text-slate-500'); ?>
                            Accounts
                        </a>
                        <a href="transactions.php" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition <?php echo $current_page === 'transactions.php' ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'; ?>">
                            <svg class="h-5 w-5 mr-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 3h12M6 8h12M6 13l8.5 8M6 13h3a4 4 0 0 0 0-8"/>
                            </svg>
                            Transactions
                        </a>
                        <a href="transfers.php" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition <?php echo $current_page === 'transfers.php' ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'; ?>">
                            <svg class="h-5 w-5 mr-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path d="M17 2l4 4-4 4"/>
                                <path d="M3 6h18"/>
                                <path d="M7 22l-4-4 4-4"/>
                                <path d="M21 18H3"/>
                            </svg>
                            Transfers
                        </a>
                        <a href="budgets.php" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition <?php echo $current_page === 'budgets.php' ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'; ?>">
                            <svg class="h-5 w-5 mr-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21.21 15.89A10 10 0 1 1 8 2.83"/>
                                <path d="M22 12A10 10 0 0 0 12 2v10z"/>
                            </svg>
                            Budgets
                        </a>
                        <a href="bills.php" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition <?php echo $current_page === 'bills.php' ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'; ?>">
                            <svg class="h-5 w-5 mr-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                                <circle cx="8" cy="14" r="1"/>
                                <circle cx="12" cy="14" r="1"/>
                                <circle cx="16" cy="14" r="1"/>
                            </svg>
                            Bills & Subscriptions
                        </a>
                        <a href="goals.php" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition <?php echo $current_page === 'goals.php' ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'; ?>">
                            <svg class="h-5 w-5 mr-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <circle cx="12" cy="12" r="6"/>
                                <circle cx="12" cy="12" r="2"/>
                            </svg>
                            Savings Goals
                        </a>
                        <a href="forecast.php" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition <?php echo $current_page === 'forecast.php' ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'; ?>">
                            <svg class="h-5 w-5 mr-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/>
                                <polyline points="16 7 22 7 22 13"/>
                            </svg>
                            Predictive Cash Flow
                        </a>
                        <a href="import.php" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition <?php echo $current_page === 'import.php' ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'; ?>">
                            <svg class="h-5 w-5 mr-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="7 10 12 15 17 10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            Import Statement
                        </a>
                        <a href="reports.php" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition <?php echo $current_page === 'reports.php' ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'; ?>">
                            <svg class="h-5 w-5 mr-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <line x1="18" y1="20" x2="18" y2="10"/>
                                <line x1="12" y1="20" x2="12" y2="4"/>
                                <line x1="6" y1="20" x2="6" y2="14"/>
                            </svg>
                            Reports
                        </a>
                        <a href="profile.php" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition <?php echo $current_page === 'profile.php' ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'; ?>">
                            <svg class="h-5 w-5 mr-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                            Profile
                        </a>
                    </div>

                    <!-- Drawer Footer -->
                    <div class="border-t border-slate-200 p-4 bg-slate-50">
                        <div class="flex items-center space-x-2.5 mb-3">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <span class="text-xs font-semibold text-slate-800"><?php echo htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <a href="../logout.php" class="w-full flex items-center justify-center py-2.5 px-4 bg-white border border-slate-300 text-slate-700 hover:bg-slate-100 hover:text-slate-900 rounded-lg text-xs font-semibold transition">
                            <svg class="h-4 w-4 mr-2 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <polyline points="16 17 21 12 16 7"/>
                                <line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
                            Sign Out
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</header>

<script>
    (function() {
        const btn = document.getElementById('mobile-menu-button');
        const closeBtn = document.getElementById('close-mobile-menu');
        const menu = document.getElementById('mobile-menu');
        const backdrop = document.getElementById('mobile-backdrop');
        const menuIcon = document.getElementById('menu-icon');
        const closeIcon = document.getElementById('close-icon');

        if (!btn || !menu || !backdrop) return;

        function openMenu() {
            backdrop.classList.remove('hidden');
            requestAnimationFrame(() => {
                backdrop.classList.remove('opacity-0');
                backdrop.classList.add('opacity-100');
                menu.classList.remove('translate-x-full');
            });
            if (menuIcon && closeIcon) {
                menuIcon.classList.add('hidden');
                closeIcon.classList.remove('hidden');
            }
            document.body.style.overflow = 'hidden';
        }

        function closeMenu() {
            menu.classList.add('translate-x-full');
            backdrop.classList.remove('opacity-100');
            backdrop.classList.add('opacity-0');
            setTimeout(() => {
                backdrop.classList.add('hidden');
            }, 200);
            if (menuIcon && closeIcon) {
                menuIcon.classList.remove('hidden');
                closeIcon.classList.add('hidden');
            }
            document.body.style.overflow = '';
        }

        btn.addEventListener('click', openMenu);
        if (closeBtn) closeBtn.addEventListener('click', closeMenu);
        backdrop.addEventListener('click', closeMenu);
    })();

    // Desktop "More" Dropdown Controller
    (function() {
        const moreBtn = document.getElementById('navMoreBtn');
        const moreMenu = document.getElementById('navMoreMenu');
        const moreChevron = document.getElementById('navMoreChevron');

        if (!moreBtn || !moreMenu) return;

        function openMore() {
            moreMenu.classList.remove('hidden');
            requestAnimationFrame(() => {
                moreMenu.classList.remove('opacity-0', 'translate-y-1');
                moreMenu.classList.add('opacity-100', 'translate-y-0');
                if (moreChevron) moreChevron.classList.add('rotate-180');
                moreBtn.setAttribute('aria-expanded', 'true');
            });
        }

        function closeMore() {
            moreMenu.classList.remove('opacity-100', 'translate-y-0');
            moreMenu.classList.add('opacity-0', 'translate-y-1');
            if (moreChevron) moreChevron.classList.remove('rotate-180');
            moreBtn.setAttribute('aria-expanded', 'false');
            setTimeout(() => {
                if (moreBtn.getAttribute('aria-expanded') === 'false') {
                    moreMenu.classList.add('hidden');
                }
            }, 150);
        }

        moreBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const isOpen = moreBtn.getAttribute('aria-expanded') === 'true';
            if (isOpen) {
                closeMore();
            } else {
                openMore();
            }
        });

        document.addEventListener('click', function(e) {
            if (!moreMenu.contains(e.target) && !moreBtn.contains(e.target)) {
                closeMore();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeMore();
            }
        });
    })();
</script>
