<?php
require_once __DIR__.'/bootstrap.php';
postOnly();
$id = validId($_POST['id'] ?? null);
$pdo = require __DIR__.'/db.php';
require_once __DIR__.'/functions.php';
deleteEvent($pdo, $id);
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
clearSoldOutCache();
header('Location: index.php', true, 303);
