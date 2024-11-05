<?php
require __DIR__ . '/../vendor/autoload.php';
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);
use OpenApi\Generator as OpenApiGenerator;

$openapi = OpenApiGenerator::scan(
    [
        __DIR__,
        __DIR__ . '/',
        __DIR__ . '/../public',
        __DIR__ . '/../src'
    ],
    [
    'openapi' => '3.1.0',
    'info' => [
        'title' => 'OMC Developer Challenge',
        'version' => '1.0',
    ],
]);

header('Content-Type: application/json');
readfile(__DIR__ . '/../docs/openapi.yaml');

echo $openapi->toJson();
