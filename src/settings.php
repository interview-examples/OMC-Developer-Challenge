<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('app_logger');
$logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/app.log',));;
$logger->setTimezone(new \DateTimeZone('Asia/Jerusalem'));

$db_access = [
    'host' => 'mongodb://mongo',
    'port' => ':27017',
    'database' => 'omc_test',
    'username' => 'root',
    'password' => 'root',
    'options' => []
];
