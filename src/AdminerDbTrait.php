<?php

namespace Lagdo\Adminer\Drivers;

use Lagdo\Adminer\Drivers\Db\ServerInterface;
use Lagdo\Adminer\Drivers\Db\DriverInterface;
use Lagdo\Adminer\Drivers\Db\ConnectionInterface;

trait AdminerDbTrait
{
    /**
     * @var ServerInterface
     */
    public $server = null;

    /**
     * @var DriverInterface
     */
    public $driver = null;

    /**
     * @var ConnectionInterface
     */
    public $connection = null;

    /**
     * Connect to a given server
     *
     * @param AdminerUtilInterface $util
     * @param string $server The server class name
     *
     * @return void
     */
    public function connect(AdminerUtilInterface $util, string $server)
    {
        $this->server = new $server($this, $util);
        $this->connection = $this->server->connect();
        $this->driver = $this->server->getDriver();
    }
}
