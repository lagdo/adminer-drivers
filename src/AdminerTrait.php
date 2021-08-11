<?php

namespace Lagdo\Adminer\Drivers;

use function class_exists;

trait AdminerTrait
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
     * Get an instance of a database server class
     *
     * @param AdminerInterface $adminer
     * @param string $driver
     *
     * @return void
     */
    public function connect(AdminerInterface $adminer, string $driver)
    {
        switch ($driver) {
        case "mysql":
            $this->server = new MySql\Server($adminer);
            break;
        case "pgsql":
            $this->server = new PgSql\Server($adminer);
            break;
        case "oracle":
            $this->server = new Oracle\Server($adminer);
            break;
        case "mssql":
            $this->server = new MsSql\Server($adminer);
            break;
        case "mongo":
            $this->server = new MongoDb\Server($adminer);
            break;
        case "elastic":
            $this->server = new Elastic\Server($adminer);
            break;
        case "sqlite":
            $this->server = new Sqlite\Server($adminer);
            break;
        }

        if (!$this->server) {
            return;
        }

        $this->connection = $this->server->connect();
        $this->driver = $this->server->getDriver();
    }
}
