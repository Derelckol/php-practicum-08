<?php
require_once __DIR__.'/bootstrap.php';
postOnly();
$id = validId($_POST['id'] ?? null);
$pdo = require __DIR__.'/db.php';
$stmt = $pdo->prepare('UPDATE events SET seats_left = seats_left - 1 WHERE id = ? AND seats_left > 0');
$stmt->execute([$id]);
if ($stmt->rowCount() !== 1) fail(409, 'Події немає або квитки закінчилися');
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
clearSoldOutCache();
header('Location: index.php', true, 303);
