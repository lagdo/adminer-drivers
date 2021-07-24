<?php

namespace Lagdo\Adminer\Drivers\Pgsql;

class Driver extends \Lagdo\Adminer\Drivers\Driver {

    function insertUpdate($table, $rows, $primary) {
        global $connection;
        foreach ($rows as $set) {
            $update = array();
            $where = array();
            foreach ($set as $key => $val) {
                $update[] = "$key = $val";
                if (isset($primary[idf_unescape($key)])) {
                    $where[] = "$key = $val";
                }
            }
            if (!(($where && queries("UPDATE " . table($table) . " SET " . implode(", ", $update) . " WHERE " . implode(" AND ", $where)) && $connection->affected_rows)
                || queries("INSERT INTO " . table($table) . " (" . implode(", ", array_keys($set)) . ") VALUES (" . implode(", ", $set) . ")")
            )) {
                return false;
            }
        }
        return true;
    }

    function slowQuery($query, $timeout) {
        $this->_conn->query("SET statement_timeout = " . (1000 * $timeout));
        $this->_conn->timeout = 1000 * $timeout;
        return $query;
    }

    function convertSearch($idf, $val, $field) {
        return (preg_match('~char|text'
                . (!preg_match('~LIKE~', $val["op"]) ? '|date|time(stamp)?|boolean|uuid|' . number_type() : '')
                . '~', $field["type"])
            ? $idf
            : "CAST($idf AS text)"
        );
    }

    function quoteBinary($s) {
        return $this->_conn->quoteBinary($s);
    }

    function warnings() {
        return $this->_conn->warnings();
    }

    function tableHelp($name) {
        $links = array(
            "information_schema" => "infoschema",
            "pg_catalog" => "catalog",
        );
        $link = $links[$_GET["ns"]];
        if ($link) {
            return "$link-" . str_replace("_", "-", $name) . ".html";
        }
    }
}
