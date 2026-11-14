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

// Get current user data
$stmt = $finance->getDb()->conn->prepare("SELECT username, full_name, email, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get user account telemetry
$accounts_count = $finance->getAccounts($user_id)->num_rows;
$transactions_count = $finance->getTransactions($user_id)->num_rows;
$transfers_count = $finance->getTransfers($user_id)->num_rows;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Profile Settings - <?php echo SITE_NAME; ?></title>
    <link rel="icon" href="../assets/img/logo.svg" data-next-head=""/>
    <link rel="stylesheet" href="../assets/css/tailwind.css?v=<?php echo asset_version(); ?>">
</head>

<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col font-sans">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="flex-1">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header Section -->
            <div class="pb-6 mb-8 border-b border-slate-200">
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Account & Security</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Manage profile credentials, multi-factor security rules, and institutional telemetry.</p>
            </div>

            <!-- Telemetry Metrics Bar -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Connected Accounts</span>
                        <div class="text-2xl font-bold text-slate-900 tracking-tight mt-1"><?php echo $accounts_count; ?></div>
                        <span class="text-xs text-slate-500 mt-0.5 block">Active ledger nodes</span>
                    </div>
                    <div class="w-11 h-11 rounded-lg bg-slate-100 text-slate-800 border border-slate-200 flex items-center justify-center">
                        <?php echo account_icon('h-5 w-5 text-slate-800'); ?>
                    </div>
                </div>

                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Recorded Transactions</span>
                        <div class="text-2xl font-bold text-slate-900 tracking-tight mt-1"><?php echo $transactions_count; ?></div>
                        <span class="text-xs text-slate-500 mt-0.5 block">Verified audit events</span>
                    </div>
                    <div class="w-11 h-11 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 3h12M6 8h12M6 13l8.5 8M6 13h3a4 4 0 0 0 0-8"/>
                        </svg>
                    </div>
                </div>

                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Settled Transfers</span>
                        <div class="text-2xl font-bold text-slate-900 tracking-tight mt-1"><?php echo $transfers_count; ?></div>
                        <span class="text-xs text-slate-500 mt-0.5 block">Inter-account movements</span>
                    </div>
                    <div class="w-11 h-11 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200 flex items-center justify-center">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 2l4 4-4 4"/>
                            <path d="M3 6h18"/>
                            <path d="M7 22l-4-4 4-4"/>
                            <path d="M21 18H3"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Profile Settings Card -->
            <div class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden mb-8">
                <div class="p-6 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 tracking-tight">Institutional Profile</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Maintain identity records and cryptographic credentials.</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                        Active Session
                    </span>
                </div>

                <form id="profileForm" class="p-6 space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">

                    <!-- Identity Section -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Full Name -->
                        <div>
                            <label for="full_name" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Legal Full Name</label>
                            <input type="text" id="full_name" name="full_name" required
                                class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-lg text-sm text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 shadow-2xs"
                                value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>"
                                placeholder="e.g. John Doe">
                        </div>

                        <!-- Username -->
                        <div>
                            <label for="username" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Username Handle</label>
                            <div class="relative">
                                <input type="text" id="username" name="username" required
                                    class="w-full px-3.5 py-2.5 pr-10 bg-white border border-slate-200 rounded-lg text-sm text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 shadow-2xs font-mono"
                                    value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>"
                                    placeholder="handle"
                                    onblur="checkUsernameAvailability()">
                                <div id="usernameStatus" class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none"></div>
                            </div>
                            <div id="usernameFeedback" class="text-xs mt-1.5 font-medium"></div>
                        </div>

                        <!-- Email (Read-only) -->
                        <div>
                            <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Verified Corporate Email</label>
                            <div class="relative">
                                <input type="email" id="email" readonly
                                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 text-slate-500 rounded-lg text-sm cursor-not-allowed shadow-2xs"
                                    value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-1">Immutable identifier assigned during onboarding.</p>
                        </div>

                        <!-- Member Since -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Provisioned Date</label>
                            <div class="px-3.5 py-2.5 bg-slate-50 border border-slate-200 text-slate-600 rounded-lg text-sm font-mono flex items-center gap-2">
                                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                                <?php echo date('F j, Y', strtotime($user['created_at'])); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="pt-6 border-t border-slate-200">
                        <div class="mb-4">
                            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Authentication Security</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Leave blank if you do not wish to modify your master password.</p>
                        </div>

                        <div class="space-y-4">
                            <!-- Current Password -->
                            <div>
                                <label for="current_password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Current Password</label>
                                <input type="password" id="current_password" name="current_password" autocomplete="current-password"
                                    class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-lg text-sm text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 shadow-2xs"
                                    placeholder="Enter current password to authorize changes">
                            </div>

                            <!-- New Password -->
                            <div>
                                <label for="new_password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">New Password</label>
                                <input type="password" id="new_password" name="new_password" autocomplete="new-password"
                                    class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-lg text-sm text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 shadow-2xs"
                                    placeholder="Minimum 8 characters with mixed case, number, symbol"
                                    onkeyup="checkPasswordStrength()">

                                <!-- Password Strength Meter -->
                                <div class="mt-2.5">
                                    <div class="flex justify-between text-xs text-slate-500 mb-1.5 font-medium">
                                        <span>Password Strength</span>
                                        <span id="passwordStrengthText" class="font-semibold text-slate-700">None</span>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                        <div id="passwordStrengthBar" class="h-1.5 rounded-full transition-all duration-300 bg-slate-200" style="width: 0%"></div>
                                    </div>
                                    <div id="passwordFeedback" class="text-xs mt-2 space-y-1"></div>
                                </div>
                            </div>

                            <!-- Confirm New Password -->
                            <div>
                                <label for="confirm_password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password"
                                    class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-lg text-sm text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 shadow-2xs"
                                    placeholder="Re-type new password"
                                    onkeyup="validatePasswordMatch()">
                                <div id="passwordMatchFeedback" class="text-xs mt-1.5 font-medium"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions Bar -->
                    <div class="pt-6 border-t border-slate-200 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                        <button type="button" onclick="resetForm()"
                            class="px-4 py-2.5 bg-white border border-slate-200 rounded-lg text-xs sm:text-sm font-semibold text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition shadow-2xs">
                            Cancel & Reset
                        </button>
                        <button type="submit" id="submitButton"
                            class="px-5 py-2.5 bg-slate-900 text-white rounded-lg text-xs sm:text-sm font-semibold hover:bg-slate-800 transition shadow-xs disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center justify-center gap-2">
                            <span>Update Profile Settings</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        const originalData = {
            full_name: <?php echo json_encode($user['full_name'] ?? ''); ?>,
            username: <?php echo json_encode($user['username'] ?? ''); ?>
        };

        function showAlert(type, message) {
            if (window.showToast) {
                window.showToast(type === 'success' ? 'Successfully Saved' : 'Action Failed', message, type);
            }
        }

        function checkUsernameAvailability() {
            const username = document.getElementById('username').value.trim();
            const usernameStatus = document.getElementById('usernameStatus');
            const usernameFeedback = document.getElementById('usernameFeedback');

            if (username === originalData.username) {
                usernameFeedback.innerHTML = '';
                usernameStatus.innerHTML = '';
                return;
            }

            if (username.length < 3) {
                usernameFeedback.innerHTML = '<span class="text-rose-600">Username must be at least 3 characters</span>';
                usernameStatus.innerHTML = `
                    <svg class="h-4 w-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                `;
                return;
            }

            usernameFeedback.innerHTML = '<span class="text-slate-500">Checking availability...</span>';
            usernameStatus.innerHTML = `
                <svg class="h-4 w-4 text-slate-500 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            `;

            const formData = new FormData();
            formData.append('action', 'check_username');
            formData.append('username', username);

            fetch('../ajax/profile.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    usernameFeedback.innerHTML = '<span class="text-emerald-700">Username is available</span>';
                    usernameStatus.innerHTML = `
                        <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    `;
                } else {
                    usernameFeedback.innerHTML = '<span class="text-rose-600">' + (data.message || 'Username unavailable') + '</span>';
                    usernameStatus.innerHTML = `
                        <svg class="h-4 w-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="15" y1="9" x2="9" y2="15"></line>
                            <line x1="9" y1="9" x2="15" y2="15"></line>
                        </svg>
                    `;
                }
            })
            .catch(() => {
                usernameFeedback.innerHTML = '<span class="text-slate-500">Could not verify username</span>';
                usernameStatus.innerHTML = '';
            });
        }

        function checkPasswordStrengthLocal(password) {
            let score = 0;
            const feedback = [];

            if (password.length >= 8) {
                score++;
            } else {
                feedback.push('At least 8 characters required');
            }

            if (/[A-Z]/.test(password)) {
                score++;
            } else {
                feedback.push('Include uppercase letter');
            }

            if (/[a-z]/.test(password)) {
                score++;
            } else {
                feedback.push('Include lowercase letter');
            }

            if (/[0-9]/.test(password)) {
                score++;
            } else {
                feedback.push('Include at least one number');
            }

            if (/[^A-Za-z0-9]/.test(password)) {
                score++;
            } else {
                feedback.push('Include special symbol (!@#%^&* or punctuation)');
            }

            let strength, color;
            if (score >= 5) {
                strength = 'Institutional Grade';
                color = 'bg-emerald-600';
            } else if (score >= 4) {
                strength = 'Strong';
                color = 'bg-emerald-500';
            } else if (score >= 3) {
                strength = 'Fair';
                color = 'bg-amber-500';
            } else if (score >= 2) {
                strength = 'Weak';
                color = 'bg-amber-600';
            } else {
                strength = 'Very Weak';
                color = 'bg-rose-500';
            }

            return { score, strength, color, feedback, progress: (score / 5) * 100 };
        }

        function checkPasswordStrength() {
            const password = document.getElementById('new_password').value;
            const strengthBar = document.getElementById('passwordStrengthBar');
            const strengthText = document.getElementById('passwordStrengthText');
            const feedback = document.getElementById('passwordFeedback');

            if (password === '') {
                strengthBar.style.width = '0%';
                strengthBar.className = 'h-1.5 rounded-full transition-all duration-300 bg-slate-200';
                strengthText.textContent = 'None';
                feedback.innerHTML = '';
                return;
            }

            const result = checkPasswordStrengthLocal(password);
            strengthBar.style.width = result.progress + '%';
            strengthBar.className = 'h-1.5 rounded-full transition-all duration-300 ' + result.color;
            strengthText.textContent = result.strength;

            if (result.feedback.length > 0) {
                feedback.innerHTML = result.feedback.map(item =>
                    `<div class="flex items-center text-rose-600">
                        <svg class="h-3 w-3 mr-1.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                        <span>${item}</span>
                    </div>`
                ).join('');
            } else {
                feedback.innerHTML = `
                    <div class="flex items-center text-emerald-700">
                        <svg class="h-3 w-3 mr-1.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span>Password fulfills all institutional complexity requirements</span>
                    </div>
                `;
            }
        }

        function validatePasswordMatch() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const feedback = document.getElementById('passwordMatchFeedback');

            if (confirmPassword === '') {
                feedback.innerHTML = '';
                return;
            }

            if (newPassword === confirmPassword) {
                feedback.innerHTML = `
                    <span class="text-emerald-700 inline-flex items-center gap-1">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        Passwords match
                    </span>
                `;
            } else {
                feedback.innerHTML = `
                    <span class="text-rose-600 inline-flex items-center gap-1">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                        Passwords do not match
                    </span>
                `;
            }
        }

        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const submitButton = document.getElementById('submitButton');
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (newPassword && newPassword !== confirmPassword) {
                showAlert('error', 'New password and confirmation do not match.');
                return;
            }

            submitButton.disabled = true;
            submitButton.innerHTML = '<span>Saving...</span>';

            const formData = new FormData(this);
            formData.append('action', 'update_profile');

            fetch('../ajax/profile.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (window.showToast) {
                        window.showToast('Successfully Saved', 'Profile and security settings updated successfully.', 'success');
                    }
                    originalData.full_name = document.getElementById('full_name').value;
                    originalData.username = document.getElementById('username').value;
                    document.getElementById('current_password').value = '';
                    document.getElementById('new_password').value = '';
                    document.getElementById('confirm_password').value = '';
                    checkPasswordStrength();
                    validatePasswordMatch();
                } else {
                    showAlert('error', data.message || 'An error occurred while updating profile.');
                }
            })
            .catch(err => {
                showAlert('error', 'Connection failure: ' + err.message);
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = '<span>Update Profile Settings</span>';
            });
        });

        function resetForm() {
            document.getElementById('full_name').value = originalData.full_name;
            document.getElementById('username').value = originalData.username;
            document.getElementById('current_password').value = '';
            document.getElementById('new_password').value = '';
            document.getElementById('confirm_password').value = '';

            document.getElementById('usernameFeedback').innerHTML = '';
            document.getElementById('usernameStatus').innerHTML = '';
            document.getElementById('passwordStrengthText').textContent = 'None';
            document.getElementById('passwordStrengthBar').style.width = '0%';
            document.getElementById('passwordStrengthBar').className = 'h-1.5 rounded-full transition-all duration-300 bg-slate-200';
            document.getElementById('passwordFeedback').innerHTML = '';
            document.getElementById('passwordMatchFeedback').innerHTML = '';
        }
    </script>
</body>

</html>
