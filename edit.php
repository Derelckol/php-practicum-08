<?php

require_once __DIR__.'/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') checkCsrf();
$pdo = require __DIR__.'/db.php';
require_once __DIR__.'/functions.php';

$id = validId($_GET['id'] ?? null);

$stmt = $pdo->prepare(
    'SELECT * FROM events WHERE id = :id'
);

$stmt->execute([
    ':id' => $id
]);

$event = $stmt->fetch();

if (!$event) {
    fail(404, 'Подію не знайдено.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    [$title, $eventDate, $venue, $seatsLeft] = validateEvent($_POST);

        updateEvent(
            $pdo,
            $id,
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

$dateForInput = date(
    'Y-m-d\TH:i',
    strtotime($event['event_date'])
);

?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Редагування події</title>
</head>

<body>

<h1>Редагування події</h1>

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
            <input
                type="text"
                name="title"
                value="<?= h($event['title']) ?>"
                required
            >
        </label>
    </p>

    <p>
        <label>
            Дата та час:<br>
            <input
                type="datetime-local"
                name="event_date"
                value="<?= $dateForInput ?>"
                required
            >
        </label>
    </p>

    <p>
        <label>
            Місце проведення:<br>
            <input
                type="text"
                name="venue"
                value="<?= h($event['venue']) ?>"
                required
            >
        </label>
    </p>

    <p>
        <label>
            Залишилось місць:<br>
            <input
                type="number"
                name="seats_left"
                min="0"
                value="<?= $event['seats_left'] ?>"
                required
            >
        </label>
    </p>

    <button type="submit">
        Зберегти
    </button>

</form>

<p>
    <a href="index.php">Назад</a>
</p>

</body>
</html>