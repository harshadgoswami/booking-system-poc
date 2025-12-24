<?php
// property_from.php
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

    // Create tables if missing
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            checkin DATE NOT NULL,
            checkout DATE NOT NULL,
            days JSON DEFAULT '[]',
            service_fee ENUM('No','Yes') NOT NULL DEFAULT 'No',
            exclude_bank_holiday ENUM('No','Yes') NOT NULL DEFAULT 'No',
            payment_plan ENUM('weekly','fortnighly','Monthly','full') NOT NULL DEFAULT 'Monthly',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS properties (
            id INT AUTO_INCREMENT PRIMARY KEY,
            booking_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            night_price DECIMAL(10,2) NOT NULL DEFAULT 0,
            deposit DECIMAL(10,2) NOT NULL DEFAULT 0,
            checkout_date DATE DEFAULT NULL,
            is_cancelled ENUM('No','Yes') NOT NULL DEFAULT 'No',
            notify_day INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} catch (PDOException $e) {
    $error = 'Database connection failed: ' . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $checkin = trim((string)($_POST['checkin'] ?? ''));
    $checkout = trim((string)($_POST['checkout'] ?? ''));
    $days = $_POST['days'] ?? [];
    $service_fee = in_array($_POST['service_fee'] ?? 'No', ['Yes','No']) ? $_POST['service_fee'] : 'No';
    $exclude_bank_holiday = in_array($_POST['exclude_bank_holiday'] ?? 'No', ['Yes','No']) ? $_POST['exclude_bank_holiday'] : 'No';
    $payment_plan = in_array($_POST['payment_plan'] ?? 'Monthly', ['weekly','fortnighly','Monthly','full']) ? $_POST['payment_plan'] : 'Monthly';
    $properties = $_POST['properties'] ?? [];

    // Basic server-side validation
    $errors = [];

    $d1 = DateTime::createFromFormat('Y-m-d', $checkin);
    $d2 = DateTime::createFromFormat('Y-m-d', $checkout);
    if (!($d1 && $d1->format('Y-m-d') === $checkin)) {
        $errors[] = 'Invalid check-in date.';
    }
    if (!($d2 && $d2->format('Y-m-d') === $checkout)) {
        $errors[] = 'Invalid check-out date.';
    }
    if (empty($errors) && $d2 <= $d1) {
        $errors[] = 'Checkout date must be greater than checkin date.';
    }

    // Validate days (allow only known values)
    $validDays = ['mon','tue','wed','thu','fri','sat','sun'];
    $days = array_values(array_intersect($validDays, array_map('strtolower', $days)));

    // Validate properties array (expect array of arrays)
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

            if ($title === '') {
                $errors[] = "Property #".($idx+1).": title is required.";
            }
            if (!is_numeric($night_price)) {
                $errors[] = "Property #".($idx+1).": night price must be a number.";
            }
            if (!is_numeric($deposit)) {
                $errors[] = "Property #".($idx+1).": deposit must be a number.";
            }
            if ($p_checkout !== '') {
                $pcd = DateTime::createFromFormat('Y-m-d', $p_checkout);
                if (!($pcd && $pcd->format('Y-m-d') === $p_checkout)) {
                    $errors[] = "Property #".($idx+1).": invalid checkout date.";
                }
            } else {
                $p_checkout = null;
            }
            $notify_day_int = (int)$notify_day;
            if ($notify_day !== '' && (!ctype_digit((string)$notify_day) && $notify_day_int < 0)) {
                $errors[] = "Property #".($idx+1).": notify day must be a non-negative integer.";
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

            $insBooking = $pdo->prepare("INSERT INTO bookings (checkin, checkout, days, service_fee, exclude_bank_holiday, payment_plan) VALUES (:checkin, :checkout, :days, :service_fee, :exclude_bank_holiday, :payment_plan)");
            $insBooking->execute([
                ':checkin' => $checkin,
                ':checkout' => $checkout,
                ':days' => json_encode($days),
                ':service_fee' => $service_fee,
                ':exclude_bank_holiday' => $exclude_bank_holiday,
                ':payment_plan' => $payment_plan,
            ]);
            $bookingId = (int)$pdo->lastInsertId();

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

            
            $success = 'Booking and properties saved successfully.';
            $pdo->commit();
            // Clear post to avoid resubmission
            $_POST = [];
            // on success redirect to edit page for the newly created booking
            $bookingId = (int)$bookingId;
            header('Location: edit-booking.php?bookingId=' . $bookingId);
            exit;
            
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Failed to save: ' . $e->getMessage();
        }
    } else {
        $error = implode(' ', $errors);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Property Booking Form</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        .property-group { border: 1px solid #e3e3e3; padding: 12px; margin-bottom:10px; border-radius:6px; }
        .btn-space { margin-left:.5rem; }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="d-flex align-items-center mb-4">
        <h1 class="me-auto mb-0">Booking & Property Form</h1>
        <a href="index.php" class="btn btn-outline-secondary">Home</a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
    <?php endif; ?>

    <form method="post" id="mainForm" novalidate>
        <div class="card mb-3">
            <div class="card-header">Booking Form Details</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Check-in Date</label>
                        <input type="date" name="checkin" id="checkin" class="form-control" value="<?= htmlspecialchars($_POST['checkin'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Check-out Date</label>
                        <input type="date" name="checkout" id="checkout" class="form-control" value="<?= htmlspecialchars($_POST['checkout'] ?? '') ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Days</label>
                        <div class="d-flex gap-2 flex-wrap">
                            <?php
                            $dayLabels = ['mon'=>'Mon','tue'=>'Tue','wed'=>'Wed','thu'=>'Thu','fri'=>'Fri','sat'=>'Sat','sun'=>'Sun'];
                            $postedDays = $_POST['days'] ?? [];
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
                            <option value="No" <?= ($_POST['service_fee'] ?? 'No') === 'No' ? 'selected' : '' ?>>No</option>
                            <option value="Yes" <?= ($_POST['service_fee'] ?? '') === 'Yes' ? 'selected' : '' ?>>Yes</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Exclude Bank Holiday?</label>
                        <select name="exclude_bank_holiday" class="form-select">
                            <option value="No" <?= ($_POST['exclude_bank_holiday'] ?? 'No') === 'No' ? 'selected' : '' ?>>No</option>
                            <option value="Yes" <?= ($_POST['exclude_bank_holiday'] ?? '') === 'Yes' ? 'selected' : '' ?>>Yes</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Select Payment Plan</label>
                        <select name="payment_plan" class="form-select">
                            <option value="weekly" <?= ($_POST['payment_plan'] ?? '') === 'weekly' ? 'selected' : '' ?>>weekly</option>
                            <option value="fortnighly" <?= ($_POST['payment_plan'] ?? '') === 'fortnighly' ? 'selected' : '' ?>>fortnighly</option>
                            <option value="Monthly" <?= ($_POST['payment_plan'] ?? 'Monthly') === 'Monthly' ? 'selected' : '' ?>>Monthly</option>
                            <option value="full" <?= ($_POST['payment_plan'] ?? '') === 'full' ? 'selected' : '' ?>>full</option>
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
                <!-- existing property groups from POST if any -->
                <?php
                $postedProps = $_POST['properties'] ?? [];
                if (is_array($postedProps) && count($postedProps) > 0):
                    foreach ($postedProps as $pidx => $p):
                        $p = (array)$p;
            ?>
                <div class="property-group" data-index="<?= $pidx ?>">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Title</label>
                            <input type="text" name="properties[<?= $pidx ?>][title]" class="form-control" value="<?= htmlspecialchars($p['title'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Night Price</label>
                            <input type="number" step="0.01" name="properties[<?= $pidx ?>][night_price]" class="form-control" value="<?= htmlspecialchars($p['night_price'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Deposit</label>
                            <input type="number" step="0.01" name="properties[<?= $pidx ?>][deposit]" class="form-control" value="<?= htmlspecialchars($p['deposit'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Checkout Date</label>
                            <input type="date" name="properties[<?= $pidx ?>][checkout_date]" class="form-control" value="<?= htmlspecialchars($p['checkout_date'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="row g-2 align-items-end mt-2">
                        <div class="col-md-2">
                            <label class="form-label">Notify Day</label>
                            <input type="number" name="properties[<?= $pidx ?>][notify_day]" class="form-control" value="<?= htmlspecialchars($p['notify_day'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
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
                        <div class="col-md-6">
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
                    </div>

                    <div class="row g-2 align-items-end mt-2">
                        <div class="col-md-2">
                            <label class="form-label">Notify Day</label>
                            <input type="number" name="properties[0][notify_day]" class="form-control" value="">
                        </div>
                        <div class="col-md-2">
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

        <div class="mb-3">
            <button type="submit" class="btn btn-primary">Save Booking</button>
            <button type="reset" id="resetBtn" class="btn btn-secondary">Reset</button>
        </div>
    </form>
</div>

<script>
(function($){
    // property template generator using incremental index
    let propIndex = (function(){
        const existing = $('#propertiesContainer .property-group').length;
        return existing > 0 ? existing : 1;
    })();

    function makePropertyHtml(idx) {
        return `
        <div class="property-group" data-index="${idx}">
            <div class="row g-2">
                <div class="col-md-6">
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
            </div>

            <div class="row g-2 align-items-end mt-2">
                <div class="col-md-2">
                    <label class="form-label">Notify Day</label>
                    <input type="number" name="properties[${idx}][notify_day]" class="form-control" value="">
                </div>
                <div class="col-md-2">
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

    // delegated remove
    $('#propertiesContainer').on('click', '.remove-property', function(){
        const groups = $('#propertiesContainer .property-group');
        if (groups.length <= 1) {
            // clear inputs of the only group
            groups.find('input').val('');
            groups.find('select').val('No');
            return;
        }
        $(this).closest('.property-group').remove();
    });

    // client-side check: checkout > checkin
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

    // reset handler: restore one empty property group
    $('#resetBtn').on('click', function(){
        setTimeout(function(){
            $('#propertiesContainer').html(makePropertyHtml(0));
            propIndex = 1;
        }, 0);
    });
})(jQuery);
</script>
</body>
</html>