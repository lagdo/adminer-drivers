<?php

namespace Lagdo\Adminer\Drivers\Sqlite\Pdo;

use Lagdo\Adminer\Drivers\Pdo\Connection as PdoConnection;

class Connection extends PdoConnection
{
    /**
     * @inheritDoc
     */
    public function open($filename, array $options)
    {
        $this->dsn(DRIVER . ":$filename", "", "");
    }

    // These functions are manuellay copied here from the
    // \Lagdo\Adminer\Drivers\Sqlite\Connection class,
    // since multiple inheritance is not supported

    public function select_db($filename) {
        if (is_readable($filename) && $this->query("ATTACH " .
            $this->quote(preg_match("~(^[/\\\\]|:)~", $filename) ?
            $filename : dirname($_SERVER["SCRIPT_FILENAME"]) . "/$filename") . " AS a")) { // is_readable - SQLite 3
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
