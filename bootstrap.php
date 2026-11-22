<?php
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function fail(int $status, string $message): never {
    http_response_code($status);
    if (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'api.php') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success'=>false, 'error'=>$message], JSON_UNESCAPED_UNICODE);
    } else {
        header('Content-Type: text/html; charset=utf-8');
        echo '<p>'.h($message).'</p><a href="index.php">До списку</a>';
    }
    exit;
}
set_exception_handler(function(Throwable $e): void {
    error_log((string)$e);
    fail(500, 'Не вдалося виконати запит. Спробуйте пізніше.');
});
session_start(['use_strict_mode'=>true, 'cookie_httponly'=>true,
    'cookie_secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'cookie_samesite'=>'Lax']);
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="'.h($_SESSION['csrf_token']).'">';
}
function checkCsrf(): void {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? null;
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        fail(403, 'Недійсний CSRF-токен');
    }
}
function postOnly(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Allow: POST'); fail(405, 'Потрібен POST-запит');
    }
    checkCsrf();
}
function textValue($value): string {
    if (!is_string($value)) fail(400, 'Очікується текстове поле');
    return trim($value);
}
function validId($value): int {
    if (!is_string($value) && !is_int($value)) fail(400, 'Некоректний id');
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>4294967295]]);
    if ($id === false) fail(400, 'Некоректний id');
    return $id;
}
function validateEvent(array $data): array {
    $title = textValue($data['title'] ?? '');
    $venue = textValue($data['venue'] ?? '');
    $date = textValue($data['event_date'] ?? '');
    if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/D', $date)) $date = str_replace('T', ' ', $date).':00';
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $date);
    $seats = $data['seats_left'] ?? null;
    if ((!is_int($seats) && !is_string($seats)) ||
        ($seats = filter_var($seats, FILTER_VALIDATE_INT, ['options'=>['min_range'=>0,'max_range'=>4294967295]])) === false ||
        $title === '' || $venue === '' || mb_strlen($title)>255 || mb_strlen($venue)>255 ||
        !$parsed || $parsed->format('Y-m-d H:i:s') !== $date || (int)substr($date,0,4)<1000) {
        fail(400, 'Перевірте назву, місце, дату та цілу невід’ємну кількість місць');
    }
    return [$title, $date, $venue, $seats];
}
function clearSoldOutCache(): void {
    $file = soldOutCacheFile();
    if (is_file($file)) unlink($file);
}
function soldOutCacheFile(): string {
    return sys_get_temp_dir().'/php08_'.hash('sha256', __DIR__.(getenv('DB_NAME') ?: 'php_practicum_08')).'.json';
}
