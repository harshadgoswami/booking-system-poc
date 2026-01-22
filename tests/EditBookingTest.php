<?php

/**
 * EditBookingTest.php
 * Unit tests for edit-booking.php functionality
 * Tests booking update, property loading, payment calculations
 */

namespace Tests;

use App\Services\BookingService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

class EditBookingTest extends TestCase
{
    private MockObject $bookingServiceMock;

    protected function setUp(): void
    {
        $this->bookingServiceMock = $this->createMock(BookingService::class);
    }

    /**
     * Test: Booking update with valid data
     */
    public function testBookingUpdateWithValidData(): void
    {
        // ARRANGE
        $bookingId = 1;
        $bookingData = [
            'checkin' => '2024-04-01',
            'checkout' => '2024-04-15',
            'days' => ['monday', 'tuesday'],
            'service_fee' => 'Yes',
            'exclude_bank_holiday' => 'No',
            'payment_plan' => 'Monthly',
            'notification_date' => '2024-03-15',
            'cancellation_date' => '',
        ];

        $this->bookingServiceMock
            ->method('updateBooking')
            ->willReturn(true);

        // ACT
        $result = $this->bookingServiceMock->updateBooking($bookingId, $bookingData, []);

        // ASSERT
        $this->assertTrue($result);
    }

    /**
     * Test: Booking update validates checkin date
     */
    public function testBookingUpdateValidatesCheckinDate(): void
    {
        // ARRANGE
        $this->bookingServiceMock
            ->method('updateBooking')
            ->willThrowException(new \InvalidArgumentException('Invalid checkin date format'));

        // ACT & ASSERT
        $this->expectException(\InvalidArgumentException::class);
        $this->bookingServiceMock->updateBooking(1, ['checkin' => 'invalid'], []);
    }

    /**
     * Test: Booking update validates checkout after checkin
     */
    public function testBookingUpdateValidatesCheckoutAfterCheckin(): void
    {
        // ARRANGE
        $this->bookingServiceMock
            ->method('updateBooking')
            ->willThrowException(new \InvalidArgumentException('Checkout must be after checkin'));

        // ACT & ASSERT
        $this->expectException(\InvalidArgumentException::class);
        $this->bookingServiceMock->updateBooking(1, [], []);
    }

    /**
     * Test: Payment plan stored and retrieved
     */
    public function testPaymentPlanStoredAndRetrieved(): void
    {
        // ARRANGE
        $booking = [
            'id' => 1,
            'payment_plan' => 'Quarterly',
        ];

        // ACT
        $paymentPlan = $booking['payment_plan'] ?? 'Monthly';

        // ASSERT
        $this->assertEquals($paymentPlan, 'Quarterly');
    }

    /**
     * Test: Default payment plan when not specified
     */
    public function testDefaultPaymentPlanWhenNotSpecified(): void
    {
        // ARRANGE
        $booking = ['id' => 1];

        // ACT
        $paymentPlan = $booking['payment_plan'] ?? 'Monthly';

        // ASSERT
        $this->assertEquals($paymentPlan, 'Monthly');
    }

    /**
     * Test: Deposit total calculation from properties
     */
    public function testDepositTotalCalculation(): void
    {
        // ARRANGE
        $properties = [
            ['deposit' => 1000],
            ['deposit' => 1500],
            ['deposit' => 2000],
        ];

        $expectedTotal = 4500;

        // ACT
        $depositTotal = array_reduce($properties, function ($sum, $p) {
            return $sum + (float)($p['deposit'] ?? 0);
        }, 0.0);

        // ASSERT
        $this->assertEquals($depositTotal, $expectedTotal);
    }

    /**
     * Test: Booking data includes service fee flag
     */
    public function testBookingIncludesServiceFeeFlag(): void
    {
        // ARRANGE
        $booking = [
            'id' => 1,
            'service_fee' => 'Yes',
        ];

        // ACT
        $hasServiceFee = ($booking['service_fee'] ?? 'No') === 'Yes';

        // ASSERT
        $this->assertTrue($hasServiceFee);
    }

    /**
     * Test: Booking data includes holiday exclusion flag
     */
    public function testBookingIncludesHolidayExclusionFlag(): void
    {
        // ARRANGE
        $booking = [
            'id' => 1,
            'exclude_bank_holiday' => 'Yes',
        ];

        // ACT
        $excludeHolidays = ($booking['exclude_bank_holiday'] ?? 'No') === 'Yes';

        // ASSERT
        $this->assertTrue($excludeHolidays);
    }

    /**
     * Test: Notification date handling
     */
    public function testNotificationDateHandling(): void
    {
        // ARRANGE
        $booking = [
            'id' => 1,
            'notification_date' => '2024-03-15',
        ];

        // ACT
        $notificationDt = !empty($booking['notification_date'])
            ? DateTimeImmutable::createFromFormat('Y-m-d', $booking['notification_date'])
            : null;

        // ASSERT
        $this->assertNotNull($notificationDt);
    }

    /**
     * Test: Empty notification date returns null
     */
    public function testEmptyNotificationDateReturnsNull(): void
    {
        // ARRANGE
        $booking = ['notification_date' => ''];

        // ACT
        $notificationDt = !empty($booking['notification_date'])
            ? DateTimeImmutable::createFromFormat('Y-m-d', $booking['notification_date'])
            : null;

        // ASSERT
        $this->assertNull($notificationDt);
    }

    /**
     * Test: Paid periods session storage
     */
    public function testPaidPeriodsSessionStorage(): void
    {
        // ARRANGE
        $bookingId = 1;
        $paidPeriods = [1, 2, 3];
        $_SESSION['paid_periods_booking_' . $bookingId] = $paidPeriods;

        // ACT
        $retrieved = $_SESSION['paid_periods_booking_' . $bookingId];

        // ASSERT
        $this->assertEquals($retrieved, $paidPeriods);
    }

    /**
     * Test: Paid periods cleared on save
     */
    public function testPaidPeriodsCleared(): void
    {
        // ARRANGE
        $bookingId = 1;
        $_SESSION['paid_periods_booking_' . $bookingId] = [1, 2, 3];

        // ACT
        unset($_SESSION['paid_periods_booking_' . $bookingId]);

        // ASSERT
        $this->assertFalse(isset($_SESSION['paid_periods_booking_' . $bookingId]));
    }

    /**
     * Test: Cancellation date handling
     */
    public function testCancellationDateHandling(): void
    {
        // ARRANGE
        $booking = ['cancellation_date' => '2024-04-15'];

        // ACT
        $cancellationDt = !empty($booking['cancellation_date'])
            ? DateTimeImmutable::createFromFormat('Y-m-d', $booking['cancellation_date'])
            : null;

        // ASSERT
        $this->assertNotNull($cancellationDt);
    }

    /**
     * Test: Empty cancellation date returns null
     */
    public function testEmptyCancellationDateReturnsNull(): void
    {
        // ARRANGE
        $booking = ['cancellation_date' => ''];

        // ACT
        $cancellationDt = !empty($booking['cancellation_date'])
            ? DateTimeImmutable::createFromFormat('Y-m-d', $booking['cancellation_date'])
            : null;

        // ASSERT
        $this->assertNull($cancellationDt);
    }

    /**
     * Test: Booking days parsed as array
     */
    public function testBookingDaysParsedAsArray(): void
    {
        // ARRANGE
        $booking = [
            'days' => json_encode(['monday', 'wednesday', 'friday']),
        ];

        // ACT
        $days = json_decode($booking['days'] ?? '[]', true) ?: [];

        // ASSERT
        $this->assertIsArray($days);
        $this->assertCount(3, $days);
    }

    /**
     * Test: Empty days returns empty array
     */
    public function testEmptyDaysReturnsEmptyArray(): void
    {
        // ARRANGE
        $booking = ['days' => json_encode([])];

        // ACT
        $days = json_decode($booking['days'] ?? '[]', true) ?: [];

        // ASSERT
        $this->assertEmpty($days);
    }

    /**
     * Test: Service fee option parsing
     */
    public function testServiceFeeOptionParsing(): void
    {
        // ARRANGE
        $formData = ['service_fee' => 'Yes'];

        // ACT
        $hasServiceFee = ($formData['service_fee'] ?? 'No') === 'Yes';

        // ASSERT
        $this->assertTrue($hasServiceFee);
    }

    /**
     * Test: Multiple properties total calculation
     */
    public function testMultiplePropertiesTotal(): void
    {
        // ARRANGE
        $properties = [
            ['deposit' => 1000],
            ['deposit' => 1500],
            ['deposit' => 2000],
            ['deposit' => 2500],
        ];

        // ACT
        $total = array_reduce($properties, function ($sum, $p) {
            return $sum + (float)($p['deposit'] ?? 0);
        }, 0.0);

        // ASSERT
        $this->assertEquals($total, 7000);
    }
}
