<?php

namespace src;

use Psr\Container\ContainerInterface;
use src\SensorFace;

class SensorValidator
{
    private static $logger;

    public static function setContainer(ContainerInterface $container)
    {
        self::$logger = $container->get('logger');
    }

    public static function validateSensorDetails(&$sensorDetails): bool
    {
        self::$logger->debug("SensorValidator::validate()");
        self::$logger->info("input", ["sensorDetails" => $sensorDetails]);

        if (!is_array($sensorDetails)) {
            $sensorDetails = [];
        }
        $requiredKeys = [
            'sensorId' => null,
            'sensorFace' => null,
            'sensorState' => false,
        ];
        foreach ($requiredKeys as $key => $defaultValue) {
            if (!isset($sensorDetails[$key])) {
                $sensorDetails[$key] = $defaultValue;
            }
        }

        $sensorDetails = array_intersect_key($sensorDetails, $requiredKeys);

        self::$logger->info("modified", ["sensorDetails" => $sensorDetails]);

        return self::validateSensorId($sensorDetails['sensorId']) &&
            self::validateSensorFace($sensorDetails['sensorFace']) &&
            self::validateSensorState($sensorDetails['sensorState']);
    }

    public static function validateSensorId($sensorId): bool
    {
        $res = filter_var($sensorId, FILTER_VALIDATE_INT) !== false &&
            $sensorId >= 10000 && $sensorId <= 99999;
        self::$logger->info("validateSensorId:", ["sensorId" => $sensorId, "result" => $res]);

        return $res;
    }

    public static function validateSensorFace($sensorFace): bool
    {
        $res=SensorFace::tryFrom($sensorFace) !== null ||
            in_array($sensorFace, array_column(SensorFace::cases(), 'value'), true);
        self::$logger->info("validateSensorFace:", ["sensorFace" => $sensorFace, "result" => $res]);

        return $res;
    }

    public static function validateSensorState($sensorState): bool
    {
        $res=in_array($sensorState, [true, false, 1, 0, "1", "0"], true);
        self::$logger->info("validateSensorState:", ["sensorState" => $sensorState, "result" => $res]);

        return $res;
    }
}