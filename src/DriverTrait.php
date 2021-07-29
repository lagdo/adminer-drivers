<?php

namespace Lagdo\Adminer\Drivers;

trait DriverTrait
{
    /**
     * Insert or update data in table
     * @param string
     * @param array
     * @param array of arrays with escaped columns in keys and quoted data in values
     * @return bool
     */
    public function insertUpdate($table, $rows, $primary)
    {
        return false;
    }
}
