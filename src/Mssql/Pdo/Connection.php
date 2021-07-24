<?php
/**
* @author Jakub Cernohuby
* @author Vladimir Stastka
* @author Jakub Vrana
*/

namespace Lagdo\Adminer\Drivers\Mssql\Pdo;

class Connection extends \Lagdo\Adminer\Drivers\Pdo\Connection {
    var $extension = "PDO_DBLIB";

    function connect($server, $username, $password) {
        $this->dsn("dblib:charset=utf8;host=" . str_replace(":", ";unix_socket=",
            preg_replace('~:(\d)~', ';port=\1', $server)), $username, $password);
        return true;
    }

    function select_db($database) {
        // database selection is separated from the connection so dbname in DSN can't be used
        return $this->query("USE " . idf_escape($database));
    }
}
