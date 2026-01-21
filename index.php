<?php

/**
 * index.php
 * Main entry point for displaying all bookings
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

// Get all bookings
$response = $bookingController->index();
$bookings = $response['bookings'] ?? [];
$error = implode(' ', $response['errors'] ?? []);

// Include view template
include __DIR__ . '/views/index.php';
