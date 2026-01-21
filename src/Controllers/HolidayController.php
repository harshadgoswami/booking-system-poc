<?php

/**
 * HolidayController.php
 * Handles HTTP requests for holiday management
 */

namespace App\Controllers;

use App\Services\HolidayService;

class HolidayController
{
    private HolidayService $holidayService;
    private array $errors = [];
    private array $successes = [];

    public function __construct(HolidayService $holidayService)
    {
        $this->holidayService = $holidayService;
    }

    /**
     * Display holidays page
     */
    public function show(): array
    {
        try {
            $holidays = $this->holidayService->getAllHolidays();
        } catch (\Exception $e) {
            $this->errors[] = 'Failed to load holidays: ' . $e->getMessage();
            $holidays = [''];
        }

        return [
            'holidays' => $holidays,
            'errors' => $this->errors,
            'successes' => $this->successes,
        ];
    }

    /**
     * Handle form submission for syncing holidays
     */
    public function sync(array $postedDates): array
    {
        if (empty($postedDates)) {
            $this->errors[] = 'No dates provided';
            return $this->show();
        }

        try {
            $stats = $this->holidayService->syncHolidays($postedDates);

            if (empty($this->errors)) {
                $this->successes[] = $stats['message'];
            }
        } catch (\Exception $e) {
            $this->errors[] = 'Failed to sync dates: ' . $e->getMessage();
        }

        return $this->show();
    }

    /**
     * Add error message
     */
    public function addError(string $error): self
    {
        $this->errors[] = $error;
        return $this;
    }

    /**
     * Get all errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get all success messages
     */
    public function getSuccesses(): array
    {
        return $this->successes;
    }
}
