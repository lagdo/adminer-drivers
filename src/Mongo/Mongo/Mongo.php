<?php

namespace Lagdo\Adminer\Drivers\Mongo;

use Lagdo\Adminer\Drivers\Mongo as MongoDriver;
use Lagdo\Adminer\Drivers\ServerInterface;

class Mongo extends MongoDriver implements ServerInterface
{
    var $operators = array("=");

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
        global $connection;
        $return = array();
        $dbs = $connection->_link->listDBs();
        foreach ($dbs['databases'] as $db) {
            $return[] = $db['name'];
        }
        return $return;
    }

    public function count_tables($databases) {
        global $connection;
        $return = array();
        foreach ($databases as $db) {
            $return[$db] = count($connection->_link->selectDB($db)->getCollectionNames(true));
        }
        return $return;
    }

    public function tables_list() {
        global $connection;
        return array_fill_keys($connection->_db->getCollectionNames(true), 'table');
    }

    public function drop_databases($databases) {
        global $connection;
        foreach ($databases as $db) {
            $response = $connection->_link->selectDB($db)->drop();
            if (!$response['ok']) {
                return false;
            }
        }
        return true;
    }

    public function indexes($table, $connection2 = null) {
        global $connection;
        $return = array();
        foreach ($connection->_db->selectCollection($table)->getIndexInfo() as $index) {
            $descs = array();
            foreach ($index["key"] as $column => $type) {
                $descs[] = ($type == -1 ? '1' : null);
            }
            $return[$index["name"]] = array(
                "type" => ($index["name"] == "_id_" ? "PRIMARY" : ($index["unique"] ? "UNIQUE" : "INDEX")),
                "columns" => array_keys($index["key"]),
                "lengths" => array(),
                "descs" => $descs,
            );
        }
        return $return;
    }

    public function fields($table) {
        return fields_from_edit();
    }

    public function found_rows($table_status, $where) {
        global $connection;
        //! don't call count_rows()
        return $connection->_db->selectCollection($_GET["select"])->count($where);
    }
}
