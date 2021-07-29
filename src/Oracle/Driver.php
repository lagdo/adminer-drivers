<?php

namespace Lagdo\Adminer\Drivers\Oracle;

use Lagdo\Adminer\Drivers\AbstractDriver;

class Driver extends AbstractDriver {

    //! support empty $set in insert()

    public function begin() {
        return true; // automatic start
    }

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
}
