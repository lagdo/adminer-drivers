<?php

namespace Lagdo\Adminer\Drivers\Sqlite\Sqlite;

use Lagdo\Adminer\Drivers\Sqlite\Connection as SqliteConnection;

use SQLite3;

use function Lagdo\Adminer\Drivers\is_utf8;

class Connection extends SqliteConnection
{
    /**
     * @inheritDoc
     */
    public function open($filename, array $options)
    {
        $this->client = new SQLite3($filename);
        $version = $this->client->version();
        $this->server_info = $version["versionString"];
    }

    public function query($query, $unbuffered = false) {
        $result = @$this->client->query($query);
        $this->error = "";
        if (!$result) {
            $this->errno = $this->client->lastErrorCode();
            $this->error = $this->client->lastErrorMsg();
            return false;
        } elseif ($result->numColumns()) {
            return new Statement($result);
        }
        $this->affected_rows = $this->client->changes();
        return true;
    }

    public function quote($string) {
        return (is_utf8($string)
            ? "'" . $this->client->escapeString($string) . "'"
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
