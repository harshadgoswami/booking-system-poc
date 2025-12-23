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
    $days = $_POST['days'] ?? [];
    $service_fee = in_array($_POST['service_fee'] ?? 'No', ['Yes','No']) ? $_POST['service_fee'] : 'No';
    $exclude_bank_holiday = in_array($_POST['exclude_bank_holiday'] ?? 'No', ['Yes','No']) ? $_POST['exclude_bank_holiday'] : 'No';
    $payment_plan = in_array($_POST['payment_plan'] ?? 'Monthly', ['weekly','fortnighly','Monthly','full']) ? $_POST['payment_plan'] : 'Monthly';
    $properties = $_POST['properties'] ?? [];

    $errors = [];
    $d1 = DateTime::createFromFormat('Y-m-d', $checkin);
    $d2 = DateTime::createFromFormat('Y-m-d', $checkout);
    if (!($d1 && $d1->format('Y-m-d') === $checkin)) { $errors[] = 'Invalid check-in date.'; }
    if (!($d2 && $d2->format('Y-m-d') === $checkout)) { $errors[] = 'Invalid check-out date.'; }
    if (empty($errors) && $d2 <= $d1) { $errors[] = 'Checkout date must be greater than checkin date.'; }

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

            $upd = $pdo->prepare("UPDATE bookings SET checkin = :checkin, checkout = :checkout, days = :days, service_fee = :service_fee, exclude_bank_holiday = :exclude_bank_holiday, payment_plan = :payment_plan WHERE id = :id");
            $upd->execute([
                ':checkin' => $checkin,
                ':checkout' => $checkout,
                ':days' => json_encode($days),
                ':service_fee' => $service_fee,
                ':exclude_bank_holiday' => $exclude_bank_holiday,
                ':payment_plan' => $payment_plan,
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
            $notify_display_list = [];
            $today_dt = new DateTimeImmutable('today');

            $checkin_dt = DateTime::createFromFormat('Y-m-d', $booking['checkin'] ?? '');
            $checkout_dt = DateTime::createFromFormat('Y-m-d', $booking['checkout'] ?? '');

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
                $notify_display_list[] = $notify_display_dt ? $notify_display_dt->format('d/m/Y') : '';

                // effective nights until notify (exclude nights from notify_display_dt onward)
                if ($checkin_dt && $checkout_dt && $notify_display_dt) {
                    // count nights in [checkin, min(checkout, notify_display_dt) )
                    $end_for_cancel = $notify_display_dt < DateTimeImmutable::createFromMutable($checkout_dt) ? $notify_display_dt : DateTimeImmutable::createFromMutable($checkout_dt);
                    $effective_nights_until_notify = $countEligibleNights($checkin_dt, $end_for_cancel, $selectedDays, $holidays);
                } else {
                    $effective_nights_until_notify = 0;
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
                ];
            }

            // Aggregations WITHOUT cancellation: use nights_full for each property
            $final_total_no_cancel = 0.0;
            $service_fee_no_cancel = 0.0;
            foreach ($perProperty as $pp) {
                $final_total_no_cancel += $pp['night_price'] * $nights_full;
                $service_fee_no_cancel += ($booking['service_fee'] ?? 'No') === 'Yes' ? ($nights_full * 0.5) : 0;
            }
            // service_fee_no_cancel currently counts per property nights_full*0.5 repeatedly; divide by properties already looped above is correct.

            // Aggregations WITH cancellation: for properties marked cancelled use nights_until_notify, otherwise use nights_full
            $final_total_with_cancel = 0.0;
            $service_fee_with_cancel = 0.0;
            foreach ($perProperty as $pp) {
                $effective_nights = ($pp['is_cancelled'] === 'Yes') ? $pp['nights_until_notify'] : $nights_full;
                $final_total_with_cancel += $pp['night_price'] * $effective_nights;
                if (($booking['service_fee'] ?? 'No') === 'Yes') {
                    $service_fee_with_cancel += ($effective_nights * 0.5);
                }
            }
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
    <h1 class="mb-4">Edit Booking #<?= htmlspecialchars((string)$bookingId, ENT_QUOTES) ?></h1>

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

        <!-- Payment Plan: WITHOUT Cancellation -->
        <div class="card mb-3">
            <div class="card-header">Payment Plan — Without Cancellation</div>
            <div class="card-body">
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Checkin - Checkout</th>
                            <th>Notify - Due (per property)</th>
                            <th>Deposit (£)</th>
                            <th>Service Fee (£)</th>
                            <th>Final Total (£)</th>
                            <th>Nights</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <?php
                                if (isset($checkin_dt) && isset($checkout_dt)) {
                                    echo htmlspecialchars($checkin_dt->format('d/m/Y') . ' / ' . $checkout_dt->format('d/m/Y'));
                                } else {
                                    echo htmlspecialchars(($booking['checkin'] ?? '') . ' / ' . ($booking['checkout'] ?? ''));
                                }
                                ?>
                            </td>
                            <td><?= htmlspecialchars(implode(' / ', $notify_display_list)) ?></td>
                            <td>£<?= number_format($deposit_total, 2) ?></td>
                            <td>£<?= number_format($service_fee_no_cancel, 2) ?></td>
                            <td>£<?= number_format($final_total_no_cancel, 2) ?></td>
                            <td><?= (int)$nights_full ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Payment Plan: WITH Cancellation -->
        <div class="card mb-3">
            <div class="card-header">Payment Plan — With Cancellation</div>
            <div class="card-body">
                <p class="small text-muted mb-2">For properties marked "Is Cancelled = Yes", nights after the notify-due (displayed above) are ignored.</p>
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Checkin - Checkout</th>
                            <th>Notify - Due (per property)</th>
                            <th>Deposit (£)</th>
                            <th>Service Fee (£)</th>
                            <th>Final Total (£)</th>
                            <th>Nights (effective)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <?php
                                if (isset($checkin_dt) && isset($checkout_dt)) {
                                    echo htmlspecialchars($checkin_dt->format('d/m/Y') . ' / ' . $checkout_dt->format('d/m/Y'));
                                } else {
                                    echo htmlspecialchars(($booking['checkin'] ?? '') . ' / ' . ($booking['checkout'] ?? ''));
                                }
                                ?>
                            </td>
                            <td><?= htmlspecialchars(implode(' / ', $notify_display_list)) ?></td>
                            <td>£<?= number_format($deposit_total, 2) ?></td>
                            <td>£<?= number_format($service_fee_with_cancel, 2) ?></td>
                            <td>£<?= number_format($final_total_with_cancel, 2) ?></td>
                            <td>
                                <?php
                                // Show effective nights per property (comma-separated) and total sum
                                $effList = [];
                                $sumEff = 0;
                                foreach ($perProperty as $pp) {
                                    $eff = ($pp['is_cancelled'] === 'Yes') ? $pp['nights_until_notify'] : $nights_full;
                                    $effList[] = $pp['title'] . ': ' . $eff;
                                    $sumEff += $eff;
                                }
                                echo htmlspecialchars(implode(' / ', $effList));
                                ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

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