<?php

/**
 * views/index.php
 * Template for displaying all bookings with list and action links
 * Pure presentation - receives pre-processed data from entry point
 */
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Bookings</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container py-4">
        <div class="d-flex align-items-center mb-3">
            <h1 class="me-auto">Bookings</h1>
            <div class="btn-group">
                <a href="addmore-dates.php" class="btn btn-outline-primary">Add Holidays</a>
                <a href="property-form.php" class="btn btn-success">Create Booking</a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Checkin</th>
                        <th>Checkout</th>
                        <th>Days</th>
                        <th>Payment Plan</th>
                        <th>Service Fee</th>
                        <th>Exclude Holiday</th>
                        <th>Properties</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="10" class="text-center">No bookings found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $b): ?>
                            <tr>
                                <td><?= (int)$b['id'] ?></td>
                                <td><?= htmlspecialchars((new DateTime($b['checkin']))->format('d/m/Y')) ?></td>
                                <td><?= htmlspecialchars((new DateTime($b['checkout']))->format('d/m/Y')) ?></td>
                                <td>
                                    <?php
                                    $days = json_decode($b['days'] ?? '[]', true) ?: [];
                                    echo $days ? htmlspecialchars(implode(', ', $days)) : 'All';
                                    ?>
                                </td>
                                <td><?= htmlspecialchars($b['payment_plan'] ?? '') ?></td>
                                <td><?= htmlspecialchars($b['service_fee'] ?? 'No') ?></td>
                                <td><?= htmlspecialchars($b['exclude_bank_holiday'] ?? 'No') ?></td>
                                <td><?= (int)$b['prop_count'] ?></td>
                                <td><?= htmlspecialchars((new DateTime($b['created_at'] ?? date('Y-m-d')))->format('d/m/Y')) ?></td>
                                <td>
                                    <a href="edit-booking.php?bookingId=<?= (int)$b['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>