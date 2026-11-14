<?php
require_once __DIR__ . '/../includes/auth.php';
$auth = new Auth();

if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$csrf_token = $auth->generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Sign In - <?php echo SITE_NAME; ?></title>
    <link rel="icon" href="../assets/img/logo.svg" data-next-head=""/>
    <link rel="stylesheet" href="../assets/css/tailwind.css?v=<?php echo asset_version(); ?>">
</head>

<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4 sm:p-6 lg:p-8 font-sans antialiased">
    <div class="max-w-md w-full">
        <!-- Auth Card -->
        <div class="bg-white border border-slate-200 shadow-sm rounded-2xl p-8 sm:p-10">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="mx-auto w-16 h-16 flex items-center justify-center mb-4">
                    <img src="../assets/img/logo.svg" alt="<?php echo SITE_NAME; ?>" class="w-16 h-16 object-contain">
                </div>
                <p class="text-sm text-slate-500 mt-1.5">Welcome to</p>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                    <?php echo SITE_NAME; ?>
                </h1>
                <p class="text-sm text-slate-500 mt-1.5">Sign in to your financial management portal</p>
            </div>

            <!-- Login Form -->
            <form id="loginForm" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <!-- Email/Username Field -->
                <div>
                    <label for="login" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
                        Email or Username
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <input
                            type="text"
                            id="login"
                            name="login"
                            required
                            autocomplete="username"
                            placeholder="username or user@domain.com"
                            class="block w-full pl-10 pr-3.5 py-2.5 text-base md:text-sm border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900 transition bg-white text-slate-900 placeholder:text-slate-400">
                    </div>
                </div>

                <!-- Password Field -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                            Password
                        </label>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 11V7a5 5 0 0110 0v4"/>
                            </svg>
                        </div>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                            class="block w-full pl-10 pr-3.5 py-2.5 text-base md:text-sm border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900 transition bg-white text-slate-900 placeholder:text-slate-400">
                    </div>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    id="loginButton"
                    class="w-full flex justify-center items-center py-2.5 px-4 border border-transparent rounded-xl shadow-xs text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-900 transition duration-150">
                    <svg id="loginSpinner" class="hidden h-4 w-4 animate-spin mr-2 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span id="loginText">Sign In</span>
                </button>
            </form>

            <!-- Message Display -->
            <div id="message" class="mt-5 p-3.5 rounded-xl text-xs font-medium text-center hidden"></div>
        </div>

        <!-- Security & Compliance Footer -->
        <div class="mt-6 text-center text-xs text-slate-500">
            <p>Protected by enterprise session encryption & CSRF security</p>
            <p>Developed by <a href="http://www.amudhanmohan.in">Amudhan Mohan</a></p>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const loginButton = document.getElementById('loginButton');
            const loginText = document.getElementById('loginText');
            const loginSpinner = document.getElementById('loginSpinner');
            const messageDiv = document.getElementById('message');

            loginButton.disabled = true;
            loginText.textContent = 'Authenticating...';
            loginSpinner.classList.remove('hidden');
            messageDiv.classList.add('hidden');

            const formData = new FormData(this);

            fetch('../ajax/login.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        messageDiv.className = 'mt-5 p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl text-xs font-medium text-emerald-800 text-center';
                        messageDiv.textContent = 'Authenticated successfully! Redirecting...';
                        messageDiv.classList.remove('hidden');

                        setTimeout(() => {
                            window.location.href = 'dashboard.php';
                        }, 800);
                    } else {
                        messageDiv.className = 'mt-5 p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-xs font-medium text-rose-800 text-center';
                        messageDiv.textContent = data.message || 'Authentication failed. Please verify your credentials.';
                        messageDiv.classList.remove('hidden');
                    }
                })
                .catch(error => {
                    messageDiv.className = 'mt-5 p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-xs font-medium text-rose-800 text-center';
                    messageDiv.textContent = 'Connection error. Please check your network and try again.';
                    messageDiv.classList.remove('hidden');
                })
                .finally(() => {
                    loginButton.disabled = false;
                    loginText.textContent = 'Sign In';
                    loginSpinner.classList.add('hidden');
                });
        });
    </script>
</body>

</html>
