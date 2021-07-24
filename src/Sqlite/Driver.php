<?php

namespace Lagdo\Adminer\Drivers\Sqlite;

class Driver extends \Lagdo\Adminer\Drivers\Driver {

    function insertUpdate($table, $rows, $primary) {
        $values = array();
        foreach ($rows as $set) {
            $values[] = "(" . implode(", ", $set) . ")";
        }
        return queries("REPLACE INTO " . table($table) ." (" . implode(", ",
            array_keys(reset($rows))) . ") VALUES\n" . implode(",\n", $values));
    }

    function tableHelp($name) {
        if ($name == "sqlite_sequence") {
            return "fileformat2.html#seqtab";
        }
        if ($name == "sqlite_master") {
            return "fileformat2.html#$name";
        }
    }
}
