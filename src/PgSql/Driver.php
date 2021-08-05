<?php

namespace Lagdo\Adminer\Drivers\PgSql;

use Lagdo\Adminer\Drivers\AbstractDriver;

use function Lagdo\Adminer\Drivers\idf_unescape;
use function Lagdo\Adminer\Drivers\number_type;

class Driver extends AbstractDriver
{
    public function insertUpdate($table, $rows, $primary) {
        foreach ($rows as $set) {
            $update = [];
            $where = [];
            foreach ($set as $key => $val) {
                $update[] = "$key = $val";
                if (isset($primary[idf_unescape($key)])) {
                    $where[] = "$key = $val";
                }
            }
            if (!(($where && $this->server->queries("UPDATE " . $this->server->table($table) .
                " SET " . implode(", ", $update) . " WHERE " . implode(" AND ", $where)) &&
                $this->connection->affected_rows)
                || $this->server->queries("INSERT INTO " . $this->server->table($table) .
                " (" . implode(", ", array_keys($set)) . ") VALUES (" . implode(", ", $set) . ")")
            )) {
                return false;
            }
        }
        return true;
    }

    public function slowQuery($query, $timeout) {
        $this->connection->query("SET statement_timeout = " . (1000 * $timeout));
        $this->connection->timeout = 1000 * $timeout;
        return $query;
    }

    public function convertSearch($idf, $val, $field) {
        return (preg_match('~char|text' . (!preg_match('~LIKE~', $val["op"]) ?
            '|date|time(stamp)?|boolean|uuid|' . number_type() : '') . '~', $field["type"]) ?
            $idf : "CAST($idf AS text)"
        );
    }

    public function quoteBinary($s) {
        return $this->connection->quoteBinary($s);
    }

    public function warnings() {
        return $this->connection->warnings();
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
