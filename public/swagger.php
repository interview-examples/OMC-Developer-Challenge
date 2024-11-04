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
        __DIR__ . '/../src'
    ],
    [
    'openapi' => '3.0.0',
    'info' => [
        'title' => 'My API',
        'version' => '1.0',
    ],
    'paths' => [
        '/users' => [
            'get' => [
                'summary' => 'Get users',
                'responses' => [
                    '200' => [
                        'description' => 'Users list',
                    ],
                ],
            ],
        ],
    ],
]);

header('Content-Type: application/json');
echo $openapi->toJson();
