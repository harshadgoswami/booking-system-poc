<?php

/**
 * Holiday.php
 * Holiday model with properties and methods
 */

namespace App\Models;

class Holiday
{
    private ?int $id;
    private string $holidayDate;
    private ?string $createdAt;

    public function __construct(string $holidayDate, ?int $id = null, ?string $createdAt = null)
    {
        $this->holidayDate = $holidayDate;
        $this->id = $id;
        $this->createdAt = $createdAt;
    }

    /**
     * Get holiday ID
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set holiday ID
     */
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get holiday date
     */
    public function getHolidayDate(): string
    {
        return $this->holidayDate;
    }

    /**
     * Set holiday date
     */
    public function setHolidayDate(string $holidayDate): self
    {
        $this->holidayDate = $holidayDate;
        return $this;
    }

    /**
     * Get creation timestamp
     */
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    /**
     * Set creation timestamp
     */
    public function setCreatedAt(string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Convert to array for easy serialization
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'holiday_date' => $this->holidayDate,
            'created_at' => $this->createdAt,
        ];
    }
}
