<?php

namespace Lagdo\Adminer\Drivers\Sqlite\Pdo;

use Lagdo\Adminer\Drivers\Pdo\Connection as PdoConnection;
use Lagdo\Adminer\Drivers\Sqlite\ConnectionTrait;

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
