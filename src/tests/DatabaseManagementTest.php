<?php

namespace src\tests;

class DatabaseManagementTest
{
    static private $host = 'http://0.0.0.0:8080/';
    public static function testSensorRegister(int $number = 10000): string
    {
        $endpoint = 'sensor-register/';
        $id_start = 10000;
        $number_sensors = $number;

        $res = self::createSensor(self::$host . $endpoint, $id_start, $id_start + $number_sensors);
        if ($res) {
            $message = 'OK';
        } else {
            $message = 'ERROR';
        }

        return $message;
    }

    private static function createSensor($api_url, $id_start, $id_finish): bool
    {
        $result_total_creation = true;

        for ($i = $id_start; $i < $id_finish; $i++) {
            try {
                $sensorData = [
                    'sensorId' => $i * random_int(-1, 1),
                    'timestamp' => date('Y-m-d H:i:s'),
                    'temperature' => array_rand([
                        random_int(-10, 40),
                        random_int(-20, 50),
                        random_int(50, 90),
                        'test'
                    ]),
                    'sensorFace' => array_rand([10, 20, 30, 40, 'north', 'tzafon']),
                    'sensorState' => array_rand([true, false, 'bug']),
                ];
            } catch (\Random\RandomException $e) {
                throw $e;
            }

            $options = [
                'http' => [
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($sensorData),
                ],
            ];

            $context = stream_context_create($options);
            $result = file_get_contents($api_url, false, $context);

            if ($result === FALSE) {
                $result_total_creation = false;
                break;
            }
        }

        return $result_total_creation;
    }
}