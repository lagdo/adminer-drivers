<?php

namespace Lagdo\Adminer\Drivers\Mongo\Mongo;

use Lagdo\Adminer\Drivers\Mongo\Server as MongoServer;

class Server extends MongoServer
{
    /**
     * Undocumented variable
     *
     * @var [type]
     */
    protected $operators = array("=");

    /**
     * @inheritDoc
     */
    public function getDriver()
    {
        return "mongo";
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return "MongoDB (alpha)";
    }

    public function get_databases($flush) {
        $return = [];
        $dbs = $this->connection->getClient()->listDBs();
        foreach ($dbs['databases'] as $db) {
            $return[] = $db['name'];
        }
        return $return;
    }

    public function count_tables($databases) {
        $return = [];
        foreach ($databases as $db) {
            $return[$db] = count($this->connection->getClient()->selectDB($db)->getCollectionNames(true));
        }
        return $return;
    }

    public function tables_list() {
        return array_fill_keys($this->connection->_db->getCollectionNames(true), 'table');
    }

    public function drop_databases($databases) {
        foreach ($databases as $db) {
            $response = $this->connection->getClient()->selectDB($db)->drop();
            if (!$response['ok']) {
                return false;
            }
        }
        return true;
    }

    public function indexes($table, $connection2 = null) {
        $return = [];
        foreach ($this->connection->_db->selectCollection($table)->getIndexInfo() as $index) {
            $descs = [];
            foreach ($index["key"] as $column => $type) {
                $descs[] = ($type == -1 ? '1' : null);
            }
            $return[$index["name"]] = array(
                "type" => ($index["name"] == "_id_" ? "PRIMARY" : ($index["unique"] ? "UNIQUE" : "INDEX")),
                "columns" => array_keys($index["key"]),
                "lengths" => [],
                "descs" => $descs,
            );
        }
        return $return;
    }

    public function fields($table) {
        return fields_from_edit();
    }

    public function found_rows($table_status, $where) {
        //! don't call count_rows()
        return $this->connection->_db->selectCollection($_GET["select"])->count($where);
    }
}
