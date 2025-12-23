<?php
// addmore-dates.php
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
    // Connect with PDO
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_PERSISTENT => false,
    ]);

    // Create database schema if needed (creates holidays table with unique holiday_date)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS holidays (
            id INT AUTO_INCREMENT PRIMARY KEY,
            holiday_date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_holiday_date (holiday_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} catch (PDOException $e) {
    $error = 'Database connection failed: ' . $e->getMessage();
}

// Handle form submission: submitted dates are the source of truth.
// Dates removed from the form will be deleted from DB; new dates are inserted.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $posted = $_POST['dates'] ?? [];
    // Normalize and validate posted dates (keep valid Y-m-d only)
    $submittedDates = [];
    foreach ($posted as $d) {
        $d = trim((string)$d);
        if ($d === '') {
            continue;
        }
        $dt = DateTime::createFromFormat('Y-m-d', $d);
        if ($dt && $dt->format('Y-m-d') === $d) {
            $submittedDates[] = $d;
        }
    }

    try {
        // Fetch current dates from DB
        $dbDates = $pdo->query("SELECT holiday_date FROM holidays ORDER BY holiday_date ASC")->fetchAll(PDO::FETCH_COLUMN);
        if (!$dbDates) {
            $dbDates = [];
        }

        // Compute which to delete and which to insert
        // Normalize arrays to unique values
        $submittedDates = array_values(array_unique($submittedDates));
        $dbDates = array_values(array_unique($dbDates));

        $toDelete = array_values(array_diff($dbDates, $submittedDates));
        $toInsert = array_values(array_diff($submittedDates, $dbDates));

        if (empty($toDelete) && empty($toInsert)) {
            $success = 'No changes detected.';
        } else {
            $deleted = 0;
            $inserted = 0;

            $pdo->beginTransaction();

            if (!empty($toDelete)) {
                $delStmt = $pdo->prepare("DELETE FROM holidays WHERE holiday_date = :date");
                foreach ($toDelete as $d) {
                    $delStmt->execute([':date' => $d]);
                    $deleted += $delStmt->rowCount();
                }
            }

            if (!empty($toInsert)) {
                // Use INSERT IGNORE to be safe if concurrent insert happened
                $insStmt = $pdo->prepare("INSERT IGNORE INTO holidays (holiday_date) VALUES (:date)");
                foreach ($toInsert as $d) {
                    $insStmt->execute([':date' => $d]);
                    if ($insStmt->rowCount() === 1) {
                        $inserted++;
                    }
                }
            }

            $pdo->commit();
            $successParts = [];
            if ($inserted) { $successParts[] = "{$inserted} inserted"; }
            if ($deleted) { $successParts[] = "{$deleted} deleted"; }
            $success = implode(' and ', $successParts) . '.';
        }

        // After changes, refresh DB list for display
        $rows = $pdo->query("SELECT holiday_date FROM holidays ORDER BY holiday_date ASC")->fetchAll(PDO::FETCH_COLUMN);
        $initialDates = $rows && count($rows) > 0 ? $rows : [''];
        // Clear POST to avoid resubmission on refresh
        $_POST = [];
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = 'Failed to sync dates: ' . $e->getMessage();
        // Fall back to showing submitted values
        $initialDates = $_POST['dates'] ?? [''];
    }
} else {
    // On GET or error: load saved dates or show a single empty row
    if (empty($error) && isset($pdo)) {
        try {
            $rows = $pdo->query("SELECT holiday_date FROM holidays ORDER BY holiday_date ASC")->fetchAll(PDO::FETCH_COLUMN);
            $initialDates = $rows && count($rows) > 0 ? $rows : [''];
        } catch (PDOException $e) {
            $initialDates = $_POST['dates'] ?? [''];
        }
    } else {
        $initialDates = $_POST['dates'] ?? [''];
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Add More Dates</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- jQuery (required by request) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        .date-row { align-items: center; }
        .btn-space { margin-left: .5rem; }
    </style>
</head>
<body>
<div class="container py-4">
    <h1 class="mb-4">Submit Multiple Dates</h1>

    <?php if ($success): ?>
        <div class="alert alert-success" role="alert"><?= htmlspecialchars($success, ENT_QUOTES) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
    <?php endif; ?>

    <form method="post" id="datesForm" novalidate>
        <div id="datesList" class="mb-3">
            <?php foreach ($initialDates as $idx => $val): ?>
                <div class="row g-2 date-row mb-2" data-index="<?= $idx ?>">
                    <div class="col">
                        <input type="date" name="dates[]" class="form-control" value="<?= htmlspecialchars((string)$val, ENT_QUOTES) ?>">
                    </div>
                    <div class="col-auto d-flex">
                        <button type="button" class="btn btn-success add-row" title="Add"><strong>+</strong></button>
                        <button type="button" class="btn btn-danger remove-row btn-space" title="Remove"><strong>−</strong></button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mb-3">
            <button type="submit" class="btn btn-primary">Save Dates</button>
            <button type="button" id="resetBtn" class="btn btn-secondary">Reset</button>
        </div>
    </form>
</div>

<script>
    (function($) {
        function newRow() {
            const $row = $('<div>').addClass('row g-2 date-row mb-2');
            const $colInput = $('<div>').addClass('col');
            const $input = $('<input>').attr({type: 'date', name: 'dates[]'}).addClass('form-control');
            $colInput.append($input);

            const $colBtns = $('<div>').addClass('col-auto d-flex');
            const $add = $('<button type="button">').addClass('btn btn-success add-row').attr('title', 'Add').html('<strong>+</strong>');
            const $remove = $('<button type="button">').addClass('btn btn-danger remove-row btn-space').attr('title', 'Remove').html('<strong>−</strong>');
            $colBtns.append($add).append($remove);

            $row.append($colInput).append($colBtns);
            return $row;
        }

        $(function() {
            $('#datesList').on('click', '.add-row', function() {
                const $row = newRow();
                $(this).closest('.date-row').after($row);
            });

            $('#datesList').on('click', '.remove-row', function() {
                const $rows = $('#datesList .date-row');
                if ($rows.length <= 1) {
                    $rows.find('input[type="date"]').val('');
                    return;
                }
                $(this).closest('.date-row').remove();
            });

            $('#resetBtn').on('click', function() {
                $('#datesList').empty().append(newRow());
            });

            if ($('#datesList .date-row').length === 0) {
                $('#datesList').append(newRow());
            }
        });
    })(jQuery);
</script>
</body>
</html>