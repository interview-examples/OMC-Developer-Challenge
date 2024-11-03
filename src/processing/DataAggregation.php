<?php

namespace App\Processing;

use App\Sensors\SensorFace;
use App\Sensors\SensorsOperations;
use App\Sensors\SensorValidator;
use http\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use App\DatabaseManagement;

class DataAggregation
{
    public const float DEVIATION_LIMIT = 0.2;
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
     * Aggregation data by the sensor
     * for period from period_starts_from up to period_starts_from + period
     *
     * @param int $sensor_id
     * @param int $period_starts_from (unix timestamp)
     * @param int $period (in seconds)
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
     * Aggregation data by the Face
     * for period from period_starts_from up to period_starts_from + period
     *
     * @param int $sensor_face (see SensorFace enum)
     * @param int $period_starts_from (unix timestamp)
     * @param int $period (in seconds)
     * @return float|null
     */
    public function aggregateDataSensorsByFace(SensorFace $sensor_face, int $period_starts_from, int $period): ?float
    {
        $sensor_faces_cursor = $this->db_manager->getSensorsListCollection()->find(
            [
                'sensorFace' => $sensor_face->value,
                'sensorState' => SensorsOperations::SENSOR_STATE_OK,
            ],
            ['projection' => ['sensorId' => 1]]);
        $sensor_faces = array_map(fn($doc) => $doc['sensorId'], iterator_to_array($sensor_faces_cursor));

        if (empty($sensor_faces)) {
            return null;
        }

        $cursor = $this->db_manager->getTemperaturesCollection()->aggregate(
            [
                ['$match' => ['sensorId' => ['$in' => $sensor_faces], 'timestamp' => ['$gte' => $period_starts_from, '$lt' => $period_starts_from + $period]]],
                ['$group' => ['_id' => null, 'avg_temp' => ['$avg' => '$temperature']]],
            ]
        );
        $tmp = iterator_to_array($cursor);

        return $tmp[0]['avg_temp'] ?? null;
    }

    public function createListOfSensorsWithDeviation(SensorFace $sensor_face, int $period_starts_from, int $period)
    {
        $face_avg_temp = $this->aggregateDataSensorsByFace($sensor_face, $period_starts_from, $period);
        $sensor_faces_cursor = $this->db_manager->getSensorsListCollection()->find(
            [
                'sensorFace' => $sensor_face->value,
                'sensorState' => SensorsOperations::SENSOR_STATE_OK,
            ],
            ['projection' => ['sensorId' => 1]]);
        $sensor_faces = array_map(fn($doc) => $doc['sensorId'], iterator_to_array($sensor_faces_cursor));
        $deviated_sensors = array_map(
            fn($sensor_id) => $this->aggregateDataSensorByID($sensor_id, $period_starts_from, $period),
            $sensor_faces
        );
        $deviated_sensors = array_filter(
            $deviated_sensors,
            static fn($avg) => $avg !== null && abs(($avg - $face_avg_temp) / $face_avg_temp) > DataAggregation::DEVIATION_LIMIT
        );
        $result = array_map(fn($key, $value) => ['sensorId' => $sensor_faces[$key], 'averageValue' => $value], array_keys($deviated_sensors), array_values($deviated_sensors));

        $this->db_manager->getSensorsListCollection()->updateMany(
            ['sensorId' => ['$in' => $sensor_faces]],
            ['$set' => ['isSensorOutlier' => false]]
        );
        $this->db_manager->getSensorsListCollection()->updateMany(
            ['sensorId' => ['$in' => array_column($result, 'sensorId')]],
            ['$set' => ['isSensorOutlier' => true]]
        );

        return $result;
    }

    public function createListOfFaultySensors(int $period_duration)
    {
        $critical_time = time() - $period_duration;

        $this->db_manager->getSensorsListCollection()->updateMany(
            [
                'sensorLastUpdate' => ['$lt' => $critical_time],
                'sensorState' => SensorsOperations::SENSOR_STATE_OK
            ],
            ['$set' => ['sensorState' => SensorsOperations::SENSOR_STATE_FAULTY]]
        );

        return $this->db_manager->getSensorsListCollection()->find(
            ['sensorState' => SensorsOperations::SENSOR_STATE_FAULTY],
            ['projection' => [
                'sensorId' => 1,
                'sensorFace' => 1,
                'sensorLastUpdate' => 1,
                ]
            ]
        )->toArray();
    }

    /**
     * Method calculate unix time for the 0:00 of the Monday of the previous week
     *
     * @return int
     */
    private function getPeriodStartUnixTimestamp(): int
    {
        $now = new DateTime();
        $now->modify('last monday');
        $now->modify('-1 week');
        $now->setTime(0, 0, 0);
        $unixtime = $now->getTimestamp();

        return $unixtime;
    }
}