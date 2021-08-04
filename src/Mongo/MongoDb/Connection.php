<?php

namespace Lagdo\Adminer\Drivers\Mongo\MongoDb;

class Connection implements ConnectionInterface
{
    /**
     * Undocumented variable
     *
     * @var string
     */
    protected $extension = "MongoDB";

    /**
     * The server description
     *
     * @var string
     */
    protected $server_info = MONGODB_VERSION;

    /**
     * Undocumented variable
     *
     * @var int
     */
    protected $affected_rows;

    /**
     * Undocumented variable
     *
     * @var string
     */
    protected $error;

    /**
     * Undocumented variable
     *
     * @var int
     */

     protected $last_id;

    /**
     * @var MongoDB\Driver\Manager
     */
    protected $_link;

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

    public function connect($uri, $options) {
        $class = 'MongoDB\Driver\Manager';
        $this->_link = new $class($uri, $options);
        $this->executeCommand('admin', array('ping' => 1));
    }

    public function executeCommand($db, $command) {
        $class = 'MongoDB\Driver\Command';
        try {
            return $this->_link->executeCommand($db, new $class($command));
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return [];
        }
    }

    public function executeBulkWrite($namespace, $bulk, $counter) {
        try {
            $results = $this->_link->executeBulkWrite($namespace, $bulk);
            $this->affected_rows = $results->$counter();
            return true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function query($query) {
        return false;
    }

    public function select_db($database) {
        $this->_db_name = $database;
        return true;
    }

    public function quote($string) {
        return $string;
    }
}
