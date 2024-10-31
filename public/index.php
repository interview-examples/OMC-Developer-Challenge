<?php
require __DIR__ . '/../vendor/autoload.php';
use Slim\Factory\AppFactory;
use MongoDB\Client;

$app = AppFactory::create();

$app->addRoutingMiddleware();




$app->addErrorMiddleware(true, true, true); // Note: must be added last

$app->run();
