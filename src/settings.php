<?php
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

$logger = new Logger('app_logger');
$logger->pushHandler(new RotatingFileHandler(__DIR__ . '/../logs/app.log', 7, 100));
$logger->setTimezone(new \DateTimeZone('Asia/Jerusalem'));

$db_access = [
    'host' => 'mongodb://mongo',
    'port' => ':27017',
    'database' => 'omc_test',
    'username' => 'root',
    'password' => 'root',
    'options' => []
];
