<?php

namespace Lagdo\Adminer\Drivers\Elastic;

class Statement
{

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    public $num_rows;

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    public $_rows;

    public function __construct($rows)
    {
        $this->num_rows = count($rows);
        $this->_rows = $rows;
        reset($this->_rows);
    }

    public function fetch_assoc()
    {
        $return = current($this->_rows);
        next($this->_rows);
        return $return;
    }

    public function fetch_row()
    {
        return array_values($this->fetch_assoc());
    }
}
