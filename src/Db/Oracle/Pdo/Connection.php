<?php

namespace Lagdo\Adminer\Drivers\Db\Oracle\Pdo;

use Lagdo\Adminer\Drivers\Db\Pdo\Connection as PdoConnection;

/**
 * Oracle driver to be used with the pdo_oci PHP extension.
 */
class Connection extends PdoConnection
{
    /**
     * Undocumented variable
     *
     * @var string
     */
    public $_current_db;

    /**
    * @inheritDoc
    */
    public function open($server, array $options)
    {
        $username = $options['username'];
        $password = $options['password'];

        $this->dsn("oci:dbname=//$server;charset=AL32UTF8", $username, $password);
        return true;
    }

    public function select_db($database)
    {
        $this->_current_db = $database;
        return true;
    }
}
