<?php

namespace Lagdo\Adminer\Drivers\Pdo;

use PDOStatement;
use PDO;

class Statement extends PDOStatement
{
    var $_offset = 0, $num_rows;

    public function fetch_assoc() {
        return $this->fetch(PDO::FETCH_ASSOC);
    }

    public function fetch_row() {
        return $this->fetch(PDO::FETCH_NUM);
    }

    public function fetch_field() {
        $row = (object) $this->getColumnMeta($this->_offset++);
        $row->orgtable = $row->table;
        $row->orgname = $row->name;
        $row->charsetnr = (in_array("blob", (array) $row->flags) ? 63 : 0);
        return $row;
    }
}
