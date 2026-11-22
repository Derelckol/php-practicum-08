<?php

function findByVenue(PDO $pdo, string $venue): array
{
    $stmt = $pdo->prepare(
        'SELECT * FROM events WHERE venue = :venue ORDER BY event_date'
    );

    $stmt->execute([
        ':venue' => $venue
    ]);

    return $stmt->fetchAll();
}

function soldOutList(PDO $pdo): array
{
    $stmt = $pdo->prepare(
        'SELECT * FROM events WHERE seats_left = 0 ORDER BY event_date'
    );

    $stmt->execute();

    return $stmt->fetchAll();
}

function addEvent(
    PDO $pdo,
    string $title,
    string $eventDate,
    string $venue,
    int $seatsLeft
): void {
    $stmt = $pdo->prepare(
        'INSERT INTO events (title, event_date, venue, seats_left)
         VALUES (:title, :event_date, :venue, :seats_left)'
    );

    $stmt->execute([
        ':title' => $title,
        ':event_date' => $eventDate,
        ':venue' => $venue,
        ':seats_left' => $seatsLeft
    ]);
}

function updateEvent(
    PDO $pdo,
    int $id,
    string $title,
    string $eventDate,
    string $venue,
    int $seatsLeft
): void {
    $stmt = $pdo->prepare(
        'UPDATE events
         SET title = :title,
             event_date = :event_date,
             venue = :venue,
             seats_left = :seats_left
         WHERE id = :id'
    );

    $stmt->execute([
        ':title' => $title,
        ':event_date' => $eventDate,
        ':venue' => $venue,
        ':seats_left' => $seatsLeft,
        ':id' => $id
    ]);
}

function deleteEvent(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare(
        'DELETE FROM events WHERE id = :id'
    );

    $stmt->execute([
        ':id' => $id
    ]);
}