<?php

namespace Lagdo\Adminer\Drivers\Elastic;

class Statement {
    var $num_rows, $_rows;

    public function __construct($rows) {
        $this->num_rows = count($rows);
        $this->_rows = $rows;
        reset($this->_rows);
    }

    public function fetch_assoc() {
        $return = current($this->_rows);
        next($this->_rows);
        return $return;
    }

    public function fetch_row() {
        return array_values($this->fetch_assoc());
    }

}
