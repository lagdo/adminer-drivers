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
     * @param AdminerDbInterface $db
     * @param AdminerUiInterface $ui
     * @param string $driver
     *
     * @return void
     */
    public function connect(AdminerDbInterface $db, AdminerUiInterface $ui, string $driver)
    {
        switch ($driver) {
        case "mysql":
            $this->server = new MySqlServer($db, $ui);
            break;
        case "pgsql":
            $this->server = new PgSqlServer($db, $ui);
            break;
        case "oracle":
            $this->server = new OracleServer($db, $ui);
            break;
        case "mssql":
            $this->server = new MsSqlServer($db, $ui);
            break;
        case "sqlite":
            $this->server = new SqliteServer($db, $ui);
            break;
        case "mongo":
            $this->server = new MongoServer($db, $ui);
            break;
        case "elastic":
            $this->server = new ElasticServer($db, $ui);
            break;
        }

        if (!$this->server) {
            return;
        }

        $this->connection = $this->server->connect();
        $this->driver = $this->server->getDriver();
    }
}
