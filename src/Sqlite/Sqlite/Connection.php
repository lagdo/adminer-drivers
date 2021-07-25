<?php

namespace Lagdo\Adminer\Drivers\Sqlite\Sqlite;

use Lagdo\Adminer\Drivers\ConnectionInterface;
use SQLite3;

class Connection extends \Lagdo\Adminer\Drivers\Sqlite\Connection implements ConnectionInterface
{
    var $extension = "SQLite3", $server_info, $affected_rows, $errno, $error, $_link;

    public function __construct($filename) {
        $this->_link = new SQLite3($filename);
        $version = $this->_link->version();
        $this->server_info = $version["versionString"];
    }

    public function query($query) {
        $result = @$this->_link->query($query);
        $this->error = "";
        if (!$result) {
            $this->errno = $this->_link->lastErrorCode();
            $this->error = $this->_link->lastErrorMsg();
            return false;
        } elseif ($result->numColumns()) {
            return new Result($result);
        }
        $this->affected_rows = $this->_link->changes();
        return true;
    }

    public function quote($string) {
        return (is_utf8($string)
            ? "'" . $this->_link->escapeString($string) . "'"
            : "x'" . reset(unpack('H*', $string)) . "'"
        );
    }

    public function store_result() {
        return $this->_result;
    }

    public function result($query, $field = 0) {
        $result = $this->query($query);
        if (!is_object($result)) {
            return false;
        }
        $row = $result->_result->fetchArray();
        return $row[$field];
    }
}
