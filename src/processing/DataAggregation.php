<?php

namespace App\Processing;

use App\Sensors\SensorsOperations;
use App\Sensors\SensorValidator;
use http\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use App\DatabaseManagement;

class DataAggregation
{
    private DatabaseManagement $db_manager;
    private LoggerInterface $logger;
    private SensorsOperations $sensors_operations;

    public function __construct(array $db_access, LoggerInterface $logger)
    {
        $this->logger = $logger;

        $this->db_manager = new DatabaseManagement($db_access, $logger);

        $this->sensors_operations = new SensorsOperations($db_access, $this->logger);
    }

    /**
     * @param int $sensor_id
     * @param int $period_starts_from (unix timestamp)
     * @param int $period
     * @return float|null
     */
    public function aggregateDataSensorByID(int $sensor_id, int $period_starts_from, int $period): ?float
    {
        if (SensorValidator::validateSensorId($sensor_id)) {
            $cursor = $this->db_manager->getTemperaturesCollection()->aggregate(
                [
                    ['$match' => ['sensorId' => $sensor_id, 'timestamp' => ['$gte' => $period_starts_from, '$lt' => $period_starts_from + $period]]],
                    ['$group' => ['_id' => '$id', 'avg_temp' => ['$avg' => '$temperature']]],
                ]
            );
            $tmp = iterator_to_array($cursor);
            $this->logger->debug('Aggregating data for sensor ', $tmp);
            return $tmp[0]['avg_temp'] ?? null;
        }
        throw new InvalidArgumentException('Sensor ID is not set correctly');
    }

    /**
     * @param int $sensor_face
     * @param int $period_starts_from (unix timestamp)
     * @param int $period
     * @return float|null
     */
    public function aggregateDataSensorsByFace(int $sensor_face, int $period_starts_from, int $period): ?float
    {
        if (SensorValidator::validateSensorFace($sensor_face)) {
            $sensor_ids_cursor = $this->db_manager->getSensorsListCollection()->find(['sensorFace' => $sensor_face], ['projection' => ['sensorId' => 1]]);
            $sensor_ids = array_map(fn($doc) => $doc['sensorId'], iterator_to_array($sensor_ids_cursor));

            if (empty($sensor_ids)) {
                return null;
            }

            $cursor = $this->db_manager->getTemperaturesCollection()->aggregate(
                [
                    ['$match' => ['sensorId' => ['$in' => $sensor_ids], 'timestamp' => ['$gte' => $period_starts_from, '$lt' => $period_starts_from + $period]]],
                    ['$group' => ['_id' => null, 'avg_temp' => ['$avg' => '$temperature']]],
                ]
            );
            $tmp = iterator_to_array($cursor);
            $this->logger->debug('Aggregating data for sensor ', $tmp);
            return $tmp[0]['avg_temp'] ?? null;
        }
        throw new InvalidArgumentException('Sensor ID is not set correctly');
    }
}