<?php

namespace Lagdo\Adminer\Drivers\Pgsql\Pdo;

use Lagdo\Adminer\Drivers\Pdo\Connection as PdoConnection;

/**
 * PostgreSQL driver to be used with the pdo_pgsql PHP extension.
 */
class Connection extends PdoConnection
{
    /**
     * Undocumented variable
     *
     * @var int
     */
    protected $timeout;

    /**
     * The constructor
     */
    public function __construct()
    {
        $this->extension = 'PDO_PgSQL';
    }

     /**
     * @inheritDoc
     */
    public function connect($server, array $options)
    {
        $username = $options['username'];
        $password = $options['password'];

        $db = $this->adminer->database();
        //! client_encoding is supported since 9.1 but we can't yet use min_version here
        $this->dsn("pgsql:host='" . str_replace(":", "' port='", addcslashes($server, "'\\")) .
            "' client_encoding=utf8 dbname='" .
            ($db != "" ? addcslashes($db, "'\\") : "postgres") . "'", $username, $password);
        //! connect without DB in case of an error
        return true;
    }

    public function select_db($database) {
        return ($this->adminer->database() == $database);
    }

    public function quoteBinary($s) {
        return $this->quote($s);
    }

    public function query($query, $unbuffered = false) {
        $return = parent::query($query, $unbuffered);
        if ($this->timeout) {
            $this->timeout = 0;
            parent::query("RESET statement_timeout");
        }
        return $return;
    }

    public function warnings() {
        return ''; // not implemented in PDO_PgSQL as of PHP 7.2.1
    }

    public function close() {
    }
}
