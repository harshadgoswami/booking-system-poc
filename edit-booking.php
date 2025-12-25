<?php
// edit-booking.php
// GitHub Copilot

declare(strict_types=1);
session_start();

$db_host = '127.0.0.1';
$db_user = 'root';
$db_pass = '';
$db_name = 'booking_system';
$dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";

$success = '';
$error = '';

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_PERSISTENT => false,
    ]);
} catch (PDOException $e) {
    $error = 'Database connection failed: ' . $e->getMessage();
}

$bookingId = (int)($_GET['bookingId'] ?? ($_POST['bookingId'] ?? 0));
if ($bookingId <= 0) {
    $error = $error ?: 'Missing bookingId.';
}

/*
 * Handle update submission
 * - validates input
 * - replaces properties (simpler: delete + insert)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $checkin = trim((string)($_POST['checkin'] ?? ''));
    $checkout = trim((string)($_POST['checkout'] ?? ''));
    $notification_date = trim((string)($_POST['notification_date'] ?? ''));
    $cancellation_date = trim((string)($_POST['cancellation_date'] ?? ''));
    $days = $_POST['days'] ?? [];
    $service_fee = in_array($_POST['service_fee'] ?? 'No', ['Yes','No']) ? $_POST['service_fee'] : 'No';
    $exclude_bank_holiday = in_array($_POST['exclude_bank_holiday'] ?? 'No', ['Yes','No']) ? $_POST['exclude_bank_holiday'] : 'No';
    $payment_plan = in_array($_POST['payment_plan'] ?? 'Monthly', ['weekly','fortnighly','Monthly','full']) ? $_POST['payment_plan'] : 'Monthly';
    $properties = $_POST['properties'] ?? [];
    // paid periods from form (checkboxes named paid_periods[<index>]=1)
    $paid_periods_raw = $_POST['paid_periods'] ?? [];
    $paid_periods_post = [];
    if (is_array($paid_periods_raw)) {
        // keys are the period indexes that were checked
        $paid_periods_post = array_map('intval', array_keys($paid_periods_raw));
    }
    $errors = [];
    $d1 = DateTime::createFromFormat('Y-m-d', $checkin);
    $d2 = DateTime::createFromFormat('Y-m-d', $checkout);
    if (!($d1 && $d1->format('Y-m-d') === $checkin)) { $errors[] = 'Invalid check-in date.'; }
    if (!($d2 && $d2->format('Y-m-d') === $checkout)) { $errors[] = 'Invalid check-out date.'; }
    if (empty($errors) && $d2 <= $d1) { $errors[] = 'Checkout date must be greater than checkin date.'; }

    // Validate notification_date if provided
    $notification_date_val = null;
    if ($notification_date !== '') {
        $dn = DateTime::createFromFormat('Y-m-d', $notification_date);
        if (!($dn && $dn->format('Y-m-d') === $notification_date)) {
            $errors[] = 'Invalid notification date.';
        } else {
            $notification_date_val = $notification_date;
        }
    }

    // Validate cancellation_date if provided
    $cancellation_date_val = null;
    if ($cancellation_date !== '') {
        $dc = DateTime::createFromFormat('Y-m-d', $cancellation_date);
        if (!($dc && $dc->format('Y-m-d') === $cancellation_date)) {
            $errors[] = 'Invalid cancellation date.';
        } else {
            $cancellation_date_val = $cancellation_date;
        }
    }

    $validDays = ['mon','tue','wed','thu','fri','sat','sun'];
    $days = array_values(array_intersect($validDays, array_map('strtolower', $days)));

    $validatedProperties = [];
    if (!is_array($properties)) {
        $errors[] = 'Invalid property data.';
    } else {
        foreach ($properties as $idx => $p) {
            $title = trim((string)($p['title'] ?? ''));
            $night_price = $p['night_price'] ?? '';
            $deposit = $p['deposit'] ?? '';
            $p_checkout = trim((string)($p['checkout_date'] ?? ''));
            $is_cancelled = in_array($p['is_cancelled'] ?? 'No', ['Yes','No']) ? $p['is_cancelled'] : 'No';
            $notify_day = $p['notify_day'] ?? '';

            if ($title === '') { $errors[] = "Property #".($idx+1).": title is required."; }
            if ($night_price === '' || !is_numeric($night_price)) { $errors[] = "Property #".($idx+1).": night price must be a number."; }
            if ($deposit === '' || !is_numeric($deposit)) { $errors[] = "Property #".($idx+1).": deposit must be a number."; }

            if ($p_checkout !== '') {
                $pcd = DateTime::createFromFormat('Y-m-d', $p_checkout);
                if (!($pcd && $pcd->format('Y-m-d') === $p_checkout)) {
                    $errors[] = "Property #".($idx+1).": invalid checkout date.";
                }
            } else {
                $p_checkout = null;
            }

            if ($notify_day === '') {
                $notify_day_int = 0;
            } elseif (ctype_digit((string)$notify_day) && (int)$notify_day >= 0) {
                $notify_day_int = (int)$notify_day;
            } else {
                $errors[] = "Property #".($idx+1).": notify day must be a non-negative integer.";
                $notify_day_int = 0;
            }

            $validatedProperties[] = [
                'title' => $title,
                'night_price' => (float)$night_price,
                'deposit' => (float)$deposit,
                'checkout_date' => $p_checkout,
                'is_cancelled' => $is_cancelled,
                'notify_day' => $notify_day_int,
            ];
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $upd = $pdo->prepare("UPDATE bookings SET checkin = :checkin, checkout = :checkout, days = :days, service_fee = :service_fee, exclude_bank_holiday = :exclude_bank_holiday, payment_plan = :payment_plan, notification_date = :notification_date, cancellation_date = :cancellation_date WHERE id = :id");
            $upd->execute([
                ':checkin' => $checkin,
                ':checkout' => $checkout,
                ':days' => json_encode($days),
                ':service_fee' => $service_fee,
                ':exclude_bank_holiday' => $exclude_bank_holiday,
                ':payment_plan' => $payment_plan,
                ':notification_date' => $notification_date_val,
                ':cancellation_date' => $cancellation_date_val,
                ':id' => $bookingId,
            ]);

            // Replace properties for this booking
            $del = $pdo->prepare("DELETE FROM properties WHERE booking_id = :bid");
            $del->execute([':bid' => $bookingId]);

            $insProp = $pdo->prepare("INSERT INTO properties (booking_id, title, night_price, deposit, checkout_date, is_cancelled, notify_day) VALUES (:booking_id, :title, :night_price, :deposit, :checkout_date, :is_cancelled, :notify_day)");
            foreach ($validatedProperties as $vp) {
                $insProp->execute([
                    ':booking_id' => $bookingId,
                    ':title' => $vp['title'],
                    ':night_price' => $vp['night_price'],
                    ':deposit' => $vp['deposit'],
                    ':checkout_date' => $vp['checkout_date'],
                    ':is_cancelled' => $vp['is_cancelled'],
                    ':notify_day' => $vp['notify_day'],
                ]);
            }

            $pdo->commit();
            // persist paid periods selection in session so the checked state survives the redirect
            if (!empty($paid_periods_post)) {
                $_SESSION['paid_periods_booking_' . $bookingId] = $paid_periods_post;
            } else {
                // clear any previous selection
                unset($_SESSION['paid_periods_booking_' . $bookingId]);
            }
            header('Location: edit-booking.php?bookingId=' . $bookingId . '&saved=1');
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $error = 'Failed to update: ' . $e->getMessage();
        }
    } else {
        $error = implode(' ', $errors);
    }
}

/* Load booking & properties for display */
$booking = null;
$propertiesData = [];
$showWithCancel = false;
if (empty($error)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = :id");
        $stmt->execute([':id' => $bookingId]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$booking) {
            $error = 'Booking not found.';
        } else {
            $props = $pdo->prepare("SELECT * FROM properties WHERE booking_id = :bid ORDER BY id ASC");
            $props->execute([':bid' => $bookingId]);
            $propertiesData = $props->fetchAll(PDO::FETCH_ASSOC);
            $booking['days'] = json_decode($booking['days'] ?? '[]', true) ?: [];

            // --- Payment plan calculations (upcoming payment details) ---
            $deposit_total = 0.0;
            $today_dt = new DateTimeImmutable('today');

            $checkin_dt = DateTime::createFromFormat('Y-m-d', $booking['checkin'] ?? '');
            $checkout_dt = DateTime::createFromFormat('Y-m-d', $booking['checkout'] ?? '');

            // Xero invoice schedule: per-period notify/due dates will be
            // calculated from each payment period's start date when rendering
            // the tables below. Keep booking-level vars null to avoid
            // accidental use elsewhere.
            $booking_notify_dt = null;
            $booking_due_dt = null;

            // booking-level notification / cancellation dates (may be null)
            $booking_notification_dt = DateTime::createFromFormat('Y-m-d', $booking['notification_date'] ?? '');
            if ($booking_notification_dt instanceof DateTime) $booking_notification_dt = DateTimeImmutable::createFromMutable($booking_notification_dt);
            $booking_cancellation_dt = DateTime::createFromFormat('Y-m-d', $booking['cancellation_date'] ?? '');
            if ($booking_cancellation_dt instanceof DateTime) $booking_cancellation_dt = DateTimeImmutable::createFromMutable($booking_cancellation_dt);

            // load holidays between checkin (inclusive) and checkout (exclusive) when requested
            $holidays = [];
            if ($checkin_dt && $checkout_dt && ($booking['exclude_bank_holiday'] ?? 'No') === 'Yes') {
                try {
                    $hstmt = $pdo->prepare("SELECT holiday_date FROM holidays WHERE holiday_date >= :start AND holiday_date < :end");
                    $hstmt->execute([
                        ':start' => $checkin_dt->format('Y-m-d'),
                        ':end' => $checkout_dt->format('Y-m-d'),
                    ]);
                    $holidays = $hstmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
                } catch (PDOException $e) {
                    $holidays = [];
                }
            }

            // helper to count eligible nights in [from, to) considering selected weekdays and holidays
            $selectedDays = array_map('strtolower', (array) ($booking['days'] ?? [])); // ['mon','tue',...]
            $weekdayKeys = ['mon','tue','wed','thu','fri','sat','sun'];
            $countEligibleNights = function(DateTimeInterface $from, DateTimeInterface $to, array $selectedDays, array $holidays) use ($weekdayKeys) : int {
                if ($to <= $from) return 0;
                $period = new DatePeriod($from, new DateInterval('P1D'), $to);
                $c = 0;
                foreach ($period as $d) {
                    $dayKey = $weekdayKeys[(int)$d->format('N') - 1];
                    $ymd = $d->format('Y-m-d');
                    if (!empty($selectedDays)) {
                        if (!in_array($dayKey, $selectedDays, true)) continue;
                    }
                    if (!empty($holidays) && in_array($ymd, $holidays, true)) continue;
                    $c++;
                }
                return $c;
            };

            // compute full booking nights (same for all properties when not cancelled)
            $nights_full = 0;
            if ($checkin_dt && $checkout_dt && $checkout_dt > $checkin_dt) {
                $nights_full = $countEligibleNights($checkin_dt, $checkout_dt, $selectedDays, $holidays);
            }

            // per-property computations (notify date, display notify date, effective nights when cancelled)
            $perProperty = [];
            foreach ($propertiesData as $p) {
                $deposit_total += (float) ($p['deposit'] ?? 0);
                $ndays = (int) ($p['notify_day'] ?? 0);

                // notify date = checkin + notify_day
                $notify_dt = $checkin_dt ? DateTimeImmutable::createFromMutable((clone $checkin_dt)->modify("+{$ndays} days")) : null;

                // display notify (max of notify date and today) as requested
                $notify_display_dt = $notify_dt ? ($today_dt > $notify_dt ? $today_dt : $notify_dt) : null;

                // effective nights until notify (exclude nights from notify_display_dt onward)
                if ($checkin_dt && $checkout_dt && $notify_display_dt) {
                    // count nights in [checkin, min(checkout, notify_display_dt) )
                    $end_for_cancel = $notify_display_dt < DateTimeImmutable::createFromMutable($checkout_dt) ? $notify_display_dt : DateTimeImmutable::createFromMutable($checkout_dt);
                    $effective_nights_until_notify = $countEligibleNights($checkin_dt, $end_for_cancel, $selectedDays, $holidays);
                } else {
                    $effective_nights_until_notify = 0;
                }

                // compute effective cancellation end for this property when booking-level cancellation exists
                // If the property has its own checkout_date and it's earlier than the booking cancellation_date,
                // use the property's checkout_date as the cancellation base for this property.
                $effective_cancel_end = null;
                // parse property's checkout_date (if any)
                $prop_checkout_dt = null;
                if (!empty($p['checkout_date'])) {
                    $pc = DateTime::createFromFormat('Y-m-d', $p['checkout_date']);
                    if ($pc instanceof DateTime) $prop_checkout_dt = DateTimeImmutable::createFromMutable($pc);
                }

                if (($p['is_cancelled'] ?? 'No') === 'Yes' && $booking_cancellation_dt instanceof DateTimeImmutable) {
                    // choose cancellation base: property checkout if earlier than booking cancellation
                    $cancel_base = $booking_cancellation_dt;
                    if ($prop_checkout_dt instanceof DateTimeImmutable && $prop_checkout_dt < $booking_cancellation_dt) {
                        $cancel_base = $prop_checkout_dt;
                    }

                    // how many days between booking notification_date and cancel_base (guest's actual notice)
                    $diffDays = 0;
                    if ($booking_notification_dt instanceof DateTimeImmutable) {
                        if ($booking_notification_dt <= $cancel_base) {
                            $diffDays = (int)$booking_notification_dt->diff($cancel_base)->format('%a');
                        }
                    }

                    $adjust = 0;
                    if ($ndays > 0) {
                        $adjust = max(0, $ndays - $diffDays);
                    }
                    $effective_cancel_end = $cancel_base->modify("+{$adjust} days");
                }

                $perProperty[] = [
                    'id' => $p['id'] ?? null,
                    'title' => $p['title'] ?? '',
                    'night_price' => (float) ($p['night_price'] ?? 0),
                    'deposit' => (float) ($p['deposit'] ?? 0),
                    'is_cancelled' => ($p['is_cancelled'] ?? 'No'),
                    'notify_dt' => $notify_dt,
                    'notify_display_dt' => $notify_display_dt,
                    'nights_until_notify' => $effective_nights_until_notify,
                    'effective_cancel_end' => $effective_cancel_end,
                ];
            }

            // Build payment periods according to selected payment_plan (weekly, fortnighly, Monthly, full)
            $periods = []; // each: ['start'=>DateTimeImmutable, 'end'=>DateTimeImmutable]
            $plan = $booking['payment_plan'] ?? 'Monthly';
            if ($checkin_dt && $checkout_dt && $checkout_dt > $checkin_dt) {
                if ($plan === 'weekly') {
                    $cursor = DateTimeImmutable::createFromMutable($checkin_dt);
                    while ($cursor < DateTimeImmutable::createFromMutable($checkout_dt)) {
                        $end = $cursor->add(new DateInterval('P7D'));
                        if ($end > DateTimeImmutable::createFromMutable($checkout_dt)) $end = DateTimeImmutable::createFromMutable($checkout_dt);
                        $periods[] = ['start' => $cursor, 'end' => $end];
                        $cursor = $end;
                    }
                } elseif ($plan === 'fortnighly') {
                    $cursor = DateTimeImmutable::createFromMutable($checkin_dt);
                    while ($cursor < DateTimeImmutable::createFromMutable($checkout_dt)) {
                        $end = $cursor->add(new DateInterval('P14D'));
                        if ($end > DateTimeImmutable::createFromMutable($checkout_dt)) $end = DateTimeImmutable::createFromMutable($checkout_dt);
                        $periods[] = ['start' => $cursor, 'end' => $end];
                        $cursor = $end;
                    }
                } elseif ($plan === 'Monthly') {
                    // calendar-month rows: from checkin to end of that month, then successive calendar months until checkout
                    $cursor = DateTimeImmutable::createFromMutable($checkin_dt);
                    while ($cursor < DateTimeImmutable::createFromMutable($checkout_dt)) {
                        // first day of next month from cursor
                        $nextMonth = (new DateTimeImmutable($cursor->format('Y-m-01')))->modify('+1 month');
                        $end = $nextMonth;
                        if ($end > DateTimeImmutable::createFromMutable($checkout_dt)) $end = DateTimeImmutable::createFromMutable($checkout_dt);
                        $periods[] = ['start' => $cursor, 'end' => $end];
                        $cursor = $end;
                    }
                } else { // full
                    $periods[] = ['start' => DateTimeImmutable::createFromMutable($checkin_dt), 'end' => DateTimeImmutable::createFromMutable($checkout_dt)];
                }
            }

            // determine which periods were marked paid (persisted in session or from recent POST)
            $paidPeriodsSelected = [];
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $paidPeriodsSelected = $paid_periods_post ?? [];
            } elseif (!empty($_SESSION['paid_periods_booking_' . $bookingId])) {
                $paidPeriodsSelected = $_SESSION['paid_periods_booking_' . $bookingId];
            }

            // prepare per-period totals for both "no cancellation" and "with cancellation"
            $periodTotalsNoCancel = []; // each: ['start','end','nights','deposit','service_fee','final_total']
            $periodTotalsWithCancel = [];
            $numProps = count($perProperty);
            $deposit_total = array_reduce($perProperty, function($sum, $p){ return $sum + ($p['deposit'] ?? 0.0); }, 0.0);

            foreach ($periods as $pi => $pr) {
                $ps = $pr['start'];
                $pe = $pr['end'];
                // nights in period for standard (no cancel)
                $nights_period = $countEligibleNights($ps, $pe, $selectedDays, $holidays);

                // no-cancel totals
                $final_no = 0.0;
                $service_no = 0.0;
                foreach ($perProperty as $pp) {
                    $final_no += $pp['night_price'] * $nights_period;
                    if (($booking['service_fee'] ?? 'No') === 'Yes') {
                        $service_no += $nights_period * 0.5;
                    }
                }
                $deposit_row = ($pi === 0) ? $deposit_total : 0.0;
                $periodTotalsNoCancel[] = [
                    'start' => $ps, 'end' => $pe, 'nights' => $nights_period,
                    'deposit' => $deposit_row, 'service_fee' => $service_no, 'final_total' => $final_no
                ];

                // with-cancel totals: per-property effective nights inside this period
                $final_wc = 0.0;
                $service_wc = 0.0;
                foreach ($perProperty as $pp) {
                    if ($pp['is_cancelled'] === 'Yes' && $pp['effective_cancel_end'] instanceof DateTimeImmutable) {
                        // end for this property is min(period_end, effective_cancel_end)
                        $endForProp = $pp['effective_cancel_end'] < $pe ? $pp['effective_cancel_end'] : $pe;
                        $nights_prop = $countEligibleNights($ps, $endForProp, $selectedDays, $holidays);
                    } else {
                        $nights_prop = $countEligibleNights($ps, $pe, $selectedDays, $holidays);
                    }
                    $final_wc += $pp['night_price'] * $nights_prop;
                    if (($booking['service_fee'] ?? 'No') === 'Yes') {
                        $service_wc += $nights_prop * 0.5;
                    }
                }
                $deposit_row_wc = ($pi === 0) ? $deposit_total : 0.0;
                $periodTotalsWithCancel[] = [
                    'start' => $ps, 'end' => $pe, 'nights' => null, // we'll show per-property effective nights below if needed
                    'deposit' => $deposit_row_wc, 'service_fee' => $service_wc, 'final_total' => $final_wc
                ];
            }
            // Prepare After-Cancellation (HOST) rows: for each cancelled property, compute cancelled nights (original booking nights after effective_cancel_end)
            $afterCancelHost = [];
            $host_totals = ['service_fee' => 0.0, 'final_total' => 0.0];
            foreach ($perProperty as $pp) {
                if (($pp['is_cancelled'] ?? 'No') !== 'Yes' || !($pp['effective_cancel_end'] instanceof DateTimeImmutable) || !($checkout_dt instanceof DateTime)) {
                    continue;
                }
                $effEnd = $pp['effective_cancel_end'];
                $checkout_imm = DateTimeImmutable::createFromMutable($checkout_dt);
                if ($effEnd >= $checkout_imm) {
                    continue;
                }

                // count cancelled nights only from periods marked as paid (refund applies only to paid periods)
                $cancelled_nights_paid = 0;
                foreach ($periods as $pi => $prp) {
                    // only process periods that are marked as paid
                    if (empty($paidPeriodsSelected) || !in_array($pi, $paidPeriodsSelected, true)) {
                        continue;
                    }
                    $pstart = $prp['start'];
                    $pend = $prp['end'];
                    // compute overlap of [effEnd, checkout) with [pstart, pend)
                    $startForPeriod = $effEnd > $pstart ? $effEnd : $pstart;
                    $endForPeriod = $pend < $checkout_imm ? $pend : $checkout_imm;
                    if ($startForPeriod >= $endForPeriod) continue;
                    $cancelled_nights_paid += $countEligibleNights($startForPeriod, $endForPeriod, $selectedDays, $holidays);
                }

                if ($cancelled_nights_paid <= 0) {
                    continue;
                }

                $final = $pp['night_price'] * $cancelled_nights_paid;
                $service = (($booking['service_fee'] ?? 'No') === 'Yes') ? ($cancelled_nights_paid * 0.5) : 0.0;
                $afterCancelHost[] = [
                    'title' => $pp['title'],
                    'cancelled_nights' => $cancelled_nights_paid,
                    'service_fee' => $service,
                    'final_total' => $final,
                ];
                $host_totals['service_fee'] += $service;
                $host_totals['final_total'] += $final;
            }
            // decide whether to show the "With Cancellation" table: booking has cancellation date and at least one property cancelled
            $showWithCancel = ($booking_cancellation_dt instanceof DateTimeImmutable) && (count(array_filter($perProperty, function($pp){ return ($pp['is_cancelled'] ?? 'No') === 'Yes'; })) > 0);
            // --- end payment plan calculations ---
         }
     } catch (PDOException $e) {
         $error = 'Failed to load booking: ' . $e->getMessage();
     }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Edit Booking</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .property-group { border: 1px solid #e3e3e3; padding: 12px; margin-bottom:10px; border-radius:6px; }
        .btn-space { margin-left:.5rem; }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="d-flex align-items-center mb-3">
        <h1 class="me-auto mb-0">Edit Booking #<?= htmlspecialchars((string)$bookingId, ENT_QUOTES) ?></h1>
        <a href="index.php" class="btn btn-outline-secondary">Home</a>
    </div>
    <div class="mb-3 text-muted">Payment Plan: <?= htmlspecialchars($booking['payment_plan'] ?? 'Monthly', ENT_QUOTES) ?></div>
     <div class="mb-3 text-muted">
         Today: <?= htmlspecialchars((new DateTimeImmutable('today'))->format('d/m/Y'), ENT_QUOTES) ?>
     </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success">Saved successfully.</div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
    <?php endif; ?>

    <?php if ($booking): ?>
    <form method="post" id="mainForm" novalidate>
        <input type="hidden" name="bookingId" value="<?= htmlspecialchars((string)$bookingId, ENT_QUOTES) ?>">

        <div class="card mb-3">
            <div class="card-header">Booking Form Details</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Check-in Date</label>
                        <input type="date" name="checkin" id="checkin" class="form-control" value="<?= htmlspecialchars($booking['checkin'] ?? '', ENT_QUOTES) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Check-out Date</label>
                        <input type="date" name="checkout" id="checkout" class="form-control" value="<?= htmlspecialchars($booking['checkout'] ?? '', ENT_QUOTES) ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Days</label>
                        <div class="d-flex gap-2 flex-wrap">
                            <?php
                            $dayLabels = ['mon'=>'Mon','tue'=>'Tue','wed'=>'Wed','thu'=>'Thu','fri'=>'Fri','sat'=>'Sat','sun'=>'Sun'];
                            $postedDays = $booking['days'] ?? [];
                            foreach ($dayLabels as $k => $label): ?>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="day_<?= $k ?>" name="days[]" value="<?= $k ?>" <?= in_array($k, $postedDays) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="day_<?= $k ?>"><?= $label ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Is Service Fee?</label>
                        <select name="service_fee" class="form-select">
                            <option value="No" <?= ($booking['service_fee'] ?? 'No') === 'No' ? 'selected' : '' ?>>No</option>
                            <option value="Yes" <?= ($booking['service_fee'] ?? '') === 'Yes' ? 'selected' : '' ?>>Yes</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Exclude Bank Holiday?</label>
                        <select name="exclude_bank_holiday" class="form-select">
                            <option value="No" <?= ($booking['exclude_bank_holiday'] ?? 'No') === 'No' ? 'selected' : '' ?>>No</option>
                            <option value="Yes" <?= ($booking['exclude_bank_holiday'] ?? '') === 'Yes' ? 'selected' : '' ?>>Yes</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Select Payment Plan</label>
                        <select name="payment_plan" class="form-select">
                            <option value="weekly" <?= ($booking['payment_plan'] ?? '') === 'weekly' ? 'selected' : '' ?>>weekly</option>
                            <option value="fortnighly" <?= ($booking['payment_plan'] ?? '') === 'fortnighly' ? 'selected' : '' ?>>fortnighly</option>
                            <option value="Monthly" <?= ($booking['payment_plan'] ?? 'Monthly') === 'Monthly' ? 'selected' : '' ?>>Monthly</option>
                            <option value="full" <?= ($booking['payment_plan'] ?? '') === 'full' ? 'selected' : '' ?>>full</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Notification Date</label>
                        <input type="date" name="notification_date" id="notification_date" class="form-control" value="<?= htmlspecialchars($booking['notification_date'] ?? '', ENT_QUOTES) ?>">
                        <small class="text-muted">When guest informs about cancellation</small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Cancellation Date</label>
                        <input type="date" name="cancellation_date" id="cancellation_date" class="form-control" value="<?= htmlspecialchars($booking['cancellation_date'] ?? '', ENT_QUOTES) ?>">
                        <small class="text-muted">Real cancellation start date</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Property Form Details</span>
                <button type="button" id="addProperty" class="btn btn-sm btn-success">Add Property</button>
            </div>
            <div class="card-body" id="propertiesContainer">
                <?php
                if (count($propertiesData) > 0):
                    foreach ($propertiesData as $pidx => $p):
                ?>
                    <div class="property-group" data-index="<?= $pidx ?>">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label">Title</label>
                                <input type="text" name="properties[<?= $pidx ?>][title]" class="form-control" value="<?= htmlspecialchars($p['title'] ?? '', ENT_QUOTES) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Night Price</label>
                                <input type="number" step="0.01" name="properties[<?= $pidx ?>][night_price]" class="form-control" value="<?= htmlspecialchars($p['night_price'] ?? '', ENT_QUOTES) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Deposit</label>
                                <input type="number" step="0.01" name="properties[<?= $pidx ?>][deposit]" class="form-control" value="<?= htmlspecialchars($p['deposit'] ?? '', ENT_QUOTES) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Checkout Date</label>
                                <input type="date" name="properties[<?= $pidx ?>][checkout_date]" class="form-control" value="<?= htmlspecialchars($p['checkout_date'] ?? '', ENT_QUOTES) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Notify Day</label>
                                <input type="number" min="0" name="properties[<?= $pidx ?>][notify_day]" class="form-control" value="<?= htmlspecialchars( (string)$p['notify_day'] ?? '0', ENT_QUOTES) ?>">
                            </div>
                        </div>

                        <div class="row g-2 align-items-end mt-2">
                            <div class="col-md-3">
                                <label class="form-label small">Is Cancelled?</label>
                                <select name="properties[<?= $pidx ?>][is_cancelled]" class="form-select">
                                    <option value="No" <?= ($p['is_cancelled'] ?? 'No') === 'No' ? 'selected' : '' ?>>No</option>
                                    <option value="Yes" <?= ($p['is_cancelled'] ?? '') === 'Yes' ? 'selected' : '' ?>>Yes</option>
                                </select>
                            </div>
                            <div class="col d-flex justify-content-end">
                                <button type="button" class="btn btn-danger btn-sm remove-property">Remove</button>
                            </div>
                        </div>
                    </div>
                <?php
                    endforeach;
                else:
                ?>
                    <div class="property-group" data-index="0">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label">Title</label>
                                <input type="text" name="properties[0][title]" class="form-control" value="">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Night Price</label>
                                <input type="number" step="0.01" name="properties[0][night_price]" class="form-control" value="">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Deposit</label>
                                <input type="number" step="0.01" name="properties[0][deposit]" class="form-control" value="">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Checkout Date</label>
                                <input type="date" name="properties[0][checkout_date]" class="form-control" value="">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Notify Day</label>
                                <input type="number" min="0" name="properties[0][notify_day]" class="form-control" value="0">
                            </div>
                        </div>

                        <div class="row g-2 align-items-end mt-2">
                            <div class="col-md-3">
                                <label class="form-label small">Is Cancelled?</label>
                                <select name="properties[0][is_cancelled]" class="form-select">
                                    <option value="No" selected>No</option>
                                    <option value="Yes">Yes</option>
                                </select>
                            </div>
                            <div class="col d-flex justify-content-end">
                                <button type="button" class="btn btn-danger btn-sm remove-property">Remove</button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Payment Plan: WITHOUT Cancellation — periodized -->
        <div class="card mb-3">
            <div class="card-header">Payment Plan — Without Cancellation</div>
            <div class="card-body">
                <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Period</th>
                                    <th>Notify / Due</th>
                                    <th>Is Paid?</th>
                                    <th>Deposit (£)</th>
                                    <th>Service Fee (£)</th>
                                    <th>Final Total (£)</th>
                                    <th>Nights</th>
                                </tr>
                            </thead>
                    <tbody>
                        <?php foreach ($periodTotalsNoCancel as $i => $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['start']->format('d/m/Y') . ' / ' . $row['end']->format('d/m/Y')) ?></td>
                            <td><?php
                                $periodStart = $row['start'] ?? null;
                                if ($periodStart instanceof DateTimeImmutable || $periodStart instanceof DateTime) {
                                    $periodStartImm = $periodStart instanceof DateTimeImmutable ? $periodStart : DateTimeImmutable::createFromMutable($periodStart);
                                    $origNotify = $periodStartImm->modify('-16 days');
                                    $origDue = $periodStartImm->modify('-7 days');
                                    $notifyReplaced = ($today_dt > $origNotify);
                                    $dueReplaced = ($today_dt > $origDue);
                                    $notifyDisplay = $notifyReplaced ? $today_dt : $origNotify;
                                    $dueDisplay = $dueReplaced ? $today_dt : $origDue;
                                    $ndStr = htmlspecialchars($notifyDisplay->format('d/m/Y'));
                                    $ddStr = htmlspecialchars($dueDisplay->format('d/m/Y'));
                                    if ($notifyReplaced) $ndStr = '<span style="color:green">' . $ndStr . '</span>';
                                    if ($dueReplaced) $ddStr = '<span style="color:green">' . $ddStr . '</span>';
                                    if ($ndStr !== '' && $ddStr !== '') {
                                        echo $ndStr . ' / ' . $ddStr;
                                    } else {
                                        echo $ndStr !== '' ? $ndStr : ($ddStr !== '' ? $ddStr : '');
                                    }
                                }
                            ?></td>
                            <td class="text-center"><input type="checkbox" name="paid_periods[<?= $i ?>]" value="1" <?= in_array($i, $paidPeriodsSelected ?? [], true) ? 'checked' : '' ?>></td>
                            <td>£<?= number_format($row['deposit'], 2) ?></td>
                            <td>£<?= number_format($row['service_fee'], 2) ?></td>
                            <td>£<?= number_format($row['final_total'], 2) ?></td>
                            <td><?= (int)$row['nights'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td>Total</td>
                            <td></td>
                            <td></td>
                            <td>£<?= number_format(array_sum(array_column($periodTotalsNoCancel, 'deposit')), 2) ?></td>
                            <td>£<?= number_format(array_sum(array_column($periodTotalsNoCancel, 'service_fee')), 2) ?></td>
                            <td>£<?= number_format(array_sum(array_column($periodTotalsNoCancel, 'final_total')), 2) ?></td>
                            <td><?= array_sum(array_column($periodTotalsNoCancel, 'nights')) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <?php if (!empty($showWithCancel)): ?>
        <!-- Payment Plan: WITH Cancellation — periodized -->
        <div class="card mb-3">
            <div class="card-header">Payment Plan — With Cancellation</div>
            <div class="card-body">
                <p class="small text-muted mb-2">For properties marked "Is Cancelled = Yes", nights after the notify-due are ignored.</p>
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Period</th>
                            <th>Notify / Due</th>
                            <th>Deposit (£)</th>
                            <th>Service Fee (£)</th>
                            <th>Final Total (£)</th>
                            <th>Effective Nights (per property)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($periods as $i => $pr): 
                            $row = $periodTotalsWithCancel[$i];
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($pr['start']->format('d/m/Y') . ' / ' . $pr['end']->format('d/m/Y')) ?></td>
                            <td><?php
                                $periodStart = $pr['start'] ?? null;
                                if ($periodStart instanceof DateTimeImmutable || $periodStart instanceof DateTime) {
                                    $periodStartImm = $periodStart instanceof DateTimeImmutable ? $periodStart : DateTimeImmutable::createFromMutable($periodStart);
                                    $origNotify = $periodStartImm->modify('-16 days');
                                    $origDue = $periodStartImm->modify('-7 days');
                                    $notifyReplaced = ($today_dt > $origNotify);
                                    $dueReplaced = ($today_dt > $origDue);
                                    $notifyDisplay = $notifyReplaced ? $today_dt : $origNotify;
                                    $dueDisplay = $dueReplaced ? $today_dt : $origDue;
                                    $ndStr = htmlspecialchars($notifyDisplay->format('d/m/Y'));
                                    $ddStr = htmlspecialchars($dueDisplay->format('d/m/Y'));
                                    if ($notifyReplaced) $ndStr = '<span style="color:green">' . $ndStr . '</span>';
                                    if ($dueReplaced) $ddStr = '<span style="color:green">' . $ddStr . '</span>';
                                    if ($ndStr !== '' && $ddStr !== '') {
                                        echo $ndStr . ' / ' . $ddStr;
                                    } else {
                                        echo $ndStr !== '' ? $ndStr : ($ddStr !== '' ? $ddStr : '');
                                    }
                                }
                            ?></td>
                            <td>£<?= number_format($row['deposit'], 2) ?></td>
                            <td>£<?= number_format($row['service_fee'], 2) ?></td>
                            <td>£<?= number_format($row['final_total'], 2) ?></td>
                            <td>
                                <?php
                                $parts = [];
                                foreach ($perProperty as $pp) {
                                    if ($pp['is_cancelled'] === 'Yes' && $pp['effective_cancel_end'] instanceof DateTimeImmutable) {
                                        $endForProp = $pp['effective_cancel_end'] < $pr['end'] ? $pp['effective_cancel_end'] : $pr['end'];
                                        $n = $countEligibleNights($pr['start'], $endForProp, $selectedDays, $holidays);
                                    } else {
                                        $n = $countEligibleNights($pr['start'], $pr['end'], $selectedDays, $holidays);
                                    }
                                    $ecLabel = '';
                                    if ($pp['effective_cancel_end'] instanceof DateTimeImmutable) {
                                        $ecLabel = ' (effective cancel: ' . $pp['effective_cancel_end']->format('d/m/Y') . ')';
                                    }
                                    $parts[] = htmlspecialchars($pp['title'] . ': ' . $n . $ecLabel);
                                }
                                echo implode(' / ', $parts);
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td>Total</td>
                            <td></td>
                            <td>£<?= number_format(array_sum(array_column($periodTotalsWithCancel, 'deposit')), 2) ?></td>
                            <td>£<?= number_format(array_sum(array_column($periodTotalsWithCancel, 'service_fee')), 2) ?></td>
                            <td>£<?= number_format(array_sum(array_column($periodTotalsWithCancel, 'final_total')), 2) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($afterCancelHost) && !empty($paidPeriodsSelected)): ?>
        <div class="card mb-3">
            <div class="card-header">After Cancellation: Payment Plans (HOST)</div>
            <div class="card-body">
                <p class="small text-muted mb-2">Only properties that were cancelled. Values based on cancelled nights from original booking.</p>
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Property</th>
                            <th>Cancelled Nights</th>
                            <th>Service Fee (£)</th>
                            <th>Final Total (£)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($afterCancelHost as $hr): ?>
                        <tr>
                            <td><?= htmlspecialchars($hr['title']) ?></td>
                            <td><?= (int)$hr['cancelled_nights'] ?></td>
                            <td>£<?= number_format($hr['service_fee'], 2) ?></td>
                            <td>£<?= number_format($hr['final_total'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td>Total</td>
                            <td></td>
                            <td>£<?= number_format($host_totals['service_fee'], 2) ?></td>
                            <td>£<?= number_format($host_totals['final_total'], 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <div class="mb-3">
            <button type="submit" class="btn btn-primary">Update Booking</button>
            <a href="property-form.php" class="btn btn-secondary">Create New</a>
        </div>
    </form>
    <?php endif; ?>
</div>

<script>
(function($){
    // ensure propIndex picks next numeric index (avoid collisions)
    let propIndex = 0;
    $('#propertiesContainer .property-group').each(function(){
        const idx = Number($(this).data('index'));
        if (!isNaN(idx) && idx >= propIndex) propIndex = idx + 1;
    });
    if (propIndex === 0) propIndex = $('#propertiesContainer .property-group').length || 1;

    function makePropertyHtml(idx) {
        return `
        <div class="property-group" data-index="${idx}">
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label">Title</label>
                    <input type="text" name="properties[${idx}][title]" class="form-control" value="">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Night Price</label>
                    <input type="number" step="0.01" name="properties[${idx}][night_price]" class="form-control" value="">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Deposit</label>
                    <input type="number" step="0.01" name="properties[${idx}][deposit]" class="form-control" value="">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Checkout Date</label>
                    <input type="date" name="properties[${idx}][checkout_date]" class="form-control" value="">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Notify Day</label>
                    <input type="number" min="0" name="properties[${idx}][notify_day]" class="form-control" value="0">
                </div>
            </div>

            <div class="row g-2 align-items-end mt-2">
                <div class="col-md-3">
                    <label class="form-label small">Is Cancelled?</label>
                    <select name="properties[${idx}][is_cancelled]" class="form-select">
                        <option value="No" selected>No</option>
                        <option value="Yes">Yes</option>
                    </select>
                </div>
                <div class="col d-flex justify-content-end">
                    <button type="button" class="btn btn-danger btn-sm remove-property">Remove</button>
                </div>
            </div>
        </div>
        `;
    }

    $('#addProperty').on('click', function(){
        $('#propertiesContainer').append(makePropertyHtml(propIndex));
        propIndex++;
    });

    $('#propertiesContainer').on('click', '.remove-property', function(){
        const groups = $('#propertiesContainer .property-group');
        if (groups.length <= 1) {
            groups.find('input').val('');
            groups.find('select').val('No');
            return;
        }
        $(this).closest('.property-group').remove();
    });

    // client-side validation: checkout > checkin
    $('#mainForm').on('submit', function(e){
        const ci = $('#checkin').val();
        const co = $('#checkout').val();
        if (ci && co) {
            if (new Date(co) <= new Date(ci)) {
                e.preventDefault();
                alert('Checkout date must be greater than checkin date.');
                return false;
            }
        }
    });
})(jQuery);
</script>
</body>
</html>