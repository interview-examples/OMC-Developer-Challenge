<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../src/settings.php';

use src\DatabaseManagement as DBmanagement;

$app = AppFactory::create();

$app->get('/',
    function (Request $request, Response $response, $args)
    {
        $response->getBody()->write('Welcome to the OMC Developer Challenge application!');
        return $response;
    }
);

$app->get('/setup-database/',
    function (Request $request, Response $response, $args) {
        $dbManager = new DBmanagement();
        $dbManager->setupDatabase();
        $response->getBody()->write($dbManager->getMessage());

        return $response;
    }
);

$app->addErrorMiddleware(true, true, true); // Note: must be added last

$app->run();
