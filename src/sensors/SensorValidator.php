<?php
declare(strict_types=1);

namespace App\Sensors;

use Psr\Container\ContainerInterface;

class SensorValidator
{
    private static $logger;

    public static function setContainer(ContainerInterface $container): void
    {
        self::$logger = $container->get('logger');
    }

    /**
     * @param $sensor_details
     * @return bool
     */
    public static function validateSensorDetails(&$sensor_details): bool
    {
        if (!is_array($sensor_details)) {
            $sensor_details = [];
        }
        $required_keys = [
            'sensorId' => null,
            'sensorFace' => null,
        ];
        foreach ($required_keys as $key => $default_value) {
            if (!isset($sensor_details[$key])) {
                $sensor_details[$key] = $default_value;
            }
        }

        $sensor_details = array_intersect_key($sensor_details, $required_keys);

        return self::validateSensorId($sensor_details['sensorId']) &&
            self::validateSensorFace($sensor_details['sensorFace']);
    }

    public static function validateSensorId($sensor_id): bool
    {
        $res = filter_var($sensor_id, FILTER_VALIDATE_INT) !== false &&
            $sensor_id >= 10000 && $sensor_id <= 99999;

        return $res;
    }

    public static function validateSensorFace($sensor_face): bool
    {
        if ($sensor_face instanceof SensorFace) {
            $res = true;
        } else {
            $res=SensorFace::tryFrom($sensor_face) !== null ||
                in_array($sensor_face, array_column(SensorFace::cases(), 'value'), true);
        }

        return $res;
    }

    public static function validateSensorState($sensor_state): bool
    {
        $res=in_array($sensor_state, [true, false, 1, 0, "1", "0"], true);

        return $res;
    }

    public static function validateTemperatureData(array $temperature_data)
    {
        if (!is_array($temperature_data)) {
            $temperature_data = [];
        }
        $required_keys = [
            'sensorId' => null,
            'temperature' => null,
        ];
        foreach ($required_keys as $key => $default_value) {
            if (!isset($temperature_data[$key])) {
                $temperature_data[$key] = $default_value;
            }
        }

        $temperature_data = array_intersect_key($temperature_data, $required_keys);

        return self::validateSensorId($temperature_data['sensorId']) &&
            self::validateTemperature($temperature_data['temperature']);
    }

    private static function validateTimestamp($timestamp): bool
    {
        $res = filter_var($timestamp, FILTER_VALIDATE_INT) !== false
            && ($timestamp <= PHP_INT_MAX)
            && ($timestamp >= ~PHP_INT_MAX);
        self::$logger->debug("validateTimestamp:", ["timestamp" => $timestamp, "result" => $res]);

        return $res;
    }

    private static function validateTemperature($temperature): bool
    {
        $res = filter_var($temperature, FILTER_VALIDATE_FLOAT) !== false;
        self::$logger->debug("validateTemperature:", ["temperature" => $temperature, "result" => $res]);

        return $res;
    }
}