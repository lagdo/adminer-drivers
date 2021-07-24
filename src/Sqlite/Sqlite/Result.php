<?php

namespace Lagdo\Adminer\Drivers\Sqlite\Sqlite;

class Result {
    var $_result, $_offset = 0, $num_rows;

    function __construct($result) {
        $this->_result = $result;
    }

    function fetch_assoc() {
        return $this->_result->fetchArray(SQLITE3_ASSOC);
    }

    function fetch_row() {
        return $this->_result->fetchArray(SQLITE3_NUM);
    }

    function fetch_field() {
        $column = $this->_offset++;
        $type = $this->_result->columnType($column);
        return (object) array(
            "name" => $this->_result->columnName($column),
            "type" => $type,
            "charsetnr" => ($type == SQLITE3_BLOB ? 63 : 0), // 63 - binary
        );
    }

    function __desctruct() {
        return $this->_result->finalize();
    }
}
