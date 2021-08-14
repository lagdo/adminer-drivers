<?php
/**
* @author Jakub Cernohuby
* @author Vladimir Stastka
* @author Jakub Vrana
*/

namespace Lagdo\Adminer\Drivers\Db\MsSql;

use Lagdo\Adminer\Drivers\Db\Driver as AbstractDriver;

class Driver extends AbstractDriver
{
    public function insertUpdate($table, $rows, $primary)
    {
        foreach ($rows as $set) {
            $update = [];
            $where = [];
            foreach ($set as $key => $val) {
                $update[] = "$key = $val";
                if (isset($primary[$this->server->idf_unescape($key)])) {
                    $where[] = "$key = $val";
                }
            }
            //! can use only one query for all rows
            if (!$this->adminer->queries(
                "MERGE " . $this->server->table($table) . " USING (VALUES(" .
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

    public function begin()
    {
        return $this->adminer->queries("BEGIN TRANSACTION");
    }
}
