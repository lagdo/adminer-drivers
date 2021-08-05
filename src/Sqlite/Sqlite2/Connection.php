<?php

namespace Lagdo\Adminer\Drivers\Sqlite\Sqlite2;

use Lagdo\Adminer\Drivers\Sqlite\Connection as SqliteConnection;

use SQLiteDatabase;

class Connection extends SqliteConnection
{
    /**
     * @inheritDoc
     */
    public function open($filename, array $options)
    {
        $this->server_info = sqlite_libversion();
        $this->client = new SQLiteDatabase($filename);
    }

    public function query($query, $unbuffered = false) {
        $method = ($unbuffered ? "unbufferedQuery" : "query");
        $result = @$this->client->$method($query, SQLITE_BOTH, $error);
        $this->error = "";
        if (!$result) {
            $this->error = $error;
            return false;
        } elseif ($result === true) {
            $this->affected_rows = $this->changes();
            return true;
        }
        return new Statement($result);
    }

    public function quote($string) {
        return "'" . sqlite_escape_string($string) . "'";
    }

    public function store_result() {
        return $this->_result;
    }

    public function result($query, $field = 0) {
        $result = $this->query($query);
        if (!is_object($result)) {
            return false;
        }
        $row = $result->_result->fetch();
        return $row[$field];
    }
}
