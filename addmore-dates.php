<?php

/**
 * addmore-dates.php
 * Main entry point for holiday date management
 * Uses modular architecture for better maintainability and readability
 */

declare(strict_types=1);

session_start();

// Autoloader for PSR-4 namespaces
require_once __DIR__ . '/autoloader.php';

use App\Database\DatabaseConnection;
use App\Repositories\HolidayRepository;
use App\Services\HolidayService;
use App\Controllers\HolidayController;

// Initialize database connection
$dbConnection = new DatabaseConnection();
try {
    $dbConnection->initializeSchema();
    $pdo = $dbConnection->connect();
} catch (\Exception $e) {
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}

// Initialize dependencies
$holidayRepository = new HolidayRepository($pdo);
$holidayService = new HolidayService($holidayRepository);
$holidayController = new HolidayController($holidayService);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedDates = $_POST['dates'] ?? [];
    $response = $holidayController->sync($postedDates);
} else {
    $response = $holidayController->show();
}

// Extract response variables for view
$holidays = $response['holidays'];
$errors = $response['errors'];
$successes = $response['successes'];

// Include view template
include __DIR__ . '/views/holidays.php';
