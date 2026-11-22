<?php

require_once __DIR__.'/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') checkCsrf();
$pdo = require __DIR__.'/db.php';
require_once __DIR__.'/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    [$title, $eventDate, $venue, $seatsLeft] = validateEvent($_POST);

        addEvent(
            $pdo,
            $title,
            $eventDate,
            $venue,
            $seatsLeft
        );

        clearSoldOutCache();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header('Location: index.php', true, 303);
        exit;
}

?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Додати подію</title>
</head>

<body>

<h1>Додати подію</h1>

<?php if ($error !== ''): ?>

    <p style="color: red;">
        <?= h($error) ?>
    </p>

<?php endif; ?>

<form method="POST">
    <?= csrfField() ?>

    <p>
        <label>
            Назва:<br>
            <input type="text" name="title" required>
        </label>
    </p>

    <p>
        <label>
            Дата та час:<br>
            <input type="datetime-local" name="event_date" required>
        </label>
    </p>

    <p>
        <label>
            Місце проведення:<br>
            <input type="text" name="venue" required>
        </label>
    </p>

    <p>
        <label>
            Залишилось місць:<br>
            <input
                type="number"
                name="seats_left"
                min="0"
                required
            >
        </label>
    </p>

    <button type="submit">
        Додати
    </button>

</form>

<p>
    <a href="index.php">Назад</a>
</p>

</body>
</html>