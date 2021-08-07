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
            $update = [];
            $where = [];
            foreach ($set as $key => $val) {
                $update[] = "$key = $val";
                if (isset($primary[$this->server->idf_unescape($key)])) {
                    $where[] = "$key = $val";
                }
            }
            if (!(($where && $this->adminer->queries("UPDATE " . $this->server->table($table) . " SET " .
                implode(", ", $update) . " WHERE " . implode(" AND ", $where)) && $this->connection->affected_rows) ||
                $this->adminer->queries("INSERT INTO " . $this->server->table($table) .
                " (" . implode(", ", array_keys($set)) . ") VALUES (" . implode(", ", $set) . ")")
            )) {
                return false;
            }
        }
        return true;
    }
}
