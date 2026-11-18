<?php
declare(strict_types=1);
$projectRoot = dirname(__DIR__);
if (!is_file($projectRoot . '/vendor/autoload.php')) {
    http_response_code(503);
    exit('Thiếu thư viện Composer. Mở terminal trong thư mục huit-schedule và chạy: composer install');
}
require $projectRoot . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable($projectRoot)->safeLoad();
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Asia/Ho_Chi_Minh');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('HUIT_SCHEDULE_SESSION');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}
require_once __DIR__ . '/helpers.php';
