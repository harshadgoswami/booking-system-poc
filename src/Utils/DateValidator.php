<?php

/**
 * DateValidator.php
 * Utility class for date validation
 */

namespace App\Utils;

use DateTime;

class DateValidator
{
    private const DATE_FORMAT = 'Y-m-d';

    /**
     * Validate if date string is in valid Y-m-d format
     */
    public static function isValidDate(string $date): bool
    {
        $date = trim($date);

        if (empty($date)) {
            return false;
        }

        $dateTime = DateTime::createFromFormat(self::DATE_FORMAT, $date);
        return $dateTime && $dateTime->format(self::DATE_FORMAT) === $date;
    }

    /**
     * Validate if cancellation date is after booking date
     */
    public static function isCancellationDateValid(string $bookingDate, string $cancellationDate): bool
    {
        try {
            $booking = new DateTime($bookingDate);
            $cancellation = new DateTime($cancellationDate);
            return $cancellation > $booking;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Normalize and filter dates from array
     */
    public static function normalizeDates(array $dates): array
    {
        $normalized = [];
        foreach ($dates as $date) {
            $date = trim((string)$date);
            if (self::isValidDate($date)) {
                $normalized[] = $date;
            }
        }
        return array_values(array_unique($normalized));
    }
}
