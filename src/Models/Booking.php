<?php

/**
 * Booking.php
 * Booking model representing a booking entity
 */

namespace App\Models;

class Booking
{
    private ?int $id;
    private string $checkin;
    private string $checkout;
    private array $days = [];
    private string $serviceFee = 'No';
    private string $excludeBankHoliday = 'No';
    private string $paymentPlan = 'Monthly';
    private ?string $notificationDate = null;
    private ?string $cancellationDate = null;
    private ?string $createdAt = null;

    public function __construct(
        string $checkin,
        string $checkout,
        ?int $id = null,
        ?string $createdAt = null
    ) {
        $this->checkin = $checkin;
        $this->checkout = $checkout;
        $this->id = $id;
        $this->createdAt = $createdAt;
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getCheckin(): string
    {
        return $this->checkin;
    }
    public function getCheckout(): string
    {
        return $this->checkout;
    }
    public function getDays(): array
    {
        return $this->days;
    }
    public function getServiceFee(): string
    {
        return $this->serviceFee;
    }
    public function getExcludeBankHoliday(): string
    {
        return $this->excludeBankHoliday;
    }
    public function getPaymentPlan(): string
    {
        return $this->paymentPlan;
    }
    public function getNotificationDate(): ?string
    {
        return $this->notificationDate;
    }
    public function getCancellationDate(): ?string
    {
        return $this->cancellationDate;
    }
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    // Setters
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
    public function setCheckin(string $checkin): self
    {
        $this->checkin = $checkin;
        return $this;
    }
    public function setCheckout(string $checkout): self
    {
        $this->checkout = $checkout;
        return $this;
    }
    public function setDays(array $days): self
    {
        $this->days = $days;
        return $this;
    }
    public function setServiceFee(string $fee): self
    {
        $this->serviceFee = $fee;
        return $this;
    }
    public function setExcludeBankHoliday(string $exclude): self
    {
        $this->excludeBankHoliday = $exclude;
        return $this;
    }
    public function setPaymentPlan(string $plan): self
    {
        $this->paymentPlan = $plan;
        return $this;
    }
    public function setNotificationDate(?string $date): self
    {
        $this->notificationDate = $date;
        return $this;
    }
    public function setCancellationDate(?string $date): self
    {
        $this->cancellationDate = $date;
        return $this;
    }
    public function setCreatedAt(string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'checkin' => $this->checkin,
            'checkout' => $this->checkout,
            'days' => $this->days,
            'service_fee' => $this->serviceFee,
            'exclude_bank_holiday' => $this->excludeBankHoliday,
            'payment_plan' => $this->paymentPlan,
            'notification_date' => $this->notificationDate,
            'cancellation_date' => $this->cancellationDate,
            'created_at' => $this->createdAt,
        ];
    }
}
