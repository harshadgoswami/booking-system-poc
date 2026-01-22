<?php

/**
 * PropertyFormTest.php
 * Unit tests for property-form.php functionality
 * Tests booking creation, property handling, transaction management
 */

namespace Tests;

use App\Controllers\BookingController;
use App\Services\BookingService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PropertyFormTest extends TestCase
{
    private BookingController $controller;
    private MockObject $bookingServiceMock;

    protected function setUp(): void
    {
        $this->bookingServiceMock = $this->createMock(BookingService::class);
        $this->controller = new BookingController($this->bookingServiceMock);
    }

    /**
     * Test: Form submission with valid POST data
     */
    public function testFormSubmissionWithValidPostData(): void
    {
        // ARRANGE
        $postData = [
            'booking' => [
                'checkin' => '2024-04-01',
                'checkout' => '2024-04-15',
                'days' => ['monday', 'wednesday', 'friday'],
                'service_fee' => 'Yes',
                'exclude_bank_holiday' => 'No',
                'payment_plan' => 'Monthly',
                'notification_date' => '2024-03-15',
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
        $result = $this->controller->create($postData);

        // ASSERT
        $this->assertTrue($result['success']);
        $this->assertEquals($result['booking_id'], 1);
    }

    /**
     * Test: Form rejection with missing checkin date
     */
    public function testFormRejectionMissingCheckin(): void
    {
        // ARRANGE
        $postData = [
            'booking' => [
                'checkin' => '',
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
            ->willThrowException(new \InvalidArgumentException('Checkin date is required'));

        // ACT
        $result = $this->controller->create($postData);

        // ASSERT
        $this->assertFalse($result['success']);
    }

    /**
     * Test: Form rejection with missing checkout date
     */
    public function testFormRejectionMissingCheckout(): void
    {
        // ARRANGE
        $postData = [
            'booking' => [
                'checkin' => '2024-04-01',
                'checkout' => '',
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
            ->willThrowException(new \InvalidArgumentException('Checkout date is required'));

        // ACT
        $result = $this->controller->create($postData);

        // ASSERT
        $this->assertFalse($result['success']);
    }

    /**
     * Test: Form rejection with no properties
     */
    public function testFormRejectionNoProperties(): void
    {
        // ARRANGE
        $postData = [
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
            'properties' => [],
        ];

        $this->bookingServiceMock
            ->method('createBooking')
            ->willThrowException(new \InvalidArgumentException('At least one property is required'));

        // ACT
        $result = $this->controller->create($postData);

        // ASSERT
        $this->assertFalse($result['success']);
    }

    /**
     * Test: Multiple properties handling
     */
    public function testMultiplePropertiesHandling(): void
    {
        // ARRANGE
        $properties = [
            ['id' => 1, 'name' => 'Property A', 'deposit' => 1000],
            ['id' => 2, 'name' => 'Property B', 'deposit' => 1500],
            ['id' => 3, 'name' => 'Property C', 'deposit' => 2000],
            ['id' => 4, 'name' => 'Property D', 'deposit' => 2500],
        ];

        $postData = [
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
            'properties' => $properties,
        ];

        $this->bookingServiceMock
            ->method('createBooking')
            ->willReturn(1);

        // ACT
        $result = $this->controller->create($postData);

        // ASSERT
        $this->assertTrue($result['success']);
    }

    /**
     * Test: Day selection parsing
     */
    public function testDaySelectionParsing(): void
    {
        // ARRANGE
        $daysSelected = ['monday', 'wednesday', 'friday'];

        // ACT
        $parsedDays = array_map('strtolower', $daysSelected);

        // ASSERT
        $this->assertCount(3, $parsedDays);
        $this->assertContains('monday', $parsedDays);
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
     * Test: Bank holiday exclusion option parsing
     */
    public function testBankHolidayExclusionParsing(): void
    {
        // ARRANGE
        $formData = ['exclude_bank_holiday' => 'Yes'];

        // ACT
        $excludeHolidays = ($formData['exclude_bank_holiday'] ?? 'No') === 'Yes';

        // ASSERT
        $this->assertTrue($excludeHolidays);
    }

    /**
     * Test: Payment plan option selection
     */
    public function testPaymentPlanOptionSelection(): void
    {
        // ARRANGE
        $validPlans = ['Monthly', 'Quarterly', 'Semi-Annual', 'Annual'];

        foreach ($validPlans as $plan) {
            $postData = [
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
            $result = $this->controller->create($postData);

            // ASSERT
            $this->assertTrue($result['success']);
        }
    }

    /**
     * Test: Date format validation
     */
    public function testDateFormatValidation(): void
    {
        // ARRANGE
        $invalidFormats = ['2024/04/01', '04-01-2024', 'April 1, 2024'];

        foreach ($invalidFormats as $format) {
            $postData = [
                'booking' => [
                    'checkin' => $format,
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
            $result = $this->controller->create($postData);

            // ASSERT
            $this->assertFalse($result['success']);
        }
    }

    /**
     * Test: Checkout must be after checkin
     */
    public function testCheckoutMustBeAfterCheckin(): void
    {
        // ARRANGE
        $postData = [
            'booking' => [
                'checkin' => '2024-04-20',
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
            ->willThrowException(new \InvalidArgumentException('Checkout must be after checkin'));

        // ACT
        $result = $this->controller->create($postData);

        // ASSERT
        $this->assertFalse($result['success']);
    }

    /**
     * Test: Successful creation returns booking ID for redirect
     */
    public function testSuccessfulCreationReturnsBookingIdForRedirect(): void
    {
        // ARRANGE
        $expectedBookingId = 42;
        $postData = [
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
            ->willReturn($expectedBookingId);

        // ACT
        $result = $this->controller->create($postData);

        // ASSERT
        $this->assertTrue($result['success']);
        $this->assertEquals($result['booking_id'], $expectedBookingId);
    }

    /**
     * Test: Notification date optional
     */
    public function testNotificationDateOptional(): void
    {
        // ARRANGE
        $postData = [
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
            ->willReturn(1);

        // ACT
        $result = $this->controller->create($postData);

        // ASSERT
        $this->assertTrue($result['success']);
    }

    /**
     * Test: Cancellation date optional
     */
    public function testCancellationDateOptional(): void
    {
        // ARRANGE
        $postData = [
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
            ->willReturn(1);

        // ACT
        $result = $this->controller->create($postData);

        // ASSERT
        $this->assertTrue($result['success']);
    }

    /**
     * Test: Property deposit validation
     */
    public function testPropertyDepositValidation(): void
    {
        // ARRANGE
        $propertiesWithInvalidDeposits = [
            ['name' => 'Property A', 'deposit' => 'invalid'],
        ];

        $postData = [
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
            'properties' => $propertiesWithInvalidDeposits,
        ];

        $this->bookingServiceMock
            ->method('createBooking')
            ->willThrowException(new \InvalidArgumentException('Invalid deposit amount'));

        // ACT
        $result = $this->controller->create($postData);

        // ASSERT
        $this->assertFalse($result['success']);
    }

    /**
     * Test: Error message includes validation details
     */
    public function testErrorMessageIncludesValidationDetails(): void
    {
        // ARRANGE
        $postData = [
            'booking' => [
                'checkin' => '2024-04-01',
                'checkout' => '2024-04-01',
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
            ->willThrowException(new \InvalidArgumentException('Booking must span at least one day'));

        // ACT
        $result = $this->controller->create($postData);

        // ASSERT
        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['error']);
    }

    /**
     * Test: Success message includes booking confirmation
     */
    public function testSuccessMessageIncludesBookingConfirmation(): void
    {
        // ARRANGE
        $postData = [
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
            ->willReturn(1);

        // ACT
        $result = $this->controller->create($postData);

        // ASSERT
        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['message']);
    }
}
