<?php
declare(strict_types=1);

const FIRST_SENSOR_ID = 10000;
const LAST_SENSOR_ID = 10050;
const SIMULATION_TIME_IN_SECS = 180;
const SENSOR_FAILURE_RATE = 0.1;
const SENSOR_ERROR_RATE = 0.05;

const SERVICE_URL = 'http://0.0.0.0:8080';

function registerSensor($sensor_id): false|string
{
    $url = SERVICE_URL . '/sensor-details/';
    $data = json_encode(['sensorId' => $sensor_id, 'sensorFace' => array_rand([10 => 10, 20=>20, 30=>30, 40=>40])], JSON_THROW_ON_ERROR);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;

}

function sendSensorData($sensor_id, $temperature): false|string
{
    $url = SERVICE_URL . '/add-temperature/';
    $data = json_encode(
        ['sensorId' => $sensor_id, 'temperature' => $temperature],
        JSON_THROW_ON_ERROR
    );
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}

echo "Emulator started at " . date('Y-m-d H:i:s'); echo PHP_EOL; echo "<br/>";
ob_flush();
flush();
for ($i = FIRST_SENSOR_ID; $i <= LAST_SENSOR_ID; $i++) {
    $result = registerSensor($i);
    echo "*";
    ob_flush();
    flush();
}

echo PHP_EOL; echo PHP_EOL; echo "<br/>";

for ($t = 0; $t < SIMULATION_TIME_IN_SECS; $t++) {
    for ($i = FIRST_SENSOR_ID; $i <= LAST_SENSOR_ID; $i++) {
        if (random_int(0, 100) / 100 < SENSOR_FAILURE_RATE) {
            echo "o";
            ob_flush();
            flush();
            continue;
        }

        if (random_int(0, 100) / 100 < SENSOR_ERROR_RATE) {
            $temperature = random_int(-100, 100) / 10;
        } else {
            $temperature = random_int(-50, 50) / 10;
        }

        $result = sendSensorData($i, $temperature);
        echo "+";
        ob_flush();
        flush();
    }
    usleep(random_int(100, 10000) * 100);
}
echo PHP_EOL; echo "<br/>";
echo "Emulator stopped at " . date('Y-m-d H:i:s');
echo PHP_EOL;
