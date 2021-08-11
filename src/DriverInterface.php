<?php

namespace Lagdo\Adminer\Drivers;

interface DriverInterface
{
    /**
     * Return a quoted string
     *
     * @param string $string
     *
     * @return string
     */
    public function quoteBinary($string);

    /**
     * Select data from table
     * @param string
     * @param array result of $this->adminer->selectColumnsProcess()[0]
     * @param array result of $this->adminer->selectSearchProcess()
     * @param array result of $this->adminer->selectColumnsProcess()[1]
     * @param array result of $this->adminer->selectOrderProcess()
     * @param int result of $this->adminer->selectLimitProcess()
     * @param int index of page starting at zero
     * @return Statement
     */
    public function select($table, $select, $where, $group, $order = [], $limit = 1, $page = 0);

    /**
     * Insert or update data in table
     * @param string
     * @param array
     * @param array of arrays with escaped columns in keys and quoted data in values
     * @return bool
     */
    public function insertUpdate($table, $rows, $primary);

    /**
     * Get warnings about the last command
     * @return string
     */
    public function warnings();
}
