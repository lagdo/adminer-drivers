<?php

namespace Lagdo\Adminer\Drivers\Pdo;

use PDOStatement;

class Statement extends PDOStatement {
    var $_offset = 0, $num_rows;

    function fetch_assoc() {
        return $this->fetch(2); // PDO::FETCH_ASSOC
    }

    function fetch_row() {
        return $this->fetch(3); // PDO::FETCH_NUM
    }

    function fetch_field() {
        $row = (object) $this->getColumnMeta($this->_offset++);
        $row->orgtable = $row->table;
        $row->orgname = $row->name;
        $row->charsetnr = (in_array("blob", (array) $row->flags) ? 63 : 0);
        return $row;
    }
}
