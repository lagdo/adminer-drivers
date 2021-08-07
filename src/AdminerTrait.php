<?php

namespace Lagdo\Adminer\Drivers;

use function class_exists;

trait AdminerTrait
{
    /**
     * Get an instance of a database server class
     *
     * @param AdminerInterface $adminer
     * @param string $server
     *
     * @return ServerInterface
     */
    public function getDbServer(AdminerInterface $adminer, string $server)
    {
        switch($server)
        {
        case "mysql":
            return new MySql\Server($adminer);
        case "pgsql":
            return new PgSql\Server($adminer);
        case "oracle":
            return new Oracle\Server($adminer);
        case "mssql":
            return new MsSql\Server($adminer);
        case "mongo":
            if(class_exists('MongoDB'))
            {
                return new Mongo\Mongo\Server($adminer);
            }
            if(class_exists('MongoDB\Driver\Manager'))
            {
                return new Mongo\MongoDb\Server($adminer);
            }
        case "elastic":
            return new Elastic\Server($adminer);
        case "sqlite":
        case "sqlite2":
            return new Sqlite\Server($adminer, $server);
        }
        return null;
    }
}
