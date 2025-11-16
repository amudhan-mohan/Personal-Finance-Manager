<?php
require_once __DIR__ . '/auth.php';
$auth = new Auth();
?>
<nav class="bg-white shadow-lg relative z-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between items-center h-16">
            <!-- Logo -->
            <div class="flex items-center">
                <a href="dashboard.php" class="text-xl font-bold text-gray-800"><?php echo SITE_NAME; ?></a>
            </div>

            <?php if ($auth->isLoggedIn()): ?>
                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium transition duration-200">Dashboard</a>
                    <a href="accounts.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium transition duration-200">Accounts</a>
                    <a href="transactions.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium transition duration-200">Transactions</a>
                    <a href="transfers.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium transition duration-200">Transfers</a>
                    <a href="reports.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium transition duration-200">Reports</a>
                    <a href="profile.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium transition duration-200">Profile</a>
                    <span class="text-gray-600 text-sm hidden lg:inline">Welcome, <?php echo $_SESSION['username']; ?></span>
                    <a href="../logout.php" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 text-sm transition duration-200">Logout</a>
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button type="button" id="mobile-menu-button" class="text-gray-600 hover:text-gray-900 focus:outline-none focus:text-gray-900 transition duration-200">
                        <svg class="h-6 w-6 transform transition duration-200" id="menu-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                        <svg class="h-6 w-6 transform transition duration-200 hidden" id="close-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Mobile Menu Overlay -->
        <?php if ($auth->isLoggedIn()): ?>
            <!-- Backdrop -->
            <div id="mobile-backdrop" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden transition-opacity duration-300 ease-in-out opacity-0"></div>

            <!-- Mobile Menu Panel -->
            <div id="mobile-menu" class="fixed top-0 right-0 h-full w-64 bg-white shadow-xl z-50 transform transition-transform duration-300 ease-in-out translate-x-full">
                <div class="flex flex-col h-full">
                    <!-- Menu Header -->
                    <div class="flex items-center justify-between p-4 border-b border-gray-200">
                        <span class="text-lg font-semibold text-gray-800">Menu</span>
                        <button id="close-mobile-menu" class="text-gray-500 hover:text-gray-700 transition duration-200">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Menu Items -->
                    <div class="flex-1 overflow-y-auto">
                        <div class="px-4 py-6 space-y-2">
                            <a href="dashboard.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition duration-200 group">
                                <svg class="h-5 w-5 mr-3 text-gray-400 group-hover:text-blue-500 transition duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg>
                                Dashboard
                            </a>

                            <a href="accounts.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition duration-200 group">
                                <svg class="h-5 w-5 mr-3 text-gray-400 group-hover:text-blue-500 transition duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                Accounts
                            </a>

                            <a href="transactions.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition duration-200 group">
                                <svg class="h-5 w-5 mr-3 text-gray-400 group-hover:text-blue-500 transition duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                                Transactions
                            </a>

                            <a href="transfers.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition duration-200 group">
                                <svg class="h-5 w-5 mr-3 text-gray-400 group-hover:text-blue-500 transition duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                </svg>
                                Transfers
                            </a>

                            <a href="reports.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition duration-200 group">
                                <svg class="h-5 w-5 mr-3 text-gray-400 group-hover:text-blue-500 transition duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                Reports
                            </a>
                            <!-- Profile Link -->
                            <a href="profile.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition duration-200 group">
                                <svg class="h-5 w-5 mr-3 text-gray-400 group-hover:text-blue-500 transition duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                Profile
                            </a>
                        </div>
                    </div>

                    <!-- User Info & Logout -->
                    <div class="border-t border-gray-200 p-4 bg-gray-50">
                        <div class="mb-3">
                            <p class="text-sm text-gray-600">Welcome</p>
                            <p class="text-sm font-medium text-gray-900"><?php echo $_SESSION['username']; ?></p>
                        </div>
                        <a href="../logout.php" class="w-full bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700 text-sm transition duration-200 flex items-center justify-center">
                            <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</nav>

<script>
    // Mobile menu functionality
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const closeMobileMenu = document.getElementById('close-mobile-menu');
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileBackdrop = document.getElementById('mobile-backdrop');
    const menuIcon = document.getElementById('menu-icon');
    const closeIcon = document.getElementById('close-icon');

    function openMobileMenu() {
        mobileBackdrop.classList.remove('hidden');
        mobileMenu.classList.remove('translate-x-full');
        mobileBackdrop.classList.remove('opacity-0');
        mobileBackdrop.classList.add('opacity-100');

        // Switch icons
        menuIcon.classList.add('hidden');
        closeIcon.classList.remove('hidden');

        // Prevent body scroll
        document.body.style.overflow = 'hidden';
    }

    function closeMobileMenuFunc() {
        mobileMenu.classList.add('translate-x-full');
        mobileBackdrop.classList.remove('opacity-100');
        mobileBackdrop.classList.add('opacity-0');

        // Switch icons
        closeIcon.classList.add('hidden');
        menuIcon.classList.remove('hidden');

        // Allow body scroll
        document.body.style.overflow = '';

        // Hide backdrop after transition
        setTimeout(() => {
            mobileBackdrop.classList.add('hidden');
        }, 300);
    }

    // Event listeners
    mobileMenuButton.addEventListener('click', openMobileMenu);
    closeMobileMenu.addEventListener('click', closeMobileMenuFunc);
    mobileBackdrop.addEventListener('click', closeMobileMenuFunc);

    // Close menu when clicking on links
    const mobileLinks = document.querySelectorAll('#mobile-menu a');
    mobileLinks.forEach(link => {
        link.addEventListener('click', closeMobileMenuFunc);
    });

    // Close menu with Escape key
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !mobileMenu.classList.contains('translate-x-full')) {
            closeMobileMenuFunc();
        }
    });

    // Prevent scrolling when menu is open
    mobileMenu.addEventListener('touchmove', (e) => {
        e.preventDefault();
    }, {
        passive: false
    });
</script>

<style>
    /* Smooth transitions for better mobile experience */
    #mobile-menu {
        box-shadow: -4px 0 15px rgba(0, 0, 0, 0.1);
    }

    /* Custom scrollbar for mobile menu */
    #mobile-menu::-webkit-scrollbar {
        width: 4px;
    }

    #mobile-menu::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    #mobile-menu::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 2px;
    }

    #mobile-menu::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
</style>