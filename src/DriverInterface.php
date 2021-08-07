<?php

namespace Lagdo\Adminer\Drivers;

interface DriverInterface
{
    /**
     * Get the current query
     *
     * @return Query
     */
    public function getQuery();

    /**
     * Return a quoted string
     *
     * @param string $string
     *
     * @return string
     */
    public function quoteBinary($string);

    /**
     * Insert or update data in table
     * @param string
     * @param array
     * @param array of arrays with escaped columns in keys and quoted data in values
     * @return bool
     */
    public function insertUpdate($table, $rows, $primary);
}
