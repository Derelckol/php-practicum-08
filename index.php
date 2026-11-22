<?php

require_once __DIR__.'/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') checkCsrf();
$pdo = require __DIR__.'/db.php';
require_once __DIR__.'/functions.php';

$venue = textValue($_GET['venue'] ?? '');
$showSoldOut = isset($_GET['sold_out']);

if ($venue !== '') {
    $events = findByVenue($pdo, $venue);
} elseif ($showSoldOut) {
    $events = soldOutList($pdo);
} else {
    $stmt = $pdo->query('SELECT * FROM events ORDER BY event_date');
    $events = $stmt->fetchAll();
}

?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Система бронювання квитків</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }

        th {
            background: #eee;
        }

        form {
            margin: 20px 0;
        }

        input, button, a {
            padding: 8px;
            margin: 4px;
        }

        .sold-out {
            color: red;
            font-weight: bold;
        }
    </style>
</head>

<body>

<h1>Система бронювання квитків</h1>

<p>
    Практична робота №8 — Варіант 15
</p>

<p>
    <a href="add.php">Додати подію</a>
    <a href="index.php">Усі події</a>
    <a href="index.php?sold_out=1">Квитки продані</a>
</p>

<form method="GET">
    <label>
        Пошук за місцем проведення:
        <input
            type="text"
            name="venue"
            value="<?= h($venue) ?>"
            placeholder="Наприклад, Stereo Plaza"
        >
    </label>

    <button type="submit">Знайти</button>
</form>

<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Назва</th>
        <th>Дата</th>
        <th>Місце проведення</th>
        <th>Залишилось місць</th>
        <th>Дії</th>
    </tr>
    </thead>

    <tbody>

    <?php foreach ($events as $event): ?>

        <tr>
            <td><?= $event['id'] ?></td>

            <td>
                <?= h($event['title']) ?>
            </td>

            <td>
                <?= h($event['event_date']) ?>
            </td>

            <td>
                <?= h($event['venue']) ?>
            </td>

            <td>
                <?php if ($event['seats_left'] == 0): ?>

                    <span class="sold-out">
                        Квитки продані
                    </span>

                <?php else: ?>

                    <?= $event['seats_left'] ?>

                <?php endif; ?>
            </td>

            <td>
                <a href="edit.php?id=<?= $event['id'] ?>">
                    Редагувати
                </a>

                <form method="post" action="delete.php" onsubmit="return confirm('Видалити подію?')">
                    <?= csrfField() ?><input type="hidden" name="id" value="<?= h($event['id']) ?>">
                    <button>Видалити</button>
                </form>
                <form method="post" action="buy.php">
                    <?= csrfField() ?><input type="hidden" name="id" value="<?= h($event['id']) ?>">
                    <button <?= $event['seats_left'] == 0 ? 'disabled' : '' ?>>Купити квиток</button>
                </form>
            </td>
        </tr>

    <?php endforeach; ?>

    </tbody>
</table>

<?php if (empty($events)): ?>

    <p>Подій не знайдено.</p>

<?php endif; ?>

</body>
</html>