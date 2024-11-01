<?php

namespace src;
use http\Exception\InvalidArgumentException;
use src\DatabaseManagement as DBManagement;
use src\SensorValidator;

class SensorsOperations
{
    private $db_manager;
    private $logger;

    public function __construct()
    {
        global $logger;
        $this->logger = $logger;

        $this->db_manager = new DBManagement();
    }

    /**
     * Registers (or rewrite existed)
     * @param array $sensor_params
     * @return bool
     */
    public function registerSensor(array $sensor_params):?bool
    {
        if (!SensorValidator::validate($sensor_params)) {
            $this->logger->error("Invalid sensor details");
            $res = null;
        } else {
            $this->db_manager->getSensorsListCollection()->insertOne($sensor_params);
            $res = true;
        }
        return $res;
    }

    /**
     * Gets details of the sensor by its Id.
     *
     * @param array $sensor_params
     * @return array|null - sensor data is sensorId, sensorFace, sensorState
     */
    public function getOneSensorDetailsById(array $sensor_params): ?array
    {
        if (array_key_exists('sensorId', $sensor_params) &&
            SensorValidator::validateSensorId($sensor_params['sensorId'])
        ) {
            $sensor = $this->db_manager->getDB()->SensorsList->findOne(
                [
                    'sensorId' => $sensor_params['sensorId']
                ]
            );
            $sensor_details = null;
            switch (gettype($sensor)) {
                case 'object':
                    $sensor_details = [
                        'sensorId' => $sensor->sensorId,
                        'sensorFace' => $sensor->sensorFace,
                        'sensorState' => $sensor->sensorState,
                    ];
                    break;
                case 'array':
                    $sensor_details = [
                        'sensorId' => $sensor['sensorId'],
                        'sensorFace' => $sensor['sensorFace'],
                        'sensorState' => $sensor['sensorState'],
                    ];
                    break;
                case 'NULL':
                    break;
            }
            return $sensor_details;
        }
        throw new InvalidArgumentException('Sensor ID is not set correctly');
    }

}