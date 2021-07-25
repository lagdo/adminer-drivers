<?php

namespace Lagdo\Adminer\Drivers\Oracle\Pdo;

use Lagdo\Adminer\Drivers\ConnectionInterface;

/**
 * Oracle driver to be used with the pdo_oci PHP extension.
 */
class Connection extends \Lagdo\Adminer\Drivers\Pdo\Connection implements ConnectionInterface
{
    var $extension = "PDO_OCI";
    var $_current_db;

    public function connect($server, $username, $password) {
        $this->dsn("oci:dbname=//$server;charset=AL32UTF8", $username, $password);
        return true;
    }

    public function select_db($database) {
        $this->_current_db = $database;
        return true;
    }
}
