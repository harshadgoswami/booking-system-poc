<?php

/**
 * HolidayService.php
 * Business logic for holiday management
 */

namespace App\Services;

use App\Models\Holiday;
use App\Repositories\HolidayRepository;
use App\Utils\DateValidator;

class HolidayService
{
    private HolidayRepository $repository;

    public function __construct(HolidayRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get all holidays as date strings
     */
    public function getAllHolidays(): array
    {
        $dates = $this->repository->getAllDates();
        return !empty($dates) ? $dates : [''];
    }

    /**
     * Sync holidays: delete removed dates, insert new dates
     */
    public function syncHolidays(array $submittedDates): array
    {
        // Normalize and validate submitted dates
        $submittedDates = DateValidator::normalizeDates($submittedDates);

        // Get current dates from database
        $dbDates = $this->repository->getAllDates();

        // Compute differences
        $toDelete = array_diff($dbDates, $submittedDates);
        $toInsert = array_diff($submittedDates, $dbDates);

        $stats = [
            'inserted' => 0,
            'deleted' => 0,
            'message' => '',
        ];

        // If no changes
        if (empty($toDelete) && empty($toInsert)) {
            $stats['message'] = 'No changes detected.';
            return $stats;
        }

        // Delete removed dates
        if (!empty($toDelete)) {
            $stats['deleted'] = $this->repository->deleteBatch($toDelete);
        }

        // Insert new dates
        if (!empty($toInsert)) {
            $holidays = array_map(fn($date) => new Holiday($date), $toInsert);
            $stats['inserted'] = $this->repository->saveBatch($holidays);
        }

        // Generate success message
        $parts = [];
        if ($stats['inserted'] > 0) {
            $parts[] = "{$stats['inserted']} inserted";
        }
        if ($stats['deleted'] > 0) {
            $parts[] = "{$stats['deleted']} deleted";
        }
        $stats['message'] = implode(' and ', $parts) . '.';

        return $stats;
    }

    /**
     * Validate multiple dates
     */
    public function validateDates(array $dates): array
    {
        $valid = [];
        $invalid = [];

        foreach ($dates as $date) {
            if (DateValidator::isValidDate($date)) {
                $valid[] = $date;
            } else {
                $invalid[] = $date;
            }
        }

        return [
            'valid' => $valid,
            'invalid' => $invalid,
        ];
    }
}
