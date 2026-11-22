<?php
require_once __DIR__.'/bootstrap.php';
$pdo = new PDO(
    'mysql:host='.(getenv('DB_HOST') ?: '127.0.0.1').';port='.(getenv('DB_PORT') ?: '3306').';dbname='.(getenv('DB_NAME') ?: 'php_practicum_08').';charset=utf8mb4',
    getenv('DB_USER') ?: 'root', getenv('DB_PASSWORD') ?: '',
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
     PDO::ATTR_EMULATE_PREPARES=>false]);
return $pdo;
