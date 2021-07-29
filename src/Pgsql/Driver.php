<?php

namespace Lagdo\Adminer\Drivers\Pgsql;

use Lagdo\Adminer\Drivers\AbstractDriver;

class Driver extends AbstractDriver {

    public function insertUpdate($table, $rows, $primary) {
        foreach ($rows as $set) {
            $update = array();
            $where = array();
            foreach ($set as $key => $val) {
                $update[] = "$key = $val";
                if (isset($primary[idf_unescape($key)])) {
                    $where[] = "$key = $val";
                }
            }
            if (!(($where && $this->server->queries("UPDATE " . $this->server->table($table) . " SET " . implode(", ", $update) . " WHERE " . implode(" AND ", $where)) && $this->connection->affected_rows)
                || $this->server->queries("INSERT INTO " . $this->server->table($table) . " (" . implode(", ", array_keys($set)) . ") VALUES (" . implode(", ", $set) . ")")
            )) {
                return false;
            }
        }
        return true;
    }

    public function slowQuery($query, $timeout) {
        $this->_conn->query("SET statement_timeout = " . (1000 * $timeout));
        $this->_conn->timeout = 1000 * $timeout;
        return $query;
    }

    public function convertSearch($idf, $val, $field) {
        return (preg_match('~char|text'
                . (!preg_match('~LIKE~', $val["op"]) ? '|date|time(stamp)?|boolean|uuid|' . number_type() : '')
                . '~', $field["type"])
            ? $idf
            : "CAST($idf AS text)"
        );
    }

    public function quoteBinary($s) {
        return $this->_conn->quoteBinary($s);
    }

    public function warnings() {
        return $this->_conn->warnings();
    }

    public function tableHelp($name) {
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
