<?php
/**
* @author Jakub Cernohuby
* @author Vladimir Stastka
* @author Jakub Vrana
*/

namespace Lagdo\Adminer\Drivers\Mssql;

use Lagdo\Adminer\Drivers\AbstractDriver;

use function Lagdo\Adminer\Drivers\idf_unescape;

class Driver extends AbstractDriver {

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
            //! can use only one query for all rows
            if (!$this->server->queries("MERGE " . $this->server->table($table) . " USING (VALUES(" .
                implode(", ", $set) . ")) AS source (c" . implode(", c", range(1, count($set))) .
                ") ON " . implode(" AND ", $where) . //! source, c1 - possible conflict
                " WHEN MATCHED THEN UPDATE SET " . implode(", ", $update) .
                " WHEN NOT MATCHED THEN INSERT (" . implode(", ", array_keys($set)) . ") VALUES (" .
                implode(", ", $set) . ");" // ; is mandatory
            )) {
                return false;
            }
        }
        return true;
    }

    public function begin() {
        return $this->server->queries("BEGIN TRANSACTION");
    }

}
