<?php

namespace Lagdo\Adminer\Drivers\Elastic;

use Lagdo\Adminer\Drivers\AbstractDriver;
use Lagdo\Adminer\Drivers\DriverTrait;

class Driver extends AbstractDriver {
    use DriverTrait;

    public function select($table, $select, $where, $group, $order = array(), $limit = 1, $page = 0, $print = false) {
        $data = array();
        $query = "$table/_search";
        if ($select != array("*")) {
            $data["fields"] = $select;
        }
        if ($order) {
            $sort = array();
            foreach ($order as $col) {
                $col = preg_replace('~ DESC$~', '', $col, 1, $count);
                $sort[] = ($count ? array($col => "desc") : $col);
            }
            $data["sort"] = $sort;
        }
        if ($limit) {
            $data["size"] = +$limit;
            if ($page) {
                $data["from"] = ($page * $limit);
            }
        }
        foreach ($where as $val) {
            list($col, $op, $val) = explode(" ", $val, 3);
            if ($col == "_id") {
                $data["query"]["ids"]["values"][] = $val;
            }
            elseif ($col . $val != "") {
                $term = array("term" => array(($col != "" ? $col : "_all") => $val));
                if ($op == "=") {
                    $data["query"]["filtered"]["filter"]["and"][] = $term;
                } else {
                    $data["query"]["filtered"]["query"]["bool"]["must"][] = $term;
                }
            }
        }
        if ($data["query"] && !$data["query"]["filtered"]["query"] && !$data["query"]["ids"]) {
            $data["query"]["filtered"]["query"] = array("match_all" => array());
        }
        $start = microtime(true);
        $search = $this->_conn->query($query, $data);
        if ($print) {
            echo $this->adminer->selectQuery("$query: " . json_encode($data), $start, !$search);
        }
        if (!$search) {
            return false;
        }
        $return = array();
        foreach ($search['hits']['hits'] as $hit) {
            $row = array();
            if ($select == array("*")) {
                $row["_id"] = $hit["_id"];
            }
            $fields = $hit['_source'];
            if ($select != array("*")) {
                $fields = array();
                foreach ($select as $key) {
                    $fields[$key] = $hit['fields'][$key];
                }
            }
            foreach ($fields as $key => $val) {
                if ($data["fields"]) {
                    $val = $val[0];
                }
                $row[$key] = (is_array($val) ? json_encode($val) : $val); //! display JSON and others differently
            }
            $return[] = $row;
        }
        return new Min_Result($return);
    }

    public function update($type, $record, $queryWhere, $limit = 0, $separator = "\n") {
        //! use $limit
        $parts = preg_split('~ *= *~', $queryWhere);
        if (count($parts) == 2) {
            $id = trim($parts[1]);
            $query = "$type/$id";
            return $this->_conn->query($query, $record, 'POST');
        }
        return false;
    }

    public function insert($type, $record) {
        $id = ""; //! user should be able to inform _id
        $query = "$type/$id";
        $response = $this->_conn->query($query, $record, 'POST');
        $this->_conn->last_id = $response['_id'];
        return $response['created'];
    }

    public function delete($type, $queryWhere, $limit = 0) {
        //! use $limit
        $ids = array();
        if (is_array($_GET["where"]) && $_GET["where"]["_id"]) {
            $ids[] = $_GET["where"]["_id"];
        }
        if (is_array($_POST['check'])) {
            foreach ($_POST['check'] as $check) {
                $parts = preg_split('~ *= *~', $check);
                if (count($parts) == 2) {
                    $ids[] = trim($parts[1]);
                }
            }
        }
        $this->_conn->affected_rows = 0;
        foreach ($ids as $id) {
            $query = "{$type}/{$id}";
            $response = $this->_conn->query($query, '{}', 'DELETE');
            if (is_array($response) && $response['found'] == true) {
                $this->_conn->affected_rows++;
            }
        }
        return $this->_conn->affected_rows;
    }
}
