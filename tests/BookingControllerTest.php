<?php

/**
 * BookingControllerTest.php
 * Unit tests for BookingController
 * Tests index() and create() methods used in index.php and property-form.php
 */

namespace Tests;

use App\Controllers\BookingController;
use App\Services\BookingService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BookingControllerTest extends TestCase
{
    private BookingController $controller;
    private MockObject $bookingServiceMock;

    protected function setUp(): void
    {
        $this->bookingServiceMock = $this->createMock(BookingService::class);
        $this->controller = new BookingController($this->bookingServiceMock);
    }

    /**
     * Test: index() returns all bookings successfully
     */
    public function testIndexReturnsAllBookingsSuccessfully(): void
    {
        // ARRANGE
        $expectedBookings = [
            ['id' => 1, 'checkin' => '2024-01-15', 'checkout' => '2024-01-20'],
            ['id' => 2, 'checkin' => '2024-02-01', 'checkout' => '2024-02-10'],
            ['id' => 3, 'checkin' => '2024-03-05', 'checkout' => '2024-03-15'],
        ];

        $this->bookingServiceMock
            ->method('getAllBookings')
            ->willReturn($expectedBookings);

        // ACT
        $result = $this->controller->index();

        // ASSERT
        $this->assertIsArray($result);
        $this->assertArrayHasKey('bookings', $result);
        $this->assertCount(3, $result['bookings']);
        $this->assertEquals($result['bookings'], $expectedBookings);
    }

    /**
     * Test: index() returns empty array when no bookings exist
     */
    public function testIndexReturnsEmptyArrayWhenNoBookings(): void
    {
        // ARRANGE
        $this->bookingServiceMock
            ->method('getAllBookings')
            ->willReturn([]);

        // ACT
        $result = $this->controller->index();

        // ASSERT
        $this->assertIsArray($result);
        $this->assertEmpty($result['bookings']);
    }

    /**
     * Test: index() handles service exceptions and returns error
     */
    public function testIndexHandlesServiceException(): void
    {
        // ARRANGE
        $this->bookingServiceMock
            ->method('getAllBookings')
            ->willThrowException(new \Exception('Database error'));

        // ACT
        $result = $this->controller->index();

        // ASSERT
        $this->assertIsArray($result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('Database error', implode(' ', $result['errors']));
    }

    /**
     * Test: create() successfully creates booking with properties
     */
    public function testCreateSuccessfullyCreatesBooking(): void
    {
        // ARRANGE
        $bookingData = [
            'booking' => [
                'checkin' => '2024-04-01',
                'checkout' => '2024-04-15',
                'days' => ['monday', 'tuesday'],
                'service_fee' => 'Yes',
                'exclude_bank_holiday' => 'No',
                'payment_plan' => 'Monthly',
                'notification_date' => '2024-03-15',
                'cancellation_date' => '',
            ],
            'properties' => [
                ['name' => 'Property A', 'deposit' => 1000],
                ['name' => 'Property B', 'deposit' => 1500],
            ],
        ];

        $this->bookingServiceMock
            ->method('createBooking')
            ->willReturn(5);

        // ACT
        $result = $this->controller->create($bookingData);

        // ASSERT
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('booking_id', $result);
        $this->assertEquals($result['booking_id'], 5);
    }

    /**
     * Test: create() returns error when booking data is invalid
     */
    public function testCreateReturnsErrorOnInvalidData(): void
    {
        // ARRANGE
        $invalidData = [
            'booking' => [
                'checkin' => '',  // Invalid: empty checkin
                'checkout' => '2024-04-15',
            ],
            'properties' => [],
        ];

        $this->bookingServiceMock
            ->method('createBooking')
            ->willThrowException(new \InvalidArgumentException('Invalid checkin date'));

        // ACT
        $result = $this->controller->create($invalidData);

        // ASSERT
        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Invalid', $result['error']);
    }

    /**
     * Test: create() handles missing properties gracefully
     */
    public function testCreateHandlesMissingProperties(): void
    {
        // ARRANGE
        $bookingData = [
            'booking' => [
                'checkin' => '2024-04-01',
                'checkout' => '2024-04-15',
                'days' => [],
                'service_fee' => 'No',
                'exclude_bank_holiday' => 'No',
                'payment_plan' => 'Monthly',
                'notification_date' => '',
                'cancellation_date' => '',
            ],
            'properties' => [],  // No properties
        ];

        $this->bookingServiceMock
            ->method('createBooking')
            ->willThrowException(new \InvalidArgumentException('At least one property required'));

        // ACT
        $result = $this->controller->create($bookingData);

        // ASSERT
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('property', strtolower($result['error']));
    }

    /**
     * Test: create() validates date format
     */
    public function testCreateValidatesDateFormat(): void
    {
        // ARRANGE
        $bookingData = [
            'booking' => [
                'checkin' => 'invalid-date',  // Invalid format
                'checkout' => '2024-04-15',
                'days' => [],
                'service_fee' => 'No',
                'exclude_bank_holiday' => 'No',
                'payment_plan' => 'Monthly',
                'notification_date' => '',
                'cancellation_date' => '',
            ],
            'properties' => [
                ['name' => 'Property A', 'deposit' => 1000],
            ],
        ];

        $this->bookingServiceMock
            ->method('createBooking')
            ->willThrowException(new \InvalidArgumentException('Invalid date format'));

        // ACT
        $result = $this->controller->create($bookingData);

        // ASSERT
        $this->assertFalse($result['success']);
    }

    /**
     * Test: create() validates checkout after checkin
     */
    public function testCreateValidatesCheckoutAfterCheckin(): void
    {
        // ARRANGE
        $bookingData = [
            'booking' => [
                'checkin' => '2024-04-15',
                'checkout' => '2024-04-01',  // Before checkin!
                'days' => [],
                'service_fee' => 'No',
                'exclude_bank_holiday' => 'No',
                'payment_plan' => 'Monthly',
                'notification_date' => '',
                'cancellation_date' => '',
            ],
            'properties' => [
                ['name' => 'Property A', 'deposit' => 1000],
            ],
        ];

        $this->bookingServiceMock
            ->method('createBooking')
            ->willThrowException(new \InvalidArgumentException('Checkout must be after checkin'));

        // ACT
        $result = $this->controller->create($bookingData);

        // ASSERT
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('checkout', strtolower($result['error']));
    }

    /**
     * Test: create() accepts valid payment plans
     */
    public function testCreateAcceptsValidPaymentPlans(): void
    {
        // ARRANGE
        $paymentPlans = ['Monthly', 'Quarterly', 'Semi-Annual', 'Annual'];

        foreach ($paymentPlans as $plan) {
            $bookingData = [
                'booking' => [
                    'checkin' => '2024-04-01',
                    'checkout' => '2024-04-15',
                    'days' => [],
                    'service_fee' => 'No',
                    'exclude_bank_holiday' => 'No',
                    'payment_plan' => $plan,
                    'notification_date' => '',
                    'cancellation_date' => '',
                ],
                'properties' => [
                    ['name' => 'Property A', 'deposit' => 1000],
                ],
            ];

            $this->bookingServiceMock
                ->method('createBooking')
                ->willReturn(1);

            // ACT
            $result = $this->controller->create($bookingData);

            // ASSERT
            $this->assertTrue($result['success']);
        }
    }

    /**
     * Test: create() handles service fee option
     */
    public function testCreateHandlesServiceFeeOption(): void
    {
        // ARRANGE
        $bookingData = [
            'booking' => [
                'checkin' => '2024-04-01',
                'checkout' => '2024-04-15',
                'days' => [],
                'service_fee' => 'Yes',
                'exclude_bank_holiday' => 'No',
                'payment_plan' => 'Monthly',
                'notification_date' => '',
                'cancellation_date' => '',
            ],
            'properties' => [
                ['name' => 'Property A', 'deposit' => 1000],
            ],
        ];

        $this->bookingServiceMock
            ->method('createBooking')
            ->willReturn(1);

        // ACT
        $result = $this->controller->create($bookingData);

        // ASSERT
        $this->assertTrue($result['success']);
    }

    /**
     * Test: create() handles bank holiday exclusion option
     */
    public function testCreateHandlesBankHolidayExclusion(): void
    {
        // ARRANGE
        $bookingData = [
            'booking' => [
                'checkin' => '2024-04-01',
                'checkout' => '2024-04-15',
                'days' => [],
                'service_fee' => 'No',
                'exclude_bank_holiday' => 'Yes',
                'payment_plan' => 'Monthly',
                'notification_date' => '',
                'cancellation_date' => '',
            ],
            'properties' => [
                ['name' => 'Property A', 'deposit' => 1000],
            ],
        ];

        $this->bookingServiceMock
            ->method('createBooking')
            ->willReturn(1);

        // ACT
        $result = $this->controller->create($bookingData);

        // ASSERT
        $this->assertTrue($result['success']);
    }

    /**
     * Test: create() handles notification and cancellation dates
     */
    public function testCreateHandlesNotificationAndCancellationDates(): void
    {
        // ARRANGE
        $bookingData = [
            'booking' => [
                'checkin' => '2024-04-01',
                'checkout' => '2024-04-15',
                'days' => [],
                'service_fee' => 'No',
                'exclude_bank_holiday' => 'No',
                'payment_plan' => 'Monthly',
                'notification_date' => '2024-03-15',
                'cancellation_date' => '2024-03-20',
            ],
            'properties' => [
                ['name' => 'Property A', 'deposit' => 1000],
            ],
        ];

        $this->bookingServiceMock
            ->method('createBooking')
            ->willReturn(1);

        // ACT
        $result = $this->controller->create($bookingData);

        // ASSERT
        $this->assertTrue($result['success']);
    }

    /**
     * Test: create() returns booking ID for redirect
     */
    public function testCreateReturnsBookingIdForRedirect(): void
    {
        // ARRANGE
        $bookingData = [
            'booking' => [
                'checkin' => '2024-04-01',
                'checkout' => '2024-04-15',
                'days' => [],
                'service_fee' => 'No',
                'exclude_bank_holiday' => 'No',
                'payment_plan' => 'Monthly',
                'notification_date' => '',
                'cancellation_date' => '',
            ],
            'properties' => [
                ['name' => 'Property A', 'deposit' => 1000],
            ],
        ];

        $this->bookingServiceMock
            ->method('createBooking')
            ->willReturn(42);

        // ACT
        $result = $this->controller->create($bookingData);

        // ASSERT
        $this->assertTrue($result['success']);
        $this->assertEquals($result['booking_id'], 42);
        $this->assertArrayHasKey('message', $result);
    }
}
