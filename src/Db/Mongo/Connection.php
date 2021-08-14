<?php

namespace Lagdo\Adminer\Drivers\Db\Mongo;

use Lagdo\Adminer\Drivers\Db\Connection as AbstractConnection;

use MongoDB\Driver\Manager;
use MongoDB\Driver\Command;

use Exception;

class Connection extends AbstractConnection
{
    /**
     * Undocumented variable
     *
     * @var int
     */

    public $last_id;

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    public $_db;

    /**
     * Undocumented variable
     *
     * @var string
     */
    public $_db_name;

    /**
     * @inheritDoc
     */
    public function open($server, array $options)
    {
        $this->server_info = MONGODB_VERSION;

        // $class = 'MongoDB\Driver\Manager';
        // $this->client = new $class($server, $options);
        $this->client = new Manager($server, $options);
        $this->executeCommand('admin', array('ping' => 1));
    }

    public function executeCommand($db, $command)
    {
        // $class = 'MongoDB\Driver\Command';
        try {
            return $this->client->executeCommand($db, new Command($command));
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return [];
        }
    }

    public function executeBulkWrite($namespace, $bulk, $counter)
    {
        try {
            $results = $this->client->executeBulkWrite($namespace, $bulk);
            $this->affected_rows = $results->$counter();
            return true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function query($query, $unbuffered = false)
    {
        return false;
    }

    public function result($query, $field = 0)
    {
        return false;
    }

    public function select_db($database)
    {
        $this->_db_name = $database;
        return true;
    }
}
