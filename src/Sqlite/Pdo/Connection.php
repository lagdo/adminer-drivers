<?php

namespace Lagdo\Adminer\Drivers\Sqlite\Pdo;

class Connection extends \Lagdo\Adminer\Drivers\Pdo\Connection {
    var $extension = "PDO_SQLite";

    function __construct($filename) {
        $this->dsn(DRIVER . ":$filename", "", "");
    }

    // These functions are manuellay copied here from the
    // \Lagdo\Adminer\Drivers\Sqlite\Connection class,
    // since multiple inheritance is not supported

    function select_db($filename) {
        if (is_readable($filename) && $this->query("ATTACH " . $this->quote(preg_match("~(^[/\\\\]|:)~", $filename) ? $filename : dirname($_SERVER["SCRIPT_FILENAME"]) . "/$filename") . " AS a")) { // is_readable - SQLite 3
            parent::__construct($filename);
            $this->query("PRAGMA foreign_keys = 1");
            $this->query("PRAGMA busy_timeout = 500");
            return true;
        }
        return false;
    }

    function multi_query($query) {
        return $this->_result = $this->query($query);
    }

    function next_result() {
        return false;
    }
}
