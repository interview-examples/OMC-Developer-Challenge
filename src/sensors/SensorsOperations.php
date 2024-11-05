<?php
declare(strict_types=1);

namespace App\Sensors;

use Psr\Log\LoggerInterface;
use App\DatabaseManagement;

class SensorsOperations
{
    public const SENSOR_STATE_FAULTY = false;
    public const SENSOR_STATE_OK = true;
    private DatabaseManagement $db_manager;
    private LoggerInterface $logger;

    /**
     * @throws \Exception
     */
    public function __construct(array $db_access, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->db_manager = new DatabaseManagement($db_access, $logger);
    }

    /**
     * Registers new sensor
     * @param array $sensor_params
     * @return bool | null
     */
    public function registerSensor(array $sensor_params): ?bool
    {
        $res = null;
        if (!SensorValidator::validateSensorDetails($sensor_params)) {
            $this->logger->error("Invalid sensor details");
        } else {
            try {
                $sensor_params['sensorState'] = true;
                $sensor_params['isSensorOutlier'] = false;
                $sensor_params['sensorLastUpdate'] = 0;
                $this->db_manager->getSensorsListCollection()->insertOne($sensor_params);
                $res = true;
            } catch (\Exception $e) {
                $this->logger->error("Error in registerSensor method: " . $e->getMessage());
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
    public function getSensorDetailsById(array $sensor_params): ?array
    {
        if (array_key_exists('sensorId', $sensor_params) &&
            SensorValidator::validateSensorId($sensor_params['sensorId'])
        ) {
            $sensor = $this->db_manager->getSensorsListCollection()->findOne(
                [
                    'sensorId' => (int)$sensor_params['sensorId']
                ]
            );
            if (!is_null($sensor)) {
                $res = [
                    'sensorId' => $sensor->sensorId,
                    'sensorFace' => $sensor->sensorFace,
                    'sensorState' => $sensor->sensorState ? 'true' : 'false',
                    'isSensorOutlier' => $sensor->isSensorOutlier ? 'true' : 'false',
                    'sensorLastUpdate' => $sensor->sensorLastUpdate ?? 0,
                ];
            } else {
                $res = null;
                $this->logger->warning("Sensor ID does not exist");
            }
            return $res;
        }
        throw new \InvalidArgumentException('Sensor ID is not set correctly');
    }

    /**
     * Adds temperature data to the database for sensor by its ID
     * @param array $temperature_data
     * @return bool|null
     */
    public function addTemperatureData(array $temperature_data): ?bool
    {
        $res = null;
        if (!SensorValidator::validateTemperatureData($temperature_data)) {
            $this->logger->error("Invalid temperature data", $temperature_data);
        } else {
            try {
                $sensor = $this->db_manager->getSensorsListCollection()->findOne(
                    ['sensorId' => $temperature_data['sensorId']]
                );
                if ($sensor) {
                    if ($sensor->sensorState === false || $sensor->isSensorOutlier === 0) {
                        $this->logger->info("Sensor " . $temperature_data['sensorId'] . " is disabled. Value of sensorState: " . $sensor->sensorState);
                        $res = false;
                    } else {
                        $temperature_data['timestamp'] = time();
                        $this->db_manager->getTemperaturesCollection()->insertOne($temperature_data);
                        $this->refreshSensorLastUpdate($temperature_data['sensorId'], $temperature_data['timestamp']);
                        $res = true;
                    }
                } else {
                    $this->logger->warning("Sensor ID does not exist");
                }
            } catch (\Exception $e) {
                $this->logger->critical("Error in addTemperatureData method: " . $e->getMessage());
            }
        }
        return $res;
    }

    private function refreshSensorLastUpdate(int $sensor_id, int $timestamp = 0): void
    {
        $this->logger->debug("Sensor " . $sensor_id . " updated at " . $timestamp);
        $this->db_manager->getSensorsListCollection()->updateOne(
            ['sensorId' => $sensor_id],
            ['$set' => ['sensorLastUpdate' => $timestamp === 0 ? time() : $timestamp]]
        );
    }

    /**
     * Return sensor data (i.e. stored temperature) by its ID
     * @param array $sensor_params
     * @return array
     */
    public function getSensorDataById(array $sensor_params):array
    {
        if (array_key_exists('sensorId', $sensor_params) &&
            SensorValidator::validateSensorId($sensor_params['sensorId'])
        ) {
            $sensor_data = $this->db_manager->getTemperaturesCollection()->find(
                [
                    'sensorId' => (int)$sensor_params['sensorId']
                ],
                [
                    'sort' => ['timestamp' => -1]
                ]
            )->toArray();

            $sensor_details = [];
            foreach ($sensor_data as $item) {
                $sensor_details[] = [
                    'timestamp' => $item['timestamp'],
                    'temperature' => $item['temperature'],
                ];
            }
            return $sensor_details;
        }
        throw new InvalidArgumentException('Sensor ID is not set correctly');
    }

    /**
     * Future implementation
     * @param int $sensor_id
     * @return string
     */
    public function getIdBySensorId(int $sensor_id): string
    {
        $sensor = $this->db_manager->getSensorsListCollection()->findOne(['sensorId' => $sensor_id]);

        return $sensor->_id;
    }

    /**
     * Removes sensor by its ID
     *
     * @param array $sensor_params
     * @return bool|null
     */
    public function removeSensorById(array $sensor_params): ?bool
    {
        $res = null;
        if (array_key_exists('sensorId', $sensor_params) &&
            SensorValidator::validateSensorId($sensor_params['sensorId'])
        ) {
            try {
                $this->db_manager->getTemperaturesCollection()->deleteMany(
                    ['sensorId' => (int)$sensor_params['sensorId']]
                );
                $this->db_manager->getSensorsListCollection()->deleteOne(
                    ['sensorId' => (int)$sensor_params['sensorId']]
                );
                $res = true;
            } catch (\Exception $e) {
                $this->logger->critical("Error in removeSensor method: " . $e->getMessage());
            }
        } else {
            $this->logger->error("Invalid sensor ID");
        }
        return $res;
    }
}