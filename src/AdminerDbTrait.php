<?php

namespace Lagdo\Adminer\Drivers;

use Lagdo\Adminer\Drivers\Db\ServerInterface;
use Lagdo\Adminer\Drivers\Db\DriverInterface;
use Lagdo\Adminer\Drivers\Db\ConnectionInterface;

use Lagdo\Adminer\Drivers\Db\MySql\Server as MySqlServer;
use Lagdo\Adminer\Drivers\Db\PgSql\Server as PgSqlServer;
use Lagdo\Adminer\Drivers\Db\Oracle\Server as OracleServer;
use Lagdo\Adminer\Drivers\Db\MsSql\Server as MsSqlServer;
use Lagdo\Adminer\Drivers\Db\Sqlite\Server as SqliteServer;
use Lagdo\Adminer\Drivers\Db\Mongo\Server as MongoServer;
use Lagdo\Adminer\Drivers\Db\Elastic\Server as ElasticServer;

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
     * Get an instance of a database server class
     *
     * @param AdminerUtilInterface $util
     * @param string $driver
     *
     * @return void
     */
    public function connect(AdminerUtilInterface $util, string $driver)
    {
        switch ($driver) {
        case "mysql":
            $this->server = new MySqlServer($this, $util);
            break;
        case "pgsql":
            $this->server = new PgSqlServer($this, $util);
            break;
        case "oracle":
            $this->server = new OracleServer($this, $util);
            break;
        case "mssql":
            $this->server = new MsSqlServer($this, $util);
            break;
        case "sqlite":
            $this->server = new SqliteServer($this, $util);
            break;
        case "mongo":
            $this->server = new MongoServer($this, $util);
            break;
        case "elastic":
            $this->server = new ElasticServer($this, $util);
            break;
        }

        if (!$this->server) {
            return;
        }

        $this->connection = $this->server->connect();
        $this->driver = $this->server->getDriver();
    }
}
