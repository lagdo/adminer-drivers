<?php

namespace Lagdo\Adminer\Drivers\Pgsql\Pdo;

/**
 * PostgreSQL driver to be used with the pdo_pgsql PHP extension.
 */
class Connection extends \Lagdo\Adminer\Drivers\Pdo\Connection {
    var $extension = "PDO_PgSQL", $timeout;

    function connect($server, $username, $password) {
        global $adminer;
        $db = $adminer->database();
        //! client_encoding is supported since 9.1 but we can't yet use min_version here
        $this->dsn("pgsql:host='" . str_replace(":", "' port='", addcslashes($server, "'\\")) .
            "' client_encoding=utf8 dbname='" .
            ($db != "" ? addcslashes($db, "'\\") : "postgres") . "'", $username, $password);
        //! connect without DB in case of an error
        return true;
    }

    function select_db($database) {
        global $adminer;
        return ($adminer->database() == $database);
    }

    function quoteBinary($s) {
        return q($s);
    }

    function query($query, $unbuffered = false) {
        $return = parent::query($query, $unbuffered);
        if ($this->timeout) {
            $this->timeout = 0;
            parent::query("RESET statement_timeout");
        }
        return $return;
    }

    function warnings() {
        return ''; // not implemented in PDO_PgSQL as of PHP 7.2.1
    }

    function close() {
    }
}
