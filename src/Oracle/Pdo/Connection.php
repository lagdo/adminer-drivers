<?php

namespace Lagdo\Adminer\Drivers\Oracle\Pdo;

use Lagdo\Adminer\Drivers\Pdo\Connection as PdoConnection;

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
    protected $_current_db;

    /**
     * The constructor
     */
    public function __construct()
    {
        $this->extension = 'PDO_OCI';
    }

     /**
     * @inheritDoc
     */
    public function connect($server, array $options)
    {
        $username = $options['username'];
        $password = $options['password'];

        $this->dsn("oci:dbname=//$server;charset=AL32UTF8", $username, $password);
        return true;
    }

    public function select_db($database) {
        $this->_current_db = $database;
        return true;
    }
}
