<?php

namespace Lagdo\Adminer\Drivers\Mongo\Mongo;

use Lagdo\Adminer\Drivers\AbstractDriver;
use Lagdo\Adminer\Drivers\DriverTrait;

use Exception;

class Driver extends AbstractDriver {
    use DriverTrait;

    public $primary = "_id";

    public function select($table, $select, $where, $group, $order = array(), $limit = 1, $page = 0, $print = false) {
        $select = ($select == array("*")
            ? array()
            : array_fill_keys($select, true)
        );
        $sort = array();
        foreach ($order as $val) {
            $val = preg_replace('~ DESC$~', '', $val, 1, $count);
            $sort[$val] = ($count ? -1 : 1);
        }
        return new Statement($this->connection->_db->selectCollection($table)
            ->find(array(), $select)
            ->sort($sort)
            ->limit($limit != "" ? +$limit : 0)
            ->skip($page * $limit)
        );
    }

    public function insert($table, $set) {
        try {
            $return = $this->connection->_db->selectCollection($table)->insert($set);
            $this->connection->errno = $return['code'];
            $this->connection->error = $return['err'];
            $this->connection->last_id = $set['_id'];
            return !$return['err'];
        } catch (Exception $ex) {
            $this->connection->error = $ex->getMessage();
            return false;
        }
    }
}
