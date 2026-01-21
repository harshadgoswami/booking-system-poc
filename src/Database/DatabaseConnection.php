<?php

/**
 * DatabaseConnection.php
 * Manages PDO database connection and initialization
 */

namespace App\Database;

use PDO;
use PDOException;

class DatabaseConnection
{
    private static ?PDO $instance = null;
    private string $dsn;
    private string $username;
    private string $password;

    public function __construct(
        string $host = '127.0.0.1',
        string $dbname = 'booking_system',
        string $username = 'root',
        string $password = '',
        string $charset = 'utf8mb4'
    ) {
        $this->dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Get PDO instance (Singleton pattern)
     */
    public function connect(): PDO
    {
        if (self::$instance === null) {
            try {
                self::$instance = new PDO(
                    $this->dsn,
                    $this->username,
                    $this->password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_PERSISTENT => false,
                    ]
                );
            } catch (PDOException $e) {
                throw new PDOException('Database connection failed: ' . $e->getMessage());
            }
        }
        return self::$instance;
    }

    /**
     * Initialize database schema
     */
    public function initializeSchema(): void
    {
        $pdo = $this->connect();
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS holidays (
                id INT AUTO_INCREMENT PRIMARY KEY,
                holiday_date DATE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_holiday_date (holiday_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }
}
