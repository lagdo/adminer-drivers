<?php

namespace Lagdo\Adminer\Drivers\Mongo\MongoDb;

use Lagdo\Adminer\Drivers\AbstractConnection;

use MongoDB\Driver\Manager;
use MongoDB\Driver\Command;

class Connection extends AbstractConnection
{
    /**
     * Undocumented variable
     *
     * @var int
     */

     protected $last_id;

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    protected $_db;

    /**
     * Undocumented variable
     *
     * @var string
     */
    protected $_db_name;

    /**
     * The constructor
     */
    public function __construct()
    {
        $this->extension = 'MongoDB';
        $this->server_info = MONGODB_VERSION;
    }

    /**
     * @inheritDoc
     */
    public function open($server, array $options)
    {
        // $class = 'MongoDB\Driver\Manager';
        // $this->client = new $class($server, $options);
        $this->client = new Manager($server, $options);
        $this->executeCommand('admin', array('ping' => 1));
    }

    public function executeCommand($db, $command) {
        // $class = 'MongoDB\Driver\Command';
        try {
            // return $this->client->executeCommand($db, new $class($command));
            return $this->client->executeCommand($db, new Command($command));
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return [];
        }
    }

    public function executeBulkWrite($namespace, $bulk, $counter) {
        try {
            $results = $this->client->executeBulkWrite($namespace, $bulk);
            $this->affected_rows = $results->$counter();
            return true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function query($query, $unbuffered = false) {
        return false;
    }

    public function select_db($database) {
        $this->_db_name = $database;
        return true;
    }
}
