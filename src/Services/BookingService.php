<?php

/**
 * BookingService.php
 * Business logic for booking management
 */

namespace App\Services;

use App\Models\Booking;
use App\Models\Property;
use App\Repositories\BookingRepository;
use App\Repositories\PropertyRepository;
use App\Utils\BookingValidator;
use App\Utils\PropertyValidator;

class BookingService
{
    private BookingRepository $bookingRepository;
    private PropertyRepository $propertyRepository;

    public function __construct(
        BookingRepository $bookingRepository,
        PropertyRepository $propertyRepository
    ) {
        $this->bookingRepository = $bookingRepository;
        $this->propertyRepository = $propertyRepository;
    }

    /**
     * Create a new booking with properties
     */
    public function createBooking(array $data, array $properties): int
    {
        // Validate
        $errors = $this->validateBookingData($data, $properties);
        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(' ', $errors));
        }

        // Normalize data
        $normalized = $this->normalizeBookingData($data);

        // Create booking object
        $booking = new Booking($normalized['checkin'], $normalized['checkout']);
        $booking
            ->setDays($normalized['days'])
            ->setServiceFee($normalized['service_fee'])
            ->setExcludeBankHoliday($normalized['exclude_bank_holiday'])
            ->setPaymentPlan($normalized['payment_plan'])
            ->setNotificationDate($normalized['notification_date'])
            ->setCancellationDate($normalized['cancellation_date']);

        // Save booking
        $bookingId = $this->bookingRepository->save($booking);

        // Save properties
        if (!empty($properties)) {
            $propertyObjects = [];
            foreach ($properties as $propData) {
                $normalized = PropertyValidator::normalizeProperty($propData);
                $prop = new Property(
                    $bookingId,
                    $normalized['title'],
                    $normalized['night_price'],
                    $normalized['deposit']
                );
                $prop
                    ->setCheckoutDate($normalized['checkout_date'])
                    ->setIsCancelled($normalized['is_cancelled'])
                    ->setNotifyDay($normalized['notify_day']);
                $propertyObjects[] = $prop;
            }
            $this->propertyRepository->saveBatch($propertyObjects);
        }

        return $bookingId;
    }

    /**
     * Update existing booking with properties
     */
    public function updateBooking(int $bookingId, array $data, array $properties): bool
    {
        // Validate
        $errors = $this->validateBookingData($data, $properties);
        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(' ', $errors));
        }

        // Normalize data
        $normalized = $this->normalizeBookingData($data);

        // Load and update booking
        $booking = $this->bookingRepository->findById($bookingId);
        if (!$booking) {
            throw new \Exception('Booking not found');
        }

        $booking
            ->setCheckin($normalized['checkin'])
            ->setCheckout($normalized['checkout'])
            ->setDays($normalized['days'])
            ->setServiceFee($normalized['service_fee'])
            ->setExcludeBankHoliday($normalized['exclude_bank_holiday'])
            ->setPaymentPlan($normalized['payment_plan'])
            ->setNotificationDate($normalized['notification_date'])
            ->setCancellationDate($normalized['cancellation_date']);

        // Update booking
        $this->bookingRepository->update($booking);

        // Replace properties
        $this->propertyRepository->deleteByBookingId($bookingId);

        if (!empty($properties)) {
            $propertyObjects = [];
            foreach ($properties as $propData) {
                $normalized = PropertyValidator::normalizeProperty($propData);
                $prop = new Property(
                    $bookingId,
                    $normalized['title'],
                    $normalized['night_price'],
                    $normalized['deposit']
                );
                $prop
                    ->setCheckoutDate($normalized['checkout_date'])
                    ->setIsCancelled($normalized['is_cancelled'])
                    ->setNotifyDay($normalized['notify_day']);
                $propertyObjects[] = $prop;
            }
            $this->propertyRepository->saveBatch($propertyObjects);
        }

        return true;
    }

    /**
     * Get booking by ID
     */
    public function getBooking(int $bookingId): ?Booking
    {
        return $this->bookingRepository->findById($bookingId);
    }

    /**
     * Get all bookings
     */
    public function getAllBookings(): array
    {
        return $this->bookingRepository->findAllWithPropertyCount();
    }

    /**
     * Get properties for booking
     */
    public function getBookingProperties(int $bookingId): array
    {
        return $this->propertyRepository->findAllRawByBookingId($bookingId);
    }

    /**
     * Validate booking data
     */
    private function validateBookingData(array $data, array $properties): array
    {
        $errors = [];

        $checkin = trim((string)($data['checkin'] ?? ''));
        $checkout = trim((string)($data['checkout'] ?? ''));

        // Validate dates
        $errors = array_merge($errors, BookingValidator::validateDates($checkin, $checkout));

        // Validate optional dates
        try {
            BookingValidator::validateNotificationDate($data['notification_date'] ?? '');
            BookingValidator::validateCancellationDate($data['cancellation_date'] ?? '');
        } catch (\InvalidArgumentException $e) {
            $errors[] = $e->getMessage();
        }

        // Validate properties
        if (empty($properties)) {
            $errors[] = 'At least one property is required.';
        } else {
            foreach ($properties as $idx => $prop) {
                $errors = array_merge($errors, PropertyValidator::validateProperty($prop, $idx));
            }
        }

        return $errors;
    }

    /**
     * Normalize and sanitize booking data
     */
    private function normalizeBookingData(array $data): array
    {
        return [
            'checkin' => trim((string)($data['checkin'] ?? '')),
            'checkout' => trim((string)($data['checkout'] ?? '')),
            'days' => BookingValidator::validateDays($data['days'] ?? []),
            'service_fee' => BookingValidator::validateServiceFee($data['service_fee'] ?? 'No'),
            'exclude_bank_holiday' => BookingValidator::validateExcludeBankHoliday($data['exclude_bank_holiday'] ?? 'No'),
            'payment_plan' => BookingValidator::validatePaymentPlan($data['payment_plan'] ?? 'Monthly'),
            'notification_date' => BookingValidator::validateNotificationDate($data['notification_date'] ?? ''),
            'cancellation_date' => BookingValidator::validateCancellationDate($data['cancellation_date'] ?? ''),
        ];
    }
}
