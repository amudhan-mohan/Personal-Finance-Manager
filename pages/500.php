<?php

declare(strict_types=1);

http_response_code(500);

require_once __DIR__ . '/../config/constants.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = !empty($_SESSION['user_id']);

// Dynamically compute application web root for cross-environment compatibility (root domain or subfolder)
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$appRoot = rtrim(preg_replace('#/pages/[^/]+$#i', '', $scriptName), '/');
$homeUrl = $isLoggedIn ? $appRoot . '/pages/dashboard.php' : $appRoot . '/pages/login.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>500 Internal Server Error - <?php echo SITE_NAME; ?></title>
    <link rel="icon" href="<?php echo $appRoot; ?>/assets/img/logo.svg" data-next-head=""/>
    <link rel="stylesheet" href="<?php echo $appRoot; ?>/assets/css/tailwind.css?v=<?php echo asset_version(); ?>">
</head>

<body class="bg-slate-50 text-slate-900 min-h-screen flex flex-col font-sans antialiased selection:bg-slate-900 selection:text-white">

    <!-- Top Minimal Navigation -->
    <header class="bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="<?php echo $homeUrl; ?>" class="flex items-center space-x-3">
                <img src="<?php echo $appRoot; ?>/assets/img/logo.svg" alt="<?php echo SITE_NAME; ?>" class="w-8 h-8 object-contain">
                <span class="text-base font-bold text-slate-900 tracking-tight"><?php echo SITE_NAME; ?></span>
            </a>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-600 mr-1.5"></span>
                    HTTP 500
                </span>
            </div>
        </div>
    </header>

    <!-- Error Content Container -->
    <main class="flex-1 flex items-center justify-center p-4 sm:p-6 lg:p-8">
        <div class="max-w-xl w-full text-center">
            <div class="bg-white rounded-2xl border border-slate-200 p-8 sm:p-12 shadow-sm">
                
                <!-- Icon Badge -->
                <div class="w-16 h-16 mx-auto mb-6 rounded-2xl bg-amber-50 text-amber-700 flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </div>

                <!-- Status Pills -->
                <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-amber-50 text-amber-800 border border-amber-200 mb-4">
                    500 &bull; Internal System Error
                </div>

                <!-- Headline -->
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight mb-3">
                    Service Temporarily Interrupted
                </h1>

                <!-- Description -->
                <p class="text-sm text-slate-600 leading-relaxed mb-8 max-w-md mx-auto">
                    An unexpected exception occurred while processing this operation. Your financial records remain safeguarded under ACID rollback guarantees.
                </p>

                <!-- Action Button Matrix -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    <button type="button" onclick="window.location.reload()"
                        class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold uppercase tracking-wider rounded-lg transition shadow-sm">
                        Reload Page
                    </button>
                    <a href="<?php echo $homeUrl; ?>"
                        class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold uppercase tracking-wider rounded-lg transition">
                        <?php echo $isLoggedIn ? 'Return to Dashboard' : 'Go to Sign In'; ?> &rarr;
                    </a>
                </div>

            </div>
        </div>
    </main>

    <!-- Minimal Institutional Footer -->
    <footer class="bg-white border-t border-slate-200 py-4">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
            <span>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</span>
            <span>Developed by <strong class="text-slate-800">Amudhan Mohan</strong></span>
        </div>
    </footer>

</body>

</html>
