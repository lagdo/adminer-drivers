<?php

namespace Lagdo\Adminer\Drivers\Sqlite;

class Driver extends \Lagdo\Adminer\Drivers\Driver {

    public function insertUpdate($table, $rows, $primary) {
        $values = array();
        foreach ($rows as $set) {
            $values[] = "(" . implode(", ", $set) . ")";
        }
        return $this->server->queries("REPLACE INTO " . $this->server->table($table) ." (" . implode(", ",
            array_keys(reset($rows))) . ") VALUES\n" . implode(",\n", $values));
    }

    public function tableHelp($name) {
        if ($name == "sqlite_sequence") {
            return "fileformat2.html#seqtab";
        }
        if ($name == "sqlite_master") {
            return "fileformat2.html#$name";
        }
    }
}
