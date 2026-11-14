<?php
if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    session_set_cookie_params([
        'lifetime' => 24 * 60 * 60,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

class Auth
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function generateCSRFToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return (string)$_SESSION['csrf_token'];
    }

    public function verifyCSRFToken(?string $token): bool
    {
        return !empty($token) && isset($_SESSION['csrf_token']) && hash_equals((string)$_SESSION['csrf_token'], $token);
    }

    public function validateCSRFRequest(): bool
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        return $this->verifyCSRFToken($token !== null ? (string)$token : null);
    }

    public function login(string $login, string $password, ?string $csrf_token): array
    {
        if (!$this->verifyCSRFToken($csrf_token)) {
            return ['success' => false, 'message' => 'Invalid CSRF token'];
        }

        // Check if login is email or username
        $is_email = filter_var($login, FILTER_VALIDATE_EMAIL);

        if ($is_email) {
            $stmt = $this->db->conn->prepare("SELECT id, username, password_hash FROM users WHERE email = ?");
        } else {
            $stmt = $this->db->conn->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
        }

        $stmt->bind_param("s", $login);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['username'] = $user['username'];
                return ['success' => true];
            }
        }

        return ['success' => false, 'message' => 'Invalid credentials'];
    }

    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    public function requireLogin(string $redirectUrl = 'login.php'): void
    {
        if (!$this->isLoggedIn()) {
            header('Location: ' . $redirectUrl);
            exit;
        }
    }

    public function getUserId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }

    public function getCurrentUser(): ?array
    {
        if (!$this->isLoggedIn()) {
            return null;
        }
        $user_id = $this->getUserId();
        $stmt = $this->db->conn->prepare("SELECT id, username, full_name, email FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        return $user ?: ['id' => $user_id, 'username' => $_SESSION['username'] ?? 'User'];
    }

    public function getCSRFToken(): string
    {
        return $this->generateCSRFToken();
    }

    public function validateCSRFToken(?string $token): bool
    {
        return $this->verifyCSRFToken($token);
    }

    public function logout(string $redirectUrl = 'pages/login.php'): void
    {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
        header('Location: ' . $redirectUrl);
        exit;
    }
}
