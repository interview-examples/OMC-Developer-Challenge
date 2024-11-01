<?php

namespace src;

class SensorValidator
{
    public static function validate(&$sensorDetails): bool
    {
        global $logger;
        $logger->debug("SensorValidator::validate()");
        $logger->info("input", ["sensorDetails" => $sensorDetails]);
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
        $logger->info("modified", ["sensorDetails" => $sensorDetails]);

        return self::validateSensorId($sensorDetails['sensorId']) &&
            self::validateSensorFace($sensorDetails['sensorFace']) &&
            self::validateSensorState($sensorDetails['sensorState']);
    }

    public static function validateSensorId($sensorId): bool
    {
        global $logger;
        $res = filter_var($sensorId, FILTER_VALIDATE_INT) !== false &&
            $sensorId >= 10000 && $sensorId <= 99999;
        $logger->info("validateSensorId:", ["sensorId" => $sensorId, "result" => $res]);
        return $res;
    }

    public static function validateSensorFace($sensorFace): bool
    {
        global $logger;
        $res=SensorFace::tryFrom($sensorFace) !== null ||
            in_array($sensorFace, array_column(SensorFace::cases(), 'value'), true);
        $logger->info("validateSensorFace:", ["sensorFace" => $sensorFace, "result" => $res]);
        return $res;
    }

    public static function validateSensorState($sensorState): bool
    {
        global $logger;
        $res=filter_var($sensorState, FILTER_VALIDATE_BOOLEAN) !== false;
        $logger->info("validateSensorState:", ["sensorState" => $sensorState, "result" => $res]);
        return $res;
    }
}