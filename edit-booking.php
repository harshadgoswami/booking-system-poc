<?php

/**
 * edit-booking.php
 * Main entry point for editing existing bookings
 * Uses clean, modular architecture - delegates complexity to services
 */

declare(strict_types=1);

session_start();

require_once __DIR__ . '/autoloader.php';

use App\Database\DatabaseConnection;
use App\Repositories\BookingRepository;
use App\Repositories\PropertyRepository;
use App\Services\BookingService;
use App\Services\PaymentPlanCalculator;
use DateTimeImmutable;

// Initialize database and dependencies
$dbConnection = new DatabaseConnection();
$dbConnection->initializeSchema();
$pdo = $dbConnection->connect();

$bookingRepository = new BookingRepository($pdo);
$propertyRepository = new PropertyRepository($pdo);

$bookingRepository->initializeTables();
$propertyRepository->initializeTables();

// Get booking ID from request
$bookingId = (int)($_GET['bookingId'] ?? ($_POST['bookingId'] ?? 0));

if ($bookingId <= 0) {
    die('Missing or invalid bookingId.');
}

// Handle form submission - delegate to service
$bookingService = new BookingService($bookingRepository, $propertyRepository);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $formData = [
            'booking' => [
                'checkin' => $_POST['checkin'] ?? '',
                'checkout' => $_POST['checkout'] ?? '',
                'days' => $_POST['days'] ?? [],
                'service_fee' => $_POST['service_fee'] ?? 'No',
                'exclude_bank_holiday' => $_POST['exclude_bank_holiday'] ?? 'No',
                'payment_plan' => $_POST['payment_plan'] ?? 'Monthly',
                'notification_date' => $_POST['notification_date'] ?? '',
                'cancellation_date' => $_POST['cancellation_date'] ?? '',
            ],
            'properties' => $_POST['properties'] ?? [],
        ];

        $bookingService->updateBooking($bookingId, $formData['booking'], $formData['properties']);

        // Persist paid periods in session
        $paidPeriodsRaw = $_POST['paid_periods'] ?? [];
        $paidPeriodsPost = [];
        if (is_array($paidPeriodsRaw)) {
            $paidPeriodsPost = array_map('intval', array_keys($paidPeriodsRaw));
        }

        if (!empty($paidPeriodsPost)) {
            $_SESSION['paid_periods_booking_' . $bookingId] = $paidPeriodsPost;
        } else {
            unset($_SESSION['paid_periods_booking_' . $bookingId]);
        }

        header('Location: edit-booking.php?bookingId=' . $bookingId . '&saved=1');
        exit;
    } catch (\Exception $e) {
        die('Update failed: ' . htmlspecialchars($e->getMessage()));
    }
}

// Load booking and calculate payment data for display
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = :id");
$stmt->execute([':id' => $bookingId]);
$booking = $stmt->fetch(\PDO::FETCH_ASSOC);

if (!$booking) {
    die('Booking not found.');
}

// Parse booking data
$booking['days'] = json_decode($booking['days'] ?? '[]', true) ?: [];
$checkinDt = \DateTime::createFromFormat('Y-m-d', $booking['checkin']);
$checkoutDt = \DateTime::createFromFormat('Y-m-d', $booking['checkout']);

if (!$checkinDt || !$checkoutDt) {
    die('Invalid booking dates.');
}

// Load properties
$propsStmt = $pdo->prepare("SELECT * FROM properties WHERE booking_id = :bid ORDER BY id ASC");
$propsStmt->execute([':bid' => $bookingId]);
$properties = $propsStmt->fetchAll(\PDO::FETCH_ASSOC);

// Load holidays
$todayDt = new DateTimeImmutable('today');
$excludeHolidays = ($booking['exclude_bank_holiday'] ?? 'No') === 'Yes';
$holidays = $excludeHolidays ? PaymentPlanCalculator::loadHolidays($pdo, $checkinDt, $checkoutDt) : [];

// Parse dates for calculations
$checkinImm = DateTimeImmutable::createFromMutable($checkinDt);
$checkoutImm = DateTimeImmutable::createFromMutable($checkoutDt);
$notificationDt = !empty($booking['notification_date'])
    ? DateTimeImmutable::createFromFormat('Y-m-d', $booking['notification_date'])
    : null;
$cancellationDt = !empty($booking['cancellation_date'])
    ? DateTimeImmutable::createFromFormat('Y-m-d', $booking['cancellation_date'])
    : null;

// Calculate payment periods
$hasServiceFee = ($booking['service_fee'] ?? 'No') === 'Yes';
$periods = PaymentPlanCalculator::calculatePeriods($checkinImm, $checkoutImm, $booking['payment_plan'] ?? 'Monthly');

// Calculate deposit total
$depositTotal = array_reduce($properties, function ($sum, $p) {
    return $sum + (float)($p['deposit'] ?? 0);
}, 0.0);

// Calculate period totals without cancellation
$periodTotalsNoCancel = PaymentPlanCalculator::calculatePeriodsNoCancel(
    $periods,
    $properties,
    $hasServiceFee,
    array_map('strtolower', $booking['days'] ?? []),
    $holidays,
    $depositTotal
);

// Calculate period totals with cancellation
$periodTotalsWithCancel = [];
$showWithCancel = false;
if ($cancellationDt && count(array_filter($properties, fn($p) => ($p['is_cancelled'] ?? 'No') === 'Yes')) > 0) {
    $periodTotalsWithCancel = PaymentPlanCalculator::calculatePeriodsWithCancel(
        $periods,
        $properties,
        $hasServiceFee,
        array_map('strtolower', $booking['days'] ?? []),
        $holidays,
        $depositTotal,
        $cancellationDt,
        $notificationDt
    );
    $showWithCancel = true;
}

// Calculate after-cancellation (host refund) rows
$paidPeriodsSelected = $_SESSION['paid_periods_booking_' . $bookingId] ?? [];
$afterCancelHost = PaymentPlanCalculator::calculateAfterCancelHost(
    $properties,
    $periods,
    $paidPeriodsSelected,
    $hasServiceFee,
    array_map('strtolower', $booking['days'] ?? []),
    $holidays,
    $checkoutImm,
    $cancellationDt,
    $notificationDt
);

// Render view
include __DIR__ . '/views/edit-booking.php';
