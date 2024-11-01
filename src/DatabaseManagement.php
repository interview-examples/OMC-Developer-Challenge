<?php

namespace src;

use MongoDB\Client;

class DatabaseManagement
{
    private $client;
    private $db;
    private $message;

    public function __construct()
    {
        global $db_access;

        if (!isset($db_access) || !isset($db_access['host']) || !isset($db_access['port']) || !isset($db_access['database'])) {
            throw new \Exception('Database access configuration is not properly set.');
        }

        $this->client = new Client($db_access['host'] . $db_access['port']);
        $this->db = $this->client->selectDatabase($db_access['database']);
        $this->message = '';
    }

    /**
     * Prepares databases.
     *
     * It needs to be run one time only, but before of all another!
     *
     * @return void
     * @throws \Exception
     */
    public function setupDatabase():void
    {
        global $logger;
        try {
            $this->createCollections();
            $this->createIndexes();
        } catch (\Exception $e) {
            $logger->error('Error in createCollections method', ['exception' => $e]);
            throw $e;
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

    private function createCollections():void
    {
        global $logger;
        try {
            if (!$this->isCollectionExists('SensorsList')) {
                $this->db->createCollection('SensorsList');
            } else {
                $this->message .= "SensorsList collection already exists.<br>";
                $logger->info('SensorsList collection already exists.');
            }

            if (!$this->isCollectionExists('Temperatures')) {
                $this->db->createCollection('Temperatures');
            } else {
                $this->message .= "Temperatures collection already exists.<br>";
                $logger->info('Temperatures collection already exists.');
            }
        } catch (\Exception $e) {
            $logger->error('Error in createCollections method', ['exception' => $e]);
            throw $e;
        }
    }

    private function createIndexes():void
    {
        global $logger;

        try {
            $this->db->Temperatures->createIndex(['timestamp' => 1], ['expireAfterSeconds' => 86400]);
            $logger->info('Created index on timestamp');
            $this->db->Temperatures->createIndex(['sensorId' => 1]);
            $logger->info('Created index on sensorId');
            $this->db->Temperatures->createIndex(['sensorId' => 1, 'timestamp' => 1]);
            $logger->info('Created composite index on sensorId and timestamp');
            $this->db->SensorsList->createIndex(['_id' => 1]);
            $logger->info('Created unique index on _id');
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

