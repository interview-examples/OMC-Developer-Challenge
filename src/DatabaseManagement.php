<?php
declare(strict_types=1);

namespace App;

use MongoDB\Client as MongoClient;
use Psr\Log\LoggerInterface;

class DatabaseManagement
{
    private MongoClient $client;
    private \MongoDB\Database $db;
    private string $message;
    private LoggerInterface $logger;


    /**
     * @throws \Exception
     */
    public function __construct(array $db_access, LoggerInterface $logger)
    {
        global $db_access;

        if (!isset($db_access) || !isset($db_access['host']) || !isset($db_access['port']) || !isset($db_access['database'])) {
            throw new \RuntimeException('Database access configuration is not properly set.');
        }

        $this->client = new MongoClient($db_access['host'] . $db_access['port']);
        $this->db = $this->client->selectDatabase($db_access['database']);
        $this->message = '';
        $this->logger = $logger;
    }

    public function getDB(): \MongoDB\Database
    {
        return $this->db;
    }

    /**
     * Prepares databases.
     *
     * It needs to be run one time only, but before of all another!
     *
     * @return void
     */
    public function setupDatabase():void
    {
        try {
            $this->createCollections();
            $this->createSensorsListIndexes();
            $this->createTemperatureIndexes();
        } catch (\Exception $e) {
            die('Error (' . $e->getCode() . ') in createCollections method: ' . $e->getMessage());
        }
    }

    /**
     * Returns last status message
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Returns collection of Sensors
     *
     * @return \MongoDB\Collection
     */
    public function getSensorsListCollection(): \MongoDB\Collection
    {
        return $this->db->SensorsList;
    }

    public function getTemperaturesCollection():\MongoDB\Collection
    {
        return $this->db->Temperatures;
    }

    /**
     * @throws \Exception
     */
    private function createCollections():void
    {
        try {
            if (!$this->isCollectionExists('SensorsList')) {
                $this->db->createCollection('SensorsList');
            } else {
                $this->message .= "SensorsList collection already exists.<br>";
                $this->logger->info('SensorsList collection already exists.');
            }

            if (!$this->isCollectionExists('Temperatures')) {
                $this->db->createCollection('Temperatures');
            } else {
                $this->message .= "Temperatures collection already exists.<br>";
                $this->logger->info('Temperatures collection already exists.');
            }
        } catch (\Exception $e) {
            $this->logger->error('Error in createCollections method', ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * @throws \Exception
     */
    private function createTemperatureIndexes():void
    {
        global $logger;

        try {
            $this->db->Temperatures->dropIndexes();
            $logger->info('Dropped all indexes in Temperatures collection');

            $this->db->Temperatures->createIndex(['timestamp' => 1], ['expireAfterSeconds' => 86400]);
            $logger->debug('Created index on timestamp');

            $this->db->Temperatures->createIndex(['sensorId' => 1]);
            $logger->debug('Created index on sensorId');

            $this->db->Temperatures->createIndex(['sensorId' => 1, 'timestamp' => 1], ["unique" => true]);
            $logger->debug('Created composite index on sensorId and timestamp');

        } catch (\Exception $e) {
            $logger->error('Error in createIndexes method', ['exception' => $e]);
            throw $e;
        }
    }

    private function createSensorsListIndexes():void
    {
        global $logger;

        try {
            $this->db->Temperatures->dropIndexes();
            $logger->info('Dropped all indexes in SensorsList collection');

            $this->db->SensorsList->createIndex(['sensorId' => 1], ["unique" => true]);
            $logger->debug('Created unique index on sensorId');

            $this->db->SensorsList->createIndex(['sensorFace' => 1]);
            $logger->debug('Created index on sensorFace');
        } catch (\Exception $e) {
            $logger->error('Error in createIndexes method', ['exception' => $e]);
            throw $e;
        }
    }

    private function isCollectionExists($collectionName):bool
    {
        $res = false;
        $collections = $this->db->listCollections(['name' => $collectionName]);
        foreach ($collections as $collection) {
            if ($collection->getName() === $collectionName) {
                $res = true;
            }
        }
        return $res;
    }
}

