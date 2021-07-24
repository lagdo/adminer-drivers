<?php

namespace Lagdo\Adminer\Drivers\Mongo\MongoDb;

class Connection {
    var $extension = "MongoDB", $server_info = MONGODB_VERSION, $affected_rows, $error, $last_id;
    /** @var MongoDB\Driver\Manager */
    var $_link;
    var $_db, $_db_name;

    function connect($uri, $options) {
        $class = 'MongoDB\Driver\Manager';
        $this->_link = new $class($uri, $options);
        $this->executeCommand('admin', array('ping' => 1));
    }

    function executeCommand($db, $command) {
        $class = 'MongoDB\Driver\Command';
        try {
            return $this->_link->executeCommand($db, new $class($command));
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return array();
        }
    }

    function executeBulkWrite($namespace, $bulk, $counter) {
        try {
            $results = $this->_link->executeBulkWrite($namespace, $bulk);
            $this->affected_rows = $results->$counter();
            return true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    function query($query) {
        return false;
    }

    function select_db($database) {
        $this->_db_name = $database;
        return true;
    }

    function quote($string) {
        return $string;
    }
}
