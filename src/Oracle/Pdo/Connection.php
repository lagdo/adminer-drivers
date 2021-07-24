<?php

namespace Lagdo\Adminer\Drivers\Oracle\Pdo;

/**
 * Oracle driver to be used with the pdo_oci PHP extension.
 */
class Connection extends \Lagdo\Adminer\Drivers\Pdo\Connection {
    var $extension = "PDO_OCI";
    var $_current_db;

    function connect($server, $username, $password) {
        $this->dsn("oci:dbname=//$server;charset=AL32UTF8", $username, $password);
        return true;
    }

    function select_db($database) {
        $this->_current_db = $database;
        return true;
    }
}
