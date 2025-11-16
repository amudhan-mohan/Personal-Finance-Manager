<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

class Auth
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function generateCSRFToken()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function verifyCSRFToken($token)
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public function login($login, $password, $csrf_token)
    {
        if (!$this->verifyCSRFToken($csrf_token)) {
            return ['success' => false, 'message' => 'Invalid CSRF token'];
        }

        // Check if login is email or username
        $is_email = filter_var($login, FILTER_VALIDATE_EMAIL);

        if ($is_email) {
            // Login with email
            $stmt = $this->db->conn->prepare("SELECT id, username, password_hash FROM users WHERE email = ?");
        } else {
            // Login with username
            $stmt = $this->db->conn->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
        }

        $stmt->bind_param("s", $login);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                return ['success' => true];
            }
        }

        return ['success' => false, 'message' => 'Invalid credentials'];
    }

    public function isLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }

    public function getUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }

    public function logout()
    {
        session_destroy();
        header('Location: login.php');
        exit;
    }
}
