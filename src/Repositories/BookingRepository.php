<?php

/**
 * BookingRepository.php
 * Handles all database operations for bookings
 */

namespace App\Repositories;

use App\Models\Booking;
use PDO;
use PDOException;

class BookingRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Create tables if they don't exist
     */
    public function initializeTables(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS bookings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                checkin DATE NOT NULL,
                checkout DATE NOT NULL,
                days JSON DEFAULT '[]',
                service_fee ENUM('No','Yes') NOT NULL DEFAULT 'No',
                exclude_bank_holiday ENUM('No','Yes') NOT NULL DEFAULT 'No',
                payment_plan ENUM('weekly','fortnighly','Monthly','full') NOT NULL DEFAULT 'Monthly',
                notification_date DATE NULL,
                cancellation_date DATE NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    /**
     * Find booking by ID
     */
    public function findById(int $id): ?Booking
    {
        $query = "SELECT * FROM bookings WHERE id = :id";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->hydrate($row) : null;
    }

    /**
     * Get all bookings with property count, ordered by checkin
     */
    public function findAll(): array
    {
        $query = "
            SELECT b.*, COUNT(p.id) AS prop_count
            FROM bookings b
            LEFT JOIN properties p ON p.booking_id = b.id
            GROUP BY b.id
            ORDER BY b.checkin DESC, b.id DESC
        ";
        $stmt = $this->pdo->query($query);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([$this, 'hydrate'], $rows);
    }

    /**
     * Save (insert) a new booking
     */
    public function save(Booking $booking): int
    {
        $query = "
            INSERT INTO bookings (checkin, checkout, days, service_fee, exclude_bank_holiday, payment_plan, notification_date, cancellation_date)
            VALUES (:checkin, :checkout, :days, :service_fee, :exclude_bank_holiday, :payment_plan, :notification_date, :cancellation_date)
        ";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([
            ':checkin' => $booking->getCheckin(),
            ':checkout' => $booking->getCheckout(),
            ':days' => json_encode($booking->getDays()),
            ':service_fee' => $booking->getServiceFee(),
            ':exclude_bank_holiday' => $booking->getExcludeBankHoliday(),
            ':payment_plan' => $booking->getPaymentPlan(),
            ':notification_date' => $booking->getNotificationDate(),
            ':cancellation_date' => $booking->getCancellationDate(),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Update an existing booking
     */
    public function update(Booking $booking): bool
    {
        $query = "
            UPDATE bookings
            SET checkin = :checkin, checkout = :checkout, days = :days, service_fee = :service_fee,
                exclude_bank_holiday = :exclude_bank_holiday, payment_plan = :payment_plan,
                notification_date = :notification_date, cancellation_date = :cancellation_date
            WHERE id = :id
        ";
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute([
            ':checkin' => $booking->getCheckin(),
            ':checkout' => $booking->getCheckout(),
            ':days' => json_encode($booking->getDays()),
            ':service_fee' => $booking->getServiceFee(),
            ':exclude_bank_holiday' => $booking->getExcludeBankHoliday(),
            ':payment_plan' => $booking->getPaymentPlan(),
            ':notification_date' => $booking->getNotificationDate(),
            ':cancellation_date' => $booking->getCancellationDate(),
            ':id' => $booking->getId(),
        ]);
    }

    /**
     * Get all bookings as array with property count
     */
    public function findAllWithPropertyCount(): array
    {
        $query = "
            SELECT b.*, COUNT(p.id) AS prop_count
            FROM bookings b
            LEFT JOIN properties p ON p.booking_id = b.id
            GROUP BY b.id
            ORDER BY b.checkin DESC, b.id DESC
        ";
        $stmt = $this->pdo->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Convert database row to Booking object
     */
    private function hydrate(array $row): Booking
    {
        $booking = new Booking(
            $row['checkin'],
            $row['checkout'],
            (int)$row['id'],
            $row['created_at'] ?? null
        );

        $booking
            ->setDays(json_decode($row['days'] ?? '[]', true) ?: [])
            ->setServiceFee($row['service_fee'] ?? 'No')
            ->setExcludeBankHoliday($row['exclude_bank_holiday'] ?? 'No')
            ->setPaymentPlan($row['payment_plan'] ?? 'Monthly')
            ->setNotificationDate($row['notification_date'] ?? null)
            ->setCancellationDate($row['cancellation_date'] ?? null);

        return $booking;
    }
}
