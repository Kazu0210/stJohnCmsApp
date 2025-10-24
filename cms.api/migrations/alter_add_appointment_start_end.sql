-- Migration: add appointment_start_time and appointment_end_time to appointments
-- Run this in your MySQL client (phpMyAdmin, mysql CLI, etc.)

ALTER TABLE `appointments`
  ADD COLUMN `appointment_start_time` TIME DEFAULT NULL,
  ADD COLUMN `appointment_end_time` TIME DEFAULT NULL;

-- Optional: backfill existing 'time' values into appointment_start_time
UPDATE `appointments` SET appointment_start_time = `time` WHERE appointment_start_time IS NULL AND `time` IS NOT NULL;

-- You may want to later remove the legacy `time` column after switching all code to the new fields.
