<?php

namespace src;

use http\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use src\DatabaseManagement as DBManagement;

class SensorsOperations
{
    private DatabaseManagement $db_manager;
    private LoggerInterface $logger;

    /**
     * @throws \Exception
     */
    public function __construct(array $db_access, LoggerInterface $logger)
    {
        $this->logger = $logger;

        $this->db_manager = new DBManagement($db_access, $logger);
    }

    /**
     * Registers new sensor
     * @param array $sensor_params
     * @return bool | null
     */
    public function registerSensor(array $sensor_params): ?bool
    {
        $res = null;
        if (!SensorValidator::validate($sensor_params)) {
            $this->logger->error("Invalid sensor details");
        } else {
            try {
                $this->db_manager->getSensorsListCollection()->insertOne($sensor_params);
                $res = true;
            } catch (\Exception $e) {
                $this->logger->error("Error in registerSensor method: " . $e->getMessage());
            }
        }
        return $res;
    }

    /**
     * Updates existed sensor
     * @param array $sensor_params
     * @return bool | null
     */
    public function updateSensor(array $sensor_params): ?bool
    {
        $res = null;
        if (!SensorValidator::validate($sensor_params)) {
            $this->logger->error("Invalid sensor details");
        } else {
            try {
                $this->db_manager->getSensorsListCollection()->deleteOne(
                    [
                        'sensorId' => $sensor_params['sensorId']
                    ]
                );
                $this->db_manager->getSensorsListCollection()->insertOne($sensor_params);

                $res = true;
            } catch (\Exception $e) {
                $this->logger->error("Error in updateSensor method: " . $e->getMessage());
            }
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
            $sensor = $this->db_manager->getSensorsListCollection()->findOne(
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