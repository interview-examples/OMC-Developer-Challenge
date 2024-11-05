<?php

namespace App\Controllers;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Sensors\SensorsOperations;
use Slim\Views\Twig;


class SensorController extends BaseController
{
    private $sensors_operations;
    private $twig;

    public function __construct(SensorsOperations $sensors_operations, Twig $twig)
    {
        $this->sensors_operations = $sensors_operations;
        $this->twig = $twig;
    }

    public function registerSensor(Request $request, Response $response, $args): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);
        if (is_null($data)) {
            $response->getBody()->write("Invalid sensor details");
            return $response->withStatus(400);
        }

        $sensor_details = [
            'sensorId' => $data['sensorId'] ?? null,
            'sensorFace' => $data['sensorFace'] ?? null,
        ];

        if ($this->sensors_operations->registerSensor($sensor_details)) {
            $response->getBody()->write('Sensor ' . $data["sensorId"] . ' registered successfully.');
        } else {
            $response->getBody()->write("Invalid sensor details or Sensor already exists");
            $response = $response->withStatus(400);
        }

        return $response;
    }

    public function getSensorDetails(Request $request, Response $response, $args): Response
    {
        $data = $request->getQueryParams();
        $sensor_details = [
            'sensorId' => $data['sensorId'] ?? null,
        ];

        $res = $this->sensors_operations->getSensorDetailsById($sensor_details);

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

    public function removeSensor(Request $request, Response $response, $args): Response
    {
        $data = $request->getQueryParams();
        $sensor_details = [
            'sensorId' => $data['sensorId'] ?? null,
        ];

        $res = $this->sensors_operations->removeSensorById($sensor_details);

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

    public function addTemperature(Request $request, Response $response, $args): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);
        if (is_null($data)) {
            $response->getBody()->write("Invalid temperature data");
            return $response->withStatus(400);
        }

        $temperature_data = [
            'sensorId' => $data['sensorId'] ?? null,
            'temperature' => $data['temperature'] ?? null,
        ];

        if ($this->sensors_operations->addTemperatureData($temperature_data)) {
            $response->getBody()->write('Temperature data added successfully.');
        } else {
            $response->getBody()->write("Invalid temperature data or Sensor ID does not exist");
            $response = $response->withStatus(400);
        }

        return $response;
    }

    public function getSensorData(Request $request, Response $response, $args): Response
    {
        $data = $request->getQueryParams();
        $sensor_details = [
            'sensorId' => $data['sensorId'] ?? null,
        ];

        $res = $this->sensors_operations->getSensorDataById($sensor_details);

        $response->getBody()->write('Sensor ' . $data["sensorId"] . ':');
        if (!is_null($res)) {
            $response->getBody()->write('<br/>');
            $response->getBody()->write(print_r($res, true));
        }

        return $response;
    }

    public function getSensorDetailsHtml(Request $request, Response $response, $args): Response
    {
        $data = $request->getQueryParams();
        $sensor_details = [
            'sensorId' => $data['sensorId'] ?? null,
        ];

        $sensor_res = $this->sensors_operations->getSensorDetailsById($sensor_details);

        if (!is_null($sensor_res)) {
            $sensor_res['sensorFace'] = getSensorFaceName((int)($sensor_res['sensorFace'] ?? 0));
            $sensor_res['sensorLastUpdate'] = date('Y-m-d H:i:s', $sensor_res['sensorLastUpdate']);
            return $this->twig->render($response, 'sensor_details.twig', $sensor_res);
        } else {
            $response->getBody()->write('Sensor ' . $data["sensorId"] . ' not registered.');
            return $response;
        }
    }
}