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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo SITE_NAME; ?></title>
    <link rel="icon" href="/assets/img/logo.svg" data-next-head=""/>
    <link rel="stylesheet" href="/assets/css/tailwind.css">
</head>

<body class="bg-gray-100">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Profile Settings</h1>
            <p class="text-gray-600 mb-8">Manage your account settings and preferences</p>

            <!-- Profile Update Form -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-6 text-gray-800">Personal Information</h2>

                <form id="profileForm" class="space-y-6">
                    <!-- Full Name -->
                    <div>
                        <label for="full_name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                        <input type="text" id="full_name" name="full_name" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>"
                            placeholder="Enter your full name">
                    </div>

                    <!-- Username -->
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                        <div class="relative">
                            <input type="text" id="username" name="username" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>"
                                placeholder="Choose a username"
                                onblur="checkUsernameAvailability()">
                            <div id="usernameStatus" class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <!-- Status icon will appear here -->
                            </div>
                        </div>
                        <div id="usernameFeedback" class="text-sm mt-1"></div>
                    </div>

                    <!-- Email (Read-only) -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                        <input type="email" id="email" readonly
                            class="w-full px-3 py-2 border border-gray-300 bg-gray-100 rounded-md shadow-sm"
                            value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                        <p class="text-xs text-gray-500 mt-1">Email cannot be changed</p>
                    </div>

                    <!-- Member Since -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Member Since</label>
                        <p class="text-gray-600"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                    </div>

                    <hr class="border-gray-200">

                    <!-- Password Change Section -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-800 mb-4">Change Password</h3>

                        <!-- Current Password -->
                        <div class="mb-4">
                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                            <input type="password" id="current_password" name="current_password"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Enter current password">
                        </div>

                        <!-- New Password -->
                        <div class="mb-4">
                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                            <input type="password" id="new_password" name="new_password"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Enter new password"
                                onkeyup="checkPasswordStrength()">

                            <!-- Password Strength Meter -->
                            <div class="mt-2">
                                <div class="flex justify-between text-xs text-gray-600 mb-1">
                                    <span>Password Strength:</span>
                                    <span id="passwordStrengthText">None</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div id="passwordStrengthBar" class="h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                                </div>
                                <div id="passwordFeedback" class="text-xs text-gray-600 mt-1 space-y-1"></div>
                            </div>
                        </div>

                        <!-- Confirm New Password -->
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Confirm new password"
                                onkeyup="validatePasswordMatch()">
                            <div id="passwordMatchFeedback" class="text-sm mt-1"></div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="resetForm()"
                            class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Reset
                        </button>
                        <button type="submit" id="submitButton"
                            class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            Update Profile
                        </button>
                    </div>
                </form>
            </div>

            <!-- Account Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <?php
                // Get user statistics
                $accounts_count = $finance->getAccounts($user_id)->num_rows;
                $transactions_count = $finance->getTransactions($user_id)->num_rows;
                $transfers_count = $finance->getTransfers($user_id)->num_rows;
                ?>

                <div class="bg-white rounded-lg shadow-md p-6 text-center">
                    <div class="text-2xl font-bold text-blue-600"><?php echo $accounts_count; ?></div>
                    <div class="text-sm text-gray-600">Accounts</div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 text-center">
                    <div class="text-2xl font-bold text-green-600"><?php echo $transactions_count; ?></div>
                    <div class="text-sm text-gray-600">Transactions</div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 text-center">
                    <div class="text-2xl font-bold text-purple-600"><?php echo $transfers_count; ?></div>
                    <div class="text-sm text-gray-600">Transfers</div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        let originalData = {
            full_name: '<?php echo addslashes($user['full_name'] ?? ''); ?>',
            username: '<?php echo addslashes($user['username'] ?? ''); ?>'
        };

        // Username availability check
        function checkUsernameAvailability() {
            const username = document.getElementById('username').value;
            const usernameStatus = document.getElementById('usernameStatus');
            const usernameFeedback = document.getElementById('usernameFeedback');

            if (username === originalData.username) {
                usernameFeedback.innerHTML = '';
                usernameStatus.innerHTML = '';
                return;
            }

            if (username.length < 3) {
                usernameFeedback.innerHTML = '<span class="text-red-600">Username must be at least 3 characters</span>';
                usernameStatus.innerHTML = `
                <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
            `;
                return;
            }

            // Show loading
            usernameFeedback.innerHTML = '<span class="text-blue-600">Checking availability...</span>';
            usernameStatus.innerHTML = `
            <svg class="h-5 w-5 text-blue-500 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v4m0 12v4m8-10h-4M6 12H2m15.364-7.364l-2.828 2.828M7.464 17.536l-2.828 2.828m12.728 0l-2.828-2.828M7.464 6.464L4.636 3.636"/>
            </svg>
        `;

            const formData = new FormData();
            formData.append('action', 'check_username');
            formData.append('username', username);

            fetch('../ajax/profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        usernameFeedback.innerHTML = '<span class="text-green-600">Username available</span>';
                        usernameStatus.innerHTML = `
                    <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                `;
                    } else {
                        usernameFeedback.innerHTML = '<span class="text-red-600">' + data.message + '</span>';
                        usernameStatus.innerHTML = `
                    <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                `;
                    }
                })
                .catch(error => {
                    usernameFeedback.innerHTML = '<span class="text-red-600">Error checking username</span>';
                    usernameStatus.innerHTML = `
                <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            `;
                });
        }

        // Password strength check
        // Password strength check
        function checkPasswordStrength() {
            const password = document.getElementById('new_password').value;
            const strengthBar = document.getElementById('passwordStrengthBar');
            const strengthText = document.getElementById('passwordStrengthText');
            const feedback = document.getElementById('passwordFeedback');

            if (password === '') {
                strengthBar.style.width = '0%';
                strengthBar.className = 'h-2 rounded-full transition-all duration-300 bg-gray-300';
                strengthText.textContent = 'None';
                feedback.innerHTML = '';
                return;
            }

            // Client-side password strength check (no AJAX needed)
            const result = checkPasswordStrengthLocal(password);

            strengthBar.style.width = result.progress + '%';
            strengthBar.className = 'h-2 rounded-full transition-all duration-300 ' + result.color;
            strengthText.textContent = result.strength;

            let feedbackHtml = '';
            if (result.feedback.length > 0) {
                feedbackHtml = result.feedback.map(item =>
                    `<div class="flex items-center">
                <svg class="h-3 w-3 text-red-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <span class="text-xs">${item}</span>
            </div>`
                ).join('');
            } else {
                feedbackHtml = `
            <div class="flex items-center text-green-600">
                <svg class="h-3 w-3 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
                <span class="text-xs">✓ Strong password!</span>
            </div>
        `;
            }
            feedback.innerHTML = feedbackHtml;
        }

        // Client-side password strength checker
        function checkPasswordStrengthLocal(password) {
            let score = 0;
            let feedback = [];

            // Length check
            if (password.length >= 8) {
                score++;
            } else {
                feedback.push('Password should be at least 8 characters long');
            }

            // Uppercase check
            if (/[A-Z]/.test(password)) {
                score++;
            } else {
                feedback.push('Include at least one uppercase letter');
            }

            // Lowercase check
            if (/[a-z]/.test(password)) {
                score++;
            } else {
                feedback.push('Include at least one lowercase letter');
            }

            // Number check
            if (/[0-9]/.test(password)) {
                score++;
            } else {
                feedback.push('Include at least one number');
            }

            // Special character check
            if (/[^A-Za-z0-9]/.test(password)) {
                score++;
            } else {
                feedback.push('Include at least one special character');
            }

            // Determine strength level
            let strength, color;
            if (score >= 5) {
                strength = 'Very Strong';
                color = 'bg-green-500';
            } else if (score >= 4) {
                strength = 'Strong';
                color = 'bg-green-400';
            } else if (score >= 3) {
                strength = 'Good';
                color = 'bg-yellow-500';
            } else if (score >= 2) {
                strength = 'Weak';
                color = 'bg-orange-500';
            } else {
                strength = 'Very Weak';
                color = 'bg-red-500';
            }

            return {
                success: true,
                score: score,
                strength: strength,
                color: color,
                feedback: feedback,
                progress: (score / 5) * 100
            };
        }

        // Password match validation
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
                <span class="text-green-600 flex items-center">
                    <svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    Passwords match
                </span>
            `;
            } else {
                feedback.innerHTML = `
                <span class="text-red-600 flex items-center">
                    <svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    Passwords do not match
                </span>
            `;
            }
        }

        // Form submission
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const submitButton = document.getElementById('submitButton');
            const originalText = submitButton.textContent;

            // Validate password match
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (newPassword && newPassword !== confirmPassword) {
                alert('Passwords do not match!');
                return;
            }

            submitButton.disabled = true;
            submitButton.textContent = 'Updating...';

            const formData = new FormData(this);
            formData.append('action', 'update_profile');

            fetch('../ajax/profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Profile updated successfully!');
                        // Update original data
                        originalData.full_name = document.getElementById('full_name').value;
                        originalData.username = document.getElementById('username').value;
                        // Clear password fields
                        document.getElementById('current_password').value = '';
                        document.getElementById('new_password').value = '';
                        document.getElementById('confirm_password').value = '';
                        // Reset strength meter
                        checkPasswordStrength();
                        validatePasswordMatch();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error updating profile: ' + error.message);
                })
                .finally(() => {
                    submitButton.disabled = false;
                    submitButton.textContent = originalText;
                });
        });

        // Reset form
        function resetForm() {
            document.getElementById('full_name').value = originalData.full_name;
            document.getElementById('username').value = originalData.username;
            document.getElementById('current_password').value = '';
            document.getElementById('new_password').value = '';
            document.getElementById('confirm_password').value = '';

            // Reset all feedback
            document.getElementById('usernameFeedback').innerHTML = '';
            document.getElementById('usernameStatus').innerHTML = '';
            document.getElementById('passwordStrengthText').textContent = 'None';
            document.getElementById('passwordStrengthBar').style.width = '0%';
            document.getElementById('passwordStrengthBar').className = 'h-2 rounded-full transition-all duration-300 bg-gray-300';
            document.getElementById('passwordFeedback').innerHTML = '';
            document.getElementById('passwordMatchFeedback').innerHTML = '';
        }
    </script>
</body>

</html>