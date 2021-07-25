<?php

namespace Lagdo\Adminer\Drivers\Oracle;

class Driver extends \Lagdo\Adminer\Drivers\Driver {

    //! support empty $set in insert()

    public function begin() {
        return true; // automatic start
    }

    public function insertUpdate($table, $rows, $primary) {
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
}
