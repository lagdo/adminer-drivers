<?php

namespace Lagdo\Adminer\Drivers\Db\Sqlite\Pdo;

use Lagdo\Adminer\Drivers\Db\Pdo\Connection as PdoConnection;
use Lagdo\Adminer\Drivers\Db\Sqlite\ConnectionTrait;

class Connection extends PdoConnection
{
    use ConnectionTrait;

    /**
     * @inheritDoc
     */
    public function open($filename, array $options)
    {
        $this->dsn("sqlite:$filename", "", "");
    }
}
