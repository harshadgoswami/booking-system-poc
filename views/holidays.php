<?php

/**
 * views/holidays.php
 * View template for holiday management
 */
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Add More Dates</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        .date-row {
            align-items: center;
        }

        .btn-space {
            margin-left: .5rem;
        }
    </style>
</head>

<body>
    <div class="container py-4">
        <div class="d-flex align-items-center mb-4">
            <h1 class="me-auto mb-0">Save Holidays</h1>
            <a href="index.php" class="btn btn-outline-secondary">Home</a>
        </div>

        <?php foreach ($successes as $message): ?>
            <div class="alert alert-success" role="alert"><?= htmlspecialchars($message, ENT_QUOTES) ?></div>
        <?php endforeach; ?>

        <?php foreach ($errors as $message): ?>
            <div class="alert alert-danger" role="alert"><?= htmlspecialchars($message, ENT_QUOTES) ?></div>
        <?php endforeach; ?>

        <form method="post" id="datesForm" novalidate>
            <div id="datesList" class="mb-3">
                <?php foreach ($holidays as $idx => $val): ?>
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
                const $input = $('<input>').attr({
                    type: 'date',
                    name: 'dates[]'
                }).addClass('form-control');
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