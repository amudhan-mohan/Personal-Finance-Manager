<?php

declare(strict_types=1);

class Database
{
    private string $host;
    private string $username;
    private string $password;
    private string $database;
    public ?mysqli $conn = null;

    public function __construct()
    {
        // Standard 4-parameter database configuration (Compatible with free hosting & localhost)
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->username = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
        $this->database = getenv('DB_NAME') ?: 'finance_manager';

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);
            $this->conn->set_charset("utf8mb4");
        } catch (mysqli_sql_exception $e) {
            error_log("Database Connection Failure: " . $e->getMessage());
            die("Database connection failed. Please verify configuration or service status.");
        }
    }

    public function getConnection(): mysqli
    {
        return $this->conn;
    }
}
