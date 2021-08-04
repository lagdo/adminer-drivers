<?php
/**
* @author Jakub Cernohuby
* @author Vladimir Stastka
* @author Jakub Vrana
*/

namespace Lagdo\Adminer\Drivers\Mssql\Pdo;

use Lagdo\Adminer\Drivers\Pdo\Connection as PdoConnection;

class Connection extends PdoConnection
{
    /**
     * The constructor
     */
    public function __construct()
    {
        $this->extension = 'PDO_DBLIB';
    }

     /**
     * @inheritDoc
     */
    public function open($server, array $options)
    {
        $username = $options['username'];
        $password = $options['password'];

        $this->dsn("dblib:charset=utf8;host=" . str_replace(":", ";unix_socket=",
            preg_replace('~:(\d)~', ';port=\1', $server)), $username, $password);
        return true;
    }

    public function select_db($database) {
        // database selection is separated from the connection so dbname in DSN can't be used
        return $this->query("USE " . $this->server->idf_escape($database));
    }
}
