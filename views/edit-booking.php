<?php

/**
 * views/edit-booking.php
 * Template for editing bookings - receives data from controller
 *
 * Expected variables:
 * - $booking: array with booking details
 * - $bookingId: int
 * - $properties: array of property data
 * - $periodTotalsNoCancel: array of period calculations
 * - $periodTotalsWithCancel: array (if applicable)
 * - $periods: array of payment periods
 * - $paidPeriodsSelected: array of selected period indexes
 * - $afterCancelHost: array (if applicable)
 * - $showWithCancel: bool
 * - $todayDt: DateTimeImmutable
 */

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
        .property-group {
            border: 1px solid #e3e3e3;
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 6px;
        }

        .btn-space {
            margin-left: .5rem;
        }
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
            Today: <?= htmlspecialchars($todayDt->format('d/m/Y'), ENT_QUOTES) ?>
        </div>

        <?php if (isset($_GET['saved'])): ?>
            <div class="alert alert-success">Saved successfully.</div>
        <?php endif; ?>

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
                                $dayLabels = ['mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri', 'sat' => 'Sat', 'sun' => 'Sun'];
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
                    if (count($properties) > 0):
                        foreach ($properties as $pidx => $p):
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
                                        <input type="number" min="0" name="properties[<?= $pidx ?>][notify_day]" class="form-control" value="<?= htmlspecialchars((string)$p['notify_day'] ?? '0', ENT_QUOTES) ?>">
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
                                        $periodStart = $row['start'];
                                        $origNotify = $periodStart->modify('-16 days');
                                        $origDue = $periodStart->modify('-7 days');
                                        $notifyReplaced = ($todayDt > $origNotify);
                                        $dueReplaced = ($todayDt > $origDue);
                                        $notifyDisplay = $notifyReplaced ? $todayDt : $origNotify;
                                        $dueDisplay = $dueReplaced ? $todayDt : $origDue;
                                        $ndStr = htmlspecialchars($notifyDisplay->format('d/m/Y'));
                                        $ddStr = htmlspecialchars($dueDisplay->format('d/m/Y'));
                                        if ($notifyReplaced) $ndStr = '<span style="color:green">' . $ndStr . '</span>';
                                        if ($dueReplaced) $ddStr = '<span style="color:green">' . $ddStr . '</span>';
                                        echo $ndStr . ' / ' . $ddStr;
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

            <?php if ($showWithCancel): ?>
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
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($periods as $i => $pr): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($pr['start']->format('d/m/Y') . ' / ' . $pr['end']->format('d/m/Y')) ?></td>
                                        <td><?php
                                            $periodStart = $pr['start'];
                                            $origNotify = $periodStart->modify('-16 days');
                                            $origDue = $periodStart->modify('-7 days');
                                            $notifyReplaced = ($todayDt > $origNotify);
                                            $dueReplaced = ($todayDt > $origDue);
                                            $notifyDisplay = $notifyReplaced ? $todayDt : $origNotify;
                                            $dueDisplay = $dueReplaced ? $todayDt : $origDue;
                                            $ndStr = htmlspecialchars($notifyDisplay->format('d/m/Y'));
                                            $ddStr = htmlspecialchars($dueDisplay->format('d/m/Y'));
                                            if ($notifyReplaced) $ndStr = '<span style="color:green">' . $ndStr . '</span>';
                                            if ($dueReplaced) $ddStr = '<span style="color:green">' . $ddStr . '</span>';
                                            echo $ndStr . ' / ' . $ddStr;
                                            ?></td>
                                        <td>£<?= number_format($periodTotalsWithCancel[$i]['deposit'], 2) ?></td>
                                        <td>£<?= number_format($periodTotalsWithCancel[$i]['service_fee'], 2) ?></td>
                                        <td>£<?= number_format($periodTotalsWithCancel[$i]['final_total'], 2) ?></td>
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
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($afterCancelHost['rows'])): ?>
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
                                <?php foreach ($afterCancelHost['rows'] as $hr): ?>
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
                                    <td>£<?= number_format($afterCancelHost['totals']['service_fee'], 2) ?></td>
                                    <td>£<?= number_format($afterCancelHost['totals']['final_total'], 2) ?></td>
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
    </div>

    <script>
        (function($) {
            let propIndex = 0;
            $('#propertiesContainer .property-group').each(function() {
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

            $('#addProperty').on('click', function() {
                $('#propertiesContainer').append(makePropertyHtml(propIndex));
                propIndex++;
            });

            $('#propertiesContainer').on('click', '.remove-property', function() {
                const groups = $('#propertiesContainer .property-group');
                if (groups.length <= 1) {
                    groups.find('input').val('');
                    groups.find('select').val('No');
                    return;
                }
                $(this).closest('.property-group').remove();
            });

            $('#mainForm').on('submit', function(e) {
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