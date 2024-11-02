<?php

use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container;

require __DIR__ . '/../vendor/autoload.php';
$settings = require __DIR__ . '/../src/settings.php';

use src\DatabaseManagement as DBManagement;
use src\SensorValidator;
use src\SensorsOperations;
//use src\tests\DatabaseManagementTest as DBMTest;

$container = new Container();
$container->set('logger', $settings['logger']);
$container->set('db_access', $settings['db_access']);
AppFactory::setContainer($container);

SensorValidator::setContainer($container);

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

        $data = json_decode($request->getBody()->getContents(), true);
        if (is_null($data)) {
            $response->getBody()->write("Invalid sensor details");
            return $response->withStatus(400);
        }

        $sensor_details = [
            'sensorId' => $data['sensorId'],
            'sensorFace' => $data['sensorFace'],
            'sensorState' => $data['sensorState'] ?? true,
        ];

        $sensor = new SensorsOperations($db_access, $logger);
        if ($sensor->registerSensor($sensor_details)) {
            $response->getBody()->write('Sensor ' . $data["sensorId"] . ' registered successfully.');
        } else {
            $response->getBody()->write("Invalid sensor details or Sensor already exists");
            $response = $response->withStatus(400);
        }

        return $response;
    }
);

/**
 * @OA\Put(
 *     path="/sensor-register/",
 *     @OA\Response(
 *         response="200",
 *         description=""
 *     )
 * )
 */
$app->put('/sensor-details/',
    function (Request $request, Response $response, $args) use ($container)
    {
        $logger = $container->get('logger');
        $db_access = $container->get('db_access');

        $data = json_decode($request->getBody()->getContents(), true);
        if (is_null($data)) {
            $response->getBody()->write("Invalid sensor details");
            return $response->withStatus(400);
        }

        $sensor_details = [
            'sensorId' => $data['sensorId'],
            'sensorFace' => $data['sensorFace'],
            'sensorState' => $data['sensorState'] ?? true,
        ];

        $sensor = new SensorsOperations($db_access, $logger);
        if ($sensor->updateSensor($sensor_details)) {
            $response->getBody()->write('Sensor ' . $data["sensorId"] . ' updated successfully.');
        } else {
            $response->getBody()->write("Invalid sensor details");
            $response = $response->withStatus(400);
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

$app->get('/tests/get-sensors/',
    function (Request $request, Response $response, $args) use ($container)
    {
        $logger = $container->get('logger');
        $db_access = $container->get('db_access');

        $db_manager = new DBManagement($db_access, $logger);
        $cursor = $db_manager->getSensorsListCollection()->find();
        $sensors = iterator_to_array($cursor);
        $response->getBody()->write(json_encode($sensors));

        return $response->withHeader('Content-Type', 'application/json');
    }
);

$app->delete('/tests/remove-all-sensors/',
    function (Request $request, Response $response, $args) use ($container)
    {
        $logger = $container->get('logger');
        $db_access = $container->get('db_access');

        $db_manager = new DBManagement($db_access, $logger);
        $result = $db_manager->getSensorsListCollection()->deleteMany([]);
        $response->getBody()->write(json_encode([
            'deletedCount' => $result->getDeletedCount()
        ]));

        return $response->withHeader('Content-Type', 'application/json');
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
