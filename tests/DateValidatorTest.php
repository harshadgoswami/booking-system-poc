<?php

/**
 * tests/DateValidatorTest.php
 * Unit tests for DateValidator utility class
 */

namespace Tests;

use App\Utils\DateValidator;
use PHPUnit\Framework\TestCase;

class DateValidatorTest extends TestCase
{
    /**
     * Test: isValidDate accepts valid Y-m-d format
     */
    public function testIsValidDateAcceptsValidFormat(): void
    {
        $validDates = [
            '2024-12-25',
            '2025-01-01',
            '2020-02-29', // leap year
            '1999-12-31',
        ];

        foreach ($validDates as $date) {
            $this->assertTrue(DateValidator::isValidDate($date), "Date $date should be valid");
        }
    }

    /**
     * Test: isValidDate rejects invalid formats
     */
    public function testIsValidDateRejectsInvalidFormats(): void
    {
        $invalidDates = [
            'invalid',
            '25-12-2024',
            '12/25/2024',
            '2024-13-01', // invalid month
            '2024-01-32', // invalid day
            '2025-02-29', // not a leap year
            '',
            '2024-1-1',   // missing leading zeros
        ];

        foreach ($invalidDates as $date) {
            $this->assertFalse(DateValidator::isValidDate($date), "Date $date should be invalid");
        }
    }

    /**
     * Test: isValidDate trims whitespace
     */
    public function testIsValidDateTrimsWhitespace(): void
    {
        $this->assertTrue(DateValidator::isValidDate('  2024-12-25  '));
        $this->assertTrue(DateValidator::isValidDate("\t2024-12-25\n"));
    }

    /**
     * Test: isCancellationDateValid accepts dates after booking date
     */
    public function testIsCancellationDateValidAcceptsFutureDate(): void
    {
        $this->assertTrue(DateValidator::isCancellationDateValid('2024-01-01', '2024-01-02'));
        $this->assertTrue(DateValidator::isCancellationDateValid('2024-12-24', '2024-12-25'));
        $this->assertTrue(DateValidator::isCancellationDateValid('2024-01-01', '2025-01-01'));
    }

    /**
     * Test: isCancellationDateValid rejects dates before or equal to booking date
     */
    public function testIsCancellationDateValidRejectsPastDate(): void
    {
        $this->assertFalse(DateValidator::isCancellationDateValid('2024-01-02', '2024-01-01'));
        $this->assertFalse(DateValidator::isCancellationDateValid('2024-01-01', '2024-01-01'));
    }

    /**
     * Test: isCancellationDateValid handles invalid date strings
     */
    public function testIsCancellationDateValidHandlesInvalidDates(): void
    {
        $this->assertFalse(DateValidator::isCancellationDateValid('invalid', '2024-01-01'));
        $this->assertFalse(DateValidator::isCancellationDateValid('2024-01-01', 'invalid'));
    }

    /**
     * Test: normalizeDates filters valid dates
     */
    public function testNormalizeDatesFiltersValidDates(): void
    {
        $input = ['2024-12-25', 'invalid', '2025-01-01', '', '2025-02-14'];
        $result = DateValidator::normalizeDates($input);

        $this->assertCount(3, $result);
        $this->assertContains('2024-12-25', $result);
        $this->assertContains('2025-01-01', $result);
        $this->assertContains('2025-02-14', $result);
    }

    /**
     * Test: normalizeDates removes duplicates
     */
    public function testNormalizeDatesRemovesDuplicates(): void
    {
        $input = ['2024-12-25', '2024-12-25', '2025-01-01', '2025-01-01', '2025-01-01'];
        $result = DateValidator::normalizeDates($input);

        $this->assertCount(2, $result);
        $this->assertEqual(array_count_values($result)['2024-12-25'], 1);
        $this->assertEqual(array_count_values($result)['2025-01-01'], 1);
    }

    /**
     * Test: normalizeDates handles empty input
     */
    public function testNormalizeDatesHandlesEmptyInput(): void
    {
        $result = DateValidator::normalizeDates([]);

        $this->assertEmpty($result);
    }

    /**
     * Test: normalizeDates trims whitespace from dates
     */
    public function testNormalizeDatesTrimmsWhitespace(): void
    {
        $input = ['  2024-12-25  ', '2025-01-01', '2025-02-14'];
        $result = DateValidator::normalizeDates($input);

        //print_r($result); // Debug line

        $this->assertCount(3, $result);
        $this->assertContains('2024-12-25', $result);
        $this->assertContains('2025-01-01', $result);
        $this->assertContains('2025-02-14', $result);
    }

    /**
     * Test: normalizeDates returns unique and sorted-like array
     */
    public function testNormalizeDatesReturnsArrayWithUniqueValues(): void
    {
        $input = ['2025-01-01', '2024-12-25', '2025-01-01', '2024-12-25'];
        $result = DateValidator::normalizeDates($input);

        $this->assertCount(2, $result);
        $this->assertEqual(count(array_unique($result)), 2);
    }

    /**
     * Test: isValidDate with edge cases
     */
    public function testIsValidDateEdgeCases(): void
    {
        // Valid edge case: Feb 29, 2020 (leap year)
        $this->assertTrue(DateValidator::isValidDate('2020-02-29'));

        // Invalid edge case: Feb 29, 2021 (not leap year)
        $this->assertFalse(DateValidator::isValidDate('2021-02-29'));

        // Valid: Last day of year
        $this->assertTrue(DateValidator::isValidDate('2024-12-31'));

        // Valid: First day of year
        $this->assertTrue(DateValidator::isValidDate('2024-01-01'));
    }

    /**
     * Helper: Assert two values are equal
     */
    private function assertEqual($actual, $expected): void
    {
        $this->assertEquals($expected, $actual);
    }
}
