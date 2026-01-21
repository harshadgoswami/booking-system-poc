<?php

/**
 * Property.php
 * Property model representing a property associated with a booking
 */

namespace App\Models;

class Property
{
    private ?int $id;
    private int $bookingId;
    private string $title;
    private float $nightPrice;
    private float $deposit;
    private ?string $checkoutDate = null;
    private string $isCancelled = 'No';
    private int $notifyDay = 0;
    private ?string $createdAt = null;

    public function __construct(
        int $bookingId,
        string $title,
        float $nightPrice = 0.0,
        float $deposit = 0.0,
        ?int $id = null,
        ?string $createdAt = null
    ) {
        $this->bookingId = $bookingId;
        $this->title = $title;
        $this->nightPrice = $nightPrice;
        $this->deposit = $deposit;
        $this->id = $id;
        $this->createdAt = $createdAt;
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getBookingId(): int
    {
        return $this->bookingId;
    }
    public function getTitle(): string
    {
        return $this->title;
    }
    public function getNightPrice(): float
    {
        return $this->nightPrice;
    }
    public function getDeposit(): float
    {
        return $this->deposit;
    }
    public function getCheckoutDate(): ?string
    {
        return $this->checkoutDate;
    }
    public function getIsCancelled(): string
    {
        return $this->isCancelled;
    }
    public function getNotifyDay(): int
    {
        return $this->notifyDay;
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
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }
    public function setNightPrice(float $price): self
    {
        $this->nightPrice = $price;
        return $this;
    }
    public function setDeposit(float $deposit): self
    {
        $this->deposit = $deposit;
        return $this;
    }
    public function setCheckoutDate(?string $date): self
    {
        $this->checkoutDate = $date;
        return $this;
    }
    public function setIsCancelled(string $cancelled): self
    {
        $this->isCancelled = $cancelled;
        return $this;
    }
    public function setNotifyDay(int $day): self
    {
        $this->notifyDay = $day;
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
            'booking_id' => $this->bookingId,
            'title' => $this->title,
            'night_price' => $this->nightPrice,
            'deposit' => $this->deposit,
            'checkout_date' => $this->checkoutDate,
            'is_cancelled' => $this->isCancelled,
            'notify_day' => $this->notifyDay,
            'created_at' => $this->createdAt,
        ];
    }
}
