-- Migration: Add notification_date and cancellation_date to bookings table
-- Run this SQL to add the new columns

ALTER TABLE bookings ADD COLUMN notification_date DATE NULL AFTER payment_plan;
ALTER TABLE bookings ADD COLUMN cancellation_date DATE NULL AFTER notification_date;
