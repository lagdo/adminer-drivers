<?php

namespace Lagdo\Adminer\Drivers\Pdo;

use PDO;
use Exception;

class Connection {
    var $_result, $server_info, $affected_rows, $errno, $error, $pdo;

    public function __construct() {
        $pos = array_search("SQL", $this->adminer->operators);
        if ($pos !== false) {
            unset($this->adminer->operators[$pos]);
        }
    }

    public function dsn($dsn, $username, $password, $options = array()) {
        try {
            $this->pdo = new PDO($dsn, $username, $password, $options);
        } catch (Exception $ex) {
            auth_error(h($ex->getMessage()));
        }
        $this->pdo->setAttribute(3, 1); // 3 - PDO::ATTR_ERRMODE, 1 - PDO::ERRMODE_WARNING
        $this->pdo->setAttribute(13, array('Statement')); // 13 - PDO::ATTR_STATEMENT_CLASS
        $this->server_info = @$this->pdo->getAttribute(4); // 4 - PDO::ATTR_SERVER_VERSION
    }

    /*abstract function select_db($database);*/

    public function quote($string) {
        return $this->pdo->quote($string);
    }

    public function query($query, $unbuffered = false) {
        $result = $this->pdo->query($query);
        $this->error = "";
        if (!$result) {
            list(, $this->errno, $this->error) = $this->pdo->errorInfo();
            if (!$this->error) {
                $this->error = lang('Unknown error.');
            }
            return false;
        }
        $this->store_result($result);
        return $result;
    }

    public function multi_query($query) {
        return $this->_result = $this->query($query);
    }

    public function store_result($result = null) {
        if (!$result) {
            $result = $this->_result;
            if (!$result) {
                return false;
            }
        }
        if ($result->columnCount()) {
            $result->num_rows = $result->rowCount(); // is not guaranteed to work with all drivers
            return $result;
        }
        $this->affected_rows = $result->rowCount();
        return true;
    }

    public function next_result() {
        if (!$this->_result) {
            return false;
        }
        $this->_result->_offset = 0;
        return @$this->_result->nextRowset(); // @ - PDO_PgSQL doesn't support it
    }

    public function result($query, $field = 0) {
        $result = $this->query($query);
        if (!$result) {
            return false;
        }
        $row = $result->fetch();
        return $row[$field];
    }
}
