<?php

namespace Lagdo\Adminer\Drivers\Mysql;

class Driver extends \Lagdo\Adminer\Drivers\Driver {

    function insert($table, $set) {
        return ($set ? parent::insert($table, $set) : queries("INSERT INTO " . table($table) . " ()\nVALUES ()"));
    }

    function insertUpdate($table, $rows, $primary) {
        $columns = array_keys(reset($rows));
        $prefix = "INSERT INTO " . table($table) . " (" . implode(", ", $columns) . ") VALUES\n";
        $values = array();
        foreach ($columns as $key) {
            $values[$key] = "$key = VALUES($key)";
        }
        $suffix = "\nON DUPLICATE KEY UPDATE " . implode(", ", $values);
        $values = array();
        $length = 0;
        foreach ($rows as $set) {
            $value = "(" . implode(", ", $set) . ")";
            if ($values && (strlen($prefix) + $length + strlen($value) + strlen($suffix) > 1e6)) { // 1e6 - default max_allowed_packet
                if (!queries($prefix . implode(",\n", $values) . $suffix)) {
                    return false;
                }
                $values = array();
                $length = 0;
            }
            $values[] = $value;
            $length += strlen($value) + 2; // 2 - strlen(",\n")
        }
        return queries($prefix . implode(",\n", $values) . $suffix);
    }

    function slowQuery($query, $timeout) {
        if (min_version('5.7.8', '10.1.2')) {
            if (preg_match('~MariaDB~', $this->_conn->server_info)) {
                return "SET STATEMENT max_statement_time=$timeout FOR $query";
            } elseif (preg_match('~^(SELECT\b)(.+)~is', $query, $match)) {
                return "$match[1] /*+ MAX_EXECUTION_TIME(" . ($timeout * 1000) . ") */ $match[2]";
            }
        }
    }

    function convertSearch($idf, $val, $field) {
        return (preg_match('~char|text|enum|set~', $field["type"]) && !preg_match("~^utf8~", $field["collation"]) && preg_match('~[\x80-\xFF]~', $val['val'])
            ? "CONVERT($idf USING " . charset($this->_conn) . ")"
            : $idf
        );
    }

    function warnings() {
        $result = $this->_conn->query("SHOW WARNINGS");
        if ($result && $result->num_rows) {
            ob_start();
            select($result); // select() usually needs to print a big table progressively
            return ob_get_clean();
        }
    }

    function tableHelp($name) {
        $maria = preg_match('~MariaDB~', $this->_conn->server_info);
        if (information_schema(DB)) {
            return strtolower(($maria ? "information-schema-$name-table/" : str_replace("_", "-", $name) . "-table.html"));
        }
        if (DB == "mysql") {
            return ($maria ? "mysql$name-table/" : "system-database.html"); //! more precise link
        }
    }

}
