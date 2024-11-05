<?php
declare(strict_types=1);

use App\Controllers\AggregationController;
use App\Controllers\ReportController;
use App\Controllers\SensorController;
use App\DatabaseManagement as DBManagement;
use App\Processing\DataAggregation;
use App\Sensors\SensorFace;
use App\Sensors\SensorValidator;
use App\Sensors\SensorsOperations;
use DI\Container;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Twig\Loader\FilesystemLoader;

require __DIR__ . '/../vendor/autoload.php';
$settings = require __DIR__ . '/../src/settings.php';

$container = new Container();
$container->set('logger', $settings['logger']);
$container->set('db_access', $settings['db_access']);
$container->set(Twig\Loader\LoaderInterface::class, function()
{
    return new FilesystemLoader(__DIR__ . '/../templates');
});
$container->set(Twig::class, function($container)
{
    return new Twig($container->get(Twig\Loader\LoaderInterface::class), ['cache' => false]);
});
$container->set(DataAggregation::class, function ($c)
{
    return new DataAggregation($c->get('db_access'), $c->get('logger'));
});
$container->set(ReportController::class, function($container)
{
    return new ReportController(
        $container->get(DataAggregation::class),
        $container->get(Twig::class)
    );
});
$container->set(SensorsOperations::class, function($container)
{
    return new SensorsOperations(
        $container->get('db_access'),
        $container->get('logger')
    );
});
$container->set(SensorController::class, function($container)
{
    return new SensorController(
        $container->get(SensorsOperations::class),
        $container->get(Twig::class)
    );
});
$container->set(AggregationController::class, function($container)
{
    return new AggregationController(
        $container->get(DataAggregation::class),
        $container->get(Twig::class)
    );
});
$container->set('settings', function () {
    return require __DIR__ . '/../src/settings.php';
});
$twig = $container->get(Twig::class);

AppFactory::setContainer($container);

SensorValidator::setContainer($container);

$app = AppFactory::create();
$app->add(TwigMiddleware::createFromContainer($app, Twig::class));

/**
 * @OA\Info(
 *     title="OMC Developer Challenge",
 *     version="0.1"
 * )
 * @OA\PathItem(path="/")
 */
$app->get('/swagger.php', function (Request $request, Response $response, $args) {
    require __DIR__ . '/../public/swagger.php';
    return $response;
});
$app->get('/docs/', function ($request, $response) use ($container)
{
    $twig = $container->get(Twig::class);

    return $twig->render($response, 'swagger.twig');
});

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
 *     path="/sensor-details/",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="sensorId", type="integer"),
 *             @OA\Property(property="sensorFace", type="integer")
 *         )
 *     ),
 *     @OA\Response(
 *         response="200",
 *         description="Creates new sensor in the database. sensorID must be unique. sensorFace must be one of the following: 10, 20, 30, 40"
 *     )
 * )
 */
$app->post('/sensor-details/', [SensorController::class, 'registerSensor']);
/**
 * @OA\GET(
 *     path="/sensor-details/",
 *     @OA\Response(
 *         response="200",
 *         description="Returns JSON - details of the sensor by its Id."
 *     )
 * )
 */
$app->get('/sensor-details/', [SensorController::class, 'getSensorDetails']);
/**
 * @OA\GET(
 *     path="/html/sensor-details/",
 *     @OA\Response(
 *         response="200",
 *         description="Returns HTML - details of the sensor by its Id."
 *     )
 * )
 */
$app->get('/html/sensor-details/', [SensorController::class, 'getSensorDetailsHtml']);
$app->delete('/remove-sensor/', [SensorController::class, 'removeSensor']);
$app->post('/add-temperature/', [SensorController::class, 'addTemperature']);
$app->get('/sensor-data/', [SensorController::class, 'getSensorData']);

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

$app->get('/html/broken-sensors/',
    function (Request $request, Response $response) use ($twig)
    {
        return $twig->render($response, 'broken_sensors_input_params.twig');
    }
);

$app->get('/html/deviated-sensors/',
    function (Request $request, Response $response) use ($twig)
    {
        return $twig->render($response, 'deviated_sensors_input_params.twig');
    }
);

$app->get('/lastweek-report/', [ReportController::class, 'getLastWeekReportJson']);
$app->get('/html/lastweek-report/', [ReportController::class, 'getLastWeekReportHtml']);

$app->get('/html/aggregate-hourly/',
    function (Request $request, Response $response) use ($twig)
    {
        return $twig->render($response, 'aggregate_hourly_input_params.twig');
    }
);

$app->get('/aggregate-by-sensor/', [AggregationController::class, 'aggregateBySensor']);
$app->get('/aggregate-by-face/', [AggregationController::class, 'aggregateByFace']);
$app->get('/broken-sensors/', [AggregationController::class, 'listBrokenSensors']);
$app->get('/html/broken-sensors-response/', [AggregationController::class, 'listBrokenSensorsHtml']);
$app->get('/deviation-sensors/', [AggregationController::class, 'listDeviatedSensors']);
$app->get('/html/deviated-sensors-response/', [AggregationController::class, 'listDeviatedSensorsHtml']);
$app->get('/aggregate-hourly/', [AggregationController::class, 'aggregateHourly']);
$app->get('/html/aggregate-hourly-response/', [AggregationController::class, 'aggregateHourlyHtml']);
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
    return SensorFace::from($sensor_face_tmp)->name ?? '';
}
