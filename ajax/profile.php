<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$finance = new FinanceManager();
$user_id = $auth->getUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'update_profile':
            if (!$auth->validateCSRFRequest()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid or missing CSRF token']);
                break;
            }

            $full_name = trim($_POST['full_name'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';

            // Validate inputs
            if (empty($full_name) || empty($username)) {
                echo json_encode(['success' => false, 'message' => 'Full name and username are required']);
                break;
            }

            // Check if username already exists (excluding current user)
            $stmt = $finance->getDb()->conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->bind_param("si", $username, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Username already exists']);
                break;
            }

            // Start transaction
            $finance->getDb()->conn->begin_transaction();

            try {
                // Update basic profile info
                $stmt = $finance->getDb()->conn->prepare("UPDATE users SET full_name = ?, username = ? WHERE id = ?");
                $stmt->bind_param("ssi", $full_name, $username, $user_id);
                $stmt->execute();

                // Update password if provided
                if (!empty($current_password) && !empty($new_password)) {
                    // Verify current password
                    $stmt = $finance->getDb()->conn->prepare("SELECT password_hash FROM users WHERE id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $user = $stmt->get_result()->fetch_assoc();

                    if (!password_verify($current_password, $user['password_hash'])) {
                        throw new Exception('Current password is incorrect');
                    }

                    // Validate new password strength
                    if (strlen($new_password) < 8) {
                        throw new Exception('New password must be at least 8 characters long');
                    }

                    // Update password
                    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $finance->getDb()->conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                    $stmt->bind_param("si", $new_password_hash, $user_id);
                    $stmt->execute();
                }

                $finance->getDb()->conn->commit();

                // Update session username if changed
                if ($_SESSION['username'] !== $username) {
                    $_SESSION['username'] = $username;
                }

                echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
            } catch (Exception $e) {
                $finance->getDb()->conn->rollback();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        case 'check_username':
            $username = $_POST['username'] ?? '';

            if (empty($username)) {
                echo json_encode(['success' => false, 'message' => 'Username is required']);
                break;
            }

            $stmt = $finance->getDb()->conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->bind_param("si", $username, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Username already exists']);
            } else {
                echo json_encode(['success' => true, 'message' => 'Username available']);
            }
            break;

        // In the POST section, update the check_password_strength case:
        case 'check_password_strength':
            $password = $_POST['password'] ?? '';
            echo json_encode(checkPasswordStrength($password));
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'get_profile':
            $stmt = $finance->getDb()->conn->prepare("SELECT username, full_name, email, created_at FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user) {
                echo json_encode(['success' => true, 'profile' => $user]);
            } else {
                echo json_encode(['success' => false, 'message' => 'User not found']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

// Password strength checker function
function checkPasswordStrength($password)
{
    $score = 0;
    $feedback = [];

    if (strlen($password) >= 8) {
        $score++;
    } else {
        $feedback[] = 'Password should be at least 8 characters long';
    }

    if (preg_match('/[A-Z]/', $password)) {
        $score++;
    } else {
        $feedback[] = 'Include at least one uppercase letter';
    }

    if (preg_match('/[a-z]/', $password)) {
        $score++;
    } else {
        $feedback[] = 'Include at least one lowercase letter';
    }

    if (preg_match('/[0-9]/', $password)) {
        $score++;
    } else {
        $feedback[] = 'Include at least one number';
    }

    if (preg_match('/[^A-Za-z0-9]/', $password)) {
        $score++;
    } else {
        $feedback[] = 'Include at least one special character';
    }

    // Determine strength level
    if ($score >= 5) {
        $strength = 'Very Strong';
        $color = 'bg-green-500';
    } else if ($score >= 4) {
        $strength = 'Strong';
        $color = 'bg-green-400';
    } else if ($score >= 3) {
        $strength = 'Good';
        $color = 'bg-yellow-500';
    } else if ($score >= 2) {
        $strength = 'Weak';
        $color = 'bg-orange-500';
    } else {
        $strength = 'Very Weak';
        $color = 'bg-red-500';
    }

    return [
        'success' => true,
        'score' => $score,
        'strength' => $strength,
        'color' => $color,
        'feedback' => $feedback,
        'progress' => ($score / 5) * 100
    ];
}
