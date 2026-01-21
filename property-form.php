<?php

/**
 * property-form.php
 * Main entry point for creating new bookings and properties
 * Uses modular architecture for better maintainability
 */

declare(strict_types=1);

session_start();

// Autoloader for PSR-4 namespaces
require_once __DIR__ . '/autoloader.php';

use App\Database\DatabaseConnection;
use App\Repositories\BookingRepository;
use App\Repositories\PropertyRepository;
use App\Services\BookingService;
use App\Controllers\BookingController;

$success = '';
$error = '';

// Initialize database connection
$dbConnection = new DatabaseConnection();
try {
    $dbConnection->initializeSchema();
    $pdo = $dbConnection->connect();
} catch (\Exception $e) {
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}

// Initialize dependencies
$bookingRepository = new BookingRepository($pdo);
$propertyRepository = new PropertyRepository($pdo);
$bookingService = new BookingService($bookingRepository, $propertyRepository);
$bookingController = new BookingController($bookingService);

// Initialize tables
$bookingRepository->initializeTables();
$propertyRepository->initializeTables();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingData = [
        'checkin' => $_POST['checkin'] ?? '',
        'checkout' => $_POST['checkout'] ?? '',
        'days' => $_POST['days'] ?? [],
        'service_fee' => $_POST['service_fee'] ?? 'No',
        'exclude_bank_holiday' => $_POST['exclude_bank_holiday'] ?? 'No',
        'payment_plan' => $_POST['payment_plan'] ?? 'Monthly',
        'notification_date' => $_POST['notification_date'] ?? '',
        'cancellation_date' => $_POST['cancellation_date'] ?? '',
    ];

    $propertiesData = $_POST['properties'] ?? [];

    try {
        $pdo->beginTransaction();
        $result = $bookingController->create([
            'booking' => $bookingData,
            'properties' => $propertiesData,
        ]);

        if ($result['success']) {
            $success = $result['message'];
            $pdo->commit();
            $bookingId = $result['booking_id'];
            header('Location: edit-booking.php?bookingId=' . $bookingId);
            exit;
        } else {
            $error = $result['error'];
            $pdo->rollBack();
        }
    } catch (\Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = 'Failed to save: ' . htmlspecialchars($e->getMessage());
    }
}

// Include view template
include __DIR__ . '/views/property-form.php';
