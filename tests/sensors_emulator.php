<?php

$apiUrl = 'http://0.0.0.0:8080/sensor-register/';

for ($i = 10006; $i < 10010; $i++) {
    try {
/*        $sensorData = [
            'sensorId' => $i,
            'timestamp' => date('Y-m-d H:i:s'),
            'temperature' => array_rand([random_int(-10, 40), random_int(-20, 50), random_int(50, 90), 'test']),
            'sensorFace' => array_rand(['NORTH' => 10, 'West' =>20, 'East' =>30, '40' =>40, 40, 'NORTH' => 'north', 'tzafon'], 1),
            'sensorState' => array_rand([true, false, 'bug']),
        ];*/
        $sensorData = [
            'sensorId' => $i,
            'sensorFace' => 10,
            'sensorState' => true,
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
    $result = file_get_contents($apiUrl, false, $context);

    if ($result === FALSE) {
        die('Error sending sensor data');
    }

    echo "Sensor $i data sent.\n";
}

