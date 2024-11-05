<?php

namespace App\Controllers;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Processing\DataAggregation;
use App\Sensors\SensorFace;
use Slim\Views\Twig;

class AggregationController extends BaseController
{
    private $data_aggregation;
    private $twig;

    public function __construct(DataAggregation $data_aggregation, Twig $twig)
    {
        $this->data_aggregation = $data_aggregation;
        $this->twig = $twig;
    }

    public function aggregateBySensor(Request $request, Response $response, $args): Response
    {
        $data = $request->getQueryParams();
        $sensor_id = (int)($data['sensorId'] ?? 0);
        $start_from = (int)($data['start_from'] ?? 0);
        $period = (int)($data['period'] ?? 0);

        $avg_temp = $this->data_aggregation->aggregateDataSensorByID($sensor_id, $start_from, $period);

        $response->getBody()->write('Sensor ' . $data["sensorId"] . ':');
        if (!is_null($avg_temp)) {
            $response->getBody()->write('<br/>');
            $response->getBody()->write(number_format($avg_temp, 2));
        }

        return $response;
    }

    public function aggregateByFace(Request $request, Response $response, $args): Response
    {
        $data = $request->getQueryParams();
        $start_from = (int)($data['start_from'] ?? 0);
        $period = (int)($data['period'] ?? 0);
        $sensor_face_tmp = (int)($data['sensorFace'] ?? 0);
        $sensor_face_value = SensorFace::tryfrom($sensor_face_tmp);

        if (is_null($sensor_face_value)) {
            $response->getBody()->write("Invalid sensor face");
            return $response->withStatus(400);
        }

        $avg_temp = $this->data_aggregation->aggregateDataSensorsByFace($sensor_face_value, $start_from, $period);

        $response->getBody()->write('Face ' . strtoupper(getSensorFaceName($sensor_face_tmp)). ':');
        if (!is_null($avg_temp)) {
            $response->getBody()->write('<br/>');
            $response->getBody()->write(number_format($avg_temp, 2));
        }

        return $response;
    }

    public function listBrokenSensors(Request $request, Response $response, $args): Response
    {
        $data = $request->getQueryParams();
        $res = $this->data_aggregation->createListOfFaultySensors((int)($data['period_duration'] ?? 0));
        return $this->respondWithJson($response, $res);
    }

    public function listBrokenSensorsHtml(Request $request, Response $response, $args): Response
    {
        $data = $request->getQueryParams();
        $res = $this->data_aggregation->createListOfFaultySensors((int)($data['period_duration'] ?? 0));
        return $this->respondWithHtml($response, 'broken_sensors.twig', ['sensors' => $res], $this->twig);
    }

    public function listDeviatedSensors(Request $request, Response $response, $args): Response
    {
        $data = $request->getQueryParams();
        $start_from = (int)($data['start_from'] ?? 0);
        $period = (int)($data['period'] ?? 0);
        $sensor_face_tmp = (int)($data['sensorFace'] ?? 0);
        $sensor_face_value = SensorFace::tryfrom($sensor_face_tmp);

        if (is_null($sensor_face_value)) {
            $response->getBody()->write("Invalid sensor face");
            return $response->withStatus(400);
        }

        $res = $this->data_aggregation->createListOfSensorsWithDeviation($sensor_face_value, $start_from, $period);
        return $this->respondWithJson($response, $res);
    }

    public function listDeviatedSensorsHtml(Request $request, Response $response, $args): Response
    {
        $data = $request->getQueryParams();
        $start_from = (int)($data['start_from'] ?? 0);
        $period = (int)($data['period'] ?? 0);
        $sensor_face_tmp = (int)($data['sensor_face'] ?? 0);
        $sensor_face_value = SensorFace::tryfrom($sensor_face_tmp);

        if (is_null($sensor_face_value)) {
            $response->getBody()->write("Invalid Sensor Face value");
            return $response->withStatus(400);
        }

        $res = $this->data_aggregation->createListOfSensorsWithDeviation($sensor_face_value, $start_from, $period);
        return $this->respondWithHtml($response, 'deviated_sensors.twig', ['sensor_face' => getSensorFaceName($sensor_face_tmp), 'sensors' => $res], $this->twig);
    }

    public function aggregateHourly(Request $request, Response $response, $args): Response
    {
        $data = $request->getQueryParams();
        $start_from = (int)($data['start_from'] ?? 0);
        $res = $this->data_aggregation->createHourlyAggregatedReport($start_from);
        return $this->respondWithJson($response, $res);
    }

    public function aggregateHourlyHtml(Request $request, Response $response, $args): Response
    {
        $data = $request->getQueryParams();
        $start_from = (int)($data['start_from'] ?? 0);
        $res = $this->data_aggregation->createHourlyAggregatedReport($start_from);
        return $this->respondWithHtml($response, 'aggregate_hourly.twig', ['data' => $res], $this->twig);
    }
}