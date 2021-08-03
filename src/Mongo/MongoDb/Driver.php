<?php

namespace Lagdo\Adminer\Drivers\Mongo\MongoDb;

use Lagdo\Adminer\Drivers\AbstractDriver;
use Lagdo\Adminer\Drivers\DriverTrait;

class Driver extends AbstractDriver {
    use DriverTrait;

    public $primary = "_id";

    public function select($table, $select, $where, $group, $order = [], $limit = 1, $page = 0, $print = false) {
        $select = ($select == array("*")
            ? []
            : array_fill_keys($select, 1)
        );
        if (count($select) && !isset($select['_id'])) {
            $select['_id'] = 0;
        }
        $where = $this->connection->where_to_query($where);
        $sort = [];
        foreach ($order as $val) {
            $val = preg_replace('~ DESC$~', '', $val, 1, $count);
            $sort[$val] = ($count ? -1 : 1);
        }
        if (isset($_GET['limit']) && is_numeric($_GET['limit']) && $_GET['limit'] > 0) {
            $limit = $_GET['limit'];
        }
        $limit = min(200, max(1, (int) $limit));
        $skip = $page * $limit;
        $class = 'MongoDB\Driver\Query';
        try {
            return new Statement($this->connection->_link->executeQuery("$this->connection->_db_name.$table", new $class($where, array('projection' => $select, 'limit' => $limit, 'skip' => $skip, 'sort' => $sort))));
        } catch (Exception $e) {
            $this->connection->error = $e->getMessage();
            return false;
        }
    }

    public function update($table, $set, $queryWhere, $limit = 0, $separator = "\n") {
        $db = $this->connection->_db_name;
        $where = sql_query_where_parser($queryWhere);
        $class = 'MongoDB\Driver\BulkWrite';
        $bulk = new $class(array());
        if (isset($set['_id'])) {
            unset($set['_id']);
        }
        $removeFields = [];
        foreach ($set as $key => $value) {
            if ($value == 'NULL') {
                $removeFields[$key] = 1;
                unset($set[$key]);
            }
        }
        $update = array('$set' => $set);
        if (count($removeFields)) {
            $update['$unset'] = $removeFields;
        }
        $bulk->update($where, $update, array('upsert' => false));
        return $this->connection->executeBulkWrite("$db.$table", $bulk, 'getModifiedCount');
    }

    public function delete($table, $queryWhere, $limit = 0) {
        $db = $this->connection->_db_name;
        $where = sql_query_where_parser($queryWhere);
        $class = 'MongoDB\Driver\BulkWrite';
        $bulk = new $class(array());
        $bulk->delete($where, array('limit' => $limit));
        return $this->connection->executeBulkWrite("$db.$table", $bulk, 'getDeletedCount');
    }

    public function insert($table, $set) {
        $db = $this->connection->_db_name;
        $class = 'MongoDB\Driver\BulkWrite';
        $bulk = new $class(array());
        if ($set['_id'] == '') {
            unset($set['_id']);
        }
        $bulk->insert($set);
        return $this->connection->executeBulkWrite("$db.$table", $bulk, 'getInsertedCount');
    }
}