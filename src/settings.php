<?php

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;

$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    if (!mkdir($logDir, 0777, true) && !is_dir($logDir)) {
        throw new \RuntimeException(sprintf('Directory "%s" was not created', $logDir));
    }
}
if (!is_writable($logDir)) {
    throw new \RuntimeException(sprintf('Directory "%s" is not writable', $logDir));
}
chmod($logDir, 0777);

$logger = new Logger('app_logger');

$logger->pushHandler(new RotatingFileHandler($logDir. '/app.log', 7, Logger::DEBUG));
$logger->setTimezone(new \DateTimeZone('Asia/Jerusalem'));

$db_access = [
    'host' => 'mongodb://mongo',
    'port' => ':27017',
    'database' => 'omc_test',
    'username' => 'root',
    'password' => 'root',
    'options' => []
];

return [
    'logger' => $logger,
    'db_access' => $db_access
];