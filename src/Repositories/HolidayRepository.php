<?php

/**
 * HolidayRepository.php
 * Handles all database operations for holidays
 */

namespace App\Repositories;

use App\Models\Holiday;
use PDO;
use PDOException;

class HolidayRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Find holiday by ID
     */
    public function findById(int $id): ?Holiday
    {
        $query = "SELECT * FROM holidays WHERE id = :id";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return $this->hydrate($row);
    }

    /**
     * Get all holidays ordered by date
     */
    public function findAll(): array
    {
        $query = "SELECT * FROM holidays ORDER BY holiday_date ASC";
        $stmt = $this->pdo->query($query);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([$this, 'hydrate'], $rows);
    }

    /**
     * Get all holiday dates as strings
     */
    public function getAllDates(): array
    {
        $query = "SELECT holiday_date FROM holidays ORDER BY holiday_date ASC";
        $stmt = $this->pdo->query($query);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    /**
     * Save (insert) a new holiday
     */
    public function save(Holiday $holiday): bool
    {
        $query = "INSERT IGNORE INTO holidays (holiday_date) VALUES (:date)";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':date' => $holiday->getHolidayDate()]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Save multiple holidays in a transaction
     */
    public function saveBatch(array $holidays): int
    {
        $inserted = 0;
        $query = "INSERT IGNORE INTO holidays (holiday_date) VALUES (:date)";
        $stmt = $this->pdo->prepare($query);

        try {
            $this->pdo->beginTransaction();

            foreach ($holidays as $holiday) {
                $stmt->execute([':date' => $holiday->getHolidayDate()]);
                if ($stmt->rowCount() === 1) {
                    $inserted++;
                }
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
     * Delete holiday by date
     */
    public function deleteByDate(string $date): bool
    {
        $query = "DELETE FROM holidays WHERE holiday_date = :date";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':date' => $date]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Delete multiple holidays in a transaction
     */
    public function deleteBatch(array $dates): int
    {
        $deleted = 0;
        $query = "DELETE FROM holidays WHERE holiday_date = :date";
        $stmt = $this->pdo->prepare($query);

        try {
            $this->pdo->beginTransaction();

            foreach ($dates as $date) {
                $stmt->execute([':date' => $date]);
                $deleted += $stmt->rowCount();
            }

            $this->pdo->commit();
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        return $deleted;
    }

    /**
     * Convert database row to Holiday object
     */
    private function hydrate(array $row): Holiday
    {
        return new Holiday(
            $row['holiday_date'],
            (int)$row['id'],
            $row['created_at'] ?? null
        );
    }
}
