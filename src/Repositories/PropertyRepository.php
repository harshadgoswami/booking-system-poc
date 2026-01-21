<?php

/**
 * PropertyRepository.php
 * Handles all database operations for properties
 */

namespace App\Repositories;

use App\Models\Property;
use PDO;
use PDOException;

class PropertyRepository
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
            CREATE TABLE IF NOT EXISTS properties (
                id INT AUTO_INCREMENT PRIMARY KEY,
                booking_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                night_price DECIMAL(10,2) NOT NULL DEFAULT 0,
                deposit DECIMAL(10,2) NOT NULL DEFAULT 0,
                checkout_date DATE DEFAULT NULL,
                is_cancelled ENUM('No','Yes') NOT NULL DEFAULT 'No',
                notify_day INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    /**
     * Find property by ID
     */
    public function findById(int $id): ?Property
    {
        $query = "SELECT * FROM properties WHERE id = :id";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->hydrate($row) : null;
    }

    /**
     * Get all properties for a booking
     */
    public function findByBookingId(int $bookingId): array
    {
        $query = "SELECT * FROM properties WHERE booking_id = :booking_id ORDER BY id ASC";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':booking_id' => $bookingId]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map([$this, 'hydrate'], $rows);
    }

    /**
     * Save (insert) a new property
     */
    public function save(Property $property): int
    {
        $query = "
            INSERT INTO properties (booking_id, title, night_price, deposit, checkout_date, is_cancelled, notify_day)
            VALUES (:booking_id, :title, :night_price, :deposit, :checkout_date, :is_cancelled, :notify_day)
        ";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([
            ':booking_id' => $property->getBookingId(),
            ':title' => $property->getTitle(),
            ':night_price' => $property->getNightPrice(),
            ':deposit' => $property->getDeposit(),
            ':checkout_date' => $property->getCheckoutDate(),
            ':is_cancelled' => $property->getIsCancelled(),
            ':notify_day' => $property->getNotifyDay(),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Save multiple properties in a transaction
     */
    public function saveBatch(array $properties): int
    {
        $inserted = 0;
        $query = "
            INSERT INTO properties (booking_id, title, night_price, deposit, checkout_date, is_cancelled, notify_day)
            VALUES (:booking_id, :title, :night_price, :deposit, :checkout_date, :is_cancelled, :notify_day)
        ";
        $stmt = $this->pdo->prepare($query);

        try {
            $this->pdo->beginTransaction();

            foreach ($properties as $property) {
                $stmt->execute([
                    ':booking_id' => $property->getBookingId(),
                    ':title' => $property->getTitle(),
                    ':night_price' => $property->getNightPrice(),
                    ':deposit' => $property->getDeposit(),
                    ':checkout_date' => $property->getCheckoutDate(),
                    ':is_cancelled' => $property->getIsCancelled(),
                    ':notify_day' => $property->getNotifyDay(),
                ]);
                $inserted++;
            }

            $this->pdo->commit();
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        return $inserted;
    }

    /**
     * Delete all properties for a booking
     */
    public function deleteByBookingId(int $bookingId): int
    {
        $query = "DELETE FROM properties WHERE booking_id = :booking_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':booking_id' => $bookingId]);

        return $stmt->rowCount();
    }

    /**
     * Get all properties as raw data (for data tables)
     */
    public function findAllRawByBookingId(int $bookingId): array
    {
        $query = "SELECT * FROM properties WHERE booking_id = :booking_id ORDER BY id ASC";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':booking_id' => $bookingId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Convert database row to Property object
     */
    private function hydrate(array $row): Property
    {
        $property = new Property(
            (int)$row['booking_id'],
            $row['title'],
            (float)$row['night_price'],
            (float)$row['deposit'],
            (int)$row['id'],
            $row['created_at'] ?? null
        );

        $property
            ->setCheckoutDate($row['checkout_date'] ?? null)
            ->setIsCancelled($row['is_cancelled'] ?? 'No')
            ->setNotifyDay((int)($row['notify_day'] ?? 0));

        return $property;
    }
}
