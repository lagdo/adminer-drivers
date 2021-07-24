<?php

namespace Lagdo\Adminer\Drivers\Sqlite\Sqlite2;

use SQLiteDatabase;

class Connection extends \Lagdo\Adminer\Drivers\Sqlite\Connection {
    var $extension = "SQLite", $server_info, $affected_rows, $error, $_link;

    function __construct($filename) {
        $this->server_info = sqlite_libversion();
        $this->_link = new SQLiteDatabase($filename);
    }

    function query($query, $unbuffered = false) {
        $method = ($unbuffered ? "unbufferedQuery" : "query");
        $result = @$this->_link->$method($query, SQLITE_BOTH, $error);
        $this->error = "";
        if (!$result) {
            $this->error = $error;
            return false;
        } elseif ($result === true) {
            $this->affected_rows = $this->changes();
            return true;
        }
        return new Result($result);
    }

    function quote($string) {
        return "'" . sqlite_escape_string($string) . "'";
    }

    function store_result() {
        return $this->_result;
    }

    function result($query, $field = 0) {
        $result = $this->query($query);
        if (!is_object($result)) {
            return false;
        }
        $row = $result->_result->fetch();
        return $row[$field];
    }
}
