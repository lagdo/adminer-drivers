<?php

namespace Lagdo\Adminer\Drivers\Mongo\MongoDb;

class Driver extends \Lagdo\Adminer\Drivers\Driver {
    public $primary = "_id";

    function select($table, $select, $where, $group, $order = array(), $limit = 1, $page = 0, $print = false) {
        global $connection;
        $select = ($select == array("*")
            ? array()
            : array_fill_keys($select, 1)
        );
        if (count($select) && !isset($select['_id'])) {
            $select['_id'] = 0;
        }
        $where = where_to_query($where);
        $sort = array();
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
            return new Result($connection->_link->executeQuery("$connection->_db_name.$table", new $class($where, array('projection' => $select, 'limit' => $limit, 'skip' => $skip, 'sort' => $sort))));
        } catch (Exception $e) {
            $connection->error = $e->getMessage();
            return false;
        }
    }

    function update($table, $set, $queryWhere, $limit = 0, $separator = "\n") {
        global $connection;
        $db = $connection->_db_name;
        $where = sql_query_where_parser($queryWhere);
        $class = 'MongoDB\Driver\BulkWrite';
        $bulk = new $class(array());
        if (isset($set['_id'])) {
            unset($set['_id']);
        }
        $removeFields = array();
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
        return $connection->executeBulkWrite("$db.$table", $bulk, 'getModifiedCount');
    }

    function delete($table, $queryWhere, $limit = 0) {
        global $connection;
        $db = $connection->_db_name;
        $where = sql_query_where_parser($queryWhere);
        $class = 'MongoDB\Driver\BulkWrite';
        $bulk = new $class(array());
        $bulk->delete($where, array('limit' => $limit));
        return $connection->executeBulkWrite("$db.$table", $bulk, 'getDeletedCount');
    }

    function insert($table, $set) {
        global $connection;
        $db = $connection->_db_name;
        $class = 'MongoDB\Driver\BulkWrite';
        $bulk = new $class(array());
        if ($set['_id'] == '') {
            unset($set['_id']);
        }
        $bulk->insert($set);
        return $connection->executeBulkWrite("$db.$table", $bulk, 'getInsertedCount');
    }
}
