<?php

/**
 * PropertyValidator.php
 * Validates property data before persistence
 */

namespace App\Utils;

use DateTime;

class PropertyValidator
{
    /**
     * Validate single property data
     */
    public static function validateProperty(array $property, int $index): array
    {
        $errors = [];
        $prefix = "Property #" . ($index + 1);

        $title = trim((string)($property['title'] ?? ''));
        $nightPrice = $property['night_price'] ?? '';
        $deposit = $property['deposit'] ?? '';
        $checkoutDate = trim((string)($property['checkout_date'] ?? ''));
        $isCancelled = $property['is_cancelled'] ?? 'No';
        $notifyDay = $property['notify_day'] ?? '';

        // Validate title
        if (empty($title)) {
            $errors[] = "{$prefix}: title is required.";
        }

        // Validate night price
        if (!is_numeric($nightPrice)) {
            $errors[] = "{$prefix}: night price must be a number.";
        }

        // Validate deposit
        if (!is_numeric($deposit)) {
            $errors[] = "{$prefix}: deposit must be a number.";
        }

        // Validate checkout date
        if ($checkoutDate !== '') {
            $d = DateTime::createFromFormat('Y-m-d', $checkoutDate);
            if (!($d && $d->format('Y-m-d') === $checkoutDate)) {
                $errors[] = "{$prefix}: invalid checkout date.";
            }
        }

        // Validate notify day
        if ($notifyDay !== '' && (!ctype_digit((string)$notifyDay) || (int)$notifyDay < 0)) {
            $errors[] = "{$prefix}: notify day must be a non-negative integer.";
        }

        return $errors;
    }

    /**
     * Validate is_cancelled enum
     */
    public static function validateIsCancelled(string $value): string
    {
        return in_array($value, ['Yes', 'No']) ? $value : 'No';
    }

    /**
     * Normalize and extract property data
     */
    public static function normalizeProperty(array $property): array
    {
        return [
            'title' => trim((string)($property['title'] ?? '')),
            'night_price' => (float)($property['night_price'] ?? 0.0),
            'deposit' => (float)($property['deposit'] ?? 0.0),
            'checkout_date' => empty($property['checkout_date']) ? null : trim((string)$property['checkout_date']),
            'is_cancelled' => self::validateIsCancelled($property['is_cancelled'] ?? 'No'),
            'notify_day' => empty($property['notify_day']) ? 0 : (int)$property['notify_day'],
        ];
    }
}
