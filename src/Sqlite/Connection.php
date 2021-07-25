<?php

namespace Lagdo\Adminer\Drivers\Sqlite;

use Lagdo\Adminer\Drivers\ConnectionInterface;

class Connection {

    public function __construct() {
        parent::__construct(":memory:");
        $this->query("PRAGMA foreign_keys = 1");
    }

    public function select_db($filename) {
        if (is_readable($filename) && $this->query("ATTACH " . $this->quote(preg_match("~(^[/\\\\]|:)~", $filename) ? $filename : dirname($_SERVER["SCRIPT_FILENAME"]) . "/$filename") . " AS a")) { // is_readable - SQLite 3
            parent::__construct($filename);
            $this->query("PRAGMA foreign_keys = 1");
            $this->query("PRAGMA busy_timeout = 500");
            return true;
        }
        return false;
    }

    public function multi_query($query) {
        return $this->_result = $this->query($query);
    }

    public function next_result() {
        return false;
    }
}
