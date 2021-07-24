<?php

namespace Lagdo\Adminer\Drivers\Mongo\Mongo;

use Exception;

class Driver extends \Lagdo\Adminer\Drivers\Driver {
    public $primary = "_id";

    function select($table, $select, $where, $group, $order = array(), $limit = 1, $page = 0, $print = false) {
        $select = ($select == array("*")
            ? array()
            : array_fill_keys($select, true)
        );
        $sort = array();
        foreach ($order as $val) {
            $val = preg_replace('~ DESC$~', '', $val, 1, $count);
            $sort[$val] = ($count ? -1 : 1);
        }
        return new Result($this->_conn->_db->selectCollection($table)
            ->find(array(), $select)
            ->sort($sort)
            ->limit($limit != "" ? +$limit : 0)
            ->skip($page * $limit)
        );
    }

    function insert($table, $set) {
        try {
            $return = $this->_conn->_db->selectCollection($table)->insert($set);
            $this->_conn->errno = $return['code'];
            $this->_conn->error = $return['err'];
            $this->_conn->last_id = $set['_id'];
            return !$return['err'];
        } catch (Exception $ex) {
            $this->_conn->error = $ex->getMessage();
            return false;
        }
    }
}
