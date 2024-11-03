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
            'sensorId' => $data['sensorId'] ?? null,
            'sensorFace' => $data['sensorFace'] ?? null,
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

$app->get('/sensor-details/',
    function (Request $request, Response $response, $args) use ($container)
    {
        $logger = $container->get('logger');
        $db_access = $container->get('db_access');

        $data = $request->getQueryParams();

        $sensor_details = [
            'sensorId' => $data['sensorId'] ?? null,
        ];

        $sensor = new SensorsOperations($db_access, $logger);
        $res = $sensor->getSensorDetailsById($sensor_details);

        if (!is_null($res)) {
            $res['sensorFace'] = getSensorFaceName((int)($res['sensorFace'] ?? 0));
            $response->getBody()->write('Sensor ' . $data["sensorId"] . ':');
            $response->getBody()->write('<br/>');
            $response->getBody()->write(print_r($res, true));
        } else {
            $response->getBody()->write('Sensor ' . $data["sensorId"] . ' not registered.');
        }

        return $response;
    }
);

$app->delete('/remove-sensor/',
    function (Request $request, Response $response, $args) use ($container)
    {
        $logger = $container->get('logger');
        $db_access = $container->get('db_access');

        $data = $request->getQueryParams();

        $sensor_details = [
            'sensorId' => $data['sensorId'] ?? null,
        ];

        $sensor = new SensorsOperations($db_access, $logger);
        $res = $sensor->removeSensorById($sensor_details);

        $response->getBody()->write('Sensor ' . $data["sensorId"] . ':');
        if ($res) {
            $response->getBody()->write('<br/>');
            $response->getBody()->write('Sensor removed successfully');
        } else {
            $response->getBody()->write('<br/>');
            $response->getBody()->write('Sensor not found');
        }

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
            'sensorId' => $data['sensorId'] ?? null,
            'temperature' => $data['temperature'] ?? null,
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

$app->get('/sensor-data/',
    function (Request $request, Response $response, $args) use ($container)
    {
        $logger = $container->get('logger');
        $db_access = $container->get('db_access');

        $data = $request->getQueryParams();
        $sensor_details = [
            'sensorId' => $data['sensorId'] ?? null,
        ];

        $sensor = new SensorsOperations($db_access, $logger);
        $res = $sensor->getSensorDataById($sensor_details);

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

$app->get('/aggregate-by-sensor/',
    function (Request $request, Response $response, $args) use ($container)
    {
        $logger = $container->get('logger');
        $db_access = $container->get('db_access');

        $data = $request->getQueryParams();
        $sensor_id = (int)($data['sensorId'] ?? 0);
        $start_from = (int)($data['start_from'] ?? 0);
        $period = (int)($data['period'] ?? 0);

        $sensor = new DataAggregation($db_access, $logger);
        $avg_temp = $sensor->aggregateDataSensorByID($sensor_id, $start_from, $period);

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
        $start_from = (int)($data['start_from'] ?? 0);
        $period = (int)($data['period'] ?? 0);
        $avg_temp = null;

        $sensor = new DataAggregation($db_access, $logger);
        $sensor_face_tmp = (int)($data['sensorFace'] ?? 0);
        $sensor_face_value = SensorFace::tryfrom($sensor_face_tmp);
        if (is_null($sensor_face_value)) {
            $response->getBody()->write("Invalid sensor face");
            $response->withStatus(400);
        } else {
            $avg_temp = $sensor->aggregateDataSensorsByFace($sensor_face_value, $start_from, $period);

            $response->getBody()->write('Face ' . strtoupper(getSensorFaceName($sensor_face_tmp)). ':');
        }
        if (!is_null($avg_temp)) {
            $response->getBody()->write('<br/>');
            $response->getBody()->write(number_format($avg_temp, 2));
        }

        return $response;
    }
);

$app->get('/faulty-sensors/',
    function (Request $request, Response $response, $args) use ($container)
    {
        $logger = $container->get('logger');
        $db_access = $container->get('db_access');

        $data = $request->getQueryParams();

        $sensor = new DataAggregation($db_access, $logger);
        $res = $sensor->createListOfFaultySensors((int)($data['period_duration'] ?? 0));
        $response->getBody()->write(json_encode($res));

        return $response->withHeader('Content-Type', 'application/json');
    }
);

$app->get('/deviation-sensors/',
    function (Request $request, Response $response, $args) use ($container)
    {
        $logger = $container->get('logger');
        $db_access = $container->get('db_access');

        $data = $request->getQueryParams();
        $start_from = (int)($data['start_from'] ?? 0);
        $period = (int)($data['period'] ?? 0);

        $sensor = new DataAggregation($db_access, $logger);
        $sensor_face_tmp = (int)($data['sensorFace'] ?? 0);
        $sensor_face_value = SensorFace::tryfrom($sensor_face_tmp);
        if (is_null($sensor_face_value)) {
            $response->getBody()->write("Invalid sensor face");
            $response->withStatus(400);
        } else {
            $res = $sensor->createListOfSensorsWithDeviation($sensor_face_value, $start_from, $period);

            $response->getBody()->write('Face ' . strtoupper(getSensorFaceName($sensor_face_tmp)). ':');
            $response->getBody()->write(json_encode($res));
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

function getSensorFaceName($sensor_face): string
{
    $sensor_face_tmp = (int)($sensor_face ?? 0);
    $sensor_face_name = SensorFace::from($sensor_face_tmp)->name ?? '';

    return $sensor_face_name;
}
