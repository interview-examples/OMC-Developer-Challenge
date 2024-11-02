<?php
declare(strict_types=1);

use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container;

require __DIR__ . '/../vendor/autoload.php';
$settings = require __DIR__ . '/../src/settings.php';

use App\DatabaseManagement as DBManagement;
use \App\Sensors\SensorFace;
use App\Sensors\SensorValidator;
use App\Sensors\SensorsOperations;
use \App\Processing\DataAggregation;

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
    function (Request $request, Response $response, $args) use ($container): Response
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

$app->get('/sensor-data/',
    function (Request $request, Response $response, $args) use ($container)
    {
        $logger = $container->get('logger');
        $db_access = $container->get('db_access');

        $data = $request->getQueryParams();
        $sensor_details = [
            'sensorId' => $data['sensorId'],
        ];

        $sensor = new SensorsOperations($db_access, $logger);
        $res = $sensor->getOneSensorDataById($sensor_details);

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

$app->post('/add-temperature/',
    function (Request $request, Response $response, $args) use ($container)
    {
        $logger = $container->get('logger');
        $db_access = $container->get('db_access');

        $data = json_decode($request->getBody()->getContents(), true);
        if (is_null($data)) {
            $response->getBody()->write("Invalid temperature data");
            return $response->withStatus(400);
        }

        $temperature_data = [
            'sensorId' => $data['sensorId'],
            'timestamp' => $data['timestamp'],
            'temperature' => $data['temperature'],
        ];

        $sensor = new SensorsOperations($db_access, $logger);
        if ($sensor->addTemperatureData($temperature_data)) {
            $response->getBody()->write('Temperature data added successfully.');
        } else {
            $response->getBody()->write("Invalid temperature data or Sensor ID does not exist");
            $response = $response->withStatus(400);
        }

        return $response;
    }
);

$app->get('/aggregate-by-sensor/',
    function (Request $request, Response $response, $args) use ($container)
    {
        $logger = $container->get('logger');
        $db_access = $container->get('db_access');

        $data = $request->getQueryParams();

        $sensor = new DataAggregation($db_access, $logger);
        $avg_temp = $sensor->aggregateDataSensorByID((int)$data['sensorId'], (int)$data['start_from'], (int)$data['period']);

        $response->getBody()->write('Sensor ' . $data["sensorId"] . ':');
        if (!is_null($avg_temp)) {
            $response->getBody()->write('<br/>');
            $response->getBody()->write(number_format($avg_temp, 2));
        }

        return $response;
    }
);

$app->get('/aggregate-by-face/',
    function (Request $request, Response $response, $args) use ($container)
    {
        $logger = $container->get('logger');
        $db_access = $container->get('db_access');

        $data = $request->getQueryParams();

        $sensor = new DataAggregation($db_access, $logger);
        $avg_temp = $sensor->aggregateDataSensorsByFace((int)$data['sensorFace'], (int)$data['start_from'], (int)$data['period']);

        $response->getBody()->write('Face ' . strtoupper(SensorFace::from((int)$data["sensorFace"])->name). ':');
        if (!is_null($avg_temp)) {
            $response->getBody()->write('<br/>');
            $response->getBody()->write(number_format($avg_temp, 2));
        }

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
 *         description="Test area Welcome screen"
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
        ], JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json');
    }
);

$app->delete('/tests/remove-all-temperatures/',
    function (Request $request, Response $response, $args) use ($container)
    {
        $logger = $container->get('logger');
        $db_access = $container->get('db_access');

        $db_manager = new DBManagement($db_access, $logger);
        $result = $db_manager->getTemperaturesCollection()->deleteMany([]);
        $response->getBody()->write(json_encode([
            'deletedCount' => $result->getDeletedCount()
        ], JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json');
    }
);

$app->addErrorMiddleware(true, true, true); // Note: must be added last

$app->run();
