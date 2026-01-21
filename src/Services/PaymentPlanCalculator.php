<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DateTime;
use DateInterval;
use DatePeriod;

/**
 * PaymentPlanCalculator
 * Handles all payment plan period calculations and refund logic
 * Extracted from edit-booking.php for modularity
 */
class PaymentPlanCalculator
{
    /**
     * Count eligible nights in a date range considering weekday selection and holidays
     */
    public static function countEligibleNights(
        DateTimeImmutable|DateTime $from,
        DateTimeImmutable|DateTime $to,
        array $selectedDays,
        array $holidays = []
    ): int {
        if ($to <= $from) return 0;

        $from = $from instanceof DateTime ? DateTimeImmutable::createFromMutable($from) : $from;
        $to = $to instanceof DateTime ? DateTimeImmutable::createFromMutable($to) : $to;

        $weekdayKeys = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
        $period = new DatePeriod($from, new DateInterval('P1D'), $to);
        $count = 0;

        foreach ($period as $day) {
            $dayKey = $weekdayKeys[(int)$day->format('N') - 1];
            $ymd = $day->format('Y-m-d');

            if (!empty($selectedDays) && !in_array($dayKey, $selectedDays, true)) {
                continue;
            }
            if (!empty($holidays) && in_array($ymd, $holidays, true)) {
                continue;
            }

            $count++;
        }

        return $count;
    }

    /**
     * Calculate all payment periods based on payment plan type
     */
    public static function calculatePeriods(
        DateTime|DateTimeImmutable $checkin,
        DateTime|DateTimeImmutable $checkout,
        string $paymentPlan
    ): array {
        if ($checkout <= $checkin) return [];

        $checkin = $checkin instanceof DateTime ? DateTimeImmutable::createFromMutable($checkin) : $checkin;
        $checkout = $checkout instanceof DateTime ? DateTimeImmutable::createFromMutable($checkout) : $checkout;

        $periods = [];

        if ($paymentPlan === 'weekly') {
            $cursor = $checkin;
            while ($cursor < $checkout) {
                $end = $cursor->add(new DateInterval('P7D'));
                if ($end > $checkout) $end = $checkout;
                $periods[] = ['start' => $cursor, 'end' => $end];
                $cursor = $end;
            }
        } elseif ($paymentPlan === 'fortnighly') {
            $cursor = $checkin;
            while ($cursor < $checkout) {
                $end = $cursor->add(new DateInterval('P14D'));
                if ($end > $checkout) $end = $checkout;
                $periods[] = ['start' => $cursor, 'end' => $end];
                $cursor = $end;
            }
        } elseif ($paymentPlan === 'Monthly') {
            $cursor = $checkin;
            while ($cursor < $checkout) {
                $nextMonth = (new DateTimeImmutable($cursor->format('Y-m-01')))->modify('+1 month');
                $end = $nextMonth;
                if ($end > $checkout) $end = $checkout;
                $periods[] = ['start' => $cursor, 'end' => $end];
                $cursor = $end;
            }
        } else { // full
            $periods[] = ['start' => $checkin, 'end' => $checkout];
        }

        return $periods;
    }

    /**
     * Calculate per-period totals without cancellation
     */
    public static function calculatePeriodsNoCancel(
        array $periods,
        array $properties,
        bool $hasServiceFee,
        array $selectedDays,
        array $holidays,
        float $depositTotal
    ): array {
        $results = [];

        foreach ($periods as $periodIndex => $period) {
            $start = $period['start'];
            $end = $period['end'];
            $nights = self::countEligibleNights($start, $end, $selectedDays, $holidays);

            $totalPrice = 0.0;
            $totalServiceFee = 0.0;

            foreach ($properties as $prop) {
                $totalPrice += ($prop['night_price'] ?? 0) * $nights;
                if ($hasServiceFee) {
                    $totalServiceFee += $nights * 0.5;
                }
            }

            $results[] = [
                'start' => $start,
                'end' => $end,
                'nights' => $nights,
                'deposit' => ($periodIndex === 0) ? $depositTotal : 0.0,
                'service_fee' => $totalServiceFee,
                'final_total' => $totalPrice,
            ];
        }

        return $results;
    }

    /**
     * Calculate per-period totals with cancellation
     */
    public static function calculatePeriodsWithCancel(
        array $periods,
        array $properties,
        bool $hasServiceFee,
        array $selectedDays,
        array $holidays,
        float $depositTotal,
        DateTimeImmutable|DateTime|null $cancellationDate,
        DateTimeImmutable|DateTime|null $notificationDate
    ): array {
        if (!$cancellationDate) return [];

        $cancellationDate = $cancellationDate instanceof DateTime
            ? DateTimeImmutable::createFromMutable($cancellationDate)
            : $cancellationDate;

        $results = [];

        foreach ($periods as $periodIndex => $period) {
            $start = $period['start'];
            $end = $period['end'];

            $totalPrice = 0.0;
            $totalServiceFee = 0.0;

            foreach ($properties as $prop) {
                if (($prop['is_cancelled'] ?? 'No') === 'Yes') {
                    // Calculate effective cancellation end for this property
                    $effectiveCancelEnd = self::calculateEffectiveCancelEnd(
                        $prop,
                        $cancellationDate,
                        $notificationDate
                    );

                    if ($effectiveCancelEnd) {
                        $endForProp = $effectiveCancelEnd < $end ? $effectiveCancelEnd : $end;
                        $nights = self::countEligibleNights($start, $endForProp, $selectedDays, $holidays);
                    } else {
                        $nights = self::countEligibleNights($start, $end, $selectedDays, $holidays);
                    }
                } else {
                    $nights = self::countEligibleNights($start, $end, $selectedDays, $holidays);
                }

                $totalPrice += ($prop['night_price'] ?? 0) * $nights;
                if ($hasServiceFee) {
                    $totalServiceFee += $nights * 0.5;
                }
            }

            $results[] = [
                'start' => $start,
                'end' => $end,
                'deposit' => ($periodIndex === 0) ? $depositTotal : 0.0,
                'service_fee' => $totalServiceFee,
                'final_total' => $totalPrice,
            ];
        }

        return $results;
    }

    /**
     * Calculate effective cancellation end date for a property
     */
    private static function calculateEffectiveCancelEnd(
        array $property,
        DateTimeImmutable $cancellationDate,
        DateTimeImmutable|DateTime|null $notificationDate
    ): ?DateTimeImmutable {
        if (($property['is_cancelled'] ?? 'No') !== 'Yes') {
            return null;
        }

        $ndays = (int)($property['notify_day'] ?? 0);
        $cancelBase = $cancellationDate;

        // Use property's checkout_date if earlier than booking cancellation date
        if (!empty($property['checkout_date'])) {
            $propCheckout = DateTime::createFromFormat('Y-m-d', $property['checkout_date']);
            if ($propCheckout instanceof DateTime) {
                $propCheckoutImm = DateTimeImmutable::createFromMutable($propCheckout);
                if ($propCheckoutImm < $cancelBase) {
                    $cancelBase = $propCheckoutImm;
                }
            }
        }

        // Calculate adjustment based on notification date
        $diffDays = 0;
        if ($notificationDate instanceof DateTime) {
            $notificationDate = DateTimeImmutable::createFromMutable($notificationDate);
        }

        if ($notificationDate instanceof DateTimeImmutable && $notificationDate <= $cancelBase) {
            $diffDays = (int)$notificationDate->diff($cancelBase)->format('%a');
        }

        $adjust = $ndays > 0 ? max(0, $ndays - $diffDays) : 0;

        return $cancelBase->modify("+{$adjust} days");
    }

    /**
     * Calculate after-cancellation (host) refund rows
     */
    public static function calculateAfterCancelHost(
        array $properties,
        array $periods,
        array $paidPeriodsSelected,
        bool $hasServiceFee,
        array $selectedDays,
        array $holidays,
        DateTimeImmutable|DateTime $checkout,
        DateTimeImmutable|DateTime|null $cancellationDate,
        DateTimeImmutable|DateTime|null $notificationDate
    ): array {
        if (!$cancellationDate || empty($paidPeriodsSelected)) {
            return [];
        }

        $checkout = $checkout instanceof DateTime ? DateTimeImmutable::createFromMutable($checkout) : $checkout;

        $results = [];
        $totalHost = ['service_fee' => 0.0, 'final_total' => 0.0];

        foreach ($properties as $prop) {
            if (($prop['is_cancelled'] ?? 'No') !== 'Yes') {
                continue;
            }

            $effectiveCancelEnd = self::calculateEffectiveCancelEnd($prop, $cancellationDate, $notificationDate);
            if (!$effectiveCancelEnd || $effectiveCancelEnd >= $checkout) {
                continue;
            }

            $cancelledNightsPaid = 0;

            foreach ($periods as $periodIndex => $period) {
                if (!in_array($periodIndex, $paidPeriodsSelected, true)) {
                    continue;
                }

                $pstart = $period['start'];
                $pend = $period['end'];
                $startForPeriod = $effectiveCancelEnd > $pstart ? $effectiveCancelEnd : $pstart;
                $endForPeriod = $pend < $checkout ? $pend : $checkout;

                if ($startForPeriod < $endForPeriod) {
                    $cancelledNightsPaid += self::countEligibleNights($startForPeriod, $endForPeriod, $selectedDays, $holidays);
                }
            }

            if ($cancelledNightsPaid <= 0) {
                continue;
            }

            $finalTotal = ($prop['night_price'] ?? 0) * $cancelledNightsPaid;
            $serviceFee = $hasServiceFee ? ($cancelledNightsPaid * 0.5) : 0.0;

            $results[] = [
                'title' => $prop['title'],
                'cancelled_nights' => $cancelledNightsPaid,
                'service_fee' => $serviceFee,
                'final_total' => $finalTotal,
            ];

            $totalHost['service_fee'] += $serviceFee;
            $totalHost['final_total'] += $finalTotal;
        }

        return [
            'rows' => $results,
            'totals' => $totalHost,
        ];
    }

    /**
     * Get holidays from database
     */
    public static function loadHolidays(\PDO $pdo, DateTime|DateTimeImmutable $checkin, DateTime|DateTimeImmutable $checkout): array
    {
        $checkin = $checkin instanceof DateTimeImmutable ? $checkin : DateTimeImmutable::createFromMutable($checkin);
        $checkout = $checkout instanceof DateTimeImmutable ? $checkout : DateTimeImmutable::createFromMutable($checkout);

        try {
            $stmt = $pdo->prepare("SELECT holiday_date FROM holidays WHERE holiday_date >= :start AND holiday_date < :end");
            $stmt->execute([
                ':start' => $checkin->format('Y-m-d'),
                ':end' => $checkout->format('Y-m-d'),
            ]);
            return $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
        } catch (\Exception $e) {
            return [];
        }
    }
}
