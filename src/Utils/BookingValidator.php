<?php

/**
 * BookingValidator.php
 * Validates booking data before persistence
 */

namespace App\Utils;

use DateTime;

class BookingValidator
{
    /**
     * Validate booking dates and format
     */
    public static function validateDates(string $checkin, string $checkout): array
    {
        $errors = [];

        $d1 = DateTime::createFromFormat('Y-m-d', $checkin);
        if (!($d1 && $d1->format('Y-m-d') === $checkin)) {
            $errors[] = 'Invalid check-in date.';
        }

        $d2 = DateTime::createFromFormat('Y-m-d', $checkout);
        if (!($d2 && $d2->format('Y-m-d') === $checkout)) {
            $errors[] = 'Invalid check-out date.';
        }

        if (empty($errors) && $d2 <= $d1) {
            $errors[] = 'Checkout date must be greater than checkin date.';
        }

        return $errors;
    }

    /**
     * Validate optional notification date
     */
    public static function validateNotificationDate(string $date): ?string
    {
        if (empty(trim($date))) {
            return null;
        }

        $d = DateTime::createFromFormat('Y-m-d', $date);
        if (!($d && $d->format('Y-m-d') === $date)) {
            throw new \InvalidArgumentException('Invalid notification date.');
        }

        return $date;
    }

    /**
     * Validate optional cancellation date
     */
    public static function validateCancellationDate(string $date): ?string
    {
        if (empty(trim($date))) {
            return null;
        }

        $d = DateTime::createFromFormat('Y-m-d', $date);
        if (!($d && $d->format('Y-m-d') === $date)) {
            throw new \InvalidArgumentException('Invalid cancellation date.');
        }

        return $date;
    }

    /**
     * Validate service fee enum
     */
    public static function validateServiceFee(string $value): string
    {
        return in_array($value, ['Yes', 'No']) ? $value : 'No';
    }

    /**
     * Validate exclude bank holiday enum
     */
    public static function validateExcludeBankHoliday(string $value): string
    {
        return in_array($value, ['Yes', 'No']) ? $value : 'No';
    }

    /**
     * Validate payment plan enum
     */
    public static function validatePaymentPlan(string $value): string
    {
        $valid = ['weekly', 'fortnighly', 'Monthly', 'full'];
        return in_array($value, $valid) ? $value : 'Monthly';
    }

    /**
     * Validate and normalize days array
     */
    public static function validateDays(array $days): array
    {
        $validDays = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
        return array_values(array_intersect($validDays, array_map('strtolower', $days)));
    }
}
