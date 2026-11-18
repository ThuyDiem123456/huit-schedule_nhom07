<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit('CLI only');
$checks = [
    'PHP 8.1+' => PHP_VERSION_ID >= 80100,
    'PDO' => extension_loaded('pdo'),
    'MySQL PDO driver (pdo_mysql)' => extension_loaded('pdo_mysql'),
    'Multibyte strings (mbstring)' => extension_loaded('mbstring'),
    'iconv' => extension_loaded('iconv'),
    'Composer vendor/autoload.php' => is_file(dirname(__DIR__) . '/vendor/autoload.php'),
    '.env configuration' => is_file(dirname(__DIR__) . '/.env'),
];
echo "HUIT Schedule Hub - Environment check\n\n";
foreach ($checks as $name => $okay) echo ($okay ? '[OK]  ' : '[FAIL] ') . $name . "\n";
echo "\n";
if (in_array(false, $checks, true)) {
    echo "Enable missing extensions in Laragon PHP, copy .env.example to .env, and run composer install.\n";
    exit(1);
}
echo "Prerequisites look ready. You can now import the SQL sample and run php database/seed.php.\n";
