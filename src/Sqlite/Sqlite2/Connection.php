<?php

namespace Lagdo\Adminer\Drivers\Sqlite\Sqlite2;

use Lagdo\Adminer\Drivers\ConnectionInterface;
use SQLiteDatabase;

class Connection extends \Lagdo\Adminer\Drivers\Sqlite\Connection implements ConnectionInterface
{
    /**
     * The extension name
     *
     * @var string
     */
    protected $extension = "SQLite";

    /**
     * The server description
     *
     * @var string
     */
    protected $server_info;

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
     * @var [type]
     */
    protected $_link;

    public function __construct($filename) {
        $this->server_info = sqlite_libversion();
        $this->_link = new SQLiteDatabase($filename);
    }

    public function query($query, $unbuffered = false) {
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
