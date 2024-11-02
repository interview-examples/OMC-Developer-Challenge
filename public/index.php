<?php

use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container;

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/settings.php';

use src\DatabaseManagement as DBManagement;
use src\SensorsOperations;
//use src\tests\DatabaseManagementTest as DBMTest;

$container = new Container();
AppFactory::setContainer($container);

$container->set('logger', function() {
    return new \Monolog\Logger('app_logger');
});

$container->set('db_access', function() {
    return [
        'host' => 'localhost',
        'port' => '27017',
        'database' => 'sensors_db'
    ];
});

/**
 * @OA\Info(title="OMC Developer Challenge", version="0.1")
 */

$app = AppFactory::create();

/**
 * @OA\Get(
 *     path="/",
 *     @OA\Response(
 *         response="200",
 *         description="Welcome screen"
 *     )
 * )
 */
$app->get('/',
    function (Request $request, Response $response, $args)
    {
        $response->getBody()->write('<h1>Welcome to the OMC Developer Challenge application</h1>');
        return $response;
    }
);

/**
 * @OA\Post(
 *     path="/sensor-register/",
 *     @OA\Response(
 *         response="200",
 *         description=""
 *     )
 * )
 */
$app->post('/sensor-details/',
    function (Request $request, Response $response, $args) use ($container)
    {
        $logger = $container->get('logger');
        $db_access = $container->get('db_access');

        $data = $request->getParsedBody();
        $sensor_details = [
            'sensorId' => $data['sensorId'],
            'sensorFace' => $data['sensorFace'],
            'sensorState' => $data['sensorState'] ?? true,
        ];

        $sensor = new SensorsOperations($db_access, $logger);
        if ($sensor->registerSensor($sensor_details)) {
            $response->getBody()->write('Sensor ' . $data["sensorId"] . ' registered successfully.');
        } else {
            $response->getBody()->write("Invalid sensor details");
        }

        return $response;
    }
);

$app->get('/sensor-details/',
    function (Request $request, Response $response, $args) use ($container)
    {
        $logger = $container->get('logger');
        $db_access = $container->get('db_access');

        $data = $request->getQueryParams();
        $sensor_details = [
            'sensorId' => $data['sensorId'],
        ];

        $sensor = new SensorsOperations($db_access, $logger);
        $res = $sensor->getOneSensorDetailsById($sensor_details);

        $response->getBody()->write('Sensor ' . $data["sensorId"] . ':');
        if (!is_null($res)) {
            $response->getBody()->write('<br/>');
            $response->getBody()->write(print_r($res, true));
        }

        return $response;
    }
);

/**
 * @OA\Get(
 *     path="/setup-database/",
 *     @OA\Response(
 *         response="200",
 *         description="Prepare databases. Needs one run only, but before of all anothers!"
 *     )
 * )
 */
$app->get('/setup-database/',
    function (Request $request, Response $response, $args) use ($container)
    {
        $logger = $container->get('logger');
        $db_access = $container->get('db_access');

        $db_manager = new DBManagement($db_access, $logger);
        $db_manager->setupDatabase();
        $response->getBody()->write($db_manager->getMessage());

        return $response;
    }
);

/**
 * Test endpoints =============================
 */
/**
 * @OA\Get(
 *     path="/",
 *     @OA\Response(
 *         response="200",
 *         description="Welcome screen"
 *     )
 * )
 */
$app->get('/tests/',
    function (Request $request, Response $response, $args)
    {
        $response->getBody()->write('<h1>OMC Developer Challenge tests</h1><h2>Functional tests area</h1>');
        return $response;
    }
);

/*$app->get('/tests/sensor-register/',
    function (Request $request, Response $response, $args) {
        $test = DBMTest::testSensorRegister(3);

        $response->getBody()->write($test);
        return $response;
    }
);*/

$app->addErrorMiddleware(true, true, true); // Note: must be added last

$app->run();
