<?php

/**
 * BookingController.php
 * Handles HTTP requests for booking management
 */

namespace App\Controllers;

use App\Services\BookingService;

class BookingController
{
    private BookingService $bookingService;
    private array $errors = [];
    private array $successes = [];

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /**
     * Display all bookings
     */
    public function index(): array
    {
        try {
            $bookings = $this->bookingService->getAllBookings();
        } catch (\Exception $e) {
            $this->errors[] = 'Failed to load bookings: ' . $e->getMessage();
            $bookings = [];
        }

        return [
            'bookings' => $bookings,
            'errors' => $this->errors,
            'successes' => $this->successes,
        ];
    }

    /**
     * Display create booking form
     */
    public function createForm(): array
    {
        return [
            'errors' => $this->errors,
            'successes' => $this->successes,
        ];
    }

    /**
     * Handle booking creation
     */
    public function create(array $formData): array
    {
        try {
            $bookingId = $this->bookingService->createBooking(
                $formData['booking'] ?? [],
                $formData['properties'] ?? []
            );

            return [
                'success' => true,
                'booking_id' => $bookingId,
                'message' => 'Booking created successfully.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Display edit booking form
     */
    public function editForm(int $bookingId): array
    {
        try {
            $booking = $this->bookingService->getBooking($bookingId);
            if (!$booking) {
                $this->errors[] = 'Booking not found.';
                return ['booking' => null, 'properties' => [], 'errors' => $this->errors];
            }

            $properties = $this->bookingService->getBookingProperties($bookingId);

            return [
                'booking' => $booking,
                'properties' => $properties,
                'errors' => $this->errors,
            ];
        } catch (\Exception $e) {
            $this->errors[] = 'Failed to load booking: ' . $e->getMessage();
            return ['booking' => null, 'properties' => [], 'errors' => $this->errors];
        }
    }

    /**
     * Handle booking update
     */
    public function update(int $bookingId, array $formData): array
    {
        try {
            $this->bookingService->updateBooking(
                $bookingId,
                $formData['booking'] ?? [],
                $formData['properties'] ?? []
            );

            return [
                'success' => true,
                'message' => 'Booking updated successfully.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Add error
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
     * Get all successes
     */
    public function getSuccesses(): array
    {
        return $this->successes;
    }
}
